<?php 
    require_once 'database.php';
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Invoice</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1></h1>
    <?php

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieving form data
        $productIds = $_POST['ProductID'];
        $productNames = $_POST['ProductName'];
        $CustomerID = $_POST['CustomerID'];
        $quantitys = $_POST['Quantity'];
        $unitPrices = $_POST['UnitPrice'];
        $SaleDate = $_POST['SaleDate'];
        $tt=0;

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert invoice
            $stmt = $conn->prepare("INSERT INTO invoice (CustomerID, InvoiceDate, Amount) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $CustomerID, $SaleDate, $tt);
            $stmt->execute();
            $invoiceID = $stmt->insert_id;
            $stmt->close();

            // Insert sale items
            $stmt = $conn->prepare("INSERT INTO sale (ProductID, ProductName, CustomerID, Description, Quantity, UnitPrice, Amount, SaleDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($productIds); $i++) {
                $productId = $productIds[$i];
                $productName = $productNames[$i];
                $quantity = $quantitys[$i];
                $unitPrice = $unitPrices[$i];
                $amount = $quantity * $unitPrice;
                
                $description = 'Desc'; // Assuming a static description as per the original code
                $tt += $amount;

                $stmt->bind_param("isisidds", $productId, $productName, $CustomerID, $description, $quantity, $unitPrice, $amount, $SaleDate);
                $stmt->execute();
            }
            $stmt->close();

            // Update invoice with the total amount
            $stmt2 = $conn->prepare("UPDATE invoice SET Amount = ? WHERE InvoiceID = ?");
            $stmt2->bind_param("di", $tt, $invoiceID);
            $stmt2->execute();
            $stmt2->close();

            // Insert invoice items
            $stmt1 = $conn->prepare("INSERT INTO invoiceitem (InvoiceID, ProductID, Quantity, UnitPrice, TotalPrice) VALUES (?, ?, ?, ?, ?)");
            for ($j = 0; $j < count($productIds); $j++) {
                $productId = $productIds[$j];
                $quantity = $quantitys[$j];
                $unitPrice = $unitPrices[$j];
                $amount = $quantity * $unitPrice;

                $stmt1->bind_param("iiidd", $invoiceID, $productId, $quantity, $unitPrice, $amount);
                $stmt1->execute();
            }
            $stmt1->close();

            // Commit transaction
            $conn->commit();

            // Redirect to the same page with a success message
            header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "Failed to create invoice: " . $e->getMessage();
        }
    }
    ?>

    <br><br>
    <div class="container">
        <form action="invoice.php" method="POST">
            <div class="row">
                <div class="col-xs-12">
                    <div class="invoice-title">
                        <h2>Invoice</h2>
                        <h3 class="pull-right">
                            <?php
                                $sql_sno = "SELECT MAX(InvoiceID) AS max_sno FROM invoice";
                                $result_sno = mysqli_query($conn, $sql_sno);
                                $row = mysqli_fetch_assoc($result_sno);
                                $r1 = $row['max_sno'] + 1;
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
                                                <!-- Unit Price -->
                                                <input type="number" class="form-control border-0" name="UnitPrice[]" placeholder="Unit Price">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" class="form-control border-0" name="Quantity[]" placeholder="Quantity">
                                            </td>
                                            <td class="text-right">
                                                <input type="text" name="Amount[]" class="form-control border-0" readonly>
                                            </td>
                                        </tr>
                                        <tr class="hh">
                                            <td class="thick-line"><button type="button" class="btt" onclick="addRow()">+</button></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line text-center"></td>
                                            <td class="thick-line text-right"></td>
                                        </tr>
                                        <tr>
                                            <td class="thick-line"></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line text-center"><strong>Subtotal</strong></td>
                                            <td class="thick-line text-right">$670.99</td>
                                        </tr>
                                        <tr>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line text-center"><strong>Shipping</strong></td>
                                            <td class="no-line text-right">$15</td>
                                        </tr>
                                        <tr>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line text-center"><strong>Total</strong></td>
                                            <td class="no-line text-right">$685.99</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>
</body>
<script>
    function addRow() {
        const table = document.getElementById("dynamicTable");
        const rowCount = table.rows.length;
        const columnCount = table.rows[0].cells.length;

        const newRow = table.insertRow(rowCount - 4); // Append to the end of the table
        let newCell = newRow.insertCell(-1);
        newCell.innerHTML = `<input type="text" class="form-control border-0" name="ProductID[]" placeholder="Product Id" list="ProductID" required><datalist id="ProductID"><?php $sql_data="SELECT * FROM inventory"; $result_data=mysqli_query($conn,$sql_data);while($row=mysqli_fetch_assoc($result_data)){echo "<option value='".$row['ProductID']."'>".$row['ProductID']."</option>";}?></datalist>`;
        newCell = newRow.insertCell(-1);
        newCell.innerHTML = `<input type="text" name="ProductName[]" class="form-control border-0" placeholder="Eg: Chalk"/>`;
        newCell = newRow.insertCell(-1);
        newCell.innerHTML = `<input type="number" name="UnitPrice[]" class="form-control border-0" placeholder="Unit Price"/>`;
        newCell = newRow.insertCell(-1);
        newCell.innerHTML = `<input type="number" name="Quantity[]" class="form-control border-0" placeholder="Quantity"/>`;
        newCell = newRow.insertCell(-1);
        newCell.innerHTML = `<input type="number" name="Amount[]" class="form-control border-0" readonly/>`;
    }
</script>
</html>
