<?php
    session_start();
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
require_once '../database.php';
if(isset($_SESSION['Inv'])){
    $InvoiceID=$_SESSION['Inv'];
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
    <button class="btn btn-outline-secondary no-print" onclick="window.location.href='./invoiceHistory.php'"><</button>
    <button class="btn btn-outline-secondary no-print" onclick="window.location.href='../index.php'">🏠</button>
    <br>
    <h1 class="no-print">Invoice</h1>
    <div class="container">
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
                                $sql_sno = "SELECT * FROM invoice WHERE InvoiceID='$InvoiceID'";
                                $result_sno = mysqli_query($conn, $sql_sno);
                                $row = mysqli_fetch_assoc($result_sno);
                                $r1 = $row['InvoiceID'];
                                echo "InvoiceID #" . $r1;
                            ?>
                        </h5>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-xs-6">
                            <address>
                                <strong>Billed To:</strong><br>
                                <?php
                                    echo '<label><input class="form-control" list="customer" name="CustomerID" placeholder="Customer Id" value="'.$row['CustomerID'].'"readonly></label>';
                                ?>
                                <br>
                            </address>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6 text-right">
                            <address>
                                <strong>Order Date:</strong><br>
                                <?php
                                    echo '<input type="date" class="form-control border-0" style="width: 150px;" name="SaleDate" value="'.$row['InvoiceDate'].'"readonly>'
                                ?>
                                <br><br>
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
                                        <?php
                                            $sql_inv="SELECT * FROM invoiceitem WHERE InvoiceID='$InvoiceID'";
                                            $result_inv=mysqli_query($conn,$sql_inv);
                                            while($row_inv=$result_inv->fetch_assoc()){
                                                echo '<tr>';
                                                echo '<td>' . $row_inv['ProductID'] . '</td>';
                                                echo '<td>' . $row_inv['ProductName'] . '</td>';
                                                echo '<td>' . $row_inv['Description'] . '</td>';
                                                echo '<td>' . $row_inv['Quantity'] . '</td>';
                                                echo '<td>' . $row_inv['UnitPrice'] . '</td>';
                                                echo '<td>' . $row_inv['TotalPrice'] . '</td>';
                                                echo '</tr>';
                                            }
                                        ?>
                                        <tr>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line"></td>
                                            <td class="no-line text-center"><strong>Grand Total:</strong></td>
                                            <!-- <td class="no-line text-right"><input type="number" class="form-control border-0" id="total" readonly></td> -->
                                            <?php
                                                echo '<td class="no-line text-right"><input type="number" class="form-control border-0" id="total" value="'.$row['Amount'].'" readonly></td>';
                                                mysqli_close($conn);
                                            ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-primary no-print" onclick="printAndSubmit()">Print</button>
            </div>
        </form>
    </div>
</div>
</body>
<script>
    function printAndSubmit() {
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