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
        $revenue = [
            "Revenue" => 5000
        ];

        $gp = [
            "Gross Profit" => 500
        ];

        $expenses = [
            "Rent" => 800,
            "Salary" => 3300,
            "Car" => 500,
            "Utilities" => 200
        ];

        $totalRevenue = array_sum($revenue);
        $totalGp = array_sum($gp);
        $totalExpenses = array_sum($expenses);
        $netCashFlow = $totalGp - $totalExpenses;
    ?>
    <div class="cash-flow-container">
        <h1>Cash Flow Statement</h1>
        <div class="cash-flow-section">
            <h2>Revenue</h2>
            <?php foreach ($revenue as $description => $amount): ?>
                <div class="item">
                    <span class="description"><?= $description; ?>:</span>
                    <span class="amount">QR <?= number_format($amount, 2); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="cash-flow-section">
            <h2>Gross Profit</h2>
            <?php foreach ($gp as $description => $amount): ?>
                <div class="item">
                    <span class="description"><?= $description; ?>:</span>
                    <span class="amount">QR <?= number_format($amount, 2); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="cash-flow-section">
            <h2>Expenses</h2>
            <?php foreach ($expenses as $description => $amount): ?>
                <div class="item">
                    <span class="description"><?= $description; ?>:</span>
                    <span class="amount">QR <?= number_format($amount); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="cash-flow-section">
            <h2>Net Cash Flow</h2>
            <div class="item">
                <span class="description">Total Income:</span>
                <span class="amount">QR <?= number_format($totalGp, 2); ?></span>
            </div>
            <div class="item">
                <span class="description">Total Expenses:</span>
                <span class="amount">QR <?= number_format($totalExpenses, 2); ?></span>
            </div>
            <div class="item">
                <span class="description">Net Cash Flow:</span>
                <span class="amount">QR <?= number_format($netCashFlow, 2); ?></span>
            </div>
        </div>
    </div>
</body>
</html>
