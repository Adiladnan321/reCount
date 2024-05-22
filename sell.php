<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Sell</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1>Sell</h1>
    <?php
    // Database connection
    require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieving form data
        $productId = $_POST['ProductID'];
        $productName = $_POST['ProductName'];
        $CustomerID = $_POST['CustomerID'];
        $quantity = $_POST['Quantity'];
        $unitPrice = $_POST['UnitPrice'];
        $SaleDate = $_POST['SaleDate'];
        
        // Calculate total amount
        $amount = $unitPrice * $quantity;
        
        // SQL to check if product exists in inventory
        $sql_check = "SELECT * FROM inventory WHERE ProductID = '$productId'";
        $result_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            // Product exists, update inventory
            $row = mysqli_fetch_assoc($result_check);
            $newQuantity = $row['Quantity'] - $quantity;
            // $newUnitPrice = ($row['UnitPrice'] + $unitPrice) / 2; // Assuming average unit price
            $newAmount = $newQuantity * $unitPrice;
            if($newQuantity<0){
                echo '<div class="alert alert-danger" role="alert">Not enough Quantity!</div>';
            }
            else{
                $sql_inventory = "UPDATE inventory SET Quantity = '$newQuantity', Amount = '$newAmount' WHERE ProductID = '$productId'";
                $sql_purchase = "INSERT INTO sale (ProductID, ProductName, CustomerID, Description, Quantity, UnitPrice, Amount, SaleDate) VALUES ('$productId', '$productName', '$CustomerID', 'desc', '$quantity', '$unitPrice', '$amount', '$SaleDate')";
                mysqli_query($conn, $sql_inventory);
                mysqli_query($conn, $sql_purchase);
            }
        } else {
            // Product does not exist, insert into inventory
            echo '<div class="alert alert-danger" role="alert">Product Doesnot exists!</div>';
        }
        
        // Insert into purchase table
        
        // Execute queries
        
        // Close connection
        // mysqli_close($conn);
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="sell.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>ProductID</th>
                    <th>Product Name</th>
                    <th>Customer Id</th>
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
                        <!-- Customer ID -->
                        <label><input class="form-control" list="customer" name="CustomerID" placeholder="Customer Id"></label>
                        <datalist id="customer">
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
                        <!-- Sale Date -->
                        <input type="date" class="form-control" name="SaleDate">
                    </td>
                </tr>
            </tbody>
        </table>
        <div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>Sale History</h1>
        <?php
        session_start();
// Connect to the database (update credentials)
            require_once 'database.php';
            // Retrieve student data from the database
            $sql = "SELECT * FROM sale";
            $result = mysqli_query($conn, $sql);

            // Display student information in a table
            echo '<table class="table">';
            echo '<tr><th>S.NO</th><th>Product Id</th><th>Product name</th><th>Customer Id</th><th>Desc</th><th>QTY</th><th>Unit Price</th><th>Amt</th><th>Sale Date</th></tr>';

            while ($row = mysqli_fetch_assoc($result)) {
                // if($row['phone']!=0){
                echo '<tr>';
                echo '<td>' . $row['Sno'] . '</td>';
                echo '<td>' . $row['ProductID'] . '</td>';
                echo '<td>' . $row['ProductName'] . '</td>';
                echo '<td>' . $row['CustomerID'] . '</td>';
                echo '<td>' . $row['Description'] . '</td>';
                echo '<td>' . $row['Quantity'] . '</td>';
                echo '<td>' . $row['UnitPrice'] . '</td>';
                echo '<td>' . $row['Amount'] . '</td>';
                echo '<td>' . $row['SaleDate'] . '</td>';
                echo '</tr>';
            // }
            }
            echo '</table>';

            mysqli_close($conn);
        ?>
</div>
</body>
</html>