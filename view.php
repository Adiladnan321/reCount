<?php
// Start the session
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
}
require_once 'database.php';
// Check if the session variable 'Pr' is set
if (isset($_SESSION['Pr'])) {
    $u = $_SESSION['Pr'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Inventory</title>
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
        .ff{
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
    <button class="btn btn-outline-secondary" onclick="window.location.href='./inventory.php'"><</button>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'">🏠</button>
    <button class="btn btn-outline-secondary" onclick="location.reload();">&#10227;</button>
    <br><p></p>
    <h1>Product view</h1>
    <?php

    // Retrieve inventory data from the database
    $sql = "SELECT * FROM inventory WHERE ProductID='$u'";
    $result = $conn->query($sql);

    echo '<br>';

    // Display inventory in a table
    echo '<table class="table table-striped table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th scope="col">Product Id</th>';
    echo '<th scope="col">Product Name</th>';
    echo '<th scope="col">Supplier Id</th>';
    echo '<th scope="col">Description</th>';
    echo '<th scope="col">Quantity</th>';
    echo '<th scope="col">Unit Price</th>';
    echo '<th scope="col">Amount</th>';
    echo '<th scope="col">Reorder Level</th>';
    echo '<th scope="col">Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $row['ProductID'] . '</td>';
        echo '<td>' . $row['ProductName'] . '</td>';
        echo '<td>' . $row['SupplierID'] . '</td>';
        echo '<td>' . $row['Description'] . '</td>';
        echo '<td>' . $row['Quantity'] . '</td>';
        echo '<td>' . $row['UnitPrice'] . '</td>';
        echo '<td>' . $row['Amount'] . '</td>';
        echo '<td>' . $row['ReorderLevel'] . '</td>';
        echo '<td>' . $row['Status'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '<br><br>';


    echo '<h4>Purchase History</h4><p></p>';
    $sql5 = "SELECT * FROM purchase WHERE ProductID='$u'";
    $result5 = mysqli_query($conn, $sql5);

    // Display purchase information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th>S.NO</th>';
    echo '<th>Product Id</th>';
    echo '<th>Product Name</th>';
    echo '<th>Supplier Id</th>';
    echo '<th>Description</th>';
    echo '<th>Quantity</th>';
    echo '<th>Unit Price</th>';
    echo '<th>Amount</th>';
    echo '<th>Purchase Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row5 = mysqli_fetch_assoc($result5)) {
        echo '<tr>';
        echo '<td>' . $row5['Sno'] . '</td>';
        echo '<td>' . $row5['ProductID'] . '</td>';
        echo '<td>' . $row5['ProductName'] . '</td>';
        echo '<td>' . $row5['SupplierID'] . '</td>';
        echo '<td>' . $row5['Description'] .'</td>';
        echo '<td>' . $row5['Quantity'] . '</td>';
        echo '<td>' . $row5['UnitPrice'] . '</td>';
        echo '<td>' . $row5['Amount'] . '</td>';
        echo '<td>' . $row5['PurchaseDate'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '<br><br>';

    echo '<h4>Sale History</h4><p></p>';
    $sql6 = "SELECT * FROM sale WHERE ProductID='$u'";
    $result6 = mysqli_query($conn, $sql6);

    // Display purchase information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th>S.NO</th>';
    echo '<th>Product Id</th>';
    echo '<th>Product Name</th>';
    echo '<th>Customer Id</th>';
    echo '<th>Description</th>';
    echo '<th>Quantity</th>';
    echo '<th>Unit Price</th>';
    echo '<th>Amount</th>';
    echo '<th>Sale Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row6 = mysqli_fetch_assoc($result6)) {
        echo '<tr>';
        echo '<td>' . $row6['Sno'] . '</td>';
        echo '<td>' . $row6['ProductID'] . '</td>';
        echo '<td>' . $row6['ProductName'] . '</td>';
        echo '<td>' . $row6['CustomerID'] . '</td>';
        echo '<td>' . $row6['Description'] .'</td>';
        echo '<td>' . $row6['Quantity'] . '</td>';
        echo '<td>' . $row6['UnitPrice'] . '</td>';
        echo '<td>' . $row6['Amount'] . '</td>';
        echo '<td>' . $row6['SaleDate'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    mysqli_close($conn);
    // $conn->close();
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>

