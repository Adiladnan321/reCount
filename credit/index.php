<?php 
// session_start();
require_once '../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <title>Credit</title>
</head>
<body>
<div class="container">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button>
    <br>
    <h1>Credit</h1>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitButton'])) {
        // Retrieving form data
        $CustomerID = $_POST['CustomerID'];
        $CustomerName = $_POST['CustomerName'];
        $AmountPaid = $_POST['AmountPaid'];
        $PaidDate = $_POST['PaidDate'];

        $sql_due = "SELECT Due FROM customer WHERE CustomerID = ?";
        $stmt_due = $conn->prepare($sql_due);
        $stmt_due->bind_param("i", $CustomerID);

        if ($stmt_due->execute()) {
            $result_due = $stmt_due->get_result();
            if ($result_due->num_rows > 0) {
                $row_due = $result_due->fetch_assoc();
                $Due = $row_due['Due'] - $AmountPaid;

                if ($Due < 0) {
                    echo '<div class="alert alert-danger" role="alert">Excess Amount!!</div>';
                } else {
                    $stmt = $conn->prepare("INSERT INTO credit (CustomerID, CustomerName, AmountPaid, PaidDate, Due) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("isdsd", $CustomerID, $CustomerName, $AmountPaid, $PaidDate, $Due);
                        if ($stmt->execute()) {
                            echo '<div class="alert alert-success" role="alert">Customer added successfully!</div>';
                        } else {
                            echo '<div class="alert alert-danger" role="alert">Error adding customer!</div>';
                        }
                        $stmt->close();
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Error preparing statement!</div>';
                    }

                    $stmt_customer = $conn->prepare("UPDATE customer SET Due = ? WHERE CustomerID = ?");
                    if ($stmt_customer) {
                        $stmt_customer->bind_param("di", $Due, $CustomerID);
                        $stmt_customer->execute();
                        $stmt_customer->close();
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Error preparing update statement!</div>';
                    }
                }
            } else {
                echo '<div class="alert alert-danger" role="alert">Customer not found!</div>';
            }
            $stmt_due->close();
        } else {
            echo '<div class="alert alert-danger" role="alert">Error executing due statement!</div>';
        }
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="index.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer Id</th>
                    <th>Customer Name</th>
                    <th>Amount Paid</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <!-- Customer Id -->
                        <input type="text" class="form-control" list="CustomerIDList" name="CustomerID" placeholder="Customer Id" required onchange="updateCustomerName(this)">
                        <datalist id="CustomerIDList">
                            <?php
                                $sql_data = "SELECT * FROM customer";
                                $result_data = mysqli_query($conn, $sql_data);
                                while ($row = mysqli_fetch_assoc($result_data)) {
                                    echo "<option value='" . $row['CustomerID'] . "'>" . $row['CustomerName'] . "</option>";
                                }
                            ?>
                        </datalist>
                    </td>
                    <td>
                        <!-- Customer Name -->
                        <input type="text" class="form-control" name="CustomerName" placeholder="Eg: Khalid" required>
                    </td>
                    <td>
                        <!-- AmountPaid -->
                        <input type="number" class="form-control" name="AmountPaid" placeholder="Amount Paid" required>
                    </td>
                    <td>
                        <!-- PaidDate -->
                        <input type="date" class="form-control" name="PaidDate" required>
                    </td>
                </tr>
            </tbody>
        </table>
        <div>
            <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
        </div>
    </form>
    <br><br>
    <h1>History</h1>
    <?php
    // Retrieve customer data from the database
    $sql = "SELECT * FROM credit";
    $result = mysqli_query($conn, $sql);
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteButton'])) {
        $CreditID = intval($_POST['CreditID']);
        $stmt_delete = $conn->prepare("DELETE FROM credit WHERE CreditID = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $CreditID);
            if ($stmt_delete->execute()) {
                echo '<div class="alert alert-success" role="alert">Credit deleted successfully!</div>';
            } else {
                echo '<div class="alert alert-danger" role="alert">Error deleting Credit!</div>';
            }
            $stmt_delete->close();
        } else {
            echo '<div class="alert alert-danger" role="alert">Error preparing delete statement!</div>';
        }
    }

    // Display customer information in a table
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Credit Id</th>';
    echo '<th>Customer Id</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Amount Paid</th>';
    echo '<th>Date</th>';
    echo '<th>Due</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<form action="index.php" method="POST">';
        echo '<td><input type="hidden" value="' . $row['CreditID'] . '" name="CreditID">' . $row['CreditID'] . '</td>';
        echo '<td>' . $row['CustomerID'] . '</td>';
        echo '<td>' . $row['CustomerName'] . '</td>';
        echo '<td>' . $row['AmountPaid'] . '</td>';
        echo '<td>' . $row['PaidDate'] . '</td>';
        echo '<td>' . $row['Due'] . '</td>';
        // echo '<td><button type="submit" name="deleteButton" class="btn border-0">🗑️</button></td>';
        echo '</form>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pZt4J9qAwA/V4xODCoT2COVIKCSN5DyQqV3+hMIFlFgSCJTVW6cRB/gaTk5e2lfd" crossorigin="anonymous"></script>
<script>
    const customerData = <?php
        $customers = [];
        $sql_in = "SELECT * FROM customer";
        $result_in = mysqli_query($conn, $sql_in);
        while ($row = mysqli_fetch_assoc($result_in)) {
            $customers[$row['CustomerID']] = $row['CustomerName'];
        }
        echo json_encode($customers);
        mysqli_close($conn);
    ?>;
    function confirmSubmission() {
        return confirm("Are you sure you want to delete this purchase?");
    }
    function updateCustomerName(element) {
        const customerId = element.value;
        const customerName = customerData[customerId] || "";
        const row = element.closest("tr");
        const customerNameField = row.querySelector('input[name="CustomerName"]');
        customerNameField.value = customerName;
    }
</script>
</body>
</html>
