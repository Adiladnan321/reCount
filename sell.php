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
    <title>Sell</title>
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
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1>Sell</h1>
    <?php
    // Database connection
    // require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitButton'])) {
    // Retrieving form data
        $productId = $_POST['ProductID'];
        $productName = $_POST['ProductName'];
        $CustomerID = $_POST['CustomerID'];
        $quantity = $_POST['Quantity'];
        $newUnitPrice = $_POST['UnitPrice'];
        $SaleDate = $_POST['SaleDate'];
        $Description=$_POST['Description'];
        
        // Calculate total amount
        $amount = $newUnitPrice * $quantity;
        
        // SQL to check if product exists in inventory
        $sql_check = "SELECT * FROM inventory WHERE ProductID = '$productId'";
        $result_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            // Product exists, update inventory
            $row = mysqli_fetch_assoc($result_check);
            $newQuantity = $row['Quantity'] - $quantity;
            $unitPrice=$row['UnitPrice'];
            $newAmount = $newQuantity * $unitPrice;
            if($newQuantity<0){
                echo '<div class="alert alert-danger" role="alert">Not enough Quantity!</div>';
            }
            else{
                $sql_inventory = "UPDATE inventory SET Quantity = '$newQuantity', Amount = '$newAmount' WHERE ProductID = '$productId'";
                $sql_purchase = "INSERT INTO sale (ProductID, ProductName, CustomerID, Description, Quantity, UnitPrice, Amount, SaleDate) VALUES ('$productId', '$productName', '$CustomerID', '$Description', '$quantity', '$newUnitPrice', '$amount', '$SaleDate')";
                mysqli_query($conn, $sql_inventory);
                mysqli_query($conn, $sql_purchase);
            }
        } else {
            // Product does not exist, insert into inventory
            echo '<div class="alert alert-danger" role="alert">Product Doesnot exists!</div>';
        }
        header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
        exit();
        // Insert into purchase table
        
        // Execute queries
        
        // Close connection
        // mysqli_close($conn);
    }
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['deleteButton'])){
        $Sno=intval($_POST['Sno']);
        $ProductID=intval($_POST['Quantity']);
        $Quantity=intval($_POST['Quantity']);
        $Amount=floatval($_POST['Amount']);

        // Fetch current quantity and amount from inventory
        $stmt_get_inventory = $conn->prepare("SELECT Quantity, UnitPrice FROM inventory WHERE ProductID=?");
        $stmt_get_inventory->bind_param("i", $ProductID);
        $stmt_get_inventory->execute();
        $result_inventory = $stmt_get_inventory->get_result();
        $inventory_data = $result_inventory->fetch_assoc();

        $newQuantity = $inventory_data['Quantity'] + $Quantity;
        $newAmount = $newQuantity * $inventory_data['UnitPrice'];

        $stmt_delete = $conn->prepare("DELETE FROM sale WHERE Sno=?");
        $stmt_delete->bind_param("i", $Sno);

        $stmt_update_inventory = $conn->prepare("UPDATE inventory SET Quantity=?, Amount=? WHERE ProductID=?");
        $stmt_update_inventory->bind_param("idi", $newQuantity, $newAmount, $ProductID);
        
        if ($stmt_delete->execute() && $stmt_update_inventory->execute()) {
            echo '<div class="alert alert-success" role="alert">Sell deleted successfully!</div>';
            header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
            exit();
        } else {
            echo '<div class="alert alert-danger" role="alert">Error deleting purchase!</div>';
        }
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="sell.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>ProductID</th>
                    <th>Product Name</th>
                    <th>Customer Id</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <!-- Product ID -->
                        <input type="text" class="form-control" name="ProductID" placeholder="Product Id" list="ProductID" required onchange="updateProductName(this)">
                        <datalist id="ProductID">
                        <?php
                                $sql_data="SELECT * FROM inventory";
                                $result_data=mysqli_query($conn,$sql_data);
                                while($row=mysqli_fetch_assoc($result_data)){
                                    echo "<option value='".$row['ProductID']."'>".$row['ProductName']."</option>";
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
                                $sql_data = "SELECT * FROM customer";
                                $result_data = mysqli_query($conn,$sql_data);
                                while($row = mysqli_fetch_assoc($result_data)){
                                    echo "<option value='".$row['CustomerID']."'>".$row['CustomerName']."</option>";
                                }
                            ?>
                        </datalist>
                    </td>
                    <td>
                        <!-- Description -->
                        <textarea type="text" class="form-control" name="Description" placeholder="Description" required></textarea>
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
            <button type="submit" class="btn btn-primary" name="submitButton">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>Sale History</h1>
        <?php
            // Retrieve student data from the database
            $sql = "SELECT * FROM sale";
            $result = mysqli_query($conn, $sql);

            // Display student information in a table
            echo '<table class="table table-hover">';
            echo '<thead>';
            echo '<tr class="table-light">';
            echo '<th>S.NO</th>';
            echo '<th>Product Id</th>';
            echo '<th>Product name</th>';
            echo '<th>Customer Id</th>';
            echo '<th>Desc</th>';
            echo '<th>QTY</th>';
            echo '<th>Unit Price</th>';
            echo '<th>Amt</th>';
            echo '<th>Sale Date</th>';
            echo '<th></th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<form action="sell.php" method="POST" onsubmit="return confirmSubmission()">';
                echo '<td><input type="hidden" value="' . $row['Sno'] . '" name="Sno">'. $row['Sno'] . '</td>';
                echo '<td><input type="hidden" value="' . $row['ProductID'] . '" name="ProductID">' . $row['ProductID'] . '</td>';
                echo '<td><input type="hidden" value="' . $row['ProductName'] . '" name="ProductName">' . $row['ProductName'] . '</td>';
                echo '<td><input type="hidden" value="' . $row['CustomerID'] . '" name="CustomerID">'. $row['CustomerID'] . '</td>';
                echo '<td><input type="hidden" value="' . $row['Description'] . '" name="Description">' . $row['Description'] . '</td>';
                echo '<td><input type="hidden" value="' . $row['Quantity'] . '" name="Quantity">' . number_format($row['Quantity']) . '</td>';
                echo '<td><input type="hidden" value="' . $row['UnitPrice'] . '" name="UnitPrice">' . number_format($row['UnitPrice']) . '</td>';
                echo '<td><input type="hidden" value="' . $row['Amount'] . '" name="Amount">' . number_format($row['Amount']) . '</td>';
                echo '<td>' . $row['SaleDate'] . '</td>';
                echo '<td><button type="submit" name="deleteButton" class="btn border-0">🗑️</button></td>';
                echo '</form>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

            ?>
</div>
<script>
    const productData = <?php
            $products=[];
            $sql_data="SELECT * FROM inventory";
            $result_data=mysqli_query($conn,$sql_data);
            while($row=mysqli_fetch_assoc($result_data)){
                $products[$row['ProductID']]=$row['ProductName'];
            }
            echo json_encode($products);
            mysqli_close($conn);
            ?>;
    // console.log(productData);
function confirmSubmission() {
    return confirm("Are you sure!!!");
        }

    function updateProductName(element) {
        const productId = element.value;
        const productName = productData[productId] || "";
        const row = element.closest("tr");
        const productNameField = row.querySelector('input[name="ProductName"]');
        productNameField.value = productName;
    }
</script>
</body>
</html>