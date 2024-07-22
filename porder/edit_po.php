<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once '../database.php';

function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (!isset($_GET['poid'])) {
    echo "<div class='alert alert-danger'>No Purchase Order ID specified.</div>";
    exit();
}

$poId = intval($_GET['poid']);

// Fetch PO data 
$sql_po = "SELECT po.*, s.SupplierName FROM purchase_order po INNER JOIN supplier s ON po.SupplierID = s.SupplierID WHERE po.POID = ?";
$stmt_po = $conn->prepare($sql_po);
$stmt_po->bind_param("i", $poId);
$stmt_po->execute();
$poData = $stmt_po->get_result()->fetch_assoc();

// Fetch existing PO items 
$sql_po_items = "SELECT poi.*, i.ProductName FROM purchase_order_items poi INNER JOIN inventory i ON poi.ProductID = i.ProductID WHERE poi.POID = ?";
$stmt_po_items = $conn->prepare($sql_po_items);
$stmt_po_items->bind_param("i", $poId);
$stmt_po_items->execute();
$poItems = $stmt_po_items->get_result();

$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier");

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['updatePO'])) {
    $supplierId = sanitizeInput($_POST['SupplierID']);
    $totalItems = intval($_POST['totalItems']); 
    $productIds = $_POST['ProductID'];
    $quantities = $_POST['Quantity'];
    $unitPrices = $_POST['UnitPrice'];
    $poItemIds = isset($_POST['POItemID']) ? $_POST['POItemID'] : array();

    // Validate if number of items, product IDs, and quantities match
    if (count($productIds) != $totalItems || count($quantities) != $totalItems) {
        die("Invalid form data."); 
    }

    $conn->begin_transaction(); 
    try { 
        // Recalculate total amount
        $totalAmount = 0;
        for ($i = 0; $i < $totalItems; $i++) { 
            $totalAmount += floatval($quantities[$i]) * floatval($unitPrices[$i]); 
        }

        // Update the Purchase Order
        $sql_update_po = "UPDATE purchase_order SET SupplierID = ?, TotalAmount = ? WHERE POID = ?"; 
        $stmt_update_po = $conn->prepare($sql_update_po); 
        $stmt_update_po->bind_param("idi", $supplierId, $totalAmount, $poId); 
        $stmt_update_po->execute(); 

        // Handle item updates and additions
        for ($i = 0; $i < $totalItems; $i++) { 
            $productId = intval($productIds[$i]); 
            $quantity = intval($quantities[$i]); 
            $unitPrice = floatval($unitPrices[$i]);
            $itemId = isset($poItemIds[$i]) ? intval($poItemIds[$i]) : null;
    
            if ($itemId) {
                // Update the existing PO item 
                $sql_update_item = "UPDATE purchase_order_items SET ProductID = ?, Quantity = ?, UnitPrice = ? WHERE POItemID = ?";
                $stmt_update_item = $conn->prepare($sql_update_item); 
                $stmt_update_item->bind_param("iddi", $productId, $quantity, $unitPrice, $itemId);
                if (!$stmt_update_item->execute()) {
                    throw new Exception("Error updating PO Item"); 
                }
            } else { 
                // Add new PO Item
                $sql_insert_item = "INSERT INTO purchase_order_items (POID, ProductID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)";
                $stmt_insert_item = $conn->prepare($sql_insert_item); 
                $stmt_insert_item->bind_param("iiid", $poId, $productId, $quantity, $unitPrice);
                if (!$stmt_insert_item->execute()) { 
                    throw new Exception("Error inserting new PO Item");
                } 
            } 
        } 

        $conn->commit(); 
        echo "<div class='alert alert-success'>Purchase Order Updated Successfully</div>"; 
        // You can add a header to redirect if you need: 
        // header("Location: view_po.php?poid=" . $poId); // For example 
        exit();
    } catch (Exception $e) {
        $conn->rollback(); 
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
} 
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Edit Purchase Order</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function addProductRow() { 
            var tableBody = document.getElementById("productTableBody"); 
            var rowCount = tableBody.rows.length; 
            var row = tableBody.insertRow(rowCount); 

            // Product ID
            var cell1 = row.insertCell(0); 
            cell1.innerHTML = `
                <input type="text" class="form-control" name="ProductID[]" list="products" required> 
                <datalist id="products">
                <?php
                    $products = $conn->query("SELECT ProductID, ProductName FROM inventory");
                    while ($p = $products->fetch_assoc()) { 
                        echo "<option value='" . $p['ProductID'] . "'>" . $p['ProductName'] . "</option>";
                    } 
                ?> 
                </datalist>`;

            // Quantity
            var cell2 = row.insertCell(1); 
            cell2.innerHTML = '<input type="number" min="1" class="form-control" name="Quantity[]" required>';

            // Unit Price
            var cell3 = row.insertCell(2); 
            cell3.innerHTML = '<input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" required>';

            // Action (Delete button) - Not necessary for new rows 
            var cell4 = row.insertCell(3);
            cell4.innerHTML = ''; // Add button only after the row is added to the DOM 

            // Update total items counter
            document.getElementById('totalItems').value = rowCount + 1; 
        }

        function deleteRow(button) { 
            var row = button.parentNode.parentNode; 
            row.parentNode.removeChild(row); 

            var tableBody = document.getElementById("productTableBody");
            var rowCount = tableBody.rows.length; 
            document.getElementById('totalItems').value = rowCount; 
        } 
    </script>
</head> 
<body>
    <div class="container mt-4">
        <h2>Edit Purchase Order</h2>
        <a href="index.php" class="btn btn-secondary btn-sm mb-2">Back to Purchase Orders</a> 
        <form method="post" action=""> 
            <div class="mb-3"> 
                <label for="SupplierID">Supplier:</label> 
                <select class="form-control" id="SupplierID" name="SupplierID" required>
                    <option value="">Select Supplier</option>
                    <?php
                    // Populate Suppliers dropdown
                    $suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier"); 
                    while ($supplier = $suppliers->fetch_assoc()) {
                        $selected = ($supplier['SupplierID'] == $poData['SupplierID']) ? 'selected' : ''; 
                        echo "<option value='" . $supplier['SupplierID'] . "' $selected>" . $supplier['SupplierName'] . "</option>";
                    } 
                    ?>
                </select> 
            </div> 

            <h4>Products:</h4> 
            <table class="table">
                <thead>
                    <tr> 
                        <th>Product ID</th>
                        <th>Quantity</th> 
                        <th>Unit Price</th> 
                        <th>Action</th> 
                    </tr> 
                </thead>
                <tbody id="productTableBody">
                <?php while ($item = $poItems->fetch_assoc()) : ?>
                        <tr>
                            <td>
                                <input type="hidden" name="POItemID[]" value="<?php echo $item['POItemID']; ?>">
                                <input type="text" class="form-control" name="ProductID[]" list="products" value="<?php echo $item['ProductID']; ?>" required>
                                <datalist id="products">
                                <?php 
                                    // Populate Product ID dropdown
                                    $products = $conn->query("SELECT ProductID, ProductName FROM inventory"); 
                                    while ($product = $products->fetch_assoc()) { 
                                        echo "<option value='" . $product['ProductID'] . "'>" . $product['ProductName'] . "</option>";
                                    } 
                                ?> 
                                </datalist>
                            </td> 
                            <td><input type="number" min="1" class="form-control" name="Quantity[]" value="<?php echo $item['Quantity']; ?>" required></td>
                            <td><input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" value="<?php echo $item['UnitPrice']; ?>" required></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button>
                            </td> 
                        </tr> 
                <?php endwhile; ?> 
                </tbody>
            </table> 

            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addProductRow()">Add Product</button><br> 

            <input type="hidden" name="totalItems" id="totalItems" value="<?php echo $poItems->num_rows; ?>"> 
            <button type="submit" class="btn btn-primary" name="updatePO">Update Purchase Order</button> 
        </form> 
    </div> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>