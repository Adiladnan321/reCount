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
            <form method="GET" action="reports.php"> 
                <div class="mb-3">
                    <label for="reportType" class="form-label">Report Type</label>
                    <select class="form-select" id="reportType" name="reportType">
                        <option value="salesByCustomer">Sales by Customer</option>
                        <option value="salesByProduct">Sales by Product</option>
                        <option value="profitAndLoss">Profit and Loss</option>
                        <!-- <option value="inventorySummary">Inventory Summary</option> -->
                        <!-- Add more report options -->
                    </select>
                </div>

                <div class="mb-3" id="customerFilter">
                    <label for="customerId" class="form-label">Customer</label>
                    <select class="form-select" id="customerId" name="customerId">
                        <option value="">Select Customer</option>
                        <?php
                        $customers = $conn->query("SELECT CustomerID, CustomerName FROM customer");
                        while ($row = $customers->fetch_assoc()) {
                            echo "<option value='" . $row['CustomerID'] . "'>" . $row['CustomerName'] . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3" id="productFilter">
                    <label for="productId" class="form-label">Product</label>
                    <select class="form-select" id="productId" name="productId">
                        <option value="">Select Product</option>
                        <?php
                        $products = $conn->query("SELECT ProductID, ProductName FROM inventory");
                        while ($row = $products->fetch_assoc()) {
                            echo "<option value='" . $row['ProductID'] . "'>" . $row['ProductName'] . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="text" class="form-control" id="startDate" name="startDate" value="<?php echo date('Y-m-01'); ?>">
                </div>

                <div class="mb-3">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="text" class="form-control" id="endDate" name="endDate" value="<?php echo date('Y-m-t'); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Generate Report</button>
                <button type="submit" name="export" value="csv" class="btn btn-secondary">Export to CSV</button>
            </form>
        </div>

        <!-- Report Output -->
        <div class="col-md-8">
            <h2>Report Output</h2>
            <div id="reportOutput">
                <?php
                if (isset($_GET['reportType'])) {
                    $reportType = $_GET['reportType'];
                    $customerId = isset($_GET['customerId']) ? $_GET['customerId'] : null;
                    $productId = isset($_GET['productId']) ? $_GET['productId'] : null;
                    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
                    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

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

                    if (isset($salesData)) {
                        displaySalesData($salesData);
                    }
                } 

                function displaySalesData($salesData) {
                    echo '<table class="table">';
                    echo '<thead><tr>
                            <th>Customer/Product Name</th>
                            <th>Sale Date</th>
                            <th>Product/Customer Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                        </tr></thead>';
                    echo '<tbody>';
                    foreach ($salesData as $sale) {
                        echo '<tr>';
                        echo '<td>' . ($sale['CustomerName'] ?? $sale['ProductName']) . '</td>';
                        echo '<td>' . $sale['SaleDate'] . '</td>';
                        echo '<td>' . ($sale['ProductName'] ?? $sale['CustomerName']) . '</td>';
                        echo '<td>' . $sale['Quantity'] . '</td>';
                        echo '<td>' . $sale['UnitPrice'] . '</td>';
                        echo '<td>' . $sale['Amount'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>

            <div>
                <h2>Sales Chart</h2>
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.min.js"></script>
<script>
    // JavaScript to handle filter visibility based on report type
    const reportTypeSelect = document.getElementById('reportType');
    const customerFilter = document.getElementById('customerFilter');
    const productFilter = document.getElementById('productFilter');

    function toggleFilters() {
        const selectedReport = reportTypeSelect.value;
        if (selectedReport === 'salesByCustomer') {
            customerFilter.style.display = 'block';
            productFilter.style.display = 'none';////////////////////////////////////////////////////////////////////////////////
        } else if (selectedReport === 'salesByProduct') {
            customerFilter.style.display = 'none';
            productFilter.style.display = 'block';
        } else {
            customerFilter.style.display = 'none';
            productFilter.style.display = 'none';
        }
    }

    reportTypeSelect.addEventListener('change', toggleFilters);

    // Initial call to set the correct visibility on page load
    toggleFilters();

    // Date range picker initialization
    $(function() {
        $('input[name="startDate"], input[name="endDate"]').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end, label) {
            $(this.element).val(start.format('YYYY-MM-DD'));
        });
    });

    // Chart.js initialization
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [], // Will be filled dynamically
            datasets: [{
                label: 'Sales Amount',
                data: [], // Will be filled dynamically
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
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

    <?php if (isset($salesData)): ?>
    // Populate Chart.js data
    const salesData = <?php echo json_encode($salesData); ?>;
    const labels = salesData.map(sale => sale.SaleDate);
    const data = salesData.map(sale => sale.Amount);

    salesChart.data.labels = labels;
    salesChart.data.datasets[0].data = data;
    salesChart.update();
    <?php endif; ?>
</script>
<script>
    // JavaScript to handle filter visibility based on report type
    const reportTypeSelect = document.getElementById('reportType');
    const customerFilter = document.getElementById('customerFilter');
    const productFilter = document.getElementById('productFilter');

    function toggleFilters() {
        const selectedReport = reportTypeSelect.value;
        if (selectedReport === 'salesByCustomer') {
            customerFilter.style.display = 'block';
            productFilter.style.display = 'none';
        } else if (selectedReport === 'salesByProduct') {/////////////////////////////////////////////////////////////////////////////
            customerFilter.style.display = 'none';
            productFilter.style.display = 'block';
        } else {
            customerFilter.style.display = 'none';
            productFilter.style.display = 'none';
        }
    }

    reportTypeSelect.addEventListener('change', toggleFilters);

    // Initial call to set the correct visibility on page load
    toggleFilters();

    // Date range picker initialization
    $(function() {
        $('input[name="startDate"], input[name="endDate"]').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end, label) {
            $(this.element).val(start.format('YYYY-MM-DD'));
        });
    });

    // Chart.js initialization
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [], // Will be filled dynamically
            datasets: [{
                label: 'Sales Amount',
                data: [], // Will be filled dynamically
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
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

    <?php if (isset($salesData)): ?>
    // Populate Chart.js data
    const salesData = <?php echo json_encode($salesData); ?>;
    const labels = salesData.map(sale => sale.SaleDate);
    const data = salesData.map(sale => sale.Amount);

    salesChart.data.labels = labels;
    salesChart.data.datasets[0].data = data;
    salesChart.update();
    <?php endif; ?>
</script>

</body>
</html>
