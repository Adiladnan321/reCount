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
    // require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitButton'])) {
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
        $stmt_check = $conn->prepare("SELECT * FROM inventory WHERE ProductID = ?");
        $stmt_check->bind_param("s", $productId);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Product exists, update inventory
            $row = $result_check->fetch_assoc();
            $newQuantity = $row['Quantity'] + $quantity;
            $newAmount = $newQuantity * $unitPrice;
            $stmt_update = $conn->prepare("UPDATE inventory SET Quantity = ?, UnitPrice = ?, Amount = ? WHERE ProductID = ?");
            $stmt_update->bind_param("idss", $newQuantity, $unitPrice, $newAmount, $productId);
            $stmt_update->execute();
            header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
            exit();
        } else {
            // Product does not exist, insert into inventory
            $stmt_insert = $conn->prepare("INSERT INTO inventory (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, Amount, ReorderLevel) VALUES (?, ?, ?, 'desc', ?, ?, ?, 10)");
            $stmt_insert->bind_param("ssiid", $productId, $productName, $supplierId, $quantity, $unitPrice, $amount);
            $stmt_insert->execute();
            header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
            exit();
        }
        
        // Insert into purchase table
        $stmt_purchase = $conn->prepare("INSERT INTO purchase (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, Amount, PurchaseDate) VALUES (?, ?, ?, 'desc', ?, ?, ?, ?)");
        $stmt_purchase->bind_param("ssiidis", $productId, $productName, $supplierId, $quantity, $unitPrice, $amount, $purchaseDate);
        $stmt_purchase->execute();
        
    }
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteButton'])){
        $Sno=intval($_POST['Sno']);
        $stmt_delete=$conn->prepare("DELETE FROM purchase WHERE Sno=?");
        $stmt_delete->bind_param("i",$Sno);
        if($stmt_delete->execute()){
            echo '<div class="alert alert-success" role="alert">Purchase deleted successfully!</div>';
            header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
            exit();
        } else {
            echo '<div class="alert alert-danger" role="alert">Error deleting customer!</div>';
        }
        $stmt_delete->close();
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
                        <input type="text" class="form-control" name="ProductID" placeholder="Product Id" list="ProductIDList" required>
                        <datalist id="ProductIDList">
                            <?php
                                $sql_data = "SELECT * FROM inventory";
                                $result_data = mysqli_query($conn, $sql_data);
                                while ($row = mysqli_fetch_assoc($result_data)) {
                                    echo "<option value='" . $row['ProductID'] . "'>" . $row['ProductID'] . "</option>";
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
                        <input type="text" class="form-control" name="SupplierID" placeholder="Supplier Id" list="SupplierIDList" required>
                        <datalist id="SupplierIDList">
                            <?php
                                $sql_data = "SELECT * FROM supplier";
                                $result_data = mysqli_query($conn, $sql_data);
                                while ($row = mysqli_fetch_assoc($result_data)) {
                                    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierID'] . "</option>";
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
                        <input type="number" step="0.01" class="form-control" name="UnitPrice" placeholder="Unit Price" required>
                    </td>
                    <td>
                        <!-- Purchase Date -->
                        <input type="date" class="form-control" name="PurchaseDate" required>
                    </td>
                </tr>
            </tbody>
        </table>
        <div>
            <button type="submit" class="btn btn-primary" name="submitButton">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>Purchase History</h1>
    <?php
    session_start();
    // Retrieve purchase data from the database
    $sql = "SELECT * FROM purchase";
    $result = mysqli_query($conn, $sql);

    // Display purchase information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>S.NO</th>';
    echo '<th>Product Id</th>';
    echo '<th>Product Name</th>';
    echo '<th>Supplier Id</th>';
    echo '<th>Description</th>';
    echo '<th>Quantity</th>';
    echo '<th>Unit Price</th>';
    echo '<th>Amount</th>';
    echo '<th>Purchase Date</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<form action="purchase.php" method="POST" onsubmit="return confirmSubmission()">';
        // echo '<td>' . $row['Sno'] . '</td>';
        echo '<td><input type="hidden" value="' . $row['Sno'] . '" name="Sno">'. $row['Sno'] . '</td>';
        echo '<td>' . $row['ProductID'] . '</td>';
        echo '<td>' . $row['ProductName'] . '</td>';
        echo '<td>' . $row['SupplierID'] . '</td>';
        echo '<td>' . $row['Description'] . '</td>';
        echo '<td>' . $row['Quantity'] . '</td>';
        echo '<td>' . $row['UnitPrice'] . '</td>';
        echo '<td>' . $row['Amount'] . '</td>';
        echo '<td>' . $row['PurchaseDate'] . '</td>';
        echo '<td><button type="submit" name="deleteButton" class="btn border-0">🗑️</button></td>';
        echo '</form>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    mysqli_close($conn);
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
</body>
</html>
