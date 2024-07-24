    <?php
    session_start();
    $user = $_SESSION['user_name'];
    if (!isset($_SESSION["user"])) {
        header("Location: login.php");
    }
    require_once '../database.php';

    // // mailing stuff
    // use PHPMailer\PHPMailer\PHPMailer;
    // use PHPMailer\PHPMailer\SMTP;
    // use PHPMailer\PHPMailer\Exception;
    
    // require '../PHPMailer/src/Exception.php';
    // require '../PHPMailer/src/PHPMailer.php';
    // require '../PHPMailer/src/SMTP.php';

    // // mailing stuff ednsd here
    function processSale($conn, $productId, $saleQuantity)
    {
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Lock rows for concurrency
            $query = "SELECT BatchID, Quantity FROM ibatch WHERE ProductID = ? AND Quantity > 0 ORDER BY Date ASC FOR UPDATE";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
        
            $remainingQuantity = $saleQuantity;
        
            while ($row = $result->fetch_assoc()) {
                if ($remainingQuantity <= 0) break;
        
                if ($row['Quantity'] >= $remainingQuantity) {
                    // Update ibatch table
                    $updateQuery = "UPDATE ibatch SET Quantity = Quantity - ? WHERE BatchID = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ii", $remainingQuantity, $row['BatchID']);
                    $updateStmt->execute();
        
                    // Update inventory table
                    $inventoryTableQuery = "UPDATE inventory SET Quantity = Quantity - ? WHERE ProductID = ?";
                    $inventoryUpdateStmt = $conn->prepare($inventoryTableQuery);
                    $inventoryUpdateStmt->bind_param("ii", $remainingQuantity, $productId);
                    $inventoryUpdateStmt->execute();
        
                    $remainingQuantity = 0;
                } else {
                    // Update ibatch table
                    $updateQuery = "UPDATE ibatch SET Quantity = 0 WHERE BatchID = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("i", $row['BatchID']);
                    $updateStmt->execute();
        
                    // Update inventory table
                    $inventoryTableQuery = "UPDATE inventory SET Quantity = Quantity - ? WHERE ProductID = ?";
                    $inventoryUpdateStmt = $conn->prepare($inventoryTableQuery);
                    $inventoryUpdateStmt->bind_param("ii", $row['Quantity'], $productId);
                    $inventoryUpdateStmt->execute();
        
                    $remainingQuantity -= $row['Quantity'];
                }
            }
        
            if ($remainingQuantity > 0) {
                throw new Exception("Not enough inventory to fulfill the sale of Product ID: " . $productId);
            }

            // Commit the transaction
            $conn->commit();
        //      // After successful invoice creation, fetch invoice details for email
        // $invoiceId = $conn->insert_id; // Get the last inserted invoice ID
        // $sql = "SELECT * FROM invoiceitem WHERE InvoiceID = $invoiceId";
        // $result = $conn->query($sql);
        // $invoiceItems = '';

        // while ($row = $result->fetch_assoc()) {
        //     $invoiceItems .= "<tr>
        //                         <td>{$row['ProductName']}</td>
        //                         <td>{$row['Description']}</td>
        //                         <td>{$row['Quantity']}</td>
        //                         <td>{$row['UnitPrice']}</td>
        //                         <td>{$row['TotalPrice']}</td>
        //                       </tr>";
        // }

        // // ... (Fetch other necessary invoice details like CustomerName, InvoiceDate, etc.) ...

        // // Send email
        // $mail = new PHPMailer(true);

        // try {
        //     //Server settings
        //     $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output for troubleshooting
        //     $mail->isSMTP();
        //     $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server (e.g., 'smtp.gmail.com')
        //     $mail->SMTPAuth   = true;
        //     $mail->Username   = 'your_email@gmail.com'; // Set your email address
        //     $mail->Password   = 'your_email_password'; // Set your email password
        //     $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        //     $mail->Port       = 465; // For TLS/STARTTLS, typically 587; for SSL, typically 465

        //     //Recipients
        //     $mail->setFrom('your_email@gmail.com', 'Your Name'); // Set your sender email and name
        //     $mail->addAddress('recipient_email@example.com'); // Set recipient email address

        //     //Content
        //     $mail->isHTML(true); 
        //     $mail->Subject = 'Your Invoice';
        //     $mail->Body    = "
        //         <html>
        //         <head>
        //             <title>Invoice</title>
        //         </head>
        //         <body>
        //             <h1>Invoice</h1>
        //             <p>Dear [CustomerName],</p>
        //             <p>Thank you for your order. Please find your invoice details below:</p>
        //             <table>
        //                 <thead>
        //                     <tr>
        //                         <th>Product</th>
        //                         <th>Description</th>
        //                         <th>Quantity</th>
        //                         <th>Unit Price</th>
        //                         <th>Total Price</th>
        //                     </tr>
        //                 </thead>
        //                 <tbody>
        //                     $invoiceItems
        //                 </tbody>
        //             </table>
        //             <p><strong>Invoice Date:</strong> [InvoiceDate]</p>
        //             <p><strong>Total Amount:</strong> [totalAmount]</p>
        //             <p>Thank you for your business!</p>
        //         </body>
        //         </html>
        //     "; // Replace placeholders with actual invoice data

        //     $mail->send();
        //     echo 'Message has been sent';
        // } catch (Exception $e) {
        //     echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        // }

        // // Redirect after successful email sending
        // header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
        // exit();

    } catch (Exception $e) {
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            throw $e;
        } finally {
            // Close statements
            $stmt->close();
            if (isset($updateStmt)) {
                $updateStmt->close();
            }
            if (isset($inventoryUpdateStmt)) {
                $inventoryUpdateStmt->close();
            }
        }
    }


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
            $Descriptions = $_POST['Description'];
            $paymentMethod = $_POST['paymentMethod'];
            $totalAmount = 0;

            // Check inventory and process sales for each product
            for ($i = 0; $i < count($productIds); $i++) {
                $productId = $productIds[$i];
                $quantity = $quantitys[$i];
                processSale($conn, $productId, $quantity); // This will throw an exception if not enough inventory
            }

            // Prepare statement for sales records
            $stmt = $conn->prepare("INSERT INTO sale (ProductID, ProductName, CustomerID, Description, Quantity, UnitPrice, Amount, SaleDate, modifiedBy, paymentMethod) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($productIds); $i++) {
                $productId = $productIds[$i];
                $productName = $productNames[$i];
                $quantity = $quantitys[$i];
                $unitPrice = $unitPrices[$i];
                $amount = $quantity * $unitPrice;
                $Description = $Descriptions[$i]; 
                $totalAmount += $amount;

                $stmt->bind_param("isisiddsss", $productId, $productName, $CustomerID, $Description, $quantity, $unitPrice, $amount, $SaleDate, $user, $paymentMethod);
                $stmt->execute();
                $sql = "SELECT COUNT(*) as rowCount FROM ibatch WHERE ProductID='$productId'";
                $countResult = $conn->query($sql);
                $row = $countResult->fetch_assoc();

                if ($row['rowCount'] > 1) {
                    $sql = "SELECT * FROM ibatch WHERE ProductID='$productId' AND quantity=0";
                    $result = $conn->query($sql);

                    while ($row1 = $result->fetch_assoc()) {
                        $batchID = $row1['BatchID'];
                        $sql_drow = "DELETE FROM ibatch WHERE BatchID='$batchID'";
                        $conn->query($sql_drow);
                    }
                }
            }
            
            $stmt->close();

            // Insert invoice record
            $stmt2 = $conn->prepare("INSERT INTO invoice (CustomerID, InvoiceDate, Amount, modifiedBy, paymentMethod) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("isdss", $CustomerID, $SaleDate, $totalAmount, $user, $paymentMethod);
            $stmt2->execute();
            $invoiceId = $stmt2->insert_id; // Get the last inserted invoice ID
            $stmt2->close();

            // Insert invoice items
            $stmt1 = $conn->prepare("INSERT INTO invoiceitem (InvoiceID, ProductID, ProductName, Description, Quantity, UnitPrice, TotalPrice) VALUES (?, ?, ?, ?, ?, ?, ?)");
            for ($j = 0; $j < count($productIds); $j++) {
                $productId = $productIds[$j];
                $productName = $productNames[$j];
                $quantity = $quantitys[$j];
                $unitPrice = $unitPrices[$j];
                $Description = $Descriptions[$j];
                $amount = $quantity * $unitPrice;

                $stmt1->bind_param("iissidd", $invoiceId, $productId, $productName, $Description, $quantity, $unitPrice, $amount);
                $stmt1->execute();
            }
            $stmt1->close();


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
        <link rel="stylesheet" href="../styles.css">
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

            @media (min-width:1000px) {
                
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 5vh;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .ff{
                    background-color: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                }
            }
        </style>
    </head>
    <body>
    <div class="container ff">
        <br>
        <button class="btn btn-outline-secondary no-print" onclick="window.location.href='../index.php'"><</button>
        <br>
        <h1 class="no-print">Invoice</h1>
        <div class="container">
            <form action="index.php" method="POST" name="invoiceForm">
                <figure>
                    <img src="../assets/SRC.png" class="src"/>
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
                            <div class="col-xs-6">
                                <address>
                                    
                                    <!-- card -->
                                    <!-- <input type="list" class="form-control" name> -->
                                    <strong>Payment Method:</strong><br>
                                    <select id="pay" class="form-control" name="paymentMethod" style="width: 210px;">
                                        <option value="bank">Bank</option>
                                        <option value="cash">Cash</option>
                                    </select>
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
                                                    <input type="text" class="form-control border-0" name="ProductName[]" placeholder="Eg: Chalk" required>
                                                </td>
                                                <td class="text-center">
                                                    <!-- Description -->
                                                    <textarea type="text" class="form-control border-0" name="Description[]" placeholder="Description" required></textarea>
                                                </td>
                                                <td class="text-center">
                                                    <!-- Unit Price -->
                                                    <input type="number" class="form-control border-0" name="UnitPrice[]" placeholder="Unit Price" required min="0" step="0.01">
                                                </td>
                                                <td class="text-center">
                                                    <!-- Quantity -->
                                                    <input type="number" class="form-control border-0" name="Quantity[]" placeholder="Quantity" required min ="0" step="0.01">
                                                </td>
                                                <td class="text-right">
                                                    <!-- Amount -->
                                                    <input type="text" name="Amount0" id="amt0" class="form-control border-0" readonly step="0.01">
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
                                                <td class="no-line text-center"><strong>Grand Total:</strong></td>
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
            window.print();
            document.getElementById("invoiceForm").submit();
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