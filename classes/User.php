<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Company.php';
// Add more as needed

abstract class User {
    protected $id;
    protected $email;
    protected $name;

    public function __construct($id, $email, $name) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
    }

    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getName() { return $this->name; }

    abstract public function getRole();

    // Static password reset methods (moved from Customer)
    public static function sendPasswordResetOtp($db, $email) {
        $query = "SELECT user_id FROM registered_users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            throw new Exception('Email not registered');
        }
        $otp = rand(100000, 999999);
        $_SESSION['forgot_otp_' . $email] = $otp;
        $_SESSION['forgot_email_' . $email] = $email;
        return $otp;
    }

    public static function verifyPasswordResetOtp($email, $otp) {
        if (!isset($_SESSION['forgot_otp_' . $email]) || $_SESSION['forgot_otp_' . $email] != $otp) {
            throw new Exception('Invalid or expired OTP');
        }
        return true;
    }

    public static function resetPassword($db, $email, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE registered_users SET password_hash = :password_hash WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        unset($_SESSION['forgot_otp_' . $email]);
        unset($_SESSION['forgot_email_' . $email]);
        return true;
    }
} 