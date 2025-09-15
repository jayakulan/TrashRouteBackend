# TrashRoute Backend API

A comprehensive waste management platform that connects customers, waste collection companies, and administrators through an efficient pickup request and route optimization system.

## 🚀 Overview

TrashRoute is a full-stack waste management application that streamlines the process of waste collection through:
- **Customer pickup requests** with location pinning
- **Company route optimization** using Mapbox integration
- **Admin management dashboard** with analytics and reporting
- **Real-time tracking** and OTP verification system
- **Payment processing** and feedback management

## 🏗️ Architecture

- **Backend**: PHP 8.2+ with PDO and MySQL
- **Frontend**: React 18 with Vite and Tailwind CSS
- **Database**: MySQL/MariaDB
- **Maps**: Mapbox integration for route optimization
- **Authentication**: JWT-based token system
- **Security**: CORS, SQL injection prevention, input sanitization

## 📋 Prerequisites

- XAMPP (Apache + MySQL)
- PHP 8.2+
- MySQL/MariaDB
- Node.js (for frontend development)

## 🛠️ Setup Instructions

### 1. XAMPP Setup
1. Install XAMPP on your system
2. Start Apache and MySQL services
3. Place the backend folder in your XAMPP htdocs directory

### 2. Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `trashroute`
3. Import the `database.sql` file to create all tables
4. Verify all tables are created successfully

### 3. Configuration
1. Update database connection in `config/database.php` if needed:
   ```php
   - Host: localhost
   - Username: root
   - Password: (empty by default)
   - Database: trashroute
   ```

### 4. Frontend Setup
1. Navigate to `TrashRouteFrontend` directory
2. Run `npm install` to install dependencies
3. Run `npm run dev` to start the development server
4. Access the application at `http://localhost:5175`

## 👥 User Roles & Features

### 🔐 Admin
- **Dashboard**: Comprehensive analytics and overview
- **User Management**: Manage customers, companies, and requests
- **Route Management**: Create and assign pickup routes
- **Reports**: Generate monthly reports and analytics
- **Contact Management**: Handle customer inquiries
- **Notifications**: Send system-wide notifications
- **Feedback Management**: Review service ratings

### 🏢 Company (Waste Collection)
- **Waste Preferences**: Set preferred waste types to collect
- **Route Access**: View assigned pickup routes
- **Route Mapping**: Interactive map with optimized routes
- **Payment Processing**: Handle customer payments
- **Pickup Verification**: OTP-based pickup confirmation
- **Feedback System**: Rate and comment on pickups
- **History Logs**: Track completed pickups

### 👤 Customer
- **Waste Type Selection**: Choose type of waste to dispose
- **Location Pinning**: Set pickup location on interactive map
- **Pickup Requests**: Submit waste pickup requests
- **OTP Verification**: Secure pickup verification
- **Real-time Tracking**: Track pickup status
- **Payment**: Pay for waste collection services
- **Feedback**: Rate service quality
- **History**: View past pickup records

## 🔌 API Endpoints

### Authentication
- **POST** `/api/auth/login.php` - User login
- **POST** `/api/auth/logout.php` - User logout
- **POST** `/api/auth/check_session.php` - Session validation

### Registration & OTP
- **POST** `/api/request_otp.php` - Request OTP for customer registration
- **POST** `/api/request_otp_company.php` - Request OTP for company registration
- **POST** `/api/verify_otp_and_register.php` - Verify OTP and complete registration
- **POST** `/api/verify_otp_registercompany.php` - Verify OTP for company registration

### Customer Operations
- **POST** `/Customer/CustomerTrashType.php` - Submit waste type selection
- **POST** `/Customer/CustomerLocationPin.php` - Pin pickup location
- **POST** `/Customer/pickupotp.php` - Verify pickup OTP
- **GET** `/Customer/trackPickup.php` - Track pickup status
- **GET** `/Customer/historylogs.php` - View pickup history
- **POST** `/Customer/submitFeedback.php` - Submit service feedback

### Company Operations
- **POST** `/Company/Companywasteprefer.php` - Set waste preferences
- **POST** `/Company/Routeaccess.php` - Access assigned routes
- **POST** `/Company/Routemap.php` - Get route mapping data
- **POST** `/Company/payments.php` - Process payments
- **POST** `/Company/company_feedback.php` - Submit pickup feedback
- **GET** `/Company/comhistorylogs.php` - View company history

### Admin Operations
- **GET** `/admin/managecustomers.php` - Get all customers
- **POST** `/admin/managecustomers.php?action=delete` - Disable customer
- **GET** `/admin/managecompanies.php` - Get all companies
- **POST** `/admin/managecompanies.php?action=delete` - Disable company
- **GET** `/admin/manageRequests.php` - Get all pickup requests
- **POST** `/admin/manageRequests.php?action=delete` - Delete pickup request
- **GET** `/admin/managecontactus.php` - Get contact submissions
- **GET** `/admin/reports.php` - Generate reports
- **POST** `/admin/generate_monthly_report.php` - Generate monthly reports
- **GET** `/admin/notification.php` - Get notifications
- **POST** `/admin/notification.php` - Send notifications

### Route Management
- **POST** `/api/route_status.php` - Update route status
- **POST** `/api/complete_route.php` - Mark route as complete
- **GET** `/api/notifications.php` - Get user notifications

## 📊 Database Schema

### Core Tables
- `registered_users` - Central user management
- `customers` - Customer-specific data
- `companies` - Company-specific data
- `admins` - Admin user data
- `pickup_requests` - Pickup request data
- `routes` - Route management
- `company_feedback` - Service feedback
- `contact_us` - Customer inquiries
- `notifications` - System notifications

## 🔒 Security Features

- **JWT-based Authentication**: Secure token-based authentication
- **Password Hashing**: PHP's built-in password_hash() function
- **SQL Injection Prevention**: Prepared statements throughout
- **Input Sanitization**: All user inputs are sanitized
- **CORS Configuration**: Proper cross-origin request handling
- **Role-based Access Control**: Different permissions for each user role
- **Session Management**: Secure cookie-based session handling

## 📱 Request/Response Format

### Request Headers
```
Content-Type: application/json
Authorization: Bearer <token> (for protected endpoints)
```

### Login Request
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Customer Registration Request
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "contact_number": "1234567890",
  "address": "123 Main St, City"
}
```

### Company Registration Request
```json
{
  "name": "Waste Management Co",
  "email": "company@example.com",
  "password": "password123",
  "contact_number": "1234567890",
  "address": "456 Business Ave, City",
  "company_reg_number": "REG123456"
}
```

### Standard Response Format
```json
{
  "success": true/false,
  "message": "Response message",
  "data": {
    // Response data
  },
  "error": "Error message (if applicable)"
}
```

## 🗂️ File Structure

```
TrashRouteBackend/
├── admin/                    # Admin management endpoints
│   ├── dashboard.php
│   ├── managecustomers.php
│   ├── managecompanies.php
│   ├── manageRequests.php
│   ├── managecontactus.php
│   ├── reports.php
│   ├── generate_monthly_report.php
│   └── notification.php
├── api/                      # Core API endpoints
│   ├── auth/                # Authentication endpoints
│   ├── notifications.php
│   ├── route_status.php
│   └── complete_route.php
├── classes/                  # PHP classes
│   ├── User.php
│   ├── Admin.php
│   ├── Customer.php
│   └── Company.php
├── Company/                  # Company-specific endpoints
│   ├── Companywasteprefer.php
│   ├── Routeaccess.php
│   ├── Routemap.php
│   ├── payments.php
│   └── company_feedback.php
├── Customer/                 # Customer-specific endpoints
│   ├── CustomerTrashType.php
│   ├── CustomerLocationPin.php
│   ├── pickupotp.php
│   ├── trackPickup.php
│   └── submitFeedback.php
├── config/                   # Configuration files
│   ├── database.php
│   └── email_config.php
├── utils/                    # Utility functions
│   ├── helpers.php
│   ├── session_auth_middleware.php
│   ├── company_validator.php
│   └── customer_validator.php
├── PHPMailer/               # Email functionality
├── vendor/                  # Composer dependencies
├── database.sql            # Database schema
└── README.md               # This file
```

## 🧪 Testing

### Manual Testing
1. Use Postman or similar tool to test API endpoints
2. Test authentication with different user roles
3. Verify CRUD operations for each user type
4. Test route optimization and mapping functionality
5. Verify payment processing and OTP systems

### Test Credentials
- **Admin**: admin@gmail.com / admin
- **Test Company**: Use company registration endpoint
- **Test Customer**: Use customer registration endpoint

## 🚨 Troubleshooting

### Common Issues
- **Database Connection**: Check XAMPP services are running
- **CORS Errors**: Verify CORS headers in PHP files
- **Authentication Issues**: Check token generation and validation
- **Mapbox Errors**: Verify Mapbox API key configuration
- **File Permissions**: Ensure proper file permissions on server

### Error Logs
- Check Apache error logs in XAMPP
- Review PHP error logs
- Monitor browser console for frontend errors

## 🔄 Business Workflow

### Customer Pickup Process
1. Customer selects waste type
2. Customer pins pickup location
3. System generates pickup request
4. Admin assigns request to company
5. Company receives route with optimized path
6. Company executes pickup with OTP verification
7. Customer pays for service
8. Both parties provide feedback

### Route Optimization
- Uses Mapbox Directions API for optimal routing
- Implements nearest neighbor algorithm as fallback
- Considers traffic conditions and distance
- Generates turn-by-turn directions

## 📈 Performance & Scalability

- **Database Optimization**: Indexed queries and prepared statements
- **Route Caching**: Optimized route calculations
- **Efficient Algorithms**: Nearest neighbor and Mapbox optimization
- **Responsive Design**: Mobile-friendly interface
- **Error Handling**: Comprehensive error management

## 🤝 Contributing

1. Follow PSR coding standards
2. Use prepared statements for database queries
3. Implement proper error handling
4. Add appropriate CORS headers
5. Test all endpoints thoroughly
6. Update documentation for new features

## 📄 License

This project is proprietary software for TrashRoute waste management platform.

---

**TrashRoute** - Making waste management efficient and environmentally responsible for a cleaner tomorrow.