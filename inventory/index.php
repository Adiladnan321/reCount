<?php 
    session_start();
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
    require_once '../database.php';
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editButton'])) {
        $reorder = $_POST['reorder'];
        $ProductID = $_POST['ProductID'];

        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("UPDATE inventory SET ReorderLevel = ? WHERE ProductID = ?");
        $stmt->bind_param("ii", $reorder, $ProductID);
        $stmt->execute();
        $stmt->close();
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['viewButton'])) {
        // session_start();
        $ProductID = $_POST['ProductID'];
        $_SESSION['Pr']=$ProductID;
        header("location:view.php");

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
        @media (max-width:576px){
            /* .mobile-card{
                width: 1000px;
            } */
            .ff{
                overflow: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            .fixed-column {
                position: -webkit-sticky; /* for Safari */
                position: sticky;
                left: 0;
                /* background-color: #fff; */
                z-index: 1;
            }
        }
    </style>
</head>
<body>
<div class="container ff">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button>
    <button class="btn btn-outline-secondary" onclick="location.reload();">&#10227;</button>
    <br><br>
    <h1>📦Inventory</h1>
    <?php


    // Retrieve inventory data from the database
    // Retrieve inventory data with supplier name from the database
    $sql = "SELECT i.*, s.SupplierName 
    FROM inventory i 
    JOIN supplier s ON i.SupplierID = s.SupplierID";
    $result = $conn->query($sql);

    // Calculate total inventory value
    $sql_totalVal = "SELECT SUM(Amount) AS TotalValue FROM inventory";
    $resultVal = $conn->query($sql_totalVal);
    $totalVal = $resultVal->fetch_assoc();

    // Display total inventory value
    echo '<br>';
    echo '<h4>Total Inventory Value: QR '  . number_format($totalVal['TotalValue'], 2) . '</h4><br><br>';

    // Display inventory in a table
    echo '<table class="table table-hover table-container">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th scope="col" class="fixed-column">Product Id</th>';
    echo '<th scope="col">Product Name</th>';
    echo '<th scope="col">Supplier Id</th>';
    echo '<th scope="col">Description</th>';
    echo '<th scope="col">Quantity</th>';
    echo '<th scope="col">Unit Price</th>';
    echo '<th scope="col">Amount</th>';
    echo '<th scope="col">Reorder Level</th>';
    echo '<th scope="col"></th>';
    echo '<th scope="col">Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        $productID = $row['ProductID'];
        
        // Query to get the sum of Amount from the ibatch table for the same ProductID
        $sumQuery = "SELECT SUM(Amount) AS totalAmount FROM ibatch WHERE ProductID = ?";
        $stmt = $conn->prepare($sumQuery);
        $stmt->bind_param("i", $productID);
        $stmt->execute();
        $sumResult = $stmt->get_result();
        $sumRow = $sumResult->fetch_assoc();
        $totalAmount = $sumRow['totalAmount'] ? number_format($sumRow['totalAmount'], 2) : '0.00';
        
        echo '<tr>';
        echo '<form action="index.php" method="POST">';
        echo '<td class="fixed-column"><button name="viewButton" class="btn border-0"><input type="hidden" value="' . $productID . '" name="ProductID">' . $productID . '</button></td>';
        echo '<td><button name="viewButton" class="btn border-0">' . $row['ProductName'] . '</button></td>';
        echo '<td title="' . htmlspecialchars($row['SupplierName']) . '">' . $row['SupplierID'] . '</td>';
        echo '<td>' . $row['Description'] . '</td>';
        echo '<td>' . number_format($row['Quantity'], 2) . '</td>';
        echo '<td>' . number_format($row['UnitPrice'], 2) . '</td>';
        // echo '<td>' . number_format($row['Amount'], 2) . '</td>';
        echo '<td>' . $totalAmount . '</td>'; // Display the total amount
        echo '<td><input type="number" value="' . $row['ReorderLevel'] . '" name="reorder" class="form-control"></td>';
        echo '<td><button name="editButton" class="btn btn-outline-primary">✔️</button></td>';
        echo '</form>';
        echo '<td>' . $row['Status'] . '</td>';
        echo '</tr>';
    }
    

    echo '</tbody>';
    echo '</table>';

    $conn->close();
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>
