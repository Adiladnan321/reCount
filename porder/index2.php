<?php 
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit(); 
}
require_once '../database.php'; 

// Function to sanitize input 
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

//Handle form submission based on type 
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['purchaseType'])) {
        switch ($_POST['purchaseType']) {
            case 'direct':
                handleDirectPurchase($conn);
                break;
            case 'po':
                handlePurchaseOrderSubmit($conn);
                break;
            default:
                break;
        }
    } elseif (isset($_POST['deleteButton'])) {
        handlePurchaseDelete($conn);
    }
}

function handlePurchaseDelete($conn) {
    $sno = intval($_POST['Sno']);
    $productId = intval($_POST['ProductID']);
    $quantity = intval($_POST['Quantity']);
    
    // Fetch current quantity from inventory
    $stmt_get_inventory = $conn->prepare("SELECT Quantity FROM inventory WHERE ProductID=?");
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

// Handle direct purchases
function handleDirectPurchase($conn){
    $productId = sanitizeInput($_POST['ProductID']);
    $productName = sanitizeInput($_POST['ProductName']);
    $supplierId = sanitizeInput($_POST['SupplierID']);
    $quantity = intval($_POST['Quantity']);
    $unitPrice = floatval($_POST['UnitPrice']);
    $purchaseDate = sanitizeInput($_POST['PurchaseDate']);
    $description = sanitizeInput($_POST['Description']);
    
    // Calculate total amount
    $amount = $unitPrice * $quantity;
    
    // Begin transaction 
    $conn->begin_transaction();
    try {
        // Update Inventory
        $stmt_update_inventory = $conn->prepare("UPDATE inventory SET Quantity = Quantity + ?, Amount = Amount + ? WHERE ProductID = ?");
        $stmt_update_inventory->bind_param("idi", $quantity, $amount, $productId);
        if(!$stmt_update_inventory->execute()) {
            throw new Exception("Error updating inventory");
        }

        // Insert into purchase table 
        $stmt_purchase = $conn->prepare("INSERT INTO purchase (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, PurchaseDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_purchase->bind_param("isisids", $productId, $productName, $supplierId, $description, $quantity, $unitPrice, $purchaseDate);
        $stmt_purchase->execute();
        
        $conn->commit();
        $_SESSION['message'] = "Direct Purchase Completed Successfully.";
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: {$_SERVER['PHP_SELF']}");
    exit();
}

//Handle purchase order submission
function handlePurchaseOrderSubmit($conn){
    $supplierId = sanitizeInput($_POST['SupplierID']); 
    $totalItems = intval($_POST['totalItems']);
    $totalAmount = 0; 
    $productIds = $_POST['ProductID']; 
    $quantities = $_POST['Quantity'];
    $unitPrices = $_POST['UnitPrice']; 

    if(count($productIds) != $totalItems || count($quantities) != $totalItems){
        $_SESSION['error'] = "Invalid form data.";
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }
    
    // Begin transaction 
    $conn->begin_transaction();
    try {
        // Insert into purchase_order table 
        $stmt_po = $conn->prepare("INSERT INTO purchase_order (SupplierID, OrderDate, TotalAmount) VALUES (?, CURDATE(), ?)");
        $stmt_po->bind_param("id", $supplierId, $totalAmount);
        $stmt_po->execute();

        $poId = $conn->insert_id; 

        // Loop through items to insert into purchase_order_items
        for ($i=0; $i < $totalItems; $i++) { 
            $productId = sanitizeInput($productIds[$i]);
            $quantity = intval($quantities[$i]);
            $unitPrice = floatval($unitPrices[$i]);

            // Insert into purchase_order_items table 
            $stmt_po_item = $conn->prepare("INSERT INTO purchase_order_items (POID, ProductID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)");
            $stmt_po_item->bind_param("iiid", $poId, $productId, $quantity, $unitPrice); 
            $stmt_po_item->execute(); 

            $totalAmount += ($quantity * $unitPrice);
        }

        // Update Total Amount in the purchase order table 
        $stmt_update_total = $conn->prepare("UPDATE purchase_order SET TotalAmount = ? WHERE POID = ?");
        $stmt_update_total->bind_param("di", $totalAmount, $poId);
        $stmt_update_total->execute(); 

        // Installment Handling 
        $numInstallments = intval($_POST['numInstallments']);
        if($numInstallments > 0){
            $installmentAmount = $totalAmount / $numInstallments;
            for ($j = 1; $j <= $numInstallments; $j++) {
                $dueDate = date('Y-m-d', strtotime("+" . $j . " month"));
                $stmt_installment = $conn->prepare("INSERT INTO installments (POID, InstallmentAmount, DueDate) VALUES (?, ?, ?)");
                $stmt_installment->bind_param("ids", $poId, $installmentAmount, $dueDate);
                if(!$stmt_installment->execute()) {
                    throw new Exception("Error creating installments");
                }
            }
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Purchase Order Created Successfully with ID: " . $poId;
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: {$_SERVER['PHP_SELF']}");
    exit();
}

$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier");
$products = $conn->query("SELECT ProductID, ProductName FROM inventory");

$productOptions = [];
while ($product = $products->fetch_assoc()) {
    $productOptions[$product['ProductID']] = $product['ProductName'];
}

?> 

<!DOCTYPE html> 
<html lang="en">
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase & Purchase Orders</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> 
    <script>
        function showForm(formType) {
            document.getElementById('directPurchaseForm').style.display = (formType === 'direct') ? 'block' : 'none';
            document.getElementById('purchaseOrderForm').style.display = (formType === 'po') ? 'block' : 'none';
        }

        function addProductRow() {
            var tableBody = document.getElementById("productTableBody"); 
            var row = tableBody.insertRow(); 

            // Product ID
            var cell1 = row.insertCell(0); 
            cell1.innerHTML = '<input type="text" class="form-control" name="ProductID[]" list="products" required>';

            // Quantity
            var cell2 = row.insertCell(1); 
            cell2.innerHTML = '<input type="number" class="form-control" name="Quantity[]" required>';

            // Unit Price
            var cell3 = row.insertCell(2); 
            cell3.innerHTML = '<input type="number" class="form-control" name="UnitPrice[]" step="0.01" required>';
        }
    </script>
</head>
<body class="container"> 
    <h1>Purchase & Purchase Orders</h1>
    
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <button class="btn btn-primary" onclick="showForm('direct')">Direct Purchase</button> 
    <button class="btn btn-secondary" onclick="showForm('po')">Purchase Order</button>
    
    <form id="directPurchaseForm" method="post" action="" style="display:none">
        <input type="hidden" name="purchaseType" value="direct">
        <div class="mb-3">
            <label for="ProductID">Product</label>
            <input type="text" class="form-control" name="ProductID" list="products" required>
            <datalist id="products">
                <?php foreach ($productOptions as $id => $name): ?>
                    <option value="<?= $id ?>"><?= $name ?></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="mb-3">
            <label for="ProductName">Product Name</label>
            <input type="text" class="form-control" name="ProductName" required>
        </div>
        <div class="mb-3">
            <label for="SupplierID">Supplier</label>
            <select class="form-control" name="SupplierID" required>
                <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                    <option value="<?= $supplier['SupplierID'] ?>"><?= $supplier['SupplierName'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="Quantity">Quantity</label>
            <input type="number" class="form-control" name="Quantity" required>
        </div>
        <div class="mb-3">
            <label for="UnitPrice">Unit Price</label>
            <input type="number" class="form-control" name="UnitPrice" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="PurchaseDate">Purchase Date</label>
            <input type="date" class="form-control" name="PurchaseDate" required>
        </div>
        <div class="mb-3">
            <label for="Description">Description</label>
            <textarea class="form-control" name="Description" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    <form id="purchaseOrderForm" method="post" action="" style="display:none">
        <input type="hidden" name="purchaseType" value="po">
        <div class="mb-3">
            <label for="SupplierID">Supplier</label>
            <select class="form-control" name="SupplierID" required>
                <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                    <option value="<?= $supplier['SupplierID'] ?>"><?= $supplier['SupplierName'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="totalItems">Total Items</label>
            <input type="number" class="form-control" name="totalItems" required>
        </div>
        <div class="mb-3">
            <label for="numInstallments">Number of Installments</label>
            <input type="number" class="form-control" name="numInstallments">
        </div>
        <table class="table table-bordered" id="productTable">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <tr>
                    <td><input type="text" class="form-control" name="ProductID[]" list="products" required></td>
                    <td><input type="number" class="form-control" name="Quantity[]" required></td>
                    <td><input type="number" class="form-control" name="UnitPrice[]" step="0.01" required></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" onclick="addProductRow()">Add Product</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    <h2>Existing Purchases</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Supplier ID</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Purchase Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stmt_purchases = $conn->prepare("SELECT * FROM purchase");
            $stmt_purchases->execute();
            $result_purchases = $stmt_purchases->get_result();
            while ($purchase = $result_purchases->fetch_assoc()): 
            ?>
                <tr>
                    <td><?= $purchase['Sno'] ?></td>
                    <td><?= $purchase['ProductID'] ?></td>
                    <td><?= $purchase['ProductName'] ?></td>
                    <td><?= $purchase['SupplierID'] ?></td>
                    <td><?= $purchase['Description'] ?></td>
                    <td><?= $purchase['Quantity'] ?></td>
                    <td><?= $purchase['UnitPrice'] ?></td>
                    <td><?= $purchase['PurchaseDate'] ?></td>
                    <td>
                        <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this purchase?');">
                            <input type="hidden" name="Sno" value="<?= $purchase['Sno'] ?>">
                            <input type="hidden" name="ProductID" value="<?= $purchase['ProductID'] ?>">
                            <input type="hidden" name="Quantity" value="<?= $purchase['Quantity'] ?>">
                            <button type="submit" name="deleteButton" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>

