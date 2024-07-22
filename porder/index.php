<?php 
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit(); 
}
require_once '../database.php'; 

// Function to sanitize input 
function sanitizeInput($data) {
    $data = trim($data); 
    $data = stripslashes($data); 
    $data = htmlspecialchars($data);
    return $data;
}

//Handle form submission based on type 
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_POST['purchaseType']) && $_POST['purchaseType'] === 'po'){
        handlePurchaseOrderSubmit($conn);  //Function for Purchase Orders 
    }elseif (isset($_POST['deleteButton'])) {
        handlePurchaseDelete($conn);
    }
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

// Handle direct purchases
// function handleDirectPurchase($conn){
//     $productId = sanitizeInput($_POST['ProductID']);
//     $productName = sanitizeInput($_POST['ProductName']);
//     $supplierId = sanitizeInput($_POST['SupplierID']);
//     $quantity = intval($_POST['Quantity']);
//     $unitPrice = floatval($_POST['UnitPrice']);
//     $purchaseDate = sanitizeInput($_POST['PurchaseDate']);
//     $description = sanitizeInput($_POST['Description']);
    
//     // Calculate total amount
//     $amount = $unitPrice * $quantity;
    
//     // Begin transaction 
//     $conn->begin_transaction();
//     try {
//         // Update Inventory
//         $sql_update_inventory = "UPDATE inventory 
//                                     SET Quantity = Quantity + ?, Amount = Amount + ?
//                                     WHERE ProductID = ?";
//         $stmt_update_inventory = $conn->prepare($sql_update_inventory);
//         $stmt_update_inventory->bind_param("idi", $quantity, $amount, $productId);
//         if(!$stmt_update_inventory->execute()) {
//             throw new Exception("Error updating inventory");
//         }

//         // Insert into purchase table 
//         $sql_purchase = "INSERT INTO purchase (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, PurchaseDate) 
//                         VALUES (?, ?, ?, ?, ?, ?, ?)";
//         $stmt_purchase = $conn->prepare($sql_purchase);
//         $stmt_purchase->bind_param("isisids", $productId, $productName, $supplierId, $description, $quantity, $unitPrice, $purchaseDate);
//         $stmt_purchase->execute();
        
//         $conn->commit();
//         echo "<div class='alert alert-success'>Direct Purchase Completed Successfully.</div>"; 
//     } catch (Exception $e) {
//         $conn->rollback(); 
//         echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
//     }
// }


//Handle purchase order submission
function handlePurchaseOrderSubmit($conn){
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

        // Loop through items to insert into purchase_order_items
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

        // Update Total Amount in the purchase order table 
        $sql_update_total = "UPDATE purchase_order 
                                SET TotalAmount = ?
                                WHERE POID = ?";
        $stmt_update_total = $conn->prepare($sql_update_total); 
        $stmt_update_total->bind_param("di", $totalAmount, $poId);
        $stmt_update_total->execute(); 

        // Installment Handling 
       // Get the selected payment option
$paymentOption = $_POST['paymentOption'];

// Handle installment logic based on the selected payment option
if ($paymentOption == 'full') {
    // Full payment, no installments needed
    $installmentAmount = $totalAmount;
    $dueDate = date('Y-m-d');
    $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate) 
                        VALUES (?, ?, ?)";
    $stmt_installment = $conn->prepare($sql_installment);
    $stmt_installment->bind_param("ids", $poId, $installmentAmount, $dueDate);
    if (!$stmt_installment->execute()) {
        throw new Exception("Error creating full payment installment");
    }
} elseif ($paymentOption == '30_70') {
    // 30/70 split
    $firstInstallmentAmount = $totalAmount * 0.30;
    $secondInstallmentAmount = $totalAmount * 0.70;

    // First installment (due now)
    $dueDate = date('Y-m-d');
    $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate) 
                        VALUES (?, ?, ?)";
    $stmt_installment = $conn->prepare($sql_installment);
    $stmt_installment->bind_param("ids", $poId, $firstInstallmentAmount, $dueDate);
    if (!$stmt_installment->execute()) {
        throw new Exception("Error creating first 30/70 installment");
    }

    // Second installment (due in 1 month)
    $dueDate = date('Y-m-d', strtotime("+1 month"));
    $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate) 
                        VALUES (?, ?, ?)";
    $stmt_installment->bind_param("ids", $poId, $secondInstallmentAmount, $dueDate);
    if (!$stmt_installment->execute()) {
        throw new Exception("Error creating second 30/70 installment");
    }
} elseif ($paymentOption == '50_50') {
    // 50/50 split
    $installmentAmount = $totalAmount / 2;

    // First installment (due now)
    $dueDate = date('Y-m-d');
    $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate) 
                        VALUES (?, ?, ?)";
    $stmt_installment = $conn->prepare($sql_installment);
    $stmt_installment->bind_param("ids", $poId, $installmentAmount, $dueDate);
    if (!$stmt_installment->execute()) {
        throw new Exception("Error creating first 50/50 installment");
    }

    // Second installment (due in 1 month)
    $dueDate = date('Y-m-d', strtotime("+1 month"));
    $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate) 
                        VALUES (?, ?, ?)";
    $stmt_installment->bind_param("ids", $poId, $installmentAmount, $dueDate);
    if (!$stmt_installment->execute()) {
        throw new Exception("Error creating second 50/50 installment");
    }
} else {
    throw new Exception("Invalid payment option selected");
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
<html lang="en">
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase & Purchase Orders</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> 
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <script>
        function showForm(formType) {
            // if (formType === 'direct') {
            //     document.getElementById('directPurchaseForm').style.display = 'block'; 
            //     document.getElementById('purchaseOrderForm').style.display = 'none';
            // } else if (formType === 'po') {
            //     document.getElementById('directPurchaseForm').style.display = 'none'; 
            //     document.getElementById('purchaseOrderForm').style.display = 'block';
            // }
        }

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
        <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button><br>
   
        <div id="purchaseOrderForm" style="display:block;">
            <h3>Create Purchase Order</h3> 
            <form method="post" action=""> 
                <input type="hidden" name="purchaseType" value="po"> 
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
                <button type="button" class="btn btn-dark btn-sm mb-3" onclick="addProductRow()">Add Product</button> <br> 
                
                <div class="mb-3">
                <label for="paymentOptions">Payment Options:</label>
                <div class="mb-3">
                
                <select class="form-select" name="paymentOption" id="paymentOption" required>
                    <option value="full">Pay as whole</option>
                    <option value="30_70">30/70 split</option>
                    <option value="50_50">50/50 split</option>
                </select>
            </div>

                </div>

                <input type="hidden" name="totalItems" id="totalItems" value="1"> 
                <button type="submit" class="btn btn-outline-dark">Create Purchase Order</button> 
            </form> 
        </div>
<!-- 
        <h2>Purchase History (Direct and from POs)</h2> 
        <?php
        // Retrieve and display purchase history here (adapt based on your previous code)
        // You'll need to modify the query to fetch data from both purchase and 
        // purchase_order_items (with joins if necessary) 
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
        ?> -->

        <hr> 
        <h2>Manage Purchase Orders</h2>
        <a href="create_po.php" class="btn btn-primary mb-3">Create New Purchase Order</a> 
        <?php
        // Fetch Purchase Orders
        $sql_po = "SELECT po.*, s.SupplierName 
                   FROM purchase_order po 
                   INNER JOIN supplier s ON po.SupplierID = s.SupplierID"; 
        $result_po = $conn->query($sql_po); 
        ?>
        <table class="table table-hover"> 
            <thead> 
                <tr>
                    <th>PO ID</th> 
                    <th>Supplier</th> 
                    <th>Order Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead> 
            <tbody> 
                <?php while ($po = $result_po->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $po['POID']; ?></td>
                        <td><?php echo $po['SupplierName']; ?></td>
                        <td><?php echo $po['OrderDate']; ?></td>
                        <td><?php echo number_format($po['TotalAmount'], 2); ?></td>
                        <td><?php echo $po['Status']; ?></td>
                        <td>
                            <a href="view_po.php?poid=<?php echo $po['POID']; ?>" class="btn btn-outline-info btn-sm">View Details</a> 
                            <a href="view_installments.php?poid=<?php echo $po['POID']; ?>" class="btn btn-outline-dark btn-sm">View Installments</a>
                            <form action="edit_po.php" method="POST" style="display:inline;"> 
                                <input type="hidden" name="POID" value="<?php echo $po['POID']; ?>"> 
                                <button type="submit" class="btn btn-outline-primary btn-sm">Edit</button> 
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

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