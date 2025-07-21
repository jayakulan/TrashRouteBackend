<?php
require_once 'User.php';

class Company extends User {
    private $companyName;

    public function __construct($id, $email, $name, $companyName) {
        parent::__construct($id, $email, $name);
        $this->companyName = $companyName;
    }

    public function getCompanyName() { return $this->companyName; }
    public function getRole() { return "Company"; }

    // Business logic: Register this company in the database
    public function register($db, $password, $company_reg_number, $contact_number = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Insert into registered_users
        $query = "INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) 
                  VALUES (:name, :email, :password_hash, :contact_number, '', 'company')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->execute();
        $user_id = $db->lastInsertId();
        // Insert into companies
        $query = "INSERT INTO companies (company_id, company_reg_number, company_name) VALUES (:user_id, :company_reg_number, :company_name)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':company_reg_number', $company_reg_number);
        $stmt->bindParam(':company_name', $this->companyName);
        $stmt->execute();
        return $user_id;
    }

    public function updateProfile($db, $user_id, $name, $email, $contact_number, $address) {
        $query = "SELECT user_id FROM registered_users WHERE email = :email AND user_id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Email already in use by another account');
        }
        $query = "UPDATE registered_users SET name = :name, email = :email, contact_number = :contact_number, address = :address WHERE user_id = :user_id AND role = 'company'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return true;
    }

    public static function login($db, $email, $password) {
        $query = "SELECT * FROM registered_users WHERE email = :email AND role = 'company'";
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
        $query = "SELECT c.company_id, c.company_reg_number, ru.name, ru.email, ru.contact_number, ru.address
                  FROM companies c
                  JOIN registered_users ru ON c.company_id = ru.user_id
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
            'role' => 'company',
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
        $role = 'company';
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        $user_id = $db->lastInsertId();
        // Insert into companies table if needed
        // $query = "INSERT INTO companies (company_id) VALUES (:user_id)";
        // $stmt = $db->prepare($query);
        // $stmt->bindParam(':user_id', $user_id);
        // $stmt->execute();
        $db->commit();
        unset($_SESSION['otp_' . $email]);
        unset($_SESSION['pending_registration_' . $email]);
        return true;
    }
} 