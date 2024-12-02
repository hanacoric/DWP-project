<?php
require_once __DIR__ . '/../includes/db.php';

class Post{
    private $PostID;
    private $Image;
    private $Caption;
    private $UploadDate;
    private $Trending;
    private $UserID;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getPostID()
    {
        return $this->PostID;
    }

    public function getImage()
    {
        return $this->Image;
    }

    public function setImage($Image)
    {
        $this->Image = htmlspecialchars($Image);
    }

    public function getCaption()
    {
        return $this->Caption;
    }

    public function setCaption($Caption)
    {
        $this->Caption = htmlspecialchars($Caption);
    }

    public function getUploadDate()
    {
        return $this->UploadDate;
    }

    public function getTrending()
    {
        return $this->Trending;
    }

    public function getUserID()
    {
        return $this->UserID;
    }

    //CREATE (add a new post)
    public function createPost($imagePath, $caption, $userId) {
        $sql = "INSERT INTO Post (Image, Caption, UploadDate, UserID) VALUES (:image, :caption, NOW(), :userId)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':image', $imagePath);
        $stmt->bindParam(':caption', $caption);
        $stmt->bindParam(':userId', $userId);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error saving post: " . $e->getMessage();
            return false;
        }
    }


    //READ (get post by ID)
    public function getPost($PostID)
    {
        $this->PostID = $PostID;
        $sql = "SELECT * FROM Post WHERE PostID = :PostID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':PostID', $PostID);

        try {
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                echo "No post found with ID: $PostID";
                return false;
            }

            $this->Image = $post['Image'];
            $this->Caption = $post['Caption'];
            $this->UploadDate = $post['UploadDate'];
            $this->Trending = $post['Trending'];
            $this->UserID = $post['UserID'];

            return $post;
        } catch (PDOException $e) {
            echo "Error getting post: " . $e->getMessage();
            return false;
        }
    }

    public function getRecentPosts()
    {
        $sql = "SELECT * FROM Post ORDER BY UploadDate DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching recent posts: " . $e->getMessage();
            return [];
        }
        $sql = "SELECT Image, Caption FROM Post ORDER BY UploadDate DESC";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching posts: " . $e->getMessage();
            return [];
        }
    }


    public function getTrendingPosts()
    {
        $sql = "SELECT * FROM Post WHERE Trending = 1 ORDER BY UploadDate DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching trending posts: " . $e->getMessage();
            return [];
        }
    }


    //UPDATE (update post)
    public function updatePost($PostID, $newCaption)
    {
        $this->Caption = htmlspecialchars($newCaption);
        $this->PostID = $PostID;

        $sql = "UPDATE Post SET Caption = :caption WHERE PostID = :PostID";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':caption', $this->Caption);
        $stmt->bindParam(':PostID', $PostID);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error updating post: " . $e->getMessage();
            return false;
        }
    }

    //DELETE (delete post)
    public function deletePost($PostID)
    {
        $this->PostID = $PostID;
        $sql = "DELETE FROM Post WHERE PostID = :PostID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':PostID', $PostID);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error deleting post: " . $e->getMessage();
            return false;
        }
    }

}