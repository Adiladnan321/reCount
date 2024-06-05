<?PHP
    session_start();
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
    require_once "database.php";
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
            align-items: flex-start;
            height: 100vh;
            gap: 20px;
            padding-top: 20px;
            flex-wrap: wrap;
        }

        .cash-flow-container {
            margin-top: 80px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            box-sizing: border-box;
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

        .submitbutton{
            background-color: black; /* Green */
            border: none;
            border-radius: 20px;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            margin-left: 20px;
            cursor: pointer;
        }

        .expensebutton{
            background-color: black; /* Green */
            border: none;
            border-radius: 20px;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;         
        }
        .justice {
            align-self: flex-start;
        }

        .justice form .item {
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
                align-items: center;
                padding-top: 10px;
                gap: 10px;
            }

            .cash-flow-container {
                width: 100%;
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <?php
        // Get the fromdate and todate from URL or default to current month
        $fromdate = isset($_GET['fromdate']) ? $_GET['fromdate'] : date('Y-m-01');
        $todate = isset($_GET['todate']) ? $_GET['todate'] : date('Y-m-t');

        // Handle form submission to add a new expense
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
            $date = $_POST['date'];
            $fuel = $_POST['fuel'];
            $vehicle_maintenance = $_POST['vehicle_maintenance'];
            $salary = $_POST['salary'];
            $others = $_POST['others'];

            $sql = $conn->prepare("INSERT INTO expense (date, fuel, vehicle_maintenance, salary, others) VALUES (?, ?, ?, ?, ?)");
            $sql->bind_param("sdddd", $date, $fuel, $vehicle_maintenance, $salary, $others);
            $sql->execute();
        }

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

        // Calculate total days in the date range
        $start = new DateTime($fromdate);
        $end = new DateTime($todate);
        $end = $end->modify('+1 day');
        $interval = $start->diff($end);
        $totalDays = $interval->days;

        // Fetch expenses for the date range
        $sql = $conn->prepare("SELECT SUM(fuel) AS fuel_expenses, SUM(vehicle_maintenance) AS maintenance_expenses, SUM(others) AS other_expenses, SUM(salary) AS total_salary FROM expense WHERE date BETWEEN ? AND ?");
        $sql->bind_param("ss", $fromdate, $todate);
        $sql->execute();
        $result = $sql->get_result();
        $expensesData = $result->fetch_assoc();

        $fuelExpenses = $expensesData['fuel_expenses'] ?? 0;
        $maintenanceExpenses = $expensesData['maintenance_expenses'] ?? 0;
        $otherExpenses = $expensesData['other_expenses'] ?? 0;
        $totalSalary = $expensesData['total_salary'] ?? 0;

        // Calculate per day salary and total salary for the date range
        $dailySalary = $totalSalary / $totalDays;
        $salaryExpenses = $dailySalary * $totalDays;

        // Calculate total expenses
        $totalExpenses = $fuelExpenses + $maintenanceExpenses + $salaryExpenses + $otherExpenses;

        // Calculate net cash flow
        $netCashFlow = $grossProfit - $totalExpenses;
        
        $conn->close();
        ?>
    <div class="cash-flow-container">
        <h1>Expense Log</h1>
        <hr>
        <form method="get" action="">
            <table>
                <tr>
                    <th>From: </th>
                    <td><input type="date" class="form-control" name="fromdate" value="<?= htmlspecialchars($fromdate) ?>"></td>
                    <td rowspan="2" style="text-align: center;">
                        <input type="submit" class="submitbutton" value="Search">
                    </td>
                </tr>
                <tr>
                    <th>To: </th>
                    <td><input type="date" class="form-control" name="todate" value="<?= htmlspecialchars($todate) ?>"></td>
                </tr>
            </table>
        </form>

        <br>
        <div class="cash-flow-section">
            <h2>Expenses</h2>
            <div class="item">
                <span class="description">Fuel:</span>
                <span class="amount">QR <?= number_format($fuelExpenses, 2); ?></span>
            </div>
            <div class="item">
                <span class="description">Vehicle Maintenance:</span>
                <span class="amount">QR <?= number_format($maintenanceExpenses, 2); ?></span>
            </div>
            <div class="item">
                <span class="description">Salary:</span>
                <span class="amount">QR <?= number_format($salaryExpenses, 2); ?></span>
            </div>
            <div class="item">
                <span class="description">Other Expenses:</span>
                <span class="amount">QR <?= number_format($otherExpenses, 2); ?></span>
            </div>
        </div>
        <div class="cash-flow-section">
            <h2>Net Expenses</h2>
            <div class="item">
                <span class="description">Net expenses:</span>
                <span class="amount">QR <?= number_format($totalExpenses, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="cash-flow-container justice cash-flow-section">
        <h1>Report Expense</h1>
        <hr>
        <form method="post" action="">
            <div class="item">
                <span class="description">Date:</span>
                <input type="date" class="form-control"  name="date" required style="width: 200px;">
            </div>
            <hr>
            <div class="item">
                <span class="description">Fuel:</span>
                <input type="number" step="0.01" class="form-control" name="fuel" required style="width: 200px;">
            </div>
            <div class="item">
                <span class="description">Vehicle Maintenance:</span>
                <input type="number" step="0.01" class="form-control" name="vehicle_maintenance" required style="width: 200px;">
            </div>
            <div class="item">
                <span class="description">Salary:</span>
                <input type="number" step="0.00001" class="form-control" name="salary" required style="width: 200px;">
            </div>
            <div class="item">
                <span class="description">Others:</span>
                <input type="number" step="0.01" class="form-control" name="others" required style="width: 200px;">
            </div>
            <hr>
            <div class="item" style="text-align: center;">
                <input type="submit" name="add_expense" value="Add Expense" class="expensebutton">
            </div>
            <br>
        </form>
    </div>
</body>
</html>
