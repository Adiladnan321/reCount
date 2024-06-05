<?php 
    // session_start();
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
    <style>
        .form-search{
            border: 1px solid grey;
            width: 30%;
            display: flex;
            gap: 10px;
            border-radius: 10px;
        }
        @media (max-width:500px) {
            .mobile-card{
                width: 100%;
            }
        }
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
    <button class="btn btn-outline-secondary" onclick="window.location.href='./invoice.php'"><</button>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'">🏠</button>
    <button class="btn btn-outline-secondary" onclick="location.reload();">&#10227;</button>
    <br><br>
    <h1>Invoice History</h1>
    <form class="form-search mobile-card" action="invoiceHistory.php" method="POST">
        <input type="text" name="CustomerID"class="form-control border-0" style="margin: 2px;" placeholder="Search CustomerId">
        <input type="date" name="InvoiceDate"class="form-control border-0" style="margin: 2px;">
        <button type="submit" name="searchButton" class="btn-lg border-0" style="margin: 2px;border-radius:20px">🔍</button>
        <button type="submit" name="clearButton" class="btn-lg border-0" style="margin: 2px;border-radius:20px">clear</button>
    </form><br>
    <?php


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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['searchButton'])) {
    $CustomerID=$_POST['CustomerID'];
    $InvoiceDate=$_POST['InvoiceDate'];
    $sql = "SELECT * FROM invoice where CustomerID = '$CustomerID' OR InvoiceDate = '$InvoiceDate'";
    $result = $conn->query($sql);
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
}
else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clearButton'])){
    $sql = "SELECT * FROM invoice";
    $result = $conn->query($sql);
    // Retrieve inventory data from the database
    $sql = "SELECT * FROM invoice";
    $result = $conn->query($sql);
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
}
else {
    $sql = "SELECT * FROM invoice";
    $result = $conn->query($sql);
    // Retrieve inventory data from the database
    $sql = "SELECT * FROM invoice";
    $result = $conn->query($sql);
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
}

    echo '</tbody>';
    echo '</table>';
    $conn->close();
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>
