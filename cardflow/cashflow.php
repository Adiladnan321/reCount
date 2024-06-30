<?PHP
    session_start();
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
    require_once "../database.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Flow Statement</title>
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
    </style>
</head>
<body>
    <?php

        // Get the fromdate and todate from URL or default to current month
        $fromdate = isset($_GET['fromdate']) ? $_GET['fromdate'] : date('Y-m-01');
        $todate = isset($_GET['todate']) ? $_GET['todate'] : date('Y-m-t');

        // Fetch sales data for the date range
        $sql = $conn->prepare("SELECT SUM(UnitPrice * Quantity) AS revenue FROM sale WHERE SaleDate BETWEEN ? AND ?");
        $sql->bind_param("ss", $fromdate, $todate);
        $sql->execute();
        $result = $sql->get_result();
        $revenue = $result->fetch_assoc()['revenue'] ?? 0;

        // Fetch cost price from inventory to calculate Gross Profit
        $sql = $conn->prepare("SELECT SUM((s.UnitPrice - i.UnitPrice) * s.Quantity) AS gp FROM sale s JOIN inventory i ON s.ProductID = i.ProductID WHERE s.SaleDate BETWEEN ? AND ?");
        $sql->bind_param("ss", $fromdate, $todate);
        $sql->execute();
        $result = $sql->get_result();
        $grossProfit = $result->fetch_assoc()['gp'] ?? 0;

        // Fetch expenses for the date range
        $sql = $conn->prepare("SELECT SUM(vehicle_maintenance+fuel+salary+others) AS expenses FROM expense WHERE date BETWEEN ? AND ?");
        $sql->bind_param("ss", $fromdate, $todate);
        $sql->execute();
        $result = $sql->get_result();
        $expenses = $result->fetch_assoc()['expenses'] ?? 0;

        $netCashFlow = $grossProfit - $expenses;

        $conn->close();
    ?>
    <div class="cash-flow-container">
        <form method="get" action="">
            <table>
                <tr>
                    <th>From: </th>
                    <td><input type="date" class="form-control" name="fromdate" value="<?= htmlspecialchars($fromdate) ?>"></td>
                </tr>
                <tr>
                    <th>To: </th>
                    <td><input type="date" class="form-control" name="todate" value="<?= htmlspecialchars($todate) ?>"></td>
                </tr>
                <tr>
                    <td> </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <input type="submit" value="Submit">
                    </td>
                </tr>
            </table>
        </form>

        <h1>Cash Flow Statement</h1>
        <div class="cash-flow-section">
            <h2>Revenue</h2>
            <div class="item">
                <span class="description">Revenue:</span>
                <span class="amount">QR <?= number_format($revenue, 2); ?></span>
            </div>
        </div>
        <div class="cash-flow-section">
            <h2>Gross Profit</h2>
            <div class="item">
                <span class="description">Gross Profit:</span>
                <span class="amount">QR <?= number_format($grossProfit, 2); ?></span>
            </div>
        </div>
        <div class="cash-flow-section">
            <h2>Expenses</h2>
            <div class="item">
                <span class="description">Expenses:</span>
                <span class="amount">QR <?= number_format($expenses, 2); ?></span>
            </div>
        </div>
        <div class="cash-flow-section">
            <h2>Net Cash Flow</h2>
            <div class="item">
                <span class="description">Net Cash Flow:</span>
                <span class="amount">QR <?= number_format($netCashFlow, 2); ?></span>
            </div>
        </div>
    </div>
</body>
</html>
