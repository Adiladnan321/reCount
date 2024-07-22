<?php
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit(); 
}
require_once '../database.php'; // Update if needed 

function sanitizeInput($data) { 
    $data = trim($data); 
    $data = stripslashes($data); 
    $data = htmlspecialchars($data);
    return $data;
} 
$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier"); 

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplierId = sanitizeInput($_POST['SupplierID']);
    $totalItems = intval($_POST['totalItems']);
    $productIds = $_POST['ProductID'];
    $quantities = $_POST['Quantity']; 
    $unitPrices = $_POST['UnitPrice']; 

    // Validate Form Data 
    if (count($productIds) != $totalItems || count($quantities) != $totalItems) {
        echo "<div class='alert alert-danger'>Invalid form data.</div>";
        // You might want to redirect or handle the error more gracefully.
    } else {

        $conn->begin_transaction();
        try {
            // Calculate Total Amount 
            $totalAmount = 0;
            for ($i = 0; $i < $totalItems; $i++) {
                $totalAmount += $quantities[$i] * $unitPrices[$i];
            }

            // 1. Insert into purchase_order 
            $sql_po = "INSERT INTO purchase_order (SupplierID, OrderDate, TotalAmount, Status) 
                       VALUES (?, CURDATE(), ?, 'Pending')"; 
            $stmt_po = $conn->prepare($sql_po); 
            $stmt_po->bind_param("id", $supplierId, $totalAmount);
            
            if (!$stmt_po->execute()) {
                throw new Exception("Error creating Purchase Order: " . $stmt_po->error);
            }
            
            $poId = $conn->insert_id; 

            // 2. Insert Items into purchase_order_items 
            for ($i=0; $i < $totalItems; $i++) { 
                $productId = intval($productIds[$i]);
                $quantity = intval($quantities[$i]);
                $unitPrice = floatval($unitPrices[$i]);

                $sql_po_item = "INSERT INTO purchase_order_items (POID, ProductID, Quantity, UnitPrice) 
                                VALUES (?, ?, ?, ?)";
                $stmt_po_item = $conn->prepare($sql_po_item); 
                $stmt_po_item->bind_param("iiid", $poId, $productId, $quantity, $unitPrice);
                if (!$stmt_po_item->execute()) {
                    throw new Exception("Error adding product to Purchase Order: " . $stmt_po_item->error); 
                }
            } 
            
            $conn->commit(); 
            echo "<div class='alert alert-success'>Purchase Order Created Successfully with ID: " . $poId . "</div>"; 
            // Optionally redirect to view_po.php: header("Location: view_po.php?poid=$poId");
        } catch (Exception $e) {
            $conn->rollback(); 
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        } 
    } 
}
?> 
<!DOCTYPE html>
<html>
<head>
    <title>Create Purchase Order</title>
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
                while($p = $products->fetch_assoc()){
                    echo "<option value=\"".$p['ProductID']."\">".$p['ProductName']."</option>";
                }
            ?>
            </datalist>`;

            // Quantity 
            var cell2 = row.insertCell(1); 
            cell2.innerHTML = '<input type="number" min="1" class="form-control" name="Quantity[]" required>'; 
            
            // Unit Price 
            var cell3 = row.insertCell(2);
            cell3.innerHTML = '<input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" required>'; 
        } 
    </script>
</head>
<body> 
    <div class="container mt-4"> 
        <button class="btn btn-outline-secondary" onclick="window.location.href='index.php'">
            < Back to Purchase Orders</button><br><br> 
        <h2>Create New Purchase Order</h2>
        <form method="post" action=""> 
            <div class="mb-3"> 
                <label for="SupplierID">Supplier:</label> 
                <select class="form-control" id="SupplierID" name="SupplierID" required> 
                    <option value="">Select Supplier</option>
                    <?php
                    while ($row = $suppliers->fetch_assoc()) {
                        echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>"; 
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
                    </tr>
                </thead>
                <tbody id="productTableBody"> 
                    <tr> 
                        <td>
                        <input type="text" class="form-control" name="ProductID[]" list="products" required>
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
                        <td><input type="number" min="1" class="form-control" name="Quantity[]" required></td>
                        <td><input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" required></td>
                    </tr>
                </tbody> 
            </table>
            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addProductRow()">Add Product</button> <br>
            <input type="hidden" name="totalItems" id="totalItems" value="1"> 
            <button type="submit" class="btn btn-primary">Create Purchase Order</button> 
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script> 
    <script> 
        // Simple function to update the hidden input field 'totalItems' whenever a product row is added
        document.addEventListener('click', function(event) {
            if (event.target.onclick.toString().includes('addProductRow')) { 
                document.getElementById('totalItems').value++; 
            } 
        });
    </script> 
</body>
</html> 