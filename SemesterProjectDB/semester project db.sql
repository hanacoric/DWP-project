CREATE DATABASE IF NOT EXISTS cav1vwlij_semesterprojectdb;
USE cav1vwlij_semesterprojectdb;


SET FOREIGN_KEY_CHECKS = 0;


DROP TABLE IF EXISTS Notification;
DROP TABLE IF EXISTS Likes;
DROP TABLE IF EXISTS Comments;
DROP TABLE IF EXISTS Post;
DROP TABLE IF EXISTS UserProfile;
DROP TABLE IF EXISTS `User`;
DROP TABLE IF EXISTS Role;


SET FOREIGN_KEY_CHECKS = 1;

-- Role Table
CREATE TABLE Role (
    RoleID INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(100) NOT NULL
);

-- User Table
CREATE TABLE `User` (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Status ENUM('Active', 'Blocked') NOT NULL DEFAULT 'Active',
    RoleID INT,
    FOREIGN KEY (RoleID) REFERENCES Role(RoleID) ON DELETE SET NULL
);

-- UserProfile Table
CREATE TABLE UserProfile (
    UserProfileID INT NOT NULL  AUTO_INCREMENT,
    UserID INT DEFAULT NULL,
    Bio TEXT,
    Gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    FirstLast VARCHAR(100) DEFAULT NULL,
    BlobProfilePicture LONGBLOB NULL,
    PRIMARY KEY (UserProfileID),
    UNIQUE KEY (UserID)
);

-- Post Table
CREATE TABLE Post (
    PostID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    BlobImage LONGBLOB,
    Caption TEXT,
    UploadDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UserID INT,
    IsPinned TINYINT(1) DEFAULT 0,
    FOREIGN KEY (UserID) REFERENCES `User`(UserID) ON DELETE CASCADE
);

-- Notification Table
CREATE TABLE Notification (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    ActionType ENUM('Like', 'Comment') NOT NULL,
    Content TEXT,
    Timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UserID INT DEFAULT NULL,
    PostID INT DEFAULT NULL,
    FOREIGN KEY (UserID) REFERENCES `User`(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PostID) REFERENCES Post(PostID) ON DELETE CASCADE
);

-- Likes Table
CREATE TABLE Likes (
    LikeID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    PostID INT NOT NULL,
    Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES `User`(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PostID) REFERENCES Post(PostID) ON DELETE CASCADE
);

-- Comments Table
CREATE TABLE Comments (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    PostID INT NOT NULL,
    Comment TEXT NOT NULL,
    Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES `User`(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PostID) REFERENCES Post(PostID) ON DELETE CASCADE
);

INSERT INTO Role (RoleName) VALUES ('Admin'), ('User');

INSERT INTO User (Username, Email, Password, Status, RoleID)
VALUES ('admin', 'admin@example.com', '$2y$10$P6PmN5Op1Ff5WRf42u9o0ebymKzcyrXEj03GNP.tsGMSTE604Btku', 'Active',
        (SELECT RoleID FROM Role WHERE RoleName = 'Admin'))
ON DUPLICATE KEY UPDATE RoleID = (SELECT RoleID FROM Role WHERE RoleName = 'Admin');

-- trigger for likes
DELIMITER //

CREATE TRIGGER after_like_insert
AFTER INSERT ON Likes
FOR EACH ROW
BEGIN
    DECLARE likerUsername VARCHAR(50);
    DECLARE existingNotification INT;

    SELECT Username INTO likerUsername
    FROM User
    WHERE UserID = NEW.UserID;

    SELECT COUNT(*) INTO existingNotification
    FROM Notification
    WHERE ActionType = 'Like'
      AND UserID = (SELECT UserID FROM Post WHERE PostID = NEW.PostID)
      AND PostID = NEW.PostID;

    IF existingNotification = 0 THEN
        INSERT INTO Notification (ActionType, Content, UserID, PostID, Timestamp)
        VALUES (
            'Like',
            CONCAT(likerUsername, ' liked your post.'),
            (SELECT UserID FROM Post WHERE PostID = NEW.PostID),
            NEW.PostID,
            NOW()
        );
    END IF;
END;
//

DELIMITER ;

-- comments trigger
DELIMITER //

CREATE TRIGGER after_comment_insert
AFTER INSERT ON Comments
FOR EACH ROW
BEGIN
    DECLARE commenterUsername VARCHAR(50);
    DECLARE existingNotification INT;

    SELECT Username INTO commenterUsername
    FROM User
    WHERE UserID = NEW.UserID;

    SELECT COUNT(*) INTO existingNotification
    FROM Notification
    WHERE ActionType = 'Comment'
      AND UserID = (SELECT UserID FROM Post WHERE PostID = NEW.PostID)
      AND PostID = NEW.PostID
      AND Content LIKE CONCAT(commenterUsername, ' commented on your post: ', NEW.Comment, '%') COLLATE utf8mb4_general_ci;

    IF existingNotification = 0 THEN
        INSERT INTO Notification (ActionType, Content, UserID, PostID, Timestamp)
        VALUES (
            'Comment',
            CONCAT(commenterUsername, ' commented on your post: "', NEW.Comment, '"'),
            (SELECT UserID FROM Post WHERE PostID = NEW.PostID),
            NEW.PostID,
            NOW()
        );
    END IF;
END;
//

DELIMITER ;



--  view for trending posts
CREATE VIEW TrendingPosts AS
SELECT
    Post.PostID,
    Post.UserID,
    Post.BlobImage,
    Post.Caption,
    User.Username,
    COUNT(Likes.LikeID) AS TotalLikes
FROM Post
JOIN Likes ON Post.PostID = Likes.PostID
JOIN User ON Post.UserID = User.UserID
GROUP BY Post.PostID, Post.Caption, User.Username
ORDER BY TotalLikes DESC
LIMIT 3;

-- view for recent comments
CREATE VIEW RecentComments AS
SELECT
    Comments.CommentID,
    Comments.Comment,
    Comments.Timestamp,
    Comments.PostID,
    Comments.UserID,
    User.Username
FROM Comments
JOIN User ON Comments.UserID = User.UserID
ORDER BY Comments.Timestamp DESC;


SET FOREIGN_KEY_CHECKS = 1;
