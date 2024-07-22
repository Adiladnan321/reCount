<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}
require_once 'database.php';

// Functions to fetch data for reports
function getSalesByCustomer($conn, $customerId, $startDate, $endDate) {
    $sql = "SELECT 
                c.CustomerName, 
                s.SaleDate, 
                i.ProductName,
                s.Quantity,
                s.UnitPrice,
                s.Amount
            FROM sale s
            JOIN customer c ON s.CustomerID = c.CustomerID
            JOIN inventory i ON s.ProductID = i.ProductID
            WHERE s.SaleDate BETWEEN ? AND ?";

    if ($customerId) {
        $sql .= " AND s.CustomerID = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($customerId) {
        $stmt->bind_param("ssi", $startDate, $endDate, $customerId); 
    } else {
        $stmt->bind_param("ss", $startDate, $endDate); 
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
} 

function getSalesByProduct($conn, $productId, $startDate, $endDate) {
    $sql = "SELECT 
                i.ProductName,
                s.SaleDate, 
                c.CustomerName,
                s.Quantity,
                s.UnitPrice,
                s.Amount
            FROM sale s
            JOIN customer c ON s.CustomerID = c.CustomerID
            JOIN inventory i ON s.ProductID = i.ProductID
            WHERE s.SaleDate BETWEEN ? AND ?";

    if ($productId) {
        $sql .= " AND s.ProductID = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($productId) {
        $stmt->bind_param("ssi", $startDate, $endDate, $productId); 
    } else {
        $stmt->bind_param("ss", $startDate, $endDate); 
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
} 

function getprofitAndLoss($conn, $productId, $startDate, $endDate) {
    $sql = "SELECT 
                i.ProductName,
                s.SaleDate, 
                c.CustomerName,
                s.Quantity,
                s.UnitPrice,
                s.Amount
            FROM sale s
            JOIN customer c ON s.CustomerID = c.CustomerID
            JOIN inventory i ON s.ProductID = i.ProductID
            WHERE s.SaleDate BETWEEN ? AND ?";

    if ($productId) {
        $sql .= " AND s.ProductID = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($productId) {
        $stmt->bind_param("ssi", $startDate, $endDate, $productId); 
    } else {
        $stmt->bind_param("ss", $startDate, $endDate); 
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
} 

// Other report functions...

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $reportType = $_GET['reportType'];
    $customerId = isset($_GET['customerId']) ? $_GET['customerId'] : null;
    $productId = isset($_GET['productId']) ? $_GET['productId'] : null;
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Customer/Product Name', 'Sale Date', 'Product/Customer Name', 'Quantity', 'Unit Price', 'Amount']);

    switch ($reportType) {
        case 'salesByCustomer':
            $salesData = getSalesByCustomer($conn, $customerId, $startDate, $endDate);
            break;
        case 'salesByProduct':
            $salesData = getSalesByProduct($conn, $productId, $startDate, $endDate);
            break;
        case 'profitAndLoss':
            $salesData = getprofitAndLoss($conn, $productId, $startDate, $endDate);
            break;
        // ... other cases for different report types
    }

    foreach ($salesData as $sale) {
        fputcsv($output, [
            $sale['CustomerName'] ?? $sale['ProductName'],
            $sale['SaleDate'],
            $sale['ProductName'] ?? $sale['CustomerName'],
            $sale['Quantity'],
            $sale['UnitPrice'],
            $sale['Amount']
        ]);
    }

    fclose($output);
    exit();
}

// // Export to PDF
// // require_once 'vendor/autoload.php';

// if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
//     $reportType = $_GET['reportType'];
//     $customerId = isset($_GET['customerId']) ? $_GET['customerId'] : null;
//     $productId = isset($_GET['productId']) ? $_GET['productId'] : null;
//     $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
//     $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

//     $mpdf = new \Mpdf\Mpdf();
//     $html = '<h1>Report</h1>';

//     switch ($reportType) {
//         case 'salesByCustomer':
//             $salesData = getSalesByCustomer($conn, $customerId, $startDate, $endDate);
//             break;
//         case 'salesByProduct':
//             $salesData = getSalesByProduct($conn, $productId, $startDate, $endDate);
//             break;
//         case 'profitAndLoss':
//             $salesData = getprofitAndLoss($conn, $productId, $startDate, $endDate);
//             break;
//         // ... other cases for different report types
//     }

//     $html .= '<table border="1" style="width: 100%;">';
//     $html .= '<thead><tr>
//                 <th>Customer/Product Name</th>
//                 <th>Sale Date</th>
//                 <th>Product/Customer Name</th>
//                 <th>Quantity</th>
//                 <th>Unit Price</th>
//                 <th>Amount</th>
//               </tr></thead>';
//     $html .= '<tbody>';
//     foreach ($salesData as $sale) {
//         $html .= '<tr>';
//         $html .= '<td>' . ($sale['CustomerName'] ?? $sale['ProductName']) . '</td>';
//         $html .= '<td>' . $sale['SaleDate'] . '</td>';
//         $html .= '<td>' . ($sale['ProductName'] ?? $sale['CustomerName']) . '</td>';
//         $html .= '<td>' . $sale['Quantity'] . '</td>';
//         $html .= '<td>' . $sale['UnitPrice'] . '</td>';
//         $html .= '<td>' . $sale['Amount'] . '</td>';
//         $html .= '</tr>';
//     }
//     $html .= '</tbody></table>';

//     $mpdf->WriteHTML($html);
//     $mpdf->Output('report.pdf', 'D');
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Add your custom styles here */
    </style>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'">Home</button>
    <br>
    <h1>Accounting Reports</h1>

    <div class="row">
        <!-- Report Filters -->
        <div class="col-md-4">
            <h2>Filters</h2>
            <form method="GET" action="report1.php"> 
                <div class="mb-3">
                    <label for="reportType" class="form-label">Report Type</label>
                    <select class="form-select" id="reportType" name="reportType">
                        <option value="salesByCustomer">Sales by Customer</option>
                        <option value="salesByProduct">Sales by Product</option>
                        <option value="profitAndLoss">Profit and Loss</option>
                        <!-- Add more report types as needed -->
                    </select>
                </div>
                <div class="mb-3">
                    <label for="customerId" class="form-label">Customer ID (Optional)</label>
                    <input type="text" class="form-control" id="customerId" name="customerId">
                </div>
                <div class="mb-3">
                    <label for="productId" class="form-label">Product ID (Optional)</label>
                    <input type="text" class="form-control" id="productId" name="productId">
                </div>
                <div class="mb-3">
                    <label for="daterange" class="form-label">Date Range</label>
                    <input type="text" class="form-control" id="daterange" name="daterange" value="">
                </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
        </div>

        <!-- Report Results -->
        <div class="col-md-8">
            <h2>Report Results</h2>
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $reportType = isset($_GET['reportType']) ? $_GET['reportType'] : 'salesByCustomer';
                $customerId = isset($_GET['customerId']) ? $_GET['customerId'] : null;
                $productId = isset($_GET['productId']) ? $_GET['productId'] : null;
                $dateRange = isset($_GET['daterange']) ? explode(' - ', $_GET['daterange']) : [date('Y-m-01'), date('Y-m-t')];
                $startDate = $dateRange[0];
                $endDate = $dateRange[1];

                switch ($reportType) {
                    case 'salesByCustomer':
                        $reportData = getSalesByCustomer($conn, $customerId, $startDate, $endDate);
                        break;
                    case 'salesByProduct':
                        $reportData = getSalesByProduct($conn, $productId, $startDate, $endDate);
                        break;
                    case 'profitAndLoss':
                        $reportData = getprofitAndLoss($conn, $productId, $startDate, $endDate);
                        break;
                    // ... other cases for different report types
                }

                if (!empty($reportData)) {
                    echo "<table class='table table-striped'>";
                    echo "<thead>
                            <tr>
                                <th>Customer/Product Name</th>
                                <th>Sale Date</th>
                                <th>Product/Customer Name</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Amount</th>
                            </tr>
                          </thead>";
                    echo "<tbody>";
                    foreach ($reportData as $row) {
                        echo "<tr>";
                        echo "<td>" . ($row['CustomerName'] ?? $row['ProductName']) . "</td>";
                        echo "<td>" . $row['SaleDate'] . "</td>";
                        echo "<td>" . ($row['ProductName'] ?? $row['CustomerName']) . "</td>";
                        echo "<td>" . $row['Quantity'] . "</td>";
                        echo "<td>" . $row['UnitPrice'] . "</td>";
                        echo "<td>" . $row['Amount'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>No data available for the selected criteria.</p>";
                }
            }
            ?>

            <!-- Chart Display -->
            <h2>Chart</h2>
            <canvas id="reportChart"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var ctx = document.getElementById('reportChart').getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($reportData, 'SaleDate')); ?>,
                            datasets: [{
                                label: 'Amount',
                                data: <?php echo json_encode(array_column($reportData, 'Amount')); ?>,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
            </script>

            <!-- Export Options -->
            <h2>Export Options</h2>
            <form method="GET" action="report1.php">
                <input type="hidden" name="reportType" value="<?php echo $reportType; ?>">
                <input type="hidden" name="customerId" value="<?php echo $customerId; ?>">
                <input type="hidden" name="productId" value="<?php echo $productId; ?>">
                <input type="hidden" name="startDate" value="<?php echo $startDate; ?>">
                <input type="hidden" name="endDate" value="<?php echo $endDate; ?>">
                <button type="submit" name="export" value="csv" class="btn btn-success">Export to CSV</button>
                <!-- <button type="submit" name="export" value="pdf" class="btn btn-danger">Export to PDF</button> -->
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1/daterangepicker.min.js"></script>
<script>
    $(function() {
        $('input[name="daterange"]').daterangepicker({
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD'
            },
            startDate: '<?php echo $startDate; ?>',
            endDate: '<?php echo $endDate; ?>'
        });
    });
</script>
</body>
</html>
