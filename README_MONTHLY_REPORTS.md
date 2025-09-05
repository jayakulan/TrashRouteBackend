# TrashRoute Monthly Report System

This system automatically generates comprehensive monthly reports for the TrashRoute waste management platform using real-time data from the database.

## Features

- **Automatic Monthly Generation**: Reports are generated on the first day of each month
- **Real-time Data**: All statistics are pulled directly from the TrashRoute database
- **PDF Generation**: Professional PDF reports using dompdf library
- **Comprehensive Metrics**: Includes all requested business metrics
- **View & Download**: Reports can be viewed online or downloaded as PDFs

## Report Contents

Each monthly report includes:

1. **Waste Collection Statistics by Type**:
   - Paper, Metal, Glass, Plastic
   - Total pickup requests for each waste type
   - Completed pickup requests for each waste type
   - Completion rate percentage

2. **Business Summary**:
   - Total sold routes for the month
   - New customer registrations
   - New company registrations
   - Total completed pickups

## Setup Instructions

### 1. Install Dependencies

Navigate to the backend directory and install Composer dependencies:

```bash
cd Trashroutefinal/TrashRouteBackend
composer install
```

### 2. Create Database Table

Run the SQL script to create the reports table:

```sql
-- Execute the contents of reports_table.sql in your MySQL database
```

Or import the file directly:

```bash
mysql -u root -p trashroute < reports_table.sql
```

### 3. Set Up Cron Job (Automatic Generation)

Add this cron job to automatically generate reports on the first day of each month:

```bash
# Edit crontab
crontab -e

# Add this line (adjust the path to match your server setup)
0 0 1 * * php /path/to/Trashroutefinal/TrashRouteBackend/cron/generate_monthly_report.php
```

**Alternative**: If you can't use cron, you can manually run the script:

```bash
php /path/to/Trashroutefinal/TrashRouteBackend/cron/generate_monthly_report.php
```

### 4. Create Reports Directory

Ensure the reports directory exists and is writable:

```bash
mkdir -p Trashroutefinal/TrashRouteBackend/reports
chmod 755 Trashroutefinal/TrashRouteBackend/reports
```

## API Endpoints

### 1. Get Reports Data (Charts)
```
GET /admin/reports.php
```
Returns waste composition and weekly routes data for charts.

### 2. Get Reports List
```
GET /admin/reports.php?action=get_reports
```
Returns list of all generated monthly reports.

### 3. Generate Monthly Report
```
GET /admin/reports.php?action=generate_report&month={month}&year={year}
```
Manually generates a report for a specific month/year.

### 4. View Report
```
GET /admin/reports.php?action=view_report&report_id={id}
```
Opens a PDF report in the browser.

### 5. Download Report
```
GET /admin/reports.php?action=download_report&report_id={id}
```
Downloads a PDF report.

## Database Schema

The `reports` table structure:

```sql
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reportable_month` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `total_pickup_requests` int(11) DEFAULT 0,
  `total_completed_pickups` int(11) DEFAULT 0,
  `total_sold_routes` int(11) DEFAULT 0,
  `total_customer_registrations` int(11) DEFAULT 0,
  `total_company_registrations` int(11) DEFAULT 0,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_month_year` (`reportable_month`, `created_at`)
);
```

## Data Sources

The system pulls data from these database tables:

- **pickup_requests**: Pickup request counts and completion status
- **routes**: Sold routes (accepted_at timestamp)
- **registered_users**: New user registrations (created_at timestamp)

## File Structure

```
TrashRouteBackend/
├── admin/
│   └── reports.php              # Main API endpoint
├── cron/
│   └── generate_monthly_report.php  # Automated report generation
├── reports/                     # Generated PDF files
├── vendor/                      # Composer dependencies (dompdf)
├── composer.json               # PHP dependencies
└── reports_table.sql           # Database schema
```

## Usage

### Frontend Integration

The Reports.jsx component automatically:
- Fetches and displays existing reports
- Provides "Generate Monthly Report" button
- Shows View/Download buttons for each report
- Displays real-time statistics

### Manual Report Generation

Administrators can manually generate reports by:
1. Clicking the "Generate Monthly Report" button
2. The system automatically determines the previous month
3. Generates PDF and saves to database
4. Updates the reports list

### Automatic Generation

The cron job automatically:
1. Runs on the 1st day of each month
2. Generates report for the previous month
3. Saves PDF and database entry
4. Skips if report already exists

## Troubleshooting

### Common Issues

1. **PDF Generation Fails**:
   - Check if dompdf is installed: `composer install`
   - Ensure reports directory is writable
   - Check PHP memory limit

2. **Database Connection Errors**:
   - Verify database credentials in reports.php
   - Ensure MySQL service is running
   - Check database name and table existence

3. **Cron Job Not Working**:
   - Verify cron service is running
   - Check file permissions
   - Test manual execution first

### Logs

Check your server's error logs for detailed error messages:
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- PHP: Check php.ini error_log setting

## Security Notes

- The system uses prepared statements to prevent SQL injection
- PDF files are stored in a protected directory
- Access control should be implemented at the web server level
- Consider implementing authentication for report access

## Performance Considerations

- Large databases may require query optimization
- Consider adding database indexes for timestamp columns
- PDF generation can be memory-intensive for large datasets
- Implement caching for frequently accessed reports

## Support

For technical support or questions about the monthly report system, please contact the development team or refer to the system documentation.

