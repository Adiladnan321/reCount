<?php
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit();
}

require_once '../database.php';

if (!isset($_GET['poid'])) {
    echo "<div class='alert alert-danger'>No Purchase Order ID specified.</div>"; 
    exit(); 
} 

$poId = intval($_GET['poid']); 
$sql = "SELECT * FROM installments WHERE POID = ?"; 
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $poId); 
$stmt->execute(); 
$installments = $stmt->get_result(); 
?>

<!DOCTYPE html> 
<html>
<head>
    <title>Installment Details</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head> 
<body>
    <div class="container mt-4">
        <h2>Installment Details for PO #<?php echo $poId; ?></h2>
        <a href="index.php" class="btn btn-secondary btn-sm mb-2">Back to Purchase Orders</a> 

        <table class="table">
            <thead>
                <tr>
                    <th>Installment ID</th> 
                    <th>Installment Amount</th> 
                    <th>Due Date</th> 
                    <th>Payment Date</th>
                    <th>Status</th> 
                    <th>Actions</th> 
                </tr>
            </thead>
            <tbody>
                <?php while ($installment = $installments->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $installment['InstallmentID']; ?></td> 
                        <td><?php echo number_format($installment['InstallmentAmount'], 2); ?></td>
                        <td><?php echo $installment['DueDate']; ?></td> 
                        <td><?php echo $installment['PaymentDate'] ? $installment['PaymentDate'] : '-'; ?></td> 
                        <td><?php echo $installment['Status']; ?></td>
                        <td>
                            <?php if ($installment['Status'] == 'Pending') : ?>
                                <form action="process_installment.php" method="post"> 
                                    <input type="hidden" name="installmentId" value="<?php echo $installment['InstallmentID']; ?>"> 
                                    <button type="submit" name="markPaid" class="btn btn-success btn-sm">Mark as Paid</button>
                                </form> 
                            <?php else : ?> 
                                <span class="badge bg-success">Paid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody> 
        </table> 
    </div>
</body> 
</html> 