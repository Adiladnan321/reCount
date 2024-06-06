<?php
// Start the session
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
}
require_once 'database.php';
// Check if the session variable 'Pr' is set
if (isset($_SESSION['cust'])) {
    $u = $_SESSION['cust'];
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
    <button class="btn btn-outline-secondary" onclick="window.location.href='./customer.php'"><</button>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'">🏠</button>
    <button class="btn btn-outline-secondary" onclick="location.reload();">&#10227;</button>
    <br><p></p>
    <h1>Customer</h1>
    <?php

    // Retrieve inventory data from the database
    $sql = "SELECT * FROM customer WHERE CustomerID='$u'";
    $result = $conn->query($sql);

    echo '<br>';

    // Display inventory in a table
    echo '<table class="table table-striped table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th scope="col">Customer Id</th>';
    echo '<th scope="col">Customer Name</th>';
    echo '<th scope="col">Origin</th>';
    echo '<th scope="col">Email</th>';
    echo '<th scope="col">Phone Number</th>';
    // echo '<th scope="col">Due</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $row['CustomerID'] . '</td>';
        echo '<td>' . $row['CustomerName'] . '</td>';
        echo '<td>' . $row['Origin'] . '</td>';
        echo '<td>' . $row['Email'] . '</td>';
        echo '<td>' . $row['PhoneNumber'] . '</td>';
        // echo '<td>' . $row['Due'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '<br><br>';


    echo '<h4>History</h4><p></p>';
    $sql5 = "SELECT * FROM credit WHERE CustomerID='$u'";
    $result5 = mysqli_query($conn, $sql5);

    // Display purchase information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th>CreditID</th>';
    echo '<th>Customer Id</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Amount Paid</th>';
    echo '<th>Paid on</th>';
    echo '<th>Due</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row5 = mysqli_fetch_assoc($result5)) {
        echo '<tr>';
        echo '<td>' . $row5['CreditID'] . '</td>';
        echo '<td>' . $row5['CustomerID'] . '</td>';
        echo '<td>' . $row5['CustomerName'] . '</td>';
        echo '<td>' . $row5['AmountPaid'] . '</td>';
        echo '<td>' . $row5['PaidDate'] .'</td>';
        echo '<td>' . $row5['Due'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '<br><br>';



    mysqli_close($conn);
    // $conn->close();
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>

