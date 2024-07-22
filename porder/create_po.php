<?php
session_start();
if(!isset($_SESSION["user"])){
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

// Handle Form Submission 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplierId = sanitizeInput($_POST['SupplierID']); 
    $totalItems = intval($_POST['totalItems']);
    $totalAmount = 0; 
    $productIds = $_POST['ProductID']; 
    $quantities = $_POST['Quantity'];
    $unitPrices = $_POST['UnitPrice']; 
    
    // Validate if number of items, product IDs, and quantities match
    if(count($productIds)!=$totalItems or count($quantities)!=$totalItems){
        die("Invalid form data.");
    }
    
    // Begin transaction 
    $conn->begin_transaction();
    try {
        // Insert into purchase_order table 
        $sql_po = "INSERT INTO purchase_order (SupplierID, OrderDate, TotalAmount) 
                   VALUES (?, CURDATE(), ?)";
        $stmt_po = $conn->prepare($sql_po);
        $stmt_po->bind_param("id", $supplierId, $totalAmount);
        $stmt_po->execute();

        $poId = $conn->insert_id; 

        // Loop through items to insert into purchase_order_items and update inventory 
        for ($i=0; $i < $totalItems; $i++) { 
            $productId = $productIds[$i];
            $quantity = $quantities[$i];
            $unitPrice = $unitPrices[$i];

            // Insert into purchase_order_items table 
            $sql_po_item = "INSERT INTO purchase_order_items (POID, ProductID, Quantity, UnitPrice) 
                            VALUES (?, ?, ?, ?)";
            $stmt_po_item = $conn->prepare($sql_po_item);
            $stmt_po_item->bind_param("iiid", $poId, $productId, $quantity, $unitPrice); 
            $stmt_po_item->execute(); 

            $totalAmount += ($quantity * $unitPrice);
        }

        // Update Total Amount 
        $sql_update_total = "UPDATE purchase_order 
                                SET TotalAmount = ?
                                WHERE POID = ?";
        $stmt_update_total = $conn->prepare($sql_update_total); 
        $stmt_update_total->bind_param("di", $totalAmount, $poId);
        $stmt_update_total->execute(); 

        // Installment Handling 
        $numInstallments = intval($_POST['numInstallments']);
        if($numInstallments>0){
            $installmentAmount = $totalAmount / $numInstallments;
            for ($j = 1; $j <= $numInstallments; $j++) {
                $dueDate = date('Y-m-d', strtotime("+" . $j . " month"));
                $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate) 
                                     VALUES (?, ?, ?)";
                $stmt_installment = $conn->prepare($sql_installment);
                $stmt_installment->bind_param("ids", $poId, $installmentAmount, $dueDate);
                if(!$stmt_installment->execute()) {
                    throw new Exception("Error creating installments");
                }
            }
        }

        // Commit transaction
        $conn->commit();
        echo "<div class='alert alert-success'>Purchase Order Created Successfully with ID: " . $poId . "</div>";
    } catch (Exception $e) {
        $conn->rollback(); 
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier"); 
?>

<!DOCTYPE html>
<html> 
<head>
    <title>Purchase Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script>
        function addProductRow() {
            var tableBody = document.getElementById("productTableBody"); 
            var rowCount = tableBody.rows.length; 
            var row = tableBody.insertRow(rowCount); 

            // Product ID
            var cell1 = row.insertCell(0); 
            cell1.innerHTML = '<input type="text" class="form-control" name="ProductID[]" list="products" required>';
            var datalist = document.createElement('datalist');
            datalist.id = 'products';
            <?php 
                $products = $conn->query("SELECT ProductID, ProductName FROM inventory");
                while($p = $products->fetch_assoc()){
                    echo "datalist.innerHTML += '<option value=\"".$p['ProductID']."\">".$p['ProductName']."</option>';";
                } 
            ?>
            cell1.appendChild(datalist);
            
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
        <button class="btn btn-outline-secondary" onclick="window.location.href='index.php'"><</button><br> 
        <h2>Create Purchase Order</h2> 

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
                                $products = $conn->query("SELECT ProductID, ProductName FROM inventory");
                                while($p = $products->fetch_assoc()){
                                    echo "<option value=\"".$p['ProductID']."\">".$p['ProductName']."</option>";
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
            <div class="mb-3">/.
                <label for="numInstallments">Number of Installments (0 for full payment):</label>
                <input type="number" class="form-control" id="numInstallments" name="numInstallments" value="0"> 
            </div>
            <input type="hidden" name="totalItems" id="totalItems" value="1">
            <button type="submit" class="btn btn-primary">Create Purchase Order</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
    <script>
        //Simple function to count total items added to the purchase order.
        document.addEventListener('click', function(event) {
            if(event.target.onclick.toString().includes('addProductRow')) {
                document.getElementById('totalItems').value++; 
            }
        }); 
    </script>
</body>
</html>