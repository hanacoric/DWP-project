<?php
require_once __DIR__ . '/../includes/db.php';

class Auth
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    //login method
    public function login($username, $password) {
        $username = trim($username);
        $password = trim($password);

        $sql = "SELECT * FROM User WHERE Username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);

        try {
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['Password'])) {
                session_start();
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['logged_in'] = true;
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo "Login error: " . $e->getMessage();
            return false;
        }
    }

    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

//logout method
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    //register method
    public function register($username, $email, $password) {
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
            echo "Username or email already exists.";
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO User (Username, Email, Password, Status, RoleID) VALUES (:username, :email, :password, 'Active', 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Registration error: " . $e->getMessage();
            return false;
        }
    }

}


