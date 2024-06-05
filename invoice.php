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
        $stmt1 = $conn->prepare("INSERT INTO invoiceitem (InvoiceID, ProductID, ProductName,Description, Quantity, UnitPrice, TotalPrice) VALUES (?, ?, ?, ?, ?, ?, ?)");
        for ($j = 0; $j < count($productIds); $j++) {
            $productId = $productIds[$j];
            $quantity = $quantitys[$j];
            $unitPrice = $unitPrices[$j];
            $productName = $productNames[$i];
            $Description = $Descriptions[$i];
            $amount = $quantity * $unitPrice;

            $stmt1->bind_param("iissidd", $invoiceId, $productId,$productName,$Description, $quantity, $unitPrice, $amount);
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
        .thick-line {
            border-bottom: 1px solid black;
        }
        .fig{
            line-height: 0px;
            color: grey;
            padding-left: 10px;
        }
        @media print {
            .no-print {
                display: none;
            }
            .pp {
                display: inline;
            }
            img {
                display: none;
            }
            .print-line {
                border-bottom: 1px solid black;
            }
            .src{
                display: inline;
                margin-top: 0px;
                padding-bottom: 10px;
                width: 200px;
            }
        }
        @media (max-width:576px){
            .mobile-card{
                width: 700px;
            }
        }
    </style>
</head>
<body>
<div class="container pp">
    <br>
    <button class="btn btn-outline-secondary no-print" onclick="window.location.href='./index.php'"><</button>
    <br>
    <h1 class="no-print">Invoice</h1>
    <div class="container pp">
        <form action="invoice.php" method="POST" name="invoiceForm">
            <figure>
                <img src="./SRC.png" class="src"/>
                <figcaption class="fig">tel:44553055</figcaption>
            </figure>
            <div class="row">
                <div class="col-xs-12"><br>
                    <div class="invoice-title">
                        <h3><center>Invoice</center></h3>
                        <h5 class="pull-right">
                            <?php
                                $sql_sno = "SELECT MAX(InvoiceID) AS max_InvoiceID FROM invoice";
                                $result_sno = mysqli_query($conn, $sql_sno);
                                $row = mysqli_fetch_assoc($result_sno);
                                $r1 = $row['max_InvoiceID'] + 1;
                                echo "InvoiceID #" . $r1;
                            ?>
                        </h5>
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
                                            echo "<option value='" . $row['CustomerID'] . "'>" . $row['CustomerName'] . "</option>";
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
                                <table class="table table-condensed mobile-card" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <td><strong>Id</strong></td>
                                            <td><strong>Item</strong></td>
                                            <td><strong>Description</strong></td>
                                            <td><strong>Price</strong></td>
                                            <td><strong>Quantity</strong></td>
                                            <td class="text-right"><strong>Total</strong></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="print-line">
                                            <td>
                                                <!-- Product ID -->
                                                <input type="text" class="form-control border-0" name="ProductID[]" placeholder="Product Id" list="ProductID" required onchange="updateProductName(this)">
                                                <datalist id="ProductID">
                                                    <?php
                                                        $sql_data = "SELECT * FROM inventory";
                                                        $result_data = mysqli_query($conn, $sql_data);
                                                        while ($row = mysqli_fetch_assoc($result_data)) {
                                                            echo "<option value='" . $row['ProductID'] . "'>" . $row['ProductName'] . "</option>";
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
                                                <input type="text" name="Amount0" id="amt0" class="form-control border-0" readonly>
                                            </td>
                                        </tr>
                                        <tr class="no-print">
                                            <td class="thick-line"><button type="button" class="btt border-0  no-print" onclick="addRow()">+</button></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line"></td>
                                            <td class="thick-line text-center"></td>
                                            <td class="thick-line text-right"></td>
                                        </tr>
                                        <tr>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line text-center"><strong>Total:</strong></td>
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
        <div><br><button type="button" class="btn btn-outline-primary no-print" onclick="window.location.href='./invoiceHistory.php'">Invoice History</button></div>
    </div>
</div>
</body>
<script>
    const productData = <?php
        $products = [];
        $sql_data = "SELECT * FROM inventory";
        $result_data = mysqli_query($conn, $sql_data);
        while ($row = mysqli_fetch_assoc($result_data)) {
            $products[$row['ProductID']] = $row['ProductName'];
        }
        echo json_encode($products);
    ?>;

    function addRow() {
        const table = document.getElementById("dynamicTable");
        const rowCount = table.rows.length;
        const newRow = table.insertRow(rowCount - 2); // Append before the subtotal row
        const amountIndex = rowCount - 3;
        newRow.innerHTML = `
            <td>
                <input type="text" class="form-control border-0" name="ProductID[]" placeholder="Product Id" list="ProductID" required onchange="updateProductName(this)">
                <datalist id="ProductID"><?php $sql_data="SELECT * FROM inventory"; $result_data=mysqli_query($conn,$sql_data);while($row=mysqli_fetch_assoc($result_data)){echo "<option value='".$row['ProductID']."'>".$row['ProductName']."</option>";}?></datalist>
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
                <input type="number" name="Amount[]" id="amt${amountIndex}" class="form-control border-0" readonly/>
            </td>
        `;
    }

    function updateProductName(element) {
        const productId = element.value;
        const productName = productData[productId] || "";
        const row = element.closest("tr");
        const productNameField = row.querySelector('input[name="ProductName[]"]');
        productNameField.value = productName;
    }

    function printAndSubmit() {
        document.getElementById("invoiceForm").submit();
        window.print();
    }

    function calculateTotal() {
        const table = document.getElementById("dynamicTable");
        const rows = table.rows;
        let total = 0;

        for (let i = 1; i < rows.length - 2; i++) {
            const quantity = parseFloat(rows[i].cells[4].getElementsByTagName("input")[0].value) || 0;
            const unitPrice = parseFloat(rows[i].cells[3].getElementsByTagName("input")[0].value) || 0;
            const amount = quantity * unitPrice;
            const j = rows.length - 4;
            const a = "amt" + j;
            document.getElementById(a).value = amount;
            total += amount;
        }
        document.getElementById("total").value = total;
    }

    document.getElementById("dynamicTable").addEventListener("focusout", calculateTotal);
    document.getElementById("dynamicTable").addEventListener("click", calculateTotal);
</script>
</html>