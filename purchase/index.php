<?php 
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submitButton'])) {
        handlePurchaseSubmit($conn);
    } elseif (isset($_POST['deleteButton'])) {
        handlePurchaseDelete($conn);
    }
}

function handlePurchaseSubmit($conn) {
    // Retrieving form data
    $productId = sanitizeInput($_POST['ProductID']);
    $productName = sanitizeInput($_POST['ProductName']);
    $supplierId = sanitizeInput($_POST['SupplierID']);
    $quantity = intval($_POST['Quantity']);
    $unitPrice = floatval($_POST['UnitPrice']);
    $purchaseDate = sanitizeInput($_POST['PurchaseDate']);
    $description = sanitizeInput($_POST['Description']);
    
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
        // Removed the Amount column from the UPDATE statement
        $stmt_update = $conn->prepare("UPDATE inventory SET Quantity = ?, UnitPrice = ? WHERE ProductID = ?");
        $stmt_update->bind_param("ids", $newQuantity, $unitPrice, $productId);
        $stmt_update->execute();
    } else {
        // Product does not exist, insert into inventory
        // Removed the Amount column from the INSERT statement
        $stmt_insert = $conn->prepare("INSERT INTO inventory (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice,Amount, ReorderLevel) VALUES (?, ?, ?, ?,?, ?, ?, 10)");
        $stmt_insert->bind_param("isisidd", $productId, $productName, $supplierId, $description, $quantity, $unitPrice,$amount);
        $stmt_insert->execute();
    }
    
    // Insert into purchase table
    // Removed the Amount column from the INSERT statement
    $stmt_purchase = $conn->prepare("INSERT INTO purchase (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, PurchaseDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_purchase->bind_param("isisids", $productId, $productName, $supplierId, $description, $quantity, $unitPrice, $purchaseDate);
    $stmt_purchase->execute();

    $_SESSION['message'] = 'Purchase added successfully!';
    header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
    exit();
}

function handlePurchaseDelete($conn) {
    $sno = intval($_POST['Sno']);
    $productId = intval($_POST['ProductID']);
    $quantity = intval($_POST['Quantity']);
    
    // Fetch current quantity from inventory
    $stmt_get_inventory = $conn->prepare("SELECT Quantity, UnitPrice FROM inventory WHERE ProductID=?");
    $stmt_get_inventory->bind_param("i", $productId);
    $stmt_get_inventory->execute();
    $result_inventory = $stmt_get_inventory->get_result();
    $inventory_data = $result_inventory->fetch_assoc();
    
    $newQuantity = $inventory_data['Quantity'] - $quantity;
    
    $stmt_delete = $conn->prepare("DELETE FROM purchase WHERE Sno=?");
    $stmt_delete->bind_param("i", $sno);
    
    $stmt_update_inventory = $conn->prepare("UPDATE inventory SET Quantity=? WHERE ProductID=?");
    $stmt_update_inventory->bind_param("ii", $newQuantity, $productId);
    
    if ($stmt_delete->execute() && $stmt_update_inventory->execute()) {
        $_SESSION['message'] = 'Purchase deleted successfully!';
        header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
        exit();
    } else {
        $_SESSION['error'] = 'Error deleting purchase!';
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Purchase</title>
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
            .ff {
                background-color: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
        }
        @media (max-width:576px){
            .mobile-card {
                width: 1000px;
            }
        }
    </style>
</head>
<body>
<div class="container ff mobile-card">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button>
    <br>
    <h1>Purchase</h1>
    <?php
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-success" role="alert">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger" role="alert">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    <form class="row gy-2 gx-3 align-items-center" action="index.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>ProductID</th>
                    <th>Product Name</th>
                    <th>Supplier Id</th>
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
                        <input type="text" class="form-control" name="ProductID" placeholder="Product Id" list="ProductIDList" required onchange="updateProductName(this)">
                        <datalist id="ProductIDList">
                            <?php
                                $sql_data = "SELECT * FROM inventory";
                                $result_data = mysqli_query($conn, $sql_data);
                                while ($row = mysqli_fetch_assoc($result_data)) {
                                    echo "<option value='" . $row['ProductID'] . "'>" . $row['ProductName'] . "</option>";
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
                                    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
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
                        <input type="number" class="form-control" name="UnitPrice" placeholder="Unit Price" required>
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
    // Retrieve purchase data from the database
    $sql = "SELECT * FROM purchase";
    $result = mysqli_query($conn, $sql);

    // Display purchase information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
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
        echo '<form action="index.php" method="POST" onsubmit="return confirmSubmission()">';
        echo '<td><input type="hidden" value="' . $row['Sno'] . '" name="Sno">'. $row['Sno'] . '</td>';
        echo '<td><input type="hidden" value="' . $row['ProductID'] . '" name="ProductID">' . $row['ProductID'] . '</td>';
        echo '<td><input type="hidden" value="' . $row['ProductName'] . '" name="ProductName">' . $row['ProductName'] . '</td>';
        echo '<td><input type="hidden" value="' . $row['SupplierID'] . '" name="SupplierID">'. $row['SupplierID'] . '</td>';
        echo '<td><input type="hidden" value="' . $row['Description'] . '" name="Description">' . $row['Description'] . '</td>';
        echo '<td><input type="hidden" value="' . $row['Quantity'] . '" name="Quantity">' . number_format($row['Quantity']) . '</td>';
        echo '<td><input type="hidden" value="' . $row['UnitPrice'] . '" name="UnitPrice">' . number_format($row['UnitPrice']) . '</td>';
        echo '<td><input type="hidden" value="' . $row['Amount'] . '" name="Amount">' . number_format($row['Amount']) . '</td>';
        echo '<td>' . $row['PurchaseDate'] . '</td>';
        echo '<td><button type="submit" name="deleteButton" class="btn border-0">🗑️</button></td>';
        echo '</form>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    ?>
</div>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script> -->
<script>
    const productData = <?php
        $products = [];
        $sql_in = "SELECT * FROM inventory";
        $result_in = mysqli_query($conn, $sql_in);
        while ($row = mysqli_fetch_assoc($result_in)) {
            $products[$row['ProductID']] = $row['ProductName'];
        }
        echo json_encode($products);
        mysqli_close($conn);
    ?>;
    function confirmSubmission() {
        return confirm("Are you sure you want to delete this purchase?");
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
