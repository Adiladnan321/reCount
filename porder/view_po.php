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

$sql_po = "SELECT po.*, s.SupplierName 
           FROM purchase_order po 
           INNER JOIN supplier s ON po.SupplierID = s.SupplierID
           WHERE po.POID = ?";
$stmt_po = $conn->prepare($sql_po);
$stmt_po->bind_param("i", $poId);
$stmt_po->execute();
$po = $stmt_po->get_result()->fetch_assoc();

$sql_items = "SELECT poi.*, i.ProductName 
              FROM purchase_order_items poi 
              INNER JOIN inventory i ON poi.ProductID = i.ProductID
              WHERE poi.POID = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $poId);
$stmt_items->execute();
$poItems = $stmt_items->get_result();

$sql_installments = "SELECT * FROM installments WHERE POID = ?";
$stmt_installments = $conn->prepare($sql_installments);
$stmt_installments->bind_param("i", $poId);
$stmt_installments->execute();
$installments = $stmt_installments->get_result();
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
        <button class="btn btn-outline-secondary" onclick="window.location.href='index.php'"><</button><br><br> 
        <h2>Purchase Order Details</h2>
        <div class="card mb-3"> 
            <div class="card-body">
                <h5 class="card-title">PO # <?php echo $po['POID']; ?></h5>
                <p class="card-text"><strong>Supplier:</strong> <?php echo $po['SupplierName']; ?></p>
                <p class="card-text"><strong>Order Date:</strong> <?php echo $po['OrderDate']; ?></p>
                <p class="card-text"><strong>Total Amount:</strong> QR <?php echo number_format($po['TotalAmount'], 2); ?></p>
                <p class="card-text"><strong>Status:</strong> 
                    <?php if ($po['Status'] === 'Paid'): ?>
                        <span class="badge bg-success">Paid</span>
                    <?php elseif ($po['Status'] === 'Partially Paid'): ?>
                        <span class="badge bg-warning text-dark">Partially Paid</span>
                    <?php else: ?> 
                        <span class="badge bg-danger">Pending</span>
                    <?php endif; ?> 
                </p> 
            </div>
        </div> 
        <h3>Ordered Items</h3>
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
                    $totalOrderAmount = 0; //Initialize for calculating from items.
                    while ($item = $poItems->fetch_assoc()): 
                    $itemTotal = $item['Quantity'] * $item['UnitPrice'];
                    $totalOrderAmount += $itemTotal; //Add to order total
                ?>
                <tr> 
                    <td><?php echo $item['ProductID']; ?></td>
                    <td><?php echo $item['ProductName']; ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td>QR <?php echo number_format($item['UnitPrice'], 2); ?></td>
                    <td>QR <?php echo number_format($itemTotal, 2); ?></td>
                </tr> 
                <?php endwhile; ?>
                <!-- Display calculated order total -->
                <tr>
                    <td colspan="4" class="text-end"><strong>Total Calculated from Items:</strong></td> 
                    <td><strong>QR <?php echo number_format($totalOrderAmount, 2); ?></strong></td>
                </tr>
            </tbody> 
        </table> 
        
        <?php if ($installments->num_rows > 0): ?> 
            <h3>Installment Details</h3>
            <a href="view_installments.php?poid=<?php echo $poId; ?>" class="btn btn-info btn-sm">View & Manage Installments</a>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>