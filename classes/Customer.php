<?php
require_once 'User.php';

class Customer extends User {
    private $address;

    public function __construct($id, $email, $name, $address) {
        parent::__construct($id, $email, $name);
        $this->address = $address;
    }

    public function getAddress() { return $this->address; }
    public function getRole() { return "Customer"; }

    // Business logic: Register this customer in the database
    public function register($db, $password, $contact_number = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) 
                  VALUES (:name, :email, :password_hash, :contact_number, :address, 'customer')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':address', $this->address);
        $stmt->execute();
        $user_id = $db->lastInsertId();
        $query = "INSERT INTO customers (customer_id) VALUES (:user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $user_id;
    }

    public function updateProfile($db, $userId, $name, $email, $phone, $address, $currentPassword = null, $newPassword = null, $confirmPassword = null) {
        // Debug logging
        error_log("Customer updateProfile called with: userId=$userId, name=$name, email=$email, phone=$phone, address=$address");
        // First, get user data from registered_users table
        $stmt = $db->prepare("SELECT * FROM registered_users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Check if email is being changed and if it's already taken
        if ($email !== $user['email']) {
            $stmt = $db->prepare("SELECT user_id FROM registered_users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception('Email is already taken by another user');
            }
        }
        
        $passwordUpdate = '';
        $passwordParams = [];
        if ($currentPassword || $newPassword || $confirmPassword) {
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required for password change');
            }
            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long');
            }
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New password and confirm password do not match');
            }
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordUpdate = ', password_hash = ?';
            $passwordParams[] = $hashedPassword;
        }
        
        $updateFields = [$name, $email, $phone, $address];
        if (!empty($passwordParams)) {
            $updateFields = array_merge($updateFields, $passwordParams);
        }
        $updateFields[] = $userId;
        
        $sql = "UPDATE registered_users SET name = ?, email = ?, contact_number = ?, address = ?" . $passwordUpdate . " WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($updateFields);
        if (!$result) {
            throw new Exception('Failed to update profile');
        }
        
        $stmt = $db->prepare("SELECT user_id, name, email, contact_number, address, created_at FROM registered_users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function login($db, $email, $password) {
        $query = "SELECT * FROM registered_users WHERE email = :email AND role = 'customer'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception('Invalid email or password');
        }
        if ($user['disable_status'] !== 'active') {
            throw new Exception('Account is disabled');
        }
        // Accept both plain text and hashed passwords (for legacy/testing)
        if (
            $user['password_hash'] === $password || // plain text match
            password_verify($password, $user['password_hash']) // hashed match
        ) {
            // OK
        } else {
            throw new Exception('Invalid email or password');
        }
        $query = "SELECT c.customer_id, ru.name, ru.email, ru.contact_number, ru.address
                  FROM customers c
                  JOIN registered_users ru ON c.customer_id = ru.user_id
                  WHERE ru.user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'user' => $user,
            'profile' => $profile
        ];
    }

    public static function savePendingRegistration($input) {
        $required_fields = ['name', 'email', 'password', 'contact_number', 'address'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                throw new Exception("Missing field: $field");
            }
        }
        $email = $input['email'];
        $otp = rand(100000, 999999);
        $_SESSION['otp_' . $email] = $otp;
        $_SESSION['pending_registration_' . $email] = [
            'name' => $input['name'],
            'email' => $email,
            'password' => $input['password'],
            'contact_number' => $input['contact_number'],
            'address' => $input['address'],
            'role' => 'customer',
        ];
        return $otp;
    }

    public static function verifyOtpAndRegister($db, $email, $otp) {
        if (!isset($_SESSION['otp_' . $email]) || !isset($_SESSION['pending_registration_' . $email])) {
            throw new Exception('No OTP or registration found for this email');
        }
        if ($_SESSION['otp_' . $email] != $otp) {
            throw new Exception('Invalid OTP');
        }
        $reg = $_SESSION['pending_registration_' . $email];
        $query = "SELECT user_id FROM registered_users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Email already registered');
        }
        $db->beginTransaction();
        $hashed_password = password_hash($reg['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) VALUES (:name, :email, :password_hash, :contact_number, :address, :role)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $reg['name']);
        $stmt->bindParam(':email', $reg['email']);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':contact_number', $reg['contact_number']);
        $stmt->bindParam(':address', $reg['address']);
        $stmt->bindParam(':role', $reg['role']);
        $stmt->execute();
        $user_id = $db->lastInsertId();
        $query = "INSERT INTO customers (customer_id) VALUES (:user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $db->commit();
        unset($_SESSION['otp_' . $email]);
        unset($_SESSION['pending_registration_' . $email]);
        return true;
    }
} 