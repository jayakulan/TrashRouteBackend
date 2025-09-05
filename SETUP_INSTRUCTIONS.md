# Quick Setup Guide for Monthly Reports

## Step 1: Install Dependencies

Open Command Prompt/Terminal and navigate to the backend directory:

```bash
cd C:\xampp\htdocs\Trashroutefinal1\Trashroutefinal\TrashRouteBackend
```

Install PHP dependencies:

```bash
composer install
```

## Step 2: Create Reports Directory

Create the reports folder for PDF storage:

```bash
mkdir reports
```

## Step 3: Test the System

1. Refresh your Reports page in the browser
2. The charts should now load with data from your existing database
3. Click "Generate Monthly Report" to test PDF generation
4. The PDF will automatically download to your device

## How It Works

- **No Database Table Required**: The system generates PDFs on-demand without storing report metadata
- **On-Demand Generation**: Each time you click "Generate Monthly Report", a fresh PDF is created
- **Automatic Download**: PDFs are automatically downloaded to your device
- **Real-time Data**: Reports always contain the latest data from your database

## Troubleshooting

### If you get "dompdf not installed" error:
- Make sure you ran `composer install` in the backend directory
- Check that the `vendor` folder was created

### If you get database errors:
- Verify your database name is `trashroute`
- Ensure MySQL is running in XAMPP
- Check that your existing tables (`pickup_requests`, `routes`, `registered_users`) exist

### If charts show zero values:
- This is normal if your database doesn't have data yet
- The system will show real data once you have pickup requests and routes

## Manual Report Generation

To manually generate a report, you can run:

```bash
php C:\xampp\htdocs\Trashroutefinal1\Trashroutefinal\TrashRouteBackend\admin\generate_monthly_report.php
```

This will create a PDF file in the `reports` directory.

## Next Steps

Once everything is working:
1. Set up a cron job for automatic monthly reports (optional)
2. Customize the PDF template if needed
3. Add more metrics to the reports

## Support

If you encounter issues:
1. Check the browser console for error messages
2. Check XAMPP error logs
3. Verify all file paths are correct
4. Ensure Composer dependencies are installed
