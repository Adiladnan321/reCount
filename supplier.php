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
    <title>Supplier</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1>Supplier</h1>
    <?php
    // Database connection
    // require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieving form data
        $SupplierID = $_POST['SupplierID'];
        $SupplierName = $_POST['SupplierName'];
        $Origin = $_POST['Origin'];
        $Email = $_POST['Email'];
        $PhoneNumber = $_POST['PhoneNumber'];
        $Due = $_POST['Due'];
        
        
        // SQL to check if product exists in inventory
        $sql_check = "SELECT * FROM supplier WHERE SupplierID = '$SupplierID'";
        $result_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            echo '<div class="alert alert-danger" role="alert">Supplier Id already exists!</div>';
            // Product exists, update inventory
            // $row = mysqli_fetch_assoc($result_check);
            // $newQuantity = $row['Quantity'] + $quantity;
            // // $newUnitPrice = ($row['UnitPrice'] + $unitPrice) / 2; // Assuming average unit price
            // $newAmount = $newQuantity * $unitPrice;
            // $sql_inventory = "UPDATE inventory SET Quantity = '$newQuantity', UnitPrice = '$unitPrice', Amount = '$newAmount' WHERE ProductID = '$productId'";
        } else {
            // Product does not exist, insert into inventory
            $sql_supplier = "INSERT INTO supplier (SupplierID, SupplierName, Origin, Email, PhoneNumber, Due) VALUES ('$SupplierID', '$SupplierName', '$Origin', 'Email', '$PhoneNumber', '$Due')";
            mysqli_query($conn, $sql_supplier);
        }
        
        // Execute queries
        
        // Close connection
        // mysqli_close($conn);
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="supplier.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>Supplier Id</th>
                    <th>Supplier Name</th>
                    <th>Origin</th>
                    <th>email</th>
                    <th>Phone Number</th>
                    <th>Due (if any)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <td>
                        <!-- Supplier Id -->
                        <input type="number" class="form-control" name="SupplierID" placeholder="Supplier Id">
                    </td>
                    <td>
                        <!-- Supplier Name -->
                        <input type="text" class="form-control" name="SupplierName" placeholder="Eg: Khalid">
                    </td>
                    <td>
                        <!-- Origin -->
                        <input type="text" class="form-control" name="Origin" placeholder="Eg: India">
                    </td>
                    <td>
                        <!-- email -->
                        <input type="email" class="form-control" name="Email" placeholder="abc@example.com" required>
                    </td>
                    <td>
                        <!-- Phone Number -->
                        <input type="tel" class="form-control" name="PhoneNumber" placeholder="PhoneNumber">
                    </td>
                    <td>
                        <!-- Due -->
                        <input type="number" class="form-control" name="Due" placeholder="Due"> 
                    </td>
                </tr>
            </tbody>
        </table>
        <div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>Supplier Table</h1>
        <?php
        // session_start();
// Connect to the database (update credentials)
            // require_once 'database.php';
            // Retrieve student data from the database
            $sql = "SELECT * FROM supplier";
            $result = mysqli_query($conn, $sql);

            // Display student information in a table
            echo '<table class="table">';
            echo '<tr><th>Supplier Id</th><th>Supplier Name</th><th>Origin</th><th>email</th><th>Phone Number</th><th>Due (if any)</th></tr>';

            while ($row = mysqli_fetch_assoc($result)) {
                // if($row['phone']!=0){
                echo '<tr>';
                // echo '<td>' . $row['Sno'] . '</td>';
                echo '<td>' . $row['SupplierID'] . '</td>';
                echo '<td>' . $row['SupplierName'] . '</td>';
                echo '<td>' . $row['Origin'] . '</td>';
                echo '<td>' . $row['Email'] . '</td>';
                echo '<td>' . $row['PhoneNumber'] . '</td>';
                echo '<td>' . $row['Due'] . '</td>';
                echo '</tr>';
            // }
            }
            echo '</table>';

            mysqli_close($conn);
        ?>
</div>
</body>
</html>