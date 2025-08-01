<?php
class CompanyValidator {
    
    // Validation rules
    private static $rules = [
        'name' => [
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'pattern' => '/^[a-zA-Z0-9\s&.,()-]+$/'
        ],
        'email' => [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
        ],
        'password' => [
            'required' => true,
            'min_length' => 8,
            'max_length' => 128,
            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
        ],
        'contact_number' => [
            'required' => true,
            'exact_length' => 10,
            'pattern' => '/^\d{10}$/'
        ],
        'address' => [
            'required' => true,
            'min_length' => 10,
            'max_length' => 200
        ],
        'company_reg_number' => [
            'required' => true,
            'min_length' => 5,
            'max_length' => 50,
            'pattern' => '/^[A-Z0-9]+$/'
        ]
    ];
    
    // Error messages
    private static $error_messages = [
        'name' => [
            'required' => 'Company name is required',
            'min_length' => 'Company name must be at least 2 characters long',
            'max_length' => 'Company name cannot exceed 100 characters',
            'pattern' => 'Company name can only contain letters, numbers, spaces, and basic punctuation'
        ],
        'email' => [
            'required' => 'Email address is required',
            'pattern' => 'Please enter a valid email address',
            'unique' => 'This email address is already registered'
        ],
        'password' => [
            'required' => 'Password is required',
            'min_length' => 'Password must be at least 8 characters long',
            'max_length' => 'Password cannot exceed 128 characters',
            'pattern' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
        ],
        'contact_number' => [
            'required' => 'Phone number is required',
            'exact_length' => 'Phone number must be exactly 10 digits',
            'pattern' => 'Phone number must contain only digits'
        ],
        'address' => [
            'required' => 'Company address is required',
            'min_length' => 'Address must be at least 10 characters long',
            'max_length' => 'Address cannot exceed 200 characters'
        ],
        'company_reg_number' => [
            'required' => 'Company registration number is required',
            'min_length' => 'Registration number must be at least 5 characters long',
            'max_length' => 'Registration number cannot exceed 50 characters',
            'pattern' => 'Registration number can only contain uppercase letters and numbers',
            'unique' => 'This company registration number is already registered'
        ]
    ];
    
    /**
     * Validate company registration data
     */
    public static function validateCompanyData($data) {
        $errors = [];
        
        // Validate each field
        foreach (self::$rules as $field => $rules) {
            $value = $data[$field] ?? '';
            $field_errors = self::validateField($field, $value, $rules);
            if (!empty($field_errors)) {
                $errors[$field] = $field_errors;
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate individual field
     */
    private static function validateField($field, $value, $rules) {
        $errors = [];
        
        // Required check
        if ($rules['required'] && empty($value)) {
            $errors[] = self::$error_messages[$field]['required'];
            return $errors;
        }
        
        // Skip other validations if field is empty and not required
        if (empty($value) && !$rules['required']) {
            return $errors;
        }
        
        // Length validations
        if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
            $errors[] = self::$error_messages[$field]['min_length'];
        }
        
        if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
            $errors[] = self::$error_messages[$field]['max_length'];
        }
        
        if (isset($rules['exact_length']) && strlen($value) !== $rules['exact_length']) {
            $errors[] = self::$error_messages[$field]['exact_length'];
        }
        
        // Pattern validation
        if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
            $errors[] = self::$error_messages[$field]['pattern'];
        }
        
        return $errors;
    }
    
    /**
     * Sanitize company data
     */
    public static function sanitizeCompanyData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'name':
                    $sanitized[$key] = self::sanitizeCompanyName($value);
                    break;
                case 'email':
                    $sanitized[$key] = self::sanitizeEmail($value);
                    break;
                case 'password':
                    // Don't sanitize password, just validate
                    $sanitized[$key] = $value;
                    break;
                case 'contact_number':
                    $sanitized[$key] = self::sanitizePhoneNumber($value);
                    break;
                case 'address':
                    $sanitized[$key] = self::sanitizeAddress($value);
                    break;
                case 'company_reg_number':
                    $sanitized[$key] = self::sanitizeCompanyRegNumber($value);
                    break;
                default:
                    $sanitized[$key] = self::sanitizeGeneral($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize company name
     */
    private static function sanitizeCompanyName($name) {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name); // Replace multiple spaces with single space
        return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     */
    private static function sanitizeEmail($email) {
        $email = trim(strtolower($email));
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize phone number
     */
    private static function sanitizePhoneNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone); // Remove all non-digits
        return $phone;
    }
    
    /**
     * Sanitize address
     */
    private static function sanitizeAddress($address) {
        $address = trim($address);
        $address = preg_replace('/\s+/', ' ', $address); // Replace multiple spaces with single space
        return htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize company registration number
     */
    private static function sanitizeCompanyRegNumber($reg_number) {
        $reg_number = trim(strtoupper($reg_number));
        $reg_number = preg_replace('/[^A-Z0-9]/', '', $reg_number); // Keep only uppercase letters and numbers
        return $reg_number;
    }
    
    /**
     * General sanitization
     */
    private static function sanitizeGeneral($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Check if email is unique in database
     */
    public static function isEmailUnique($email, $db) {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM registered_users WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result['count'] == 0;
    }
    
    /**
     * Check if company registration number is unique
     */
    public static function isCompanyRegNumberUnique($reg_number, $db) {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM companies WHERE company_reg_number = ?');
        $stmt->execute([$reg_number]);
        $result = $stmt->fetch();
        return $result['count'] == 0;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[@$!%*?&]/', $password)) {
            $errors[] = 'Password must contain at least one special character (@$!%*?&)';
        }
        
        return $errors;
    }
    
    /**
     * Validate company registration number format
     */
    public static function validateCompanyRegNumber($reg_number) {
        $errors = [];
        
        // Check if it's alphanumeric and uppercase
        if (!preg_match('/^[A-Z0-9]+$/', $reg_number)) {
            $errors[] = 'Company registration number must contain only uppercase letters and numbers';
        }
        
        // Check length
        if (strlen($reg_number) < 5) {
            $errors[] = 'Company registration number must be at least 5 characters long';
        }
        
        if (strlen($reg_number) > 50) {
            $errors[] = 'Company registration number cannot exceed 50 characters';
        }
        
        return $errors;
    }
    
    /**
     * Complete validation and sanitization process
     */
    public static function processCompanyRegistration($data, $db) {
        // First sanitize the data
        $sanitized_data = self::sanitizeCompanyData($data);
        
        // Then validate the sanitized data
        $validation_errors = self::validateCompanyData($sanitized_data);
        
        // Check email uniqueness
        if (empty($validation_errors['email'])) {
            if (!self::isEmailUnique($sanitized_data['email'], $db)) {
                $validation_errors['email'] = [self::$error_messages['email']['unique']];
            }
        }
        
        // Check company registration number uniqueness
        if (empty($validation_errors['company_reg_number'])) {
            if (!self::isCompanyRegNumberUnique($sanitized_data['company_reg_number'], $db)) {
                $validation_errors['company_reg_number'] = [self::$error_messages['company_reg_number']['unique']];
            }
        }
        
        // Validate password strength
        if (empty($validation_errors['password'])) {
            $password_errors = self::validatePasswordStrength($sanitized_data['password']);
            if (!empty($password_errors)) {
                $validation_errors['password'] = $password_errors;
            }
        }
        
        // Additional validation for company registration number
        if (empty($validation_errors['company_reg_number'])) {
            $reg_number_errors = self::validateCompanyRegNumber($sanitized_data['company_reg_number']);
            if (!empty($reg_number_errors)) {
                $validation_errors['company_reg_number'] = $reg_number_errors;
            }
        }
        
        return [
            'sanitized_data' => $sanitized_data,
            'errors' => $validation_errors,
            'is_valid' => empty($validation_errors)
        ];
    }
}
?> 