<?php
session_start(); 
if (!isset($_SESSION["user"])) { 
    header("Location: login.php");
    exit(); 
}

require_once '../database.php'; 

if (!isset($_GET['poid'])) { 
    echo "<div class='alert alert-danger'>No Purchase Order ID specified.</div>"; 
    exit();
}

$poId = intval($_GET['poid']); 

// Fetch Purchase Order Details (with Supplier Name) 
$sql_po = "SELECT po.*, s.SupplierName 
           FROM purchase_order po
           INNER JOIN supplier s ON po.SupplierID = s.SupplierID
           WHERE po.POID = ?"; 
$stmt_po = $conn->prepare($sql_po); 
$stmt_po->bind_param("i", $poId); 
$stmt_po->execute();
$poData = $stmt_po->get_result()->fetch_assoc(); 

// Fetch Items for the selected Purchase Order
$sql_items = "SELECT poi.*, i.ProductName
              FROM purchase_order_items poi
              INNER JOIN inventory i ON poi.ProductID = i.ProductID 
              WHERE poi.POID = ?";
$stmt_items = $conn->prepare($sql_items); 
$stmt_items->bind_param("i", $poId);
$stmt_items->execute(); 
$poItems = $stmt_items->get_result();
?> 
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> 
</head>
<body>
    <div class="container mt-4"> 
        <button class="btn btn-outline-secondary" onclick="window.location.href='index.php'">
            < Back to Purchase Orders</button><br><br>
        <h2>Purchase Order Details</h2>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">PO # <?php echo $poData['POID']; ?></h5>
                <p class="card-text">
                    <strong>Supplier:</strong> <?php echo $poData['SupplierName']; ?> 
                </p> 
                <p class="card-text">
                    <strong>Order Date:</strong> <?php echo $poData['OrderDate']; ?>
                </p> 
                <p class="card-text">
                    <strong>Total Amount:</strong> QR <?php echo number_format($poData['TotalAmount'], 2); ?> 
                </p> 
                <p class="card-text"> 
                    <strong>Status:</strong> 
                    <?php
                    switch ($poData['Status']) {
                        case 'Pending': 
                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                            break; 
                        case 'Partially Received':
                            echo '<span class="badge bg-info text-dark">Partially Received</span>'; 
                            break;
                        case 'Received': 
                            echo '<span class="badge bg-success">Received</span>';
                            break;
                        case 'Cancelled':
                            echo '<span class="badge bg-danger">Cancelled</span>';
                            break; 
                        default:
                            echo '<span class="badge bg-secondary">Unknown</span>'; 
                            }
                    ?>
                </p> 
            </div> 
        </div>

        <h4>Ordered Items</h4>
        <table class="table table-striped">
            <thead> 
                <tr>
                    <th>Product ID</th> 
                    <th>Product Name</th>
                    <th>Quantity</th> 
                    <th>Unit Price</th> 
                    <th>Total</th>
                </tr>
            </thead>
            <tbody> 
                <?php 
                    $calculatedTotal = 0; // To independently calculate total and compare 
                    while ($item = $poItems->fetch_assoc()) :
                    $itemTotal = $item['Quantity'] * $item['UnitPrice']; 
                    $calculatedTotal += $itemTotal; 
                ?> 
                    <tr>
                        <td><?php echo $item['ProductID']; ?></td> 
                        <td><?php echo $item['ProductName']; ?></td> 
                        <td><?php echo $item['Quantity']; ?></td>
                        <td>QR <?php echo number_format($item['UnitPrice'], 2); ?></td> 
                        <td>QR <?php echo number_format($itemTotal, 2); ?></td> 
                    </tr> 
                <?php endwhile; ?>
                <!-- Row to display the calculated total --> 
                <tr>
                    <td colspan="4" class="text-end"><strong>Calculated Total:</strong></td> 
                    <td><strong>QR <?php echo number_format($calculatedTotal, 2); ?></strong></td> 
                </tr>
            </tbody> 
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></script>
</body>
</html>