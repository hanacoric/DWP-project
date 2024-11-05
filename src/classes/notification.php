<?php
require_once __DIR__ . '/../../src/includes/db.php';
class Notification
{
    private $notificationID;
    private $userID;
    private $postID;
    private $actionType; //like or comment
    private $content;
    private $timestamp;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getNotificationID()
    {
        return $this->notificationID;
    }

    public function getUserID()
    {
        return $this->userID;
    }

    public function getPostID()
    {
        return $this->postID;
    }

    public function getActionType()
    {
        return $this->actionType;
    }

    public function setActionType($actionType)
    {
        $this->actionType = htmlspecialchars($actionType);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = htmlspecialchars($content);
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    //CREATE (create a new notification)
    public function createNotification($actionType, $content, $userID, $postID){
        $this->setActionType($actionType);
        $this->setContent($content);
        $this->userID = $userID;
        $this->postID = $postID;
        $this->timestamp = date('Y-m-d H:i:s');

        $sql = "INSERT INTO Notification (ActionType, Content, Timestamp, UserID, PostID) VALUES (:actionType, :content, :timestamp, :userID, :postID)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':actionType', $this->actionType);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':timestamp', $this->timestamp);
        $stmt->bindParam(':userID', $this->userID);
        $stmt->bindParam(':postID', $this->postID);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error creating notification: " . $e->getMessage();
            return false;
        }
    }

    //READ (get all notifications for a user)
    public function getNotificationsForUser($userID)
    {
        $sql = "SELECT * FROM Notification WHERE UserID = :userID ORDER BY Timestamp DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error retrieving notifications: " . $e->getMessage();
            return false;
        }
    }

    //UPDATE (edit a comment)
    public function updateComment($notificationID, $newContent, $userID) {
        $sql = "SELECT * FROM Notification WHERE NotificationID = :notificationID AND UserID = :userID AND ActionType = 'Comment'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':notificationID', $notificationID);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($notification) {
                $this->setContent($newContent);

                $updateSql = "UPDATE Notification SET Content = :content WHERE NotificationID = :notificationID";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bindParam(':content', $this->content);
                $updateStmt->bindParam(':notificationID', $notificationID);
                $updateStmt->execute();

                return true;
            } else {
                echo "Error: You can only edit your own comments.";
                return false;
            }
        } catch (PDOException $e) {
            echo "Error updating comment: " . $e->getMessage();
            return false;
        }
    }

    //DELETE (delete a comment)
    public function deleteComment($notificationID, $userID) {
        $sql = "SELECT * FROM Notification WHERE NotificationID = :notificationID AND ActionType = 'Comment' AND UserID = :userID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':notificationID', $notificationID);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($notification) {
                $deleteSql = "DELETE FROM Notification WHERE NotificationID = :notificationID";
                $deleteStmt = $this->db->prepare($deleteSql);
                $deleteStmt->bindParam(':notificationID', $notificationID);
                $deleteStmt->execute();

                return true;
            } else {
                echo "Error: Only the original user can delete comments.";
                return false;
            }
        } catch (PDOException $e) {
            echo "Error deleting comment: " . $e->getMessage();
            return false;
        }
    }

}



