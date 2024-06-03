<?php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Retrieving form data
        $productIds = $_POST['ProductID'];
        $productNames = $_POST['ProductName'];
        $CustomerID = $_POST['CustomerID'];
        $quantitys = $_POST['Quantity'];
        $unitPrices = $_POST['UnitPrice'];
        $SaleDate = $_POST['SaleDate'];
        $Descriptions=$_POST['Description'];
        $totalAmount = 0;

        // Insert sale records and calculate total amount
        $stmt = $conn->prepare("INSERT INTO sale (ProductID, ProductName, CustomerID, Description, Quantity, UnitPrice, Amount, SaleDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < count($productIds); $i++) {
            $productId = $productIds[$i];
            $productName = $productNames[$i];
            $quantity = $quantitys[$i];
            $unitPrice = $unitPrices[$i];
            $amount = $quantity * $unitPrice;

            $Description = $Descriptions[$i]; // Static description as per original code
            $totalAmount += $amount;
            
            $stmt->bind_param("isisidds", $productId, $productName, $CustomerID, $Description, $quantity, $unitPrice, $amount, $SaleDate);
            $stmt->execute();
        }
        $stmt->close();

        // Insert invoice record
        $stmt2 = $conn->prepare("INSERT INTO invoice (CustomerID, InvoiceDate, Amount) VALUES (?, ?, ?)");
        $stmt2->bind_param("isd", $CustomerID, $SaleDate, $totalAmount);
        $stmt2->execute();
        $invoiceId = $stmt2->insert_id; // Get the last inserted invoice ID
        $stmt2->close();

        // Insert invoice items
        $stmt1 = $conn->prepare("INSERT INTO invoiceitem (InvoiceID, ProductID, Quantity, UnitPrice, TotalPrice) VALUES (?, ?, ?, ?, ?)");
        for ($j = 0; $j < count($productIds); $j++) {
            $productId = $productIds[$j];
            $quantity = $quantitys[$j];
            $unitPrice = $unitPrices[$j];
            $amount = $quantity * $unitPrice;

            $stmt1->bind_param("iiidd", $invoiceId, $productId, $quantity, $unitPrice, $amount);
            $stmt1->execute();
        }
        $stmt1->close();

        // Update inventory
        $stmt_update_inventory = $conn->prepare("UPDATE inventory SET Quantity = Quantity - ?, Amount = Amount - ? WHERE ProductID = ?");
        for ($k = 0; $k < count($productIds); $k++) {
            $productId = $productIds[$k];
            $quantity = $quantitys[$k];
            $amount = $quantity * $unitPrices[$k];

            $stmt_update_inventory->bind_param("idi", $quantity, $amount, $productId);
            $stmt_update_inventory->execute();
        }
        $stmt_update_inventory->close();

        // Commit transaction
        $conn->commit();

        // Redirect to the same page to avoid form resubmission
        header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Failed to create invoice: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Invoice</title>
    <style>
        @media print{
            .no-print{
                display: none;
            }
            .pp{
                display: inline;
            }
        }
    </style>
</head>
<body>
<div class="container pp">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1>Invoice</h1>
    <div class="container pp">
        <form action="invoice.php" method="POST" name="invoiceForm">
            <div class="row">
                <div class="col-xs-12">
                    <div class="invoice-title">
                        <h2>Invoice</h2>
                        <h3 class="pull-right">
                            <?php
                                $sql_sno = "SELECT MAX(InvoiceID) AS max_InvoiceID FROM invoice";
                                $result_sno = mysqli_query($conn, $sql_sno);
                                $row = mysqli_fetch_assoc($result_sno);
                                $r1 = $row['max_InvoiceID'] + 1;
                                echo "Order #" . $r1;
                            ?>
                        </h3>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-xs-6">
                            <address>
                                <strong>Billed To:</strong><br>
                                <label><input class="form-control" list="customer" name="CustomerID" placeholder="Customer Id"></label>
                                <datalist id="customer">
                                    <?php
                                        $sql_data = "SELECT * FROM customer";
                                        $result_data = mysqli_query($conn, $sql_data);
                                        while ($row = mysqli_fetch_assoc($result_data)) {
                                            echo "<option value='" . $row['CustomerID'] . "'>" . $row['CustomerID'] . "</option>";
                                        }
                                    ?>
                                </datalist><br>
                            </address>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6 text-right">
                            <address>
                                <strong>Order Date:</strong><br>
                                <input type="date" class="form-control border-0" style="width: 150px;" name="SaleDate"><br><br>
                            </address>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><strong>Order summary</strong></h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-condensed" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <td><strong>Id</strong></td>
                                            <td><strong>Item</strong></td>
                                            <td><strong>Description</strong></td>
                                            <td><strong>Price</strong></td>
                                            <td><strong>Quantity</strong></td>
                                            <td class="text-right"><strong>Totals</strong></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <!-- Product ID -->
                                                <input type="text" class="form-control border-0" name="ProductID[]" placeholder="Product Id" list="ProductID" required>
                                                <datalist id="ProductID">
                                                    <?php
                                                        $sql_data = "SELECT * FROM inventory";
                                                        $result_data = mysqli_query($conn, $sql_data);
                                                        while ($row = mysqli_fetch_assoc($result_data)) {
                                                            echo "<option value='" . $row['ProductID'] . "'>" . $row['ProductID'] . "</option>";
                                                        }
                                                    ?>
                                                </datalist>
                                            </td>
                                            <td class="text-center">
                                                <!-- Product Name -->
                                                <input type="text" class="form-control border-0" name="ProductName[]" placeholder="Eg: Chalk">
                                            </td>
                                            <td class="text-center">
                                                <!-- Description -->
                                                <textarea type="text" class="form-control border-0" name="Description[]" placeholder="Description"></textarea>
                                            </td>
                                            <td class="text-center">
                                                <!-- Unit Price -->
                                                <input type="number" class="form-control border-0" name="UnitPrice[]" placeholder="Unit Price">
                                            </td>
                                            <td class="text-center">
                                                <!-- Quantity -->
                                                <input type="number" class="form-control border-0" name="Quantity[]" placeholder="Quantity">
                                            </td>
                                            <td class="text-right">
                                                <!-- Amount -->
                                                <input type="text" name="Amount[]" id="amt[]" class="form-control border-0" readonly>
                                            </td>
                                        </tr>
                                        <tr class="hh">
                                            <td class="thick-line"><button type="button" class="btt  no-print" onclick="addRow()">+</button></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line text-center"></td>
                                            <td class="thick-line text-right"></td>
                                        </tr>
                                        <tr>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line text-center"><strong>Total</strong></td>
                                            <td class="no-line text-right"><input type="number" class="form-control border-0" id="total" readonly></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <button type="submit" class="btn btn-primary no-print" onclick="printAndSubmit()">Print</button>
            </div>
        </form>
    </div>
</div>
</body>
<script>
    function addRow() {
        const table = document.getElementById("dynamicTable");
        const rowCount = table.rows.length;
        const newRow = table.insertRow(rowCount - 2); // Append before the subtotal row
        newRow.innerHTML = `
            <td>
                <input type="text" class="form-control border-0" name="ProductID[]" placeholder="Product Id" list="ProductID" required>
                <datalist id="ProductID"><?php $sql_data="SELECT * FROM inventory"; $result_data=mysqli_query($conn,$sql_data);while($row=mysqli_fetch_assoc($result_data)){echo "<option value='".$row['ProductID']."'>".$row['ProductID']."</option>";}?></datalist>
            </td>
            <td class="text-center">
                <input type="text" name="ProductName[]" class="form-control border-0" placeholder="Eg: Chalk"/>
            </td>
            <td class="text-center">
                <textarea type="text" name="Description[]" class="form-control border-0" placeholder="Description"></textarea>
            </td>
            <td class="text-center">
                <input type="number" name="UnitPrice[]" class="form-control border-0" placeholder="Unit Price"/>
            </td>
            <td class="text-center">
                <input type="number" name="Quantity[]" class="form-control border-0" placeholder="Quantity"/>
            </td>
            <td class="text-right">
                <input type="number" name="Amount[]" id="amt[]" class="form-control border-0" readonly/>
            </td>
        `;
    }
    function printAndSubmit() {
        // Print the page

        // document.getElementById("total");
        document.getElementById("invoiceForm").submit();
        
        // Submit the form
        window.print();
    }
    // Calculate total value and update the total cell
    function calculateTotal() {
        var table = document.getElementById("dynamicTable");
        var rows = table.rows;
        var total = 0;
        
        for (var i = 1; i < rows.length - 2; i++) {
            var quantity = parseFloat(rows[i].cells[4].getElementsByTagName("input")[0].value);
            var unitPrice = parseFloat(rows[i].cells[3].getElementsByTagName("input")[0].value);
            var amount = quantity * unitPrice;
            total += amount;
        }
        document.getElementById("total").value = total;
    }

    
    // Call calculateTotal() whenever a change occurs in the table
    document.getElementById("dynamicTable").addEventListener("focusout", calculateTotal);
    document.getElementById("dynamicTable").addEventListener("click", calculateTotal);    

</script>
</html>
