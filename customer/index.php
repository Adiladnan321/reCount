<?php 
    session_start();
    if(!isset($_SESSION["user"])){
        header("Location: login.php");
    }
    require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Customer</title>
</head>
<style>
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
    @media (max-width:576px){
            .mobile-card{
                width: 1000px;
            }
        }
</style>
<body>
<div class="container ff mobile-card">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button>
    <br>
    <h1>Customer</h1>
    <?php
    // Database connection
    // require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitButton'])) {
        // Retrieving form data
        $CustomerID = $_POST['CustomerID'];
        $CustomerName = $_POST['CustomerName'];
        $Origin = $_POST['Origin'];
        $Email = $_POST['Email'];
        $PhoneNumber = $_POST['PhoneNumber'];
        $Due = $_POST['Due'];
        
        // Check if customer already exists
        $stmt_check = $conn->prepare("SELECT * FROM customer WHERE CustomerID = ?");
        $stmt_check->bind_param("i", $CustomerID);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            echo '<div class="alert alert-danger" role="alert">Customer ID already exists!</div>';
        } else {
            // Insert new customer
            $stmt_customer = $conn->prepare("INSERT INTO customer (CustomerID, CustomerName, Origin, Email, PhoneNumber, Due) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_customer->bind_param("issssi", $CustomerID, $CustomerName, $Origin, $Email, $PhoneNumber, $Due);
            if ($stmt_customer->execute()) {
                header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
                exit();
                echo '<div class="alert alert-success" role="alert">Customer added successfully!</div>';
                $stmt_customer->close();
            } else {
                echo '<div class="alert alert-danger" role="alert">Error adding customer!</div>';
            }
        }
        $stmt_check->close();
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="index.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer Id</th>
                    <th>Customer Name</th>
                    <th>Origin</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Due (if any)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <!-- Customer Id -->
                        <input type="number" class="form-control" name="CustomerID" placeholder="Customer Id" required>
                    </td>
                    <td>
                        <!-- Customer Name -->
                        <input type="text" class="form-control" name="CustomerName" placeholder="Eg: Khalid" required>
                    </td>
                    <td>
                        <!-- Origin -->
                        <input type="text" class="form-control" name="Origin" placeholder="Eg: India" required>
                    </td>
                    <td>
                        <!-- Email -->
                        <input type="email" class="form-control" name="Email" placeholder="abc@example.com" required>
                    </td>
                    <td>
                        <!-- Phone Number -->
                        <input type="tel" class="form-control" name="PhoneNumber" placeholder="PhoneNumber" required>
                    </td>
                    <td>
                        <!-- Due -->
                        <input type="number" class="form-control" name="Due" placeholder="Due">
                    </td>
                </tr>
            </tbody>
        </table>
        <div>
            <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>Customers</h1>
    <?php
    // session_start();

    // Retrieve customer data from the database
    $sql = "SELECT * FROM customer";
    $result = mysqli_query($conn, $sql);
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteButton'])) {
        $CustomerID = intval($_POST['CustomerID']);
        $stmt_delete = $conn->prepare("DELETE FROM customer WHERE CustomerID = ?");
        $stmt_delete->bind_param("i", $CustomerID);
        if ($stmt_delete->execute()) {
            echo '<div class="alert alert-success" role="alert">Customer deleted successfully!</div>';
            header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
            exit();
        } else {
            echo '<div class="alert alert-danger" role="alert">Error deleting customer!</div>';
        }
        $stmt_delete->close();
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dueButton'])) {
        $Due = $_POST['Due'];
        $CustomerID = $_POST['CustomerID'];

        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("UPDATE customer SET Due = ? WHERE CustomerID = ?");
        $stmt->bind_param("ii", $Due, $CustomerID);
        $stmt->execute();
        $stmt->close();
        // header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
        // exit();
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['viewButton'])) {
        $Due = $_POST['Due'];
        $CustomerID = $_POST['CustomerID'];
        $_SESSION['cust']=$CustomerID;
        header("Location: customerView.php");
        exit();
    }

    // Display customer information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr class="table-light">';
    echo '<th>Customer Id</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Origin</th>';
    echo '<th>Email</th>';
    echo '<th>Phone Number</th>';
    echo '<th>Due (if any)</th>';
    echo '<th></th>';
    echo '<th>Action</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<form action="index.php" method="POST" name="cust" onsubmit="return confirmSubmission()">';
        echo '<td><input type="hidden" value="' . $row['CustomerID'] . '" name="CustomerID"><button name="viewButton" class="btn border-0">' . $row['CustomerID'] . '</button></td>';
        echo '<td><button name="viewButton" class="btn border-0">' . $row['CustomerName'] . '</button></td>';
        echo '<td>' . $row['Origin'] . '</td>';
        echo '<td>' . $row['Email'] . '</td>';
        echo '<td>' . $row['PhoneNumber'] . '</td>';
        echo '<td><input value="' . number_format($row['Due']) . '" type="text" name="Due" class="form-control border-0>"</td>';
        echo '<td><button class="btn btn-outline-primary" type="submit" name="dueButton">✔️</button></td>';
        echo '<td><button type="submit" name="deleteButton" class="btn border-0">🗑️</button></td>';
        echo '</form>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    mysqli_close($conn);
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
<script>
    function confirmSubmission() {
            return confirm("Are you sure!!!");
        }
</script>
</body>
</html>
