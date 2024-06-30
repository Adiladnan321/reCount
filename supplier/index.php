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
    <title>Supplier</title>
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
    </style>
</head>
<body>
<div class="container ff">
    <br>
    <button class="btn btn-outline-secondary" onclick="window.location.href='../index.php'"><</button>
    <br>
    <h1>Supplier</h1>
    <?php
    // Database connection
    // require_once 'database.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitButton'])) {
    // Retrieving form data
        $SupplierID = $_POST['SupplierID'];
        $SupplierName = $_POST['SupplierName'];
        $Origin = $_POST['Origin'];
        $Email = $_POST['Email'];
        $PhoneNumber = $_POST['PhoneNumber'];
        $Due = $_POST['Due'];
        
        $stmt_check = $conn->prepare("SELECT * FROM supplier WHERE SupplierID = ?");
        $stmt_check->bind_param("i", $SupplierID);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            echo '<div class="alert alert-danger" role="alert">Suppplier ID already exists!</div>';
        } else {
            // Insert new supplier
            $stmt_supplier = $conn->prepare("INSERT INTO supplier (SupplierID, SupplierName, Origin, Email, PhoneNumber, Due) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_supplier->bind_param("issssi", $SupplierID, $SupplierName, $Origin, $Email, $PhoneNumber, $Due);
            if ($stmt_supplier->execute()) {
                header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
                exit();
                echo '<div class="alert alert-success" role="alert">Supplier added successfully!</div>';
                $stmt_supplier->close();
            } else {
                echo '<div class="alert alert-danger" role="alert">Error adding Supplier!</div>';
            }
        }
        $stmt_check->close();
    }
    ?>
    
    <form class="row gy-2 gx-3 align-items-center" action="index.php" method="POST">
        <table class="table">
            <thead>
                <tr>
                    <th>Supplier Id</th>
                    <th>Supplier Name</th>
                    <th>Origin</th>
                    <th>email</th>
                    <th>Phone Number</th>
                    <th>Due (if any)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <td>
                        <!-- Supplier Id -->
                        <input type="number" class="form-control" name="SupplierID" placeholder="Supplier Id">
                    </td>
                    <td>
                        <!-- Supplier Name -->
                        <input type="text" class="form-control" name="SupplierName" placeholder="Eg: Khalid">
                    </td>
                    <td>
                        <!-- Origin -->
                        <input type="text" class="form-control" name="Origin" placeholder="Eg: India">
                    </td>
                    <td>
                        <!-- email -->
                        <input type="email" class="form-control" name="Email" placeholder="abc@example.com" required>
                    </td>
                    <td>
                        <!-- Phone Number -->
                        <input type="tel" class="form-control" name="PhoneNumber" placeholder="PhoneNumber">
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
    <h1>Supplier Table</h1>
        <?php
            // Retrieve student data from the database
            $sql = "SELECT * FROM supplier";
            $result = mysqli_query($conn, $sql);

            if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteButton'])){
                $SupplierID=intval($_POST['SupplierID']);
                $stmt_delete=$conn->prepare("DELETE FROM supplier WHERE SupplierID=?");
                $stmt_delete->bind_param("i",$SupplierID);
                if($stmt_delete->execute()){
                    header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
                    exit();
                }
                else{
                    echo '<div class="alert alert-danger" role="alert">Error deleting supplier!</div>';
                }
                $stmt_delete->close();
            }
            if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['dueButton'])){
                $Due=$_POST['Due'];
                $SupplierID=$_POST['SupplierID'];
                $stmt = $conn->prepare("UPDATE supplier SET Due = ? WHERE SupplierID = ?");
                $stmt->bind_param("ii", $Due, $SupplierID);
                $stmt->execute();
                $stmt->close();
                header("Location: {$_SERVER['PHP_SELF']}?submitted=true");
                exit();
            }
            // Display student information in a table
            echo '<table class="table table-hover">';
            echo '<thead>';
            echo '<tr class="table-light">';
            echo '<th>Supplier Id</th>';
            echo '<th>Supplier Name</th>';
            echo '<th>Origin</th>';
            echo '<th>email</th>';
            echo '<th>Phone Number</th>';
            echo '<th>Due (if any)</th>';
            echo '<th></th>';
            echo '<th>Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<form action="index.php" method="POST" onsubmit="return confirmSubmission()">';
                echo '<td><input type="hidden" value="' . $row['SupplierID'] . '" name="SupplierID">'. $row['SupplierID'] .'</td>';
                echo '<td>' . $row['SupplierName'] . '</td>';
                echo '<td>' . $row['Origin'] . '</td>';
                echo '<td>' . $row['Email'] . '</td>';
                echo '<td>' . $row['PhoneNumber'] . '</td>';
                echo '<td><input value="' . $row['Due'] . '" type="text" name="Due" class="form-control border-0>"</td>';
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
</body>
</html>