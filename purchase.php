<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Purchase</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1>Purchase</h1>
    <?php
    // Database connection
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'recount';
    $conn = mysqli_connect($host, $username, $password, $dbname);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieving form data
        $productId = $_POST['ProductID'];
        $productName = $_POST['ProductName'];
        $supplierId = $_POST['SupplierID'];
        $quantity = $_POST['Quantity'];
        $unitPrice = $_POST['UnitPrice'];
        $purchaseDate = $_POST['PurchaseDate'];
        
        // Calculate total amount
        $amount = $unitPrice * $quantity;
        
        // SQL to check if product exists in inventory
        $sql_check = "SELECT * FROM inventory WHERE ProductID = '$productId'";
        $result_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            // Product exists, update inventory
            $row = mysqli_fetch_assoc($result_check);
            $newQuantity = $row['Quantity'] + $quantity;
            // $newUnitPrice = ($row['UnitPrice'] + $unitPrice) / 2; // Assuming average unit price
            $newAmount = $newQuantity * $unitPrice;
            $sql_inventory = "UPDATE inventory SET Quantity = '$newQuantity', UnitPrice = '$unitPrice', Amount = '$newAmount' WHERE ProductID = '$productId'";
        } else {
            // Product does not exist, insert into inventory
            $sql_inventory = "INSERT INTO inventory (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, Amount, ReorderLevel, Status) VALUES ('$productId', '$productName', '$supplierId', 'desc', '$quantity', '$unitPrice', '$amount', 10)";
        }
        
        // Insert into purchase table
        $sql_purchase = "INSERT INTO purchase (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, Amount, PurchaseDate) VALUES ('$productId', '$productName', '$supplierId', 'desc', '$quantity', '$unitPrice', '$amount', '$purchaseDate')";
        
        // Execute queries
        mysqli_query($conn, $sql_inventory);
        mysqli_query($conn, $sql_purchase);
        
        // Close connection
        // mysqli_close($conn);
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="purchase.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>ProductID</th>
                    <th>Product Name</th>
                    <th>Supplier Id</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <!-- Product ID -->
                        <input type="text" class="form-control" name="ProductID" placeholder="Product Id" list="ProductID" required>
                        <datalist id="ProductID">
                        <?php
                                $sql_data="SELECT * FROM inventory";
                                $result_data=mysqli_query($conn,$sql_data);
                                while($row=mysqli_fetch_assoc($result_data)){
                                    echo "<option value='".$row['ProductID']."'>".$row['ProductID']."</option>";
                                }
                            ?>
                        </datalist>
                    </td>
                    <td>
                        <!-- Product Name -->
                        <input type="text" class="form-control" name="ProductName" placeholder="Eg: Chalk">
                    </td>
                    <td>
                        <!-- Supplier ID -->
                        <label><input class="form-control" list="supplier" name="SupplierID" placeholder="Supplier Id"></label>
                        <datalist id="supplier">
                            <?php
                                $sql_data = "SELECT * FROM inventory";
                                $result_data = mysqli_query($conn,$sql_data);
                                while($row = mysqli_fetch_assoc($result_data)){
                                    echo "<option value='".$row['ProductID']."'>".$row['ProductID']."</option>";
                                }
                            ?>
                        </datalist>
                    </td>
                    <td>
                        <!-- Quantity -->
                        <input type="number" class="form-control" name="Quantity" placeholder="Quantity" required>
                    </td>
                    <td>
                        <!-- Unit Price -->
                        <input type="text" class="form-control" name="UnitPrice" placeholder="Unit Price">
                    </td>
                    <td>
                        <!-- Purchase Date -->
                        <input type="date" class="form-control" name="PurchaseDate">
                    </td>
                </tr>
            </tbody>
        </table>
        <div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>Purchase History</h1>
        <?php
        session_start();
// Connect to the database (update credentials)
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $dbname = 'recount';
            $conn = mysqli_connect($host, $username, $password, $dbname);

            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Retrieve student data from the database
            $sql = "SELECT * FROM purchase";
            $result = mysqli_query($conn, $sql);

            // Display student information in a table
            echo '<table class="table">';
            echo '<tr><th>S.NO</th><th>Product Id</th><th>Product name</th><th>Supplier Id</th><th>Desc</th><th>QTY</th><th>Unit Price</th><th>Amt</th><th>Purchase Date</th></tr>';

            while ($row = mysqli_fetch_assoc($result)) {
                // if($row['phone']!=0){
                echo '<tr>';
                echo '<td>' . $row['Sno'] . '</td>';
                echo '<td>' . $row['ProductID'] . '</td>';
                echo '<td>' . $row['ProductName'] . '</td>';
                echo '<td>' . $row['SupplierID'] . '</td>';
                echo '<td>' . $row['Description'] . '</td>';
                echo '<td>' . $row['Quantity'] . '</td>';
                echo '<td>' . $row['UnitPrice'] . '</td>';
                echo '<td>' . $row['Amount'] . '</td>';
                echo '<td>' . $row['PurchaseDate'] . '</td>';
                echo '</tr>';
            // }
            }
            echo '</table>';

            mysqli_close($conn);
        ?>
</div>
</body>
</html>