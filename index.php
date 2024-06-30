<?php 
    session_start();
    require_once 'database.php';
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>reCount</title>
    <style>
        @media(min-width: 577px){
            .desk-card {
                margin-top: 18rem;
            }
            .mobile-card:hover{
                background-color: #fafafa;
            }
        }
        @media (max-width: 576px) {
            .mobile-card {
                width: 100% !important;
                margin-bottom: 1rem;
            }

            .mobile-card h1 {
                font-size: 2.5rem;
            }

            .mobile-card .card-title {
                font-size: 1.2rem;
            }

            .container img {
                width: 100%;
            }

            .row {
                gap: 1rem;
            }

            body {
                padding: 0.5rem;
            }
            .desk-card{
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body style="background-image: url(./logooffstr.png); background-repeat:no-repeat; background-size:100%">
    <!-- <img src="./logooffstr.png" style="width: 100%;"><br><br> -->
    <div class="container desk-card"><br>
    <br>
    <form method="post" action="export.php">
        <button type="button" class="btn btn-outline-dark" onclick="window.location.href='./logout.php'">Logout</button>
        <button type="submit" name="export" class="btn btn-dark dd">Export Database</button>
        <?php 
            $sql2="SELECT * FROM sale";
            $res=mysqli_query($conn,$sql2);
            $bb=0;
            $cb=0;
            while($row2=mysqli_fetch_assoc($res)){
                if($row2['paymentMethod']=='bank'){
                    $bb=$bb+$row2['Amount'];
                }else if($row2['paymentMethod']=='cash'){
                    $cb=$cb+$row2['Amount'];
                }
            }
            echo "<button class='btn btn-outline-dark'>Bank Balance:".$bb."</button>";
            echo "<button class='btn btn-outline-dark'>Cash Balance:".$cb."</button>";
        ?>
    </form><br>
        <div class="row gap-5">
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'inventory'"><br><br>
                <center><h1 style="font-size: 70px;">📦  💰</h1></center>
                <center><h1 style="font-size: 70px;">📈</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Inventory</h5></center>
                    <center><h5 class="card-title">
                        <?php 
                        // session_start();
                        // require_once 'database.php';
                        $sql_totalVal = "SELECT SUM(Amount) AS TotalValue FROM inventory";
                        $resultVal = $conn->query($sql_totalVal);
                        $totalVal = $resultVal->fetch_assoc();
                        echo "<div>Total: QR ". number_format($totalVal['TotalValue']) ."</div>";
                        ?></h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'purchase'"><br><br><br>
                <center><h1 style="font-size: 100px;">🛒</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Purchase</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'sell'"><br><br><br>
                <center><h1 style="font-size: 100px;">🏷️</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Sale</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'supplier'"><br><br><br>
                <center><h1 style="font-size: 100px;">🚛</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Supplier</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'customer'"><br><br><br>
                <center><h1 style="font-size: 100px;">👨🏻</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Customer</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'invoice'"><br><br><br>
                <center><h1 style="font-size: 100px;">📋</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Invoice</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'cardflow'"><br><br><br>
                <center><h1 style="font-size: 100px;">💸</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Cash Flow</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'credit'"><br><br><br>
                <center><h1 style="font-size: 100px;">💳</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Credit</h5></center>
                </div>
            </div>
            <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'reports'"><br><br><br>
                <center><h1 style="font-size: 100px;">📑</h1></center>
                <div class="card-body"><br>
                    <center><h5 class="card-title">Reports</h5></center>
                </div>
            </div>
        </div><br><br>
    </div>
    <footer style="background-color:grey; height:300px">
    </footer>
</body>
</html>