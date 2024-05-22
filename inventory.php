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
        session_start();
// Connect to the database (update credentials)
            // $host = 'localhost';
            // $username = 'root';
            // $password = '';
            // $dbname = 'recount';
            // $conn = mysqli_connect($host, $username, $password, $dbname);

            // if (!$conn) {
            //     die("Connection failed: " . mysqli_connect_error());
            // }
            require_once 'database.php';
            // Retrieve student data from the database
            $sql = "SELECT * FROM inventory";
            $result = mysqli_query($conn, $sql);

            // Display student information in a table
            echo '<table class="table">';
            echo '<tr><th>Product Id</th><th>Product name</th><th>Supplier Id</th><th>Desc</th><th>QTY</th><th>Unit Price</th><th>Amt</th><th>Reoder</th><th>Status</th></tr>';

            while ($row = mysqli_fetch_assoc($result)) {
                // if($row['phone']!=0){
                echo '<tr>';
                // echo '<td>' . $row['Sno'] . '</td>';
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
            // }
            }
            echo '</table>';

            mysqli_close($conn);
        ?>
</div>
</body>
</html>