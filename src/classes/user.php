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

        $sql = "INSERT INTO User (Username, Email, Password, Status, RoleID) VALUES (:username, :email, :password, :status, :roleID)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindValue(':status', 'Active');
        $stmt->bindValue(':roleID', 1);

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

    // READ (get user profile by User ID)
    public function getUserProfile($userID) {
        $sql = "SELECT u.Username, u.Email, p.ProfilePicture, p.Bio, p.Gender, p.FirstName, p.LastName 
            FROM User u 
            LEFT JOIN UserProfile p ON u.UserID = p.UserID 
            WHERE u.UserID = :userID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userID', $userID);

        try {
            $stmt->execute();
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if profile data is available
            if (!$profile || !isset($profile['Username'])) {
                // Return default profile values if no entry exists
                return [
                    'Username' => 'DefaultUser',
                    'Email' => 'default@example.com',
                    'ProfilePicture' => 'assets/images/default-profile.png',
                    'Bio' => 'This is a default bio.',
                    'Gender' => 'Other',
                    'FirstName' => 'Default',
                    'LastName' => 'User'
                ];
            }

            return $profile;
        } catch (PDOException $e) {
            echo "Error fetching profile: " . $e->getMessage();
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




// Method to update the profile picture
public function updateProfilePicture($userID, $profilePicturePath) {
    $sql = "UPDATE UserProfile SET ProfilePicture = :profilePicture WHERE UserID = :userID";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':profilePicture', $profilePicturePath);
    $stmt->bindParam(':userID', $userID);

    try {
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        echo "Error updating profile picture: " . $e->getMessage();
        return false;
    }
}

// Method to update other profile information
public function updateUserProfile($userID, $username, $bio, $email, $gender, $firstName, $lastName) {
    $this->username = htmlspecialchars($username);
    $this->email = htmlspecialchars($email);

    // Update User table for username and email
    $sqlUser = "UPDATE User SET Username = :username, Email = :email WHERE UserID = :userID";
    $stmtUser = $this->db->prepare($sqlUser);
    $stmtUser->bindParam(':username', $this->username);
    $stmtUser->bindParam(':email', $this->email);
    $stmtUser->bindParam(':userID', $userID);

    // Update UserProfile table for other profile fields
    $sqlProfile = "UPDATE UserProfile SET Bio = :bio, Gender = :gender, FirstName = :firstName, LastName = :lastName WHERE UserID = :userID";
    $stmtProfile = $this->db->prepare($sqlProfile);
    $stmtProfile->bindParam(':bio', $bio);
    $stmtProfile->bindParam(':gender', $gender);
    $stmtProfile->bindParam(':firstName', $firstName);
    $stmtProfile->bindParam(':lastName', $lastName);
    $stmtProfile->bindParam(':userID', $userID);

    try {
        $stmtUser->execute();
        $stmtProfile->execute();
        return true;
    } catch (PDOException $e) {
        echo "Error updating profile information: " . $e->getMessage();
        return false;
    }
}
}
