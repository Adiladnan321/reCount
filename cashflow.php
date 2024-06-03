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
        // Database connection (replace with your actual connection details)
        $host = "localhost";
        $username = "root";
        $password = "";
        $dbname = "recount";

        // Create connection
        $conn = new mysqli($host, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Get the month and year from URL or default to current month
        $monthYear = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

        // Fetch sales data for the month
        $sql = $conn->prepare("SELECT SUM(UnitPrice * Quantity) AS revenue FROM sale WHERE DATE_FORMAT(SaleDate, '%Y-%m') = ?");
        $sql->bind_param("s", $monthYear);
        $sql->execute();
        $result = $sql->get_result();
        $revenue = $result->fetch_assoc()['revenue'] ?? 0;

        // Fetch cost price from inventory to calculate Gross Profit
        $sql = $conn->prepare("SELECT SUM((s.UnitPrice - i.UnitPrice) * s.Quantity) AS gp FROM sale s JOIN inventory i ON s.ProductID = i.ProductID WHERE DATE_FORMAT(SaleDate, '%Y-%m') = ?");
        $sql->bind_param("s", $monthYear);
        $sql->execute();
        $result = $sql->get_result();
        $grossProfit = $result->fetch_assoc()['gp'] ?? 0;

        // Fetch expenses for the month (assuming expenses are stored in the cashflow table)
        $sql = $conn->prepare("SELECT expense FROM cashflow WHERE date = ?");
        $sql->bind_param("s", $monthYear);
        $sql->execute();
        $result = $sql->get_result();
        $expenses = $result->fetch_assoc()['expenses'] ?? 0;

        $netCashFlow = $grossProfit - $expenses;

        // Insert or update the cashflow table
        $sql = $conn->prepare("INSERT INTO cashflow (revenue, gp, expense, netflow, date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE revenue = VALUES(revenue), gp = VALUES(gp), expense = VALUES(expense), netflow = VALUES(netflow)");
        $sql->bind_param("dddds", $revenue, $grossProfit, $expenses, $netCashFlow,$monthYear);
        $sql->execute();

        $conn->close();
    ?>
    <div class="cash-flow-container">
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
