<?php
require_once __DIR__ . '/../includes/db.php';

class Auth
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    //login
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

    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }



    //logout
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

//register
    public function register($username, $email, $password, $role = 'User') {
        $debug = "Starting registration: Username = $username, Email = $email\n";

        $username = trim($username);
        $email = strtolower(trim($email));
        $password = trim($password);

        try {
            $sql = "SELECT * FROM User WHERE LOWER(Username) = LOWER(:username) OR LOWER(Email) = LOWER(:email)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            $debug .= "Duplicate check result: " . json_encode($existingUser) . "\n";

            if ($existingUser) {
                $debug .= "Duplicate user found\n";
                return [false, $debug];
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $roleSql = "SELECT RoleID FROM Role WHERE RoleName = :roleName";
            $roleStmt = $this->db->prepare($roleSql);
            $roleStmt->bindParam(':roleName', $role);
            $roleStmt->execute();
            $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$roleData) {
                $debug .= "Role not found: $role\n";
                return [false, $debug];
            }

            $roleID = $roleData['RoleID'];
            $sql = "INSERT INTO User (Username, Email, Password, Status, RoleID) VALUES (:username, :email, :password, 'Active', :roleID)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':roleID', $roleID);
            $stmt->execute();

            $userID = $this->db->lastInsertId();
            $debug .= "User successfully created with ID: $userID\n";

            $sqlProfile = "INSERT INTO UserProfile (UserID, Bio, Gender, FirstLast) VALUES (:userID, '', '', '')";
            $stmtProfile = $this->db->prepare($sqlProfile);
            $stmtProfile->bindParam(':userID', $userID);
            $stmtProfile->execute();
            $debug .= "UserProfile successfully created for UserID: $userID\n";

            return [true, $debug];
        } catch (PDOException $e) {
            $debug .= "Registration error: " . $e->getMessage() . "\n";
            return [false, $debug];
        }
    }



    public function isAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
    }
}
