<?php 
    session_start();
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
    require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['viewButton'])) {
        session_start();
        $InvoiceID = $_POST['InvoiceID'];
        $_SESSION['Inv']=$InvoiceID;
        header("location:invoiceView.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Invoice History</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./invoice.php'"><</button>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'">🏠</button>
    <button class="btn btn-outline-secondary" onclick="location.reload();">&#10227;</button>
    <br><br>
    <h1>Invoice History</h1>
    <?php


    // Retrieve inventory data from the database
    $sql = "SELECT * FROM invoice";
    $result = $conn->query($sql);

    // Display inventory in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th scope="col">Invoice Id</th>';
    echo '<th scope="col">Customer Id</th>';
    echo '<th scope="col">Invoice Date</th>';
    echo '<th scope="col">Amount</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<form action="invoiceHistory.php" method="POST">';
        echo '<td><button name="viewButton" class="btn border-0"><input type="hidden" value="' . $row['InvoiceID'] . '" name="InvoiceID">' . $row['InvoiceID'] . '</button></td>';
        echo '<td>' . $row['CustomerID'] . '</td>';
        echo '<td>' . $row['InvoiceDate'] . '</td>';
        echo '<td>' . number_format($row['Amount']) . '</td>';
        echo '</form>';
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
