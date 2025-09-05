<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include dompdf autoloader and use statements
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Set CORS headers first
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Admin authentication
try {
    $adminUser = SessionAuthMiddleware::requireAdminAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => $e->getMessage()
    ]);
    exit();
}

try {
    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // Calculate last month's date range
    $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
    $lastMonthName = date('F Y', strtotime('first day of last month'));

    // Get pickup requests data for last month by waste type
    $pickupRequestsQuery = "
        SELECT 
            waste_type,
            COUNT(*) as count
        FROM pickup_requests 
        WHERE timestamp >= :start_date 
        AND timestamp <= :end_date
        GROUP BY waste_type
    ";
    
    $stmt = $pdo->prepare($pickupRequestsQuery);
    $stmt->bindParam(':start_date', $lastMonthStart);
    $stmt->bindParam(':end_date', $lastMonthEnd);
    $stmt->execute();
    $pickupResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize pickup request counts
    $pickupData = [
        'Glass' => 0,
        'Plastic' => 0,
        'Metal' => 0,
        'Paper' => 0
    ];
    
    foreach ($pickupResults as $row) {
        $wasteType = $row['waste_type'];
        if (isset($pickupData[$wasteType])) {
            $pickupData[$wasteType] = (int)$row['count'];
        }
    }

    // Get total sold routes for last month
    $soldRoutesQuery = "
        SELECT COUNT(*) as total_sold_routes
        FROM routes 
        WHERE accepted_at >= :start_date 
        AND accepted_at <= :end_date
        AND accepted_at IS NOT NULL
    ";
    
    $stmt = $pdo->prepare($soldRoutesQuery);
    $stmt->bindParam(':start_date', $lastMonthStart);
    $stmt->bindParam(':end_date', $lastMonthEnd);
    $stmt->execute();
    $soldRoutesResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSoldRoutes = (int)$soldRoutesResult['total_sold_routes'];

    // Get new customer registrations for last month
    $newCustomersQuery = "
        SELECT COUNT(*) as new_customers
        FROM registered_users 
        WHERE created_at >= :start_date 
        AND created_at <= :end_date
        AND role = 'customer'
        AND disable_status = 'active'
    ";
    
    $stmt = $pdo->prepare($newCustomersQuery);
    $stmt->bindParam(':start_date', $lastMonthStart);
    $stmt->bindParam(':end_date', $lastMonthEnd);
    $stmt->execute();
    $newCustomersResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $newCustomers = (int)$newCustomersResult['new_customers'];

    // Get new company registrations for last month
    $newCompaniesQuery = "
        SELECT COUNT(*) as new_companies
        FROM registered_users 
        WHERE created_at >= :start_date 
        AND created_at <= :end_date
        AND role = 'company'
        AND disable_status = 'active'
    ";
    
    $stmt = $pdo->prepare($newCompaniesQuery);
    $stmt->bindParam(':start_date', $lastMonthStart);
    $stmt->bindParam(':end_date', $lastMonthEnd);
    $stmt->execute();
    $newCompaniesResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $newCompanies = (int)$newCompaniesResult['new_companies'];

    // Generate PDF using dompdf
    // Create PDF options
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);

    $dompdf = new Dompdf($options);

    // HTML content for the PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Monthly Report - ' . $lastMonthName . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #3a5f46;
                padding-bottom: 20px;
            }
            .header h1 {
                color: #3a5f46;
                margin: 0;
                font-size: 28px;
            }
            .header h2 {
                color: #666;
                margin: 5px 0 0 0;
                font-size: 18px;
                font-weight: normal;
            }
            .report-date {
                text-align: center;
                margin-bottom: 30px;
                font-size: 16px;
                color: #666;
            }
            .section {
                margin-bottom: 25px;
                page-break-inside: avoid;
            }
            .section h3 {
                color: #3a5f46;
                border-bottom: 2px solid #e6f4ea;
                padding-bottom: 5px;
                margin-bottom: 15px;
                font-size: 20px;
            }
            .data-grid {
                display: table;
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .data-row {
                display: table-row;
            }
            .data-cell {
                display: table-cell;
                padding: 12px;
                border: 1px solid #ddd;
                background-color: #f9f9f9;
            }
            .data-cell:first-child {
                background-color: #3a5f46;
                color: white;
                font-weight: bold;
                width: 60%;
            }
            .data-cell:last-child {
                background-color: #e6f4ea;
                text-align: center;
                font-weight: bold;
                font-size: 18px;
                color: #2e4d3a;
                width: 40%;
            }
            .summary-section {
                background-color: #f7f9fb;
                padding: 20px;
                border-radius: 8px;
                border: 2px solid #3a5f46;
                margin-top: 30px;
            }
            .summary-section h3 {
                color: #3a5f46;
                text-align: center;
                margin-bottom: 20px;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>TrashRoute Monthly Report</h1>
            <h2>Waste Management System</h2>
        </div>
        
        <div class="report-date">
            <strong>Report Period: ' . $lastMonthName . '</strong><br>
            Generated on: ' . date('F j, Y \a\t g:i A') . '
        </div>

        <div class="section">
            <h3>📊 Pickup Requests by Waste Type</h3>
            <div class="data-grid">
                <div class="data-row">
                    <div class="data-cell">Glass Pickup Requests</div>
                    <div class="data-cell">' . $pickupData['Glass'] . '</div>
                </div>
                <div class="data-row">
                    <div class="data-cell">Plastic Pickup Requests</div>
                    <div class="data-cell">' . $pickupData['Plastic'] . '</div>
                </div>
                <div class="data-row">
                    <div class="data-cell">Metal Pickup Requests</div>
                    <div class="data-cell">' . $pickupData['Metal'] . '</div>
                </div>
                <div class="data-row">
                    <div class="data-cell">Paper Pickup Requests</div>
                    <div class="data-cell">' . $pickupData['Paper'] . '</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>🚛 Business Operations</h3>
            <div class="data-grid">
                <div class="data-row">
                    <div class="data-cell">Total Sold Routes</div>
                    <div class="data-cell">' . $totalSoldRoutes . '</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>👥 User Registrations</h3>
            <div class="data-grid">
                <div class="data-row">
                    <div class="data-cell">New Customer Registrations</div>
                    <div class="data-cell">' . $newCustomers . '</div>
                </div>
                <div class="data-row">
                    <div class="data-cell">New Company Registrations</div>
                    <div class="data-cell">' . $newCompanies . '</div>
                </div>
            </div>
        </div>

        <div class="summary-section">
            <h3>📈 Monthly Summary</h3>
            <p><strong>Total Pickup Requests:</strong> ' . array_sum($pickupData) . '</p>
            <p><strong>Total Routes Sold:</strong> ' . $totalSoldRoutes . '</p>
            <p><strong>Total New Users:</strong> ' . ($newCustomers + $newCompanies) . '</p>
            <p><strong>Most Requested Waste Type:</strong> ' . (array_sum($pickupData) > 0 ? array_keys($pickupData, max($pickupData))[0] : 'N/A') . '</p>
        </div>

        <div class="footer">
            <p>This report was automatically generated by TrashRoute Management System</p>
            <p>For questions or support, please contact the system administrator</p>
        </div>
    </body>
    </html>';

    // Load HTML into dompdf
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // Generate filename
    $filename = 'TrashRoute_Monthly_Report_' . date('Y-m', strtotime('first day of last month')) . '.pdf';

    // Set proper headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dompdf->output()));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output the PDF
    echo $dompdf->output();

} catch (Exception $e) {
    error_log("Generate Report Error: " . $e->getMessage());
    
    // If we're in PDF generation mode, show a simple error page
    if (isset($dompdf)) {
        header('Content-Type: text/html');
        echo '<html><body><h1>Error Generating Report</h1><p>An error occurred while generating the PDF report. Please try again later.</p></body></html>';
    } else {
        // If we haven't started PDF generation, return JSON error
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'message' => $e->getMessage()
        ]);
    }
}
?>
