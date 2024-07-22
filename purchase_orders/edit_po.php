<?php 
session_start(); 
if(!isset($_SESSION["user"])) {
    header("Location: login.php"); 
    exit(); 
} 
require_once '../database.php'; 

function sanitizeInput($data) {
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

// Fetch existing Purchase Order data
$sql_po = "SELECT * FROM purchase_order WHERE POID = ?"; 
$stmt_po = $conn->prepare($sql_po);
$stmt_po->bind_param("i", $poId);
$stmt_po->execute(); 
$poData = $stmt_po->get_result()->fetch_assoc();

// Fetch Purchase Order Items for this PO
$sql_po_items = "SELECT * FROM purchase_order_items WHERE POID = ?";
$stmt_po_items = $conn->prepare($sql_po_items);
$stmt_po_items->bind_param("i", $poId); 
$stmt_po_items->execute();
$poItemsResult = $stmt_po_items->get_result();

$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier");

// Update Purchase Order 
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // 1. Handle Update of Purchase Order itself
    $supplierId = sanitizeInput($_POST['SupplierID']);

    $sql_update_po = "UPDATE purchase_order SET SupplierID = ? WHERE POID = ?"; 
    $stmt_update_po = $conn->prepare($sql_update_po); 
    $stmt_update_po->bind_param("ii", $supplierId, $poId); 

    if (!$stmt_update_po->execute()) { 
        echo "<div class='alert alert-danger'>Error updating purchase order: " . $stmt_update_po->error . "</div>";
    } else { 
        // 2. Handle Updates or Additions of Purchase Order Items 
        $totalItems = intval($_POST['totalItems']); 
        for ($i = 0; $i < $totalItems; $i++) {
            //Check if POItemID exists, meaning it's an existing item to be updated 
            $poItemId = isset($_POST['POItemID'][$i]) ? intval($_POST['POItemID'][$i]) : null;

            $productId = sanitizeInput($_POST['ProductID'][$i]); 
            $quantity = intval($_POST['Quantity'][$i]);
            $unitPrice = floatval($_POST['UnitPrice'][$i]);
    
            if ($poItemId) {
                // Update existing item
                $sql_update_item = "UPDATE purchase_order_items SET 
                                    ProductID = ?, 
                                    Quantity = ?, 
                                    UnitPrice = ?
                                WHERE POItemID = ?";
                $stmt_update_item = $conn->prepare($sql_update_item); 
                $stmt_update_item->bind_param("idii", $productId, $quantity, $unitPrice, $poItemId);

                if (!$stmt_update_item->execute()) { 
                    echo "<div class='alert alert-danger'>Error updating item #" . ($i + 1) . ": " . $stmt_update_item->error . "</div>"; 
                }
            } else { 
                // Add new item 
                $sql_insert_item = "INSERT INTO purchase_order_items (POID, ProductID, Quantity, UnitPrice) 
                                VALUES (?, ?, ?, ?)"; 
                $stmt_insert_item = $conn->prepare($sql_insert_item);
                $stmt_insert_item->bind_param("iiid", $poId, $productId, $quantity, $unitPrice);

                if (!$stmt_insert_item->execute()) { 
                    echo "<div class='alert alert-danger'>Error adding item #" . ($i + 1) . ": " . $stmt_insert_item->error . "</div>";
                }
            }
        }
    
        // 3. Recalculate and Update Total Amount 
        $sql_recalculate_total = "UPDATE purchase_order po
                                   SET TotalAmount = (
                                    SELECT SUM(poi.Quantity * poi.UnitPrice) 
                                    FROM purchase_order_items poi 
                                    WHERE poi.POID = po.POID
                                   ) 
                                   WHERE po.POID = ?";
        $stmt_recalculate_total = $conn->prepare($sql_recalculate_total);
        $stmt_recalculate_total->bind_param("i", $poId); 

        if (!$stmt_recalculate_total->execute()) {
            echo "<div class='alert alert-danger'>Error recalculating total: " . $stmt_recalculate_total->error . "</div>"; 
        } else {
            echo "<div class='alert alert-success'>Purchase Order Updated Successfully!</div>"; 
            // Optionally, redirect after update:
            // header("Location: view_po.php?poid=$poId");
            // exit(); 
        } 
    } 
} 
?> 
<!DOCTYPE html>
<html> 
<head> 
    <title>Edit Purchase Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script> 
        function addProductRow() {
            var tableBody = document.getElementById("productTableBody");
            var rowCount = tableBody.rows.length; 
            var row = tableBody.insertRow(rowCount);

            // Product ID with Autocomplete
            var cell1 = row.insertCell(0); 
            cell1.innerHTML = `
                <input type="text" class="form-control" name="ProductID[]" list="products" required> 
                <datalist id="products">
                <?php 
                    $products = $conn->query("SELECT ProductID, ProductName FROM inventory"); 
                    while ($p = $products->fetch_assoc()) { 
                        echo "<option value=\"" . $p['ProductID'] . "\">" . $p['ProductName'] . "</option>";
                    }
                ?> 
                </datalist>
            `;

            // Quantity
            var cell2 = row.insertCell(1); 
            cell2.innerHTML = '<input type="number" min="1" class="form-control" name="Quantity[]" required>'; 
            // Unit Price 
            var cell3 = row.insertCell(2);
            cell3.innerHTML = '<input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" required>';
            // Delete Button
            var cell4 = row.insertCell(3);
            cell4.innerHTML = '<button type="button" class="btn btn-danger btn-sm" onclick="deleteProductRow(this)">Delete</button>'; 
        }

        function deleteProductRow(button) { 
            var row = button.parentNode.parentNode; 
            row.parentNode.removeChild(row);
            // You may need to update the total items count after deleting. 
        } 
    </script>
</head>
<body>
    <div class="container mt-4"> 
        <button class="btn btn-outline-secondary" onclick="window.location.href='index.php'"> 
            < Back to Purchase Orders </button><br><br> 
        <h2>Edit Purchase Order</h2>
        <form method="post" action="">
            <div class="mb-3">
                <label for="SupplierID">Supplier:</label>
                <select class="form-control" id="SupplierID" name="SupplierID" required> 
                    <option value="">Select Supplier</option>
                    <?php
                        // Populate the Supplier dropdown list 
                        while ($row = $suppliers->fetch_assoc()) {
                            $selected = ($row['SupplierID'] == $poData['SupplierID']) ? 'selected' : ''; 
                            echo "<option value='" . $row['SupplierID'] . "' " . $selected . ">" 
                                 . $row['SupplierName'] . "</option>"; 
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
                        <th>Actions</th>
                    </tr> 
                </thead> 
                <tbody id="productTableBody">
                    <?php 
                        $itemCount = 0; // To keep track of the item number
                        while ($poItem = $poItemsResult->fetch_assoc()): 
                            $itemCount++;
                    ?>
                            <tr> 
                                <td> 
                                    <input type="hidden" name="POItemID[]" value="<?php echo $poItem['POItemID']; ?>"> 
                                    <input type="text" class="form-control" name="ProductID[]" list="products" value="<?php echo $poItem['ProductID']; ?>" required>
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
                                <td> 
                                    <input type="number" min="1" class="form-control" name="Quantity[]" value="<?php echo $poItem['Quantity']; ?>" required> 
                                </td> 
                                <td>
                                    <input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" value="<?php echo $poItem['UnitPrice']; ?>" required>
                                </td> 
                                <td>
                                    <!-- No delete button here since we are allowing deletion of only newly added items  --> 
                                </td> 
                            </tr>
                        <?php 
                            endwhile;
                    ?> 
                </tbody>
            </table> 

            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addProductRow()">Add Product</button><br>
            <input type="hidden" name="totalItems" id="totalItems" value="<?php echo $itemCount; ?>"> <button type="submit" class="btn btn-primary">Update Purchase Order</button> 
        </form> 
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script> 
</body>
</html>