<?php
    //session_start();
    require_once 'database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Flow Statement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .cash-flow-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        .cash-flow-container h1 {
            text-align: center;
            color: #333;
        }

        .cash-flow-section {
            margin-bottom: 20px;
        }

        .cash-flow-section h2 {
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .item .description {
            color: #666;
        }

        .item .amount {
            font-weight: bold;
        }
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
<body>
    <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'expenses.php'"><br><br><br>
                <center><h1 style="font-size: 100px;">✍️</h1></center>
        <div class="card-body"><br>
            <center><h5 class="card-title">Expenses</h5></center>
        </div>
    </div>
    <div class="card shadow p-3 border-0 mobile-card" style="width: 18rem;" onclick="window.location.href = 'cashflow.php'"><br><br><br>
                <center><h1 style="font-size: 100px;">💹</h1></center>
        <div class="card-body"><br>
            <center><h5 class="card-title">Cash Flow</h5></center>
        </div>
    </div>

</body>