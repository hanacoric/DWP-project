<?php
require_once __DIR__ . '/../includes/db.php';

class Auth
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Login method
    public function login($username, $password)
    {
        $sql = "SELECT User.*, Role.RoleName FROM User LEFT JOIN Role ON User.RoleID = Role.RoleID WHERE Username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['RoleName'];
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }

    // Check if user is logged in
    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Check if user is an admin
    public function isAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
    }

    // Logout method
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    // Register method
    public function register($username, $email, $password, $role = 'User')
    {
        $username = trim($username);
        $email = trim($email);
        $password = trim($password);

        $sql = "SELECT * FROM User WHERE Username = :username OR Email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            return false;
        }


        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $roleSql = "SELECT RoleID FROM Role WHERE RoleName = :roleName";
        $roleStmt = $this->db->prepare($roleSql);
        $roleStmt->bindParam(':roleName', $role);
        $roleStmt->execute();
        $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$roleData) {
            return false;
        }

        $roleID = $roleData['RoleID'];


        $sql = "INSERT INTO User (Username, Email, Password, Status, RoleID) VALUES (:username, :email, :password, 'Active', :roleID)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':roleID', $roleID);

        try {
            $stmt->execute();
            $userID = $this->db->lastInsertId();

            $sqlProfile = "INSERT INTO UserProfile (UserID, Bio, Gender, FirstLast) VALUES (:userID, '', '', '')";
            $stmtProfile = $this->db->prepare($sqlProfile);
            $stmtProfile->bindParam(':userID', $userID);
            $stmtProfile->execute();

            return true;
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

}
