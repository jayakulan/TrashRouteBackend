# TrashRoute Backend API

This is the PHP backend for the TrashRoute waste management application.

## Setup Instructions

### 1. XAMPP Setup
1. Install XAMPP on your system
2. Start Apache and MySQL services
3. Place this backend folder in your XAMPP htdocs directory

### 2. Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `trashroute`
3. Import the database schema (you already have the tables created)

### 3. Configuration
1. Update database connection in `config/database.php` if needed:
   - Default: localhost, root, no password
   - Database name: trashroute

### 4. Test the Backend
1. Test database connection: `http://localhost/your-project-path/Backend/test.php`
2. You should see a JSON response indicating success

## API Endpoints

### Authentication
- **POST** `/api/auth/login.php` - User login
- **POST** `/api/auth/register.php` - User registration

### Request Format
All requests should be sent as JSON with the following headers:
```
Content-Type: application/json
```

### Login Request
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Register Request (Customer)
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "customer",
  "contact_number": "1234567890",
  "address": "123 Main St, City"
}
```

### Register Request (Company)
```json
{
  "name": "Waste Management Co",
  "email": "company@example.com",
  "password": "password123",
  "role": "company",
  "contact_number": "1234567890",
  "address": "456 Business Ave, City",
  "company_reg_number": "REG123456"
}
```

## Response Format
All API responses follow this format:
```json
{
  "success": true/false,
  "message": "Response message",
  "data": {
    // Response data
  }
}
```

## Security Features
- Password hashing using PHP's built-in password_hash()
- Input sanitization
- SQL injection prevention using prepared statements
- CORS headers for cross-origin requests
- JWT-like token authentication

## File Structure
```
Backend/
├── config/
│   └── database.php          # Database configuration
├── utils/
│   └── helpers.php           # Helper functions
├── api/
│   └── auth/
│       ├── login.php         # Login endpoint
│       └── register.php      # Registration endpoint
├── test.php                  # Test endpoint
└── README.md                 # This file
```

## Testing
1. Use Postman or similar tool to test API endpoints
2. Test login with existing users in your database
3. Test registration with new user data
4. Verify responses match expected format

## Troubleshooting
- Check XAMPP services are running
- Verify database connection settings
- Check file permissions
- Review Apache error logs if needed 