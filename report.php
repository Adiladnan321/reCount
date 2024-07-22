<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}
require_once 'database.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Reports</title>
    <style>
        @media (min-width:1000px) {
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 5vh;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .ff {
                background-color: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
<div class="container ff">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1>Reports</h1>

    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab" aria-controls="customer" aria-selected="true">Customer Reports</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="product-tab" data-bs-toggle="tab" data-bs-target="#product" type="button" role="tab" aria-controls="product" aria-selected="false">Product Reports</button>
        </li>
    </ul>

    <div class="tab-content" id="reportTabsContent">
        <div class="tab-pane fade show active" id="customer" role="tabpanel" aria-labelledby="customer-tab">
            <h2>Customer Reports</h2>
            <form action="reports.php" method="POST" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <label for="customerSearch" class="form-label">Search Customer:</label>
                        <input type="text" class="form-control" id="customerSearch" name="customerSearch" placeholder="Enter Customer ID or Name">
                    </div>
                    <div class="col-md-2">
                        <label for="dateFrom" class="form-label">Date From:</label>
                        <input type="date" class="form-control" id="dateFrom" name="dateFrom">
                    </div>
                    <div class="col-md-2">
                        <label for="dateTo" class="form-label">Date To:</label>
                        <input type="date" class="form-control" id="dateTo" name="dateTo">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary" name="customerReport">Generate Report</button>
                    </div>
                </div>
            </form>
            
            <?php
                if (isset($_POST['customerReport'])) {
                    displayCustomerReport($conn);
                }
            ?>
        </div>

        <div class="tab-pane fade" id="product" role="tabpanel" aria-labelledby="product-tab">
            <h2>Product Reports</h2>
            <form action="reports.php" method="POST" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <label for="productSearch" class="form-label">Search Product:</label>
                        <input type="text" class="form-control" id="productSearch" name="productSearch" placeholder="Enter Product ID or Name">
                    </div>
                    <div class="col-md-2">
                        <label for="dateFromP" class="form-label">Date From:</label>
                        <input type="date" class="form-control" id="dateFromP" name="dateFromP">
                    </div>
                    <div class="col-md-2">
                        <label for="dateToP" class="form-label">Date To:</label>
                        <input type="date" class="form-control" id="dateToP" name="dateToP">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary" name="productReport">Generate Report</button>
                    </div>
                </div>
            </form>

            <?php
                if (isset($_POST['productReport'])) {
                    displayProductReport($conn);
                }
            ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>

<?php

function displayCustomerReport($conn) {
    $search = isset($_POST['customerSearch']) ? sanitizeInput($_POST['customerSearch']) : '';
    $dateFrom = isset($_POST['dateFrom']) ? $_POST['dateFrom'] : '';
    $dateTo = isset($_POST['dateTo']) ? $_POST['dateTo'] : '';

    // Basic SQL query (you'll need to modify this based on your exact reporting needs)
    $sql = "SELECT 
                c.CustomerID, 
                c.CustomerName, 
                COUNT(s.Sno) AS TotalPurchases, 
                SUM(s.Amount) AS TotalSpent,
                c.Due AS OutstandingDue
            FROM 
                customer c
            LEFT JOIN 
                sale s ON c.CustomerID = s.CustomerID";

    // Dynamically add WHERE clauses for search and date filtering
    $whereClauses = [];
    if (!empty($search)) {
        $whereClauses[] = "(c.CustomerID LIKE '%$search%' OR c.CustomerName LIKE '%$search%')";
    }
    if (!empty($dateFrom)) {
        $whereClauses[] = "s.SaleDate >= '$dateFrom'";
    }
    if (!empty($dateTo)) {
        $whereClauses[] = "s.SaleDate <= '$dateTo'";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " GROUP BY c.CustomerID, c.CustomerName
              ORDER BY TotalSpent DESC"; 

    $result = $conn->query($sql);

    // Display results in a table
    if ($result->num_rows > 0) {
        echo '<table class="table table-hover">';
        echo '<thead>';
        echo '<tr class="table-light">';
        echo '<th>Customer ID</th>';
        echo '<th>Customer Name</th>';
        echo '<th>Total Purchases</th>';
        echo '<th>Total Spent (QR)</th>';
        echo '<th>Outstanding Due (QR)</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['CustomerID'] . '</td>';
            echo '<td>' . $row['CustomerName'] . '</td>';
            echo '<td>' . $row['TotalPurchases'] . '</td>';
            echo '<td>' . number_format($row['TotalSpent'], 2) . '</td>';
            echo '<td>' . number_format($row['OutstandingDue'], 2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="alert alert-info">No matching customer records found.</div>';
    }
}

function displayProductReport($conn) {
    $search = isset($_POST['productSearch']) ? sanitizeInput($_POST['productSearch']) : '';
    $dateFrom = isset($_POST['dateFromP']) ? $_POST['dateFromP'] : '';
    $dateTo = isset($_POST['dateToP']) ? $_POST['dateToP'] : '';

    // Basic SQL query 
    $sql = "SELECT 
                i.ProductID, 
                i.ProductName, 
                SUM(s.Quantity) AS TotalSold, 
                SUM(s.Amount) AS TotalRevenue,
                i.Quantity AS CurrentStock
            FROM 
                inventory i
            LEFT JOIN 
                sale s ON i.ProductID = s.ProductID"; 

    $whereClauses = [];
    if (!empty($search)) {
        $whereClauses[] = "(i.ProductID LIKE '%$search%' OR i.ProductName LIKE '%$search%')";
    }
    if (!empty($dateFrom)) {
        $whereClauses[] = "s.SaleDate >= '$dateFrom'";
    }
    if (!empty($dateTo)) {
        $whereClauses[] = "s.SaleDate <= '$dateTo'";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " GROUP BY i.ProductID, i.ProductName
              ORDER BY TotalSold DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<table class="table table-hover">';
        echo '<thead>';
        echo '<tr class="table-light">';
        echo '<th>Product ID</th>';
        echo '<th>Product Name</th>';
        echo '<th>Total Units Sold</th>';
        echo '<th>Total Revenue (QR)</th>';
        echo '<th>Current Stock</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['ProductID'] . '</td>';
            echo '<td>' . $row['ProductName'] . '</td>';
            echo '<td>' . $row['TotalSold'] . '</td>';
            echo '<td>' . number_format($row['TotalRevenue'], 2) . '</td>'; 
            echo '<td>' . $row['CurrentStock'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="alert alert-info">No matching product records found.</div>';
    }
}


function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>