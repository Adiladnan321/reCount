<?php 
    // session_start();
    require_once 'database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Inventory</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <button class="btn btn-outline-secondary" onclick="location.reload();">&#10227;</button>
    <br>
    <h1>Inventory</h1>
    <?php
    // session_start();
    // require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $reorder = $_POST['reorder'];
        $ProductID = $_POST['ProductID'];

        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("UPDATE inventory SET ReorderLevel = ? WHERE ProductID = ?");
        $stmt->bind_param("ii", $reorder, $ProductID);
        $stmt->execute();
        $stmt->close();
    }

    // Retrieve inventory data from the database
    $sql = "SELECT * FROM inventory";
    $result = $conn->query($sql);

    // Calculate total inventory value
    $sql_totalVal = "SELECT SUM(Amount) AS TotalValue FROM inventory";
    $resultVal = $conn->query($sql_totalVal);
    $totalVal = $resultVal->fetch_assoc();
    // Display total inventory value
    echo '<br>';
    echo '<h4><strong>Total Inventory Value: </strong>' . $totalVal['TotalValue'] . '</h4><br><br>';

    // Display inventory in a table
    echo '<table class="table table-striped table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th scope="col">Product Id</th>';
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
        echo '<tr>';
        echo '<form action="inventory.php" method="POST">';
        echo '<td><input type="hidden" value="' . $row['ProductID'] . '" name="ProductID">' . $row['ProductID'] . '</td>';
        echo '<td>' . $row['ProductName'] . '</td>';
        echo '<td>' . $row['SupplierID'] . '</td>';
        echo '<td>' . $row['Description'] . '</td>';
        echo '<td>' . $row['Quantity'] . '</td>';
        echo '<td>' . $row['UnitPrice'] . '</td>';
        echo '<td>' . $row['Amount'] . '</td>';
        echo '<td><input type="number" value="' . $row['ReorderLevel'] . '" name="reorder" class="form-control"></td>';
        echo '<td><button class="btn btn-outline-primary">✔️</button></td>';
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