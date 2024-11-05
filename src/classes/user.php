<?php
require_once __DIR__ . '/../includes/db.php';

class User {
    private $userID;
    private $username;
    private $email;
    private $password;
    private $status;
    private $roleID;
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function setUsername($username) {
        $this->username = htmlspecialchars($username);
    }

    public function getUsername() {
        return $this->username;
    }

    public function setEmail($email) {
        $this->email = htmlspecialchars($email);
    }

    public function getEmail() {
        return $this->email;
    }

    // CREATE (register)
    public function createUser($username, $email, $password) {
        $this->username = htmlspecialchars($username);
        $this->email = htmlspecialchars($email);
        $this->password = password_hash($password, PASSWORD_DEFAULT);

        // CREATE (register)
        $sql = "INSERT INTO User (Username, Email, Password, Status, RoleID) VALUES (:username, :email, :password, :status, :roleID)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindValue(':status', 'Active'); // Ensure status is set
        $stmt->bindValue(':roleID', 1); // Default role ID or as needed

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error creating user: " . $e->getMessage();
            return false;
        }
    }

    // READ (get user by ID)
    public function getUser($userID) {
        $this->userID = $userID;
        $sql = "SELECT * FROM User WHERE UserID = :userID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                echo "No user found with ID: $userID<br>";
                return false;
            }

            $this->userID = $user['UserID'];
            $this->username = $user['Username'];
            $this->email = $user['Email'];
            $this->status = $user['Status'];
            $this->roleID = $user['RoleID'];

            return $user;
        } catch (PDOException $e) {
            echo "Error getting user: " . $e->getMessage();
            return false;
        }
    }


    // UPDATE (update user)
    public function updateUser($userID, $newUsername, $newEmail) {
        $this->username = htmlspecialchars($newUsername);
        $this->email = htmlspecialchars($newEmail);

        $sql = "UPDATE User SET Username = :username, Email = :email WHERE UserID = :userID";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error updating user: " . $e->getMessage();
            return false;
        }
    }

    // DELETE (soft delete user)
    public function deleteUser($userID) {
        $sql = "UPDATE User SET Status = 'Blocked' WHERE UserID = :userID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error blocking user: " . $e->getMessage();
            return false;
        }
    }
}
