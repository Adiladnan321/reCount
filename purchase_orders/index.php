<?php
session_start();
if (!isset($_SESSION["user"])) {
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

// Function to handle direct purchase submission (Moved logic for better readability)
function handleDirectPurchase($conn) {
    $productId = sanitizeInput($_POST['ProductID']);
    $productName = sanitizeInput($_POST['ProductName']); // Assuming you are taking product name as input as well
    $supplierId = sanitizeInput($_POST['SupplierID']);
    $quantity = intval($_POST['Quantity']);
    $unitPrice = floatval($_POST['UnitPrice']);
    $purchaseDate = sanitizeInput($_POST['PurchaseDate']);
    $description = sanitizeInput($_POST['Description']);

    // Calculate total amount
    $amount = $unitPrice * $quantity;

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Update Inventory
        $sql_update_inventory = "UPDATE inventory 
                                    SET Quantity = Quantity + ?, Amount = Amount + ?
                                    WHERE ProductID = ?";
        $stmt_update_inventory = $conn->prepare($sql_update_inventory);
        $stmt_update_inventory->bind_param("idi", $quantity, $amount, $productId);

        if (!$stmt_update_inventory->execute()) {
            throw new Exception("Error updating inventory: " . $stmt_update_inventory->error); 
        }

        // Insert into purchase table 
        $sql_purchase = "INSERT INTO purchase (ProductID, ProductName, SupplierID, Description, Quantity, UnitPrice, PurchaseDate) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_purchase = $conn->prepare($sql_purchase);
        $stmt_purchase->bind_param("isisids", $productId, $productName, $supplierId, $description, $quantity, $unitPrice, $purchaseDate);

        if (!$stmt_purchase->execute()) {
            throw new Exception("Error inserting purchase: " . $stmt_purchase->error);
        }

        // Commit transaction
        $conn->commit();

        echo "<div class='alert alert-success'>Direct Purchase Completed Successfully.</div>";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback(); 
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Function to calculate installment amounts based on split ratio
function calculateInstallmentAmounts($totalAmount, $splitRatio) {
    $installmentAmounts = [];
    foreach ($splitRatio as $ratio) {
        $installmentAmounts[] = round(($totalAmount * $ratio) / 100, 2);
    }
    // Adjust last installment to account for rounding errors 
    $totalCalculated = array_sum($installmentAmounts); 
    $diff = $totalAmount - $totalCalculated; 
    $installmentAmounts[count($installmentAmounts) - 1] += $diff;

    return $installmentAmounts; 
}

//Handle purchase order submission
function handlePurchaseOrderSubmit($conn) {
    $supplierId = sanitizeInput($_POST['SupplierID']);
    $totalItems = intval($_POST['totalItems']);
    $productIds = $_POST['ProductID'];
    $quantities = $_POST['Quantity'];
    $unitPrices = $_POST['UnitPrice'];
    $totalAmount = 0;

    // Validate if number of items, product IDs, and quantities match
    if (count($productIds) != $totalItems || count($quantities) != $totalItems) {
        die("Invalid form data.");
    }

    for ($i = 0; $i < $totalItems; $i++) {
        $totalAmount += ($quantities[$i] * $unitPrices[$i]);
    }

    try {
        // Begin transaction 
        $conn->begin_transaction();

        // Insert into purchase_order table (Initially 'Pending')
        $sql_po = "INSERT INTO purchase_order (SupplierID, OrderDate, TotalAmount, Status) 
                   VALUES (?, CURDATE(), ?, 'Pending')";
        $stmt_po = $conn->prepare($sql_po);
        $stmt_po->bind_param("id", $supplierId, $totalAmount);
        if (!$stmt_po->execute()) {
            throw new Exception("Error creating purchase order: " . $stmt_po->error);
        }
        $poId = $conn->insert_id;

        // Loop through items to insert into purchase_order_items
        for ($i = 0; $i < $totalItems; $i++) {
            $productId = $productIds[$i];
            $quantity = $quantities[$i];
            $unitPrice = $unitPrices[$i];

            $sql_po_item = "INSERT INTO purchase_order_items (POID, ProductID, Quantity, UnitPrice) 
                            VALUES (?, ?, ?, ?)";
            $stmt_po_item = $conn->prepare($sql_po_item);
            $stmt_po_item->bind_param("iiid", $poId, $productId, $quantity, $unitPrice);
            
            if (!$stmt_po_item->execute()) {
                throw new Exception("Error inserting PO Item: " . $stmt_po_item->error);
            }
        }

        // Installment Handling
        $numInstallments = intval($_POST['numInstallments']);
        if ($numInstallments > 0) {

            // Determine split ratio 
            if (isset($_POST['split5050'])) {
                $splitRatio = [50, 50];
            } elseif (isset($_POST['split3070'])) {
                $splitRatio = [30, 70];
            } else { 
                // Default to full payment or handle other scenarios
                $splitRatio = [100];
            } 

            $installmentAmounts = calculateInstallmentAmounts($totalAmount, $splitRatio);

            for ($j = 0; $j < count($splitRatio); $j++) { 
                $dueDate = date('Y-m-d', strtotime("+" . ($j + 1) . " month")); 
                $installmentAmount = $installmentAmounts[$j];
                
                $sql_installment = "INSERT INTO installments (POID, InstallmentAmount, DueDate, Status) 
                                     VALUES (?, ?, ?, 'Pending')"; // Set initial status to Pending
                $stmt_installment = $conn->prepare($sql_installment);
                $stmt_installment->bind_param("ids", $poId, $installmentAmount, $dueDate);
                
                if (!$stmt_installment->execute()) {
                    throw new Exception("Error creating installments: " . $stmt_installment->error);
                }
            }
        } else {
            // Mark the order as paid if no installments
            $sql_update_po = "UPDATE purchase_order SET Status = 'Paid' WHERE POID = ?"; 
            $stmt_update_po = $conn->prepare($sql_update_po); 
            $stmt_update_po->bind_param("i", $poId);
            if (!$stmt_update_po->execute()) {
                throw new Exception("Error updating purchase order status: " . $stmt_update_po->error); 
            }
        }
        
        $conn->commit();
        echo "<div class='alert alert-success'>Purchase Order Created Successfully with ID: " . $poId . "</div>";

    } catch (Exception $e) {
        // Rollback transaction in case of errors
        $conn->rollback(); 
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>"; 
    }
}

// Function to delete a Purchase entry and update the inventory accordingly
function handlePurchaseDelete($conn) {
    $sno = intval($_POST['Sno']);
    $productId = intval($_POST['ProductID']);
    $quantity = intval($_POST['Quantity']);

    try {
        // Begin transaction 
        $conn->begin_transaction(); 

        //Fetch purchase details (you'll need unit price to update inventory)
        $sql_fetch_purchase = "SELECT * FROM purchase WHERE Sno = ?";
        $stmt_fetch_purchase = $conn->prepare($sql_fetch_purchase);
        $stmt_fetch_purchase->bind_param("i", $sno);
        if(!$stmt_fetch_purchase->execute()){
            throw new Exception("Error fetching purchase details: " . $stmt_fetch_purchase->error); 
        }
        $purchase_result = $stmt_fetch_purchase->get_result(); 
        $purchase_data = $purchase_result->fetch_assoc(); 
    
        if(!$purchase_data){
            throw new Exception("Purchase entry not found!");
        }

        //Calculate amount to deduct from inventory 
        $amount = $purchase_data['Quantity'] * $purchase_data['UnitPrice']; 

        // Update Inventory 
        $sql_update_inventory = "UPDATE inventory 
                                   SET Quantity = Quantity - ?, Amount = Amount - ? 
                                   WHERE ProductID = ?";
        $stmt_update_inventory = $conn->prepare($sql_update_inventory); 
        $stmt_update_inventory->bind_param("idi", $purchase_data['Quantity'], $amount, $productId);

        if (!$stmt_update_inventory->execute()) {
            throw new Exception("Error updating inventory: " . $stmt_update_inventory->error); 
        }
    
        // Now delete the purchase record 
        $sql_delete = "DELETE FROM purchase WHERE Sno=?"; 
        $stmt_delete = $conn->prepare($sql_delete); 
        $stmt_delete->bind_param("i", $sno); 
    
        if (!$stmt_delete->execute()) {
            throw new Exception("Error deleting purchase: " . $stmt_delete->error);
        }

        $conn->commit();
        $_SESSION['message'] = 'Purchase deleted successfully!'; 

    } catch (Exception $e) { 
        //Rollback on error 
        $conn->rollback();
        $_SESSION['error'] = 'Error deleting purchase: ' . $e->getMessage();
    }
    
    // Redirect back to the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit(); 
}

// Handle form submission based on type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['purchaseType']) && $_POST['purchaseType'] === 'po') {
        handlePurchaseOrderSubmit($conn); 
    } elseif (isset($_POST['purchaseType']) && $_POST['purchaseType'] === 'direct') {
        handleDirectPurchase($conn);
    } elseif (isset($_POST['deleteButton'])) { 
        handlePurchaseDelete($conn);
    }
}

// Fetch suppliers for dropdowns
$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier");
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
            if (formType === 'direct') { 
                document.getElementById('directPurchaseForm').style.display = 'block'; 
                document.getElementById('purchaseOrderForm').style.display = 'none'; 
            } else if (formType === 'po') {
                document.getElementById('directPurchaseForm').style.display = 'none'; 
                document.getElementById('purchaseOrderForm').style.display = 'block'; 
            } 
        }
        function addProductRow(tableId) { //Takes table ID as a parameter 
            var tableBody = document.getElementById(tableId); // Uses parameter to get the correct tbody
            var rowCount = tableBody.rows.length; 
            var row = tableBody.insertRow(rowCount);
    
            // Product ID (using a datalist for better UX)
            var cell1 = row.insertCell(0); 
            cell1.innerHTML = '<input type="text" class="form-control" name="ProductID[]" list="products" required onchange="updateProductName(this)" >'; //onchange added to dynamically update product name
            var datalist = document.createElement('datalist'); 
            datalist.id = 'products'; 
            <?php 
            // Populate datalist options 
                $products = $conn->query("SELECT ProductID, ProductName FROM inventory"); 
                while ($p = $products->fetch_assoc()) {
                    echo "datalist.innerHTML += '<option value=\"" . $p['ProductID'] . "\" data-product-name=\"" . $p['ProductName'] . "\">" . $p['ProductID'] . " - " . $p['ProductName'] . "</option>';"; // Data attribute for product name 
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

        function updateProductName(inputField) { 
            const selectedOption = inputField.selectedOptions[0]; // Get the selected option element 
            const productName = selectedOption ? selectedOption.dataset.productName : ""; // Get product name from data attribute
            const row = inputField.closest("tr");
            const productNameField = row.querySelector('input[name="ProductName[]"]');
            if (productNameField) {
                productNameField.value = productName;
            } 
        }

        function confirmSubmission(message) { 
            return confirm(message); 
        }
    </script>
</head>
<body> 
    <div class="container mt-4">
    <?php
    // Display message or error after redirect
    if(isset($_SESSION['message'])){ 
        echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>'; 
        unset($_SESSION['message']); //Remove message
    }
    if(isset($_SESSION['error'])){
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']); //Remove error
    }
    ?>

        <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button><br>
        <h2>Manage Purchases</h2>

        <ul class="nav nav-tabs mb-3"> 
            <li class="nav-item">
                <a class="nav-link active" href="#purchaseOrder" data-bs-toggle="tab" onclick="showForm('po')">Purchase Order</a>
            </li> 
            <li class="nav-item"> 
                <a class="nav-link" href="#directPurchase" data-bs-toggle="tab" onclick="showForm('direct')">Direct Purchase</a>
            </li> 
        </ul>

        <div class="tab-content"> 
            <div class="tab-pane active" id="purchaseOrder"> 
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
                        <tbody id="productTableBodyPO"> 
                            <tr> 
                                <td> 
                                    <input type="text" class="form-control" name="ProductID[]" list="products" required onchange="updateProductName(this)">
                                    <datalist id="products"> 
                                    <?php
                                    // Populate datalist options 
                                    $products = $conn->query("SELECT ProductID, ProductName FROM inventory"); 
                                    while ($p = $products->fetch_assoc()) { 
                                        echo "<option value=\"" . $p['ProductID'] . "\" data-product-name=\"" . $p['ProductName'] . "\">" . $p['ProductID'] . " - " . $p['ProductName'] . "</option>"; //Data attribute for product name 
                                    } 
                                    ?> 
                                    </datalist>
                                </td> 
                                <td><input type="number" min="1" class="form-control" name="Quantity[]" required></td> 
                                <td><input type="number" min="0.01" step="0.01" class="form-control" name="UnitPrice[]" required></td>
                            </tr> 
                        </tbody> 
                    </table> 
                    <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addProductRow('productTableBodyPO')">Add Product</button><br>
        
                    <div class="mb-3"> 
                        <label for="numInstallments">Number of Installments:</label>
                        <select class="form-control" id="numInstallments" name="numInstallments" required>
                            <option value="0">Full Payment</option> 
                            <option value="2">2 Installments</option> 
                        </select> 
                    </div>
                    
                    <div class="mb-3"> 
                        <div class="form-check"> 
                            <input class="form-check-input" type="radio" name="split5050" id="split5050" value="5050">
                            <label class="form-check-label" for="split5050"> 
                                50% - 50% Split
                            </label> 
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="split3070" id="split3070" value="3070"> 
                            <label class="form-check-label" for="split3070">
                                30% - 70% Split 
                            </label> 
                        </div>
                    </div>

                    <input type="hidden" name="totalItems" id="totalItemsPO" value="1"> 
                    <button type="submit" class="btn btn-primary">Create Purchase Order</button> 
                </form>
            </div> 
            <div class="tab-pane" id="directPurchase">
                <h3>Create Direct Purchase</h3>
                <form method="post" action="">
                    <input type="hidden" name="purchaseType" value="direct"> 
                    <div class="mb-3"> 
                        <label for="SupplierID">Supplier:</label>
                        <select class="form-control" id="SupplierID" name="SupplierID" required>
                            <option value="">Select Supplier</option> 
                            <?php
                            // Re-fetch suppliers to reset the pointer
                            $suppliers = $conn->query("SELECT SupplierID, SupplierName FROM supplier"); 
                            while ($row = $suppliers->fetch_assoc()) { 
                                echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
                            }
                            ?>
                        </select> 
                    </div>
                    <div class="mb-3"> 
                        <label for="ProductID">Product ID:</label>
                        <input type="text" class="form-control" id="ProductID" name="ProductID" list="productList" required onchange="updateProductName(this)">
                        <datalist id="productList"> 
                        <?php
                            // Populate datalist options 
                            $products = $conn->query("SELECT ProductID, ProductName FROM inventory"); 
                            while ($p = $products->fetch_assoc()) { 
                                echo "<option value=\"" . $p['ProductID'] . "\" data-product-name=\"" . $p['ProductName'] . "\">" . $p['ProductID'] . " - " . $p['ProductName'] . "</option>";
                            }
                        ?> 
                        </datalist> 
                    </div> 
                    <div class="mb-3">
                        <label for="ProductName">Product Name:</label> 
                        <input type="text" class="form-control" id="ProductName" name="ProductName" required readonly> 
                    </div> 
                    <div class="mb-3"> 
                        <label for="Quantity">Quantity:</label>
                        <input type="number" min="1" class="form-control" id="Quantity" name="Quantity" required> 
                    </div> 
                    <div class="mb-3">
                        <label for="UnitPrice">Unit Price:</label>
                        <input type="number" min="0.01" step="0.01" class="form-control" id="UnitPrice" name="UnitPrice" required> 
                    </div> 
                    <div class="mb-3"> 
                        <label for="PurchaseDate">Purchase Date:</label> 
                        <input type="date" class="form-control" id="PurchaseDate" name="PurchaseDate" value="<?php echo date('Y-m-d'); ?>" required> 
                    </div> 
                    <div class="mb-3"> 
                        <label for="Description">Description:</label>
                        <textarea class="form-control" id="Description" name="Description"></textarea>
                    </div> 
                    <button type="submit" class="btn btn-primary">Add Purchase</button>
                </form>
            </div>
        </div>
       


        <hr>
        <h2>Manage Purchases & Purchase Orders</h2>
        <table class="table table-hover table-responsive">
            <thead>
                <tr>
                    <th>Type</th> 
                    <th>ID</th> 
                    <th>Supplier</th>
                    <th>Product</th> 
                    <th>Quantity</th> 
                    <th>Unit Price</th>
                    <th>Total Amount</th> 
                    <th>Purchase Date</th>
                    <th>Status</th> 
                    <th>Actions</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php 
                // Fetch and Display Direct Purchases
                $sql_purchases = "SELECT p.*, s.SupplierName 
                              FROM purchase p 
                              JOIN supplier s ON p.SupplierID = s.SupplierID"; 
                $result_purchases = $conn->query($sql_purchases); 

                while ($purchase = $result_purchases->fetch_assoc()): 
                ?>
                <tr>
                    <td>Direct Purchase</td> 
                    <td><?php echo $purchase['Sno']; ?></td> 
                    <td><?php echo $purchase['SupplierName']; ?></td> 
                    <td><?php echo $purchase['ProductName']; ?> (ID: <?php echo $purchase['ProductID']; ?>)</td> 
                    <td><?php echo $purchase['Quantity']; ?></td>
                    <td><?php echo number_format($purchase['UnitPrice'], 2); ?></td>
                    <td><?php echo number_format($purchase['Amount'], 2); ?></td> 
                    <td><?php echo $purchase['PurchaseDate']; ?></td>
                    <td>N/A</td> 
                    <td>
                        <form action="" method="POST" onsubmit="return confirmSubmission('Are you sure you want to delete this purchase?')"> 
                            <input type="hidden" name="Sno" value="<?php echo $purchase['Sno']; ?>"> 
                            <input type="hidden" name="ProductID" value="<?php echo $purchase['ProductID']; ?>">
                            <input type="hidden" name="Quantity" value="<?php echo $purchase['Quantity']; ?>"> 
                            <button type="submit" name="deleteButton" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php
                // Fetch and Display Purchase Orders 
                $sql_po = "SELECT po.*, s.SupplierName
                           FROM purchase_order po 
                           JOIN supplier s ON po.SupplierID = s.SupplierID";
                $result_po = $conn->query($sql_po);
        
                while ($po = $result_po->fetch_assoc()) : 
                ?> 
                    <tr>
                        <td>Purchase Order</td> 
                        <td><?php echo $po['POID']; ?></td> 
                        <td><?php echo $po['SupplierName']; ?></td> 
                        <td>
                            <?php 
                            // Fetch and display product details for each PO item
                            $sql_po_items = "SELECT poi.*, i.ProductName
                                           FROM purchase_order_items poi
                                           JOIN inventory i ON poi.ProductID = i.ProductID
                                           WHERE poi.POID = ?"; 
                            $stmt_po_items = $conn->prepare($sql_po_items); 
                            $stmt_po_items->bind_param("i", $po['POID']); 
                            $stmt_po_items->execute();
                            $result_po_items = $stmt_po_items->get_result();

                            while ($po_item = $result_po_items->fetch_assoc()) { 
                                echo $po_item['ProductName'] . " (Qty: " . $po_item['Quantity'] . ")<br>";
                            } 
                            ?>
                        </td> 
                        <td>-</td> // Not applicable for the whole PO
                        <td>-</td> // Not applicable for the whole PO
                        <td><?php echo number_format($po['TotalAmount'], 2); ?></td>
                        <td><?php echo $po['OrderDate']; ?></td>
                        <td> 
                            <?php 
                                if ($po['Status'] === 'Paid') { 
                                    echo '<span class="badge bg-success">Paid</span>';
                                } elseif ($po['Status'] === 'Partially Paid') { 
                                    echo '<span class="badge bg-warning text-dark">Partially Paid</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Pending</span>';
                                } 
                            ?> 
                        </td> 
                        <td> 
                            <a href="view_po.php?poid=<?php echo $po['POID']; ?>" class="btn btn-info btn-sm">View</a> 
                            <a href="edit_po.php?poid=<?php echo $po['POID']; ?>" class="btn btn-primary btn-sm">Edit</a> 
                        </td>
                    </tr> 
                <?php endwhile; ?>
            </tbody>
        </table> 

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
    </div> 
</body> 
</html> 