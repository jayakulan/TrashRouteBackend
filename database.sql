-- Database: trashroute
-- Complete database schema for TrashRoute waste management system

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS trashroute;
USE trashroute;

-- Table: registered_users
CREATE TABLE registered_users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  contact_number VARCHAR(15),
  address TEXT,
  role ENUM('customer', 'company', 'admin') NOT NULL,
  disable_status ENUM('active', 'disabled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: customers
CREATE TABLE customers (
  customer_id INT PRIMARY KEY,
  FOREIGN KEY (customer_id) REFERENCES registered_users(user_id) ON DELETE CASCADE
);

-- Table: companies
CREATE TABLE companies (
  company_id INT PRIMARY KEY,
  company_reg_number VARCHAR(50) NOT NULL UNIQUE,
  FOREIGN KEY (company_id) REFERENCES registered_users(user_id) ON DELETE CASCADE
);

-- Table: admins
CREATE TABLE admins (
  admin_id INT PRIMARY KEY,
  FOREIGN KEY (admin_id) REFERENCES registered_users(user_id) ON DELETE CASCADE
);

-- Table: pickup_requests
CREATE TABLE pickup_requests (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  waste_type VARCHAR(100),
  quantity INT,
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  status ENUM('Request received', 'Pending', 'Accepted', 'Completed') DEFAULT 'Request received',
  otp VARCHAR(10),
  otp_verified BOOLEAN DEFAULT FALSE,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
);


-- Table: routes
CREATE TABLE routes (
  route_id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  company_id INT NOT NULL,
  no_of_customers INT DEFAULT 1,
  is_accepted BOOLEAN DEFAULT FALSE,
  accepted_at DATETIME NULL,
  is_disabled BOOLEAN DEFAULT FALSE,
  route_details TEXT,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE,
  FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

-- Table: payments
CREATE TABLE payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  route_id INT NOT NULL,
  card_number VARCHAR(16),
  cardholder_name VARCHAR(100),
  expiry_date DATE,
  pin_number VARCHAR(4),
  amount DECIMAL(10,2) DEFAULT 500.00,
  payment_status ENUM('Paid', 'Pending') DEFAULT 'Pending',
  payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
  FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE
);

-- Table: customer_feedback
CREATE TABLE customer_feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  customer_id INT NOT NULL,
  pickup_completed BOOLEAN DEFAULT FALSE,
  rating INT CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
);

-- Table: company_feedback
CREATE TABLE company_feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  company_id INT NOT NULL,
  entered_otp VARCHAR(10), -- OTP entered by the company
  pickup_verified BOOLEAN DEFAULT FALSE, -- Set to TRUE if OTP matches
  pickup_completed BOOLEAN DEFAULT FALSE,
  rating INT CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE,
  FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

CREATE TABLE `route_request_mapping` (
  `mapping_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table: otp
CREATE TABLE otp (
  otp_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  otp_code VARCHAR(10) NOT NULL,
  expiration_time DATETIME NOT NULL,
  is_used BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES registered_users(user_id) ON DELETE CASCADE
);

-- Table: notifications
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  request_id INT,
  company_id INT,
  message TEXT NOT NULL,
  seen BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES registered_users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE,
  FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE SET NULL
);

CREATE TABLE contact_us (
  contact_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,               -- Sender: customer or company (from registered_users)
  admin_id INT,              -- Receiver: admin assigned (optional)
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  subject VARCHAR(150),
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES registered_users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL
);


-- Insert sample admin user
INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) VALUES
('Admin User', 'admin@gmail.com', 'admin', '1234567890', 'Admin Address', 'admin');

-- Insert admin record
INSERT INTO admins (admin_id) VALUES (LAST_INSERT_ID());

-- Insert additional admin user
INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) VALUES
('Sarah Johnson', 'sarah.johnson@trashroute.com', 'adminSJ2024', '+94771234567', '123 Management Drive, Colombo 07, Sri Lanka', 'admin');

-- Insert additional admin record
INSERT INTO admins (admin_id) VALUES (LAST_INSERT_ID());

-- Insert sample customer user
INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) VALUES
('John Customer', 'customer@gmail.com', 'customer', '1234567891', 'Customer Address', 'customer');

-- Insert customer record
INSERT INTO customers (customer_id) VALUES (LAST_INSERT_ID());

-- Insert sample company user
INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) VALUES
('Waste Management Co', 'company@gmail.com', 'company', '1234567892', 'Company Address', 'company');

-- Insert company record
INSERT INTO companies (company_id, company_reg_number) VALUES (LAST_INSERT_ID(), 'REG123456');

-- Insert sample pickup request
INSERT INTO pickup_requests (customer_id, waste_type, quantity, latitude, longitude) VALUES
(2, 'Plastics', 5, 40.7128, -74.0060);

-- Insert sample route
INSERT INTO routes (request_id, company_id, no_of_customers, route_details) VALUES
(1, 3, 1, 'Route from company to customer location');

-- Insert sample notification
INSERT INTO notifications (user_id, request_id, message) VALUES
(2, 1, 'Your pickup request has been received and is being processed.');

-- Create indexes for better performance
CREATE INDEX idx_registered_users_email ON registered_users(email);
CREATE INDEX idx_registered_users_role ON registered_users(role);
CREATE INDEX idx_pickup_requests_customer ON pickup_requests(customer_id);
CREATE INDEX idx_pickup_requests_status ON pickup_requests(status);
CREATE INDEX idx_routes_company ON routes(company_id);
CREATE INDEX idx_routes_request ON routes(request_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_seen ON notifications(seen);
CREATE INDEX idx_otp_user ON otp(user_id);
CREATE INDEX idx_otp_expiration ON otp(expiration_time);

-- Show tables
SHOW TABLES;

-- Show sample data
SELECT 'Registered Users:' as info;
SELECT user_id, name, email, role, disable_status FROM registered_users;

SELECT 'Customers:' as info;
SELECT c.customer_id, ru.name, ru.email FROM customers c 
JOIN registered_users ru ON c.customer_id = ru.user_id;

SELECT 'Companies:' as info;
SELECT c.company_id, c.company_reg_number, ru.name, ru.email FROM companies c 
JOIN registered_users ru ON c.company_id = ru.user_id;

SELECT 'Pickup Requests:' as info;
SELECT pr.request_id, pr.waste_type, pr.quantity, pr.status, ru.name as customer_name 
FROM pickup_requests pr 
JOIN customers c ON pr.customer_id = c.customer_id 
JOIN registered_users ru ON c.customer_id = ru.user_id; 


CREATE TABLE IF NOT EXISTS admins (
  admin_id INT PRIMARY KEY,
  FOREIGN KEY (admin_id) REFERENCES registered_users(user_id) ON DELETE CASCADE
);

-- Check if admin users exist in registered_users
-- If not, create default admin user
INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) 
SELECT 'Admin User', 'admin@gmail.com', 'admin', '1234567890', 'Admin Address', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM registered_users WHERE email = 'admin@gmail.com');

-- Insert admin record for the default admin
INSERT INTO admins (admin_id) 
SELECT user_id FROM registered_users 
WHERE email = 'admin@gmail.com' AND role = 'admin'
AND NOT EXISTS (SELECT 1 FROM admins WHERE admin_id = registered_users.user_id);

-- Create additional admin if needed
INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) 
SELECT 'Sarah Johnson', 'sarah.johnson@trashroute.com', 'adminSJ2024', '+94771234567', '123 Management Drive, Colombo 07, Sri Lanka', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM registered_users WHERE email = 'sarah.johnson@trashroute.com');

-- Insert admin record for Sarah Johnson
INSERT INTO admins (admin_id) 
SELECT user_id FROM registered_users 
WHERE email = 'sarah.johnson@trashroute.com' AND role = 'admin'
AND NOT EXISTS (SELECT 1 FROM admins WHERE admin_id = registered_users.user_id);

-- Show all admin users
SELECT 'Admin Users:' as info;
SELECT ru.user_id, ru.name, ru.email, ru.role, 
       CASE WHEN a.admin_id IS NOT NULL THEN 'Yes' ELSE 'No' END as in_admins_table
FROM registered_users ru 
LEFT JOIN admins a ON ru.user_id = a.admin_id 
WHERE ru.role = 'admin'; 