<?php 
session_start();
if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit(); 
}

require_once '../database.php'; 

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['markPaid'])){
    $installmentId = intval($_POST['installmentId']);
    
    // Update the installment in the database 
    try{ 
        $sql = "UPDATE installments SET 
                    PaymentDate = CURDATE(), 
                    Status = 'Paid' 
                WHERE InstallmentID = ?";

        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("i", $installmentId);

        if($stmt->execute()){
            // Check if all installments are paid, and if so, update the purchase order status
            $sql_check_installments = "SELECT COUNT(*) as pending FROM installments WHERE POID = (SELECT POID FROM installments WHERE InstallmentID = ?) AND Status='Pending'";
            $stmt_check_installments = $conn->prepare($sql_check_installments);
            $stmt_check_installments->bind_param("i", $installmentId);
            $stmt_check_installments->execute();
            $result = $stmt_check_installments->get_result()->fetch_assoc();
            
            if ($result['pending'] == 0) {
                // All installments are paid, update the purchase order status to 'Paid'
                $poID = $_GET['poid'];
                $sql_update_po = "UPDATE purchase_order SET Status = 'Paid' WHERE POID = ?";
                $stmt_update_po = $conn->prepare($sql_update_po);
                $stmt_update_po->bind_param("i", $poID);
                if(!$stmt_update_po->execute()) {
                    throw new Exception("Error updating purchase order");
                }
            }
        }
        else{
            throw new Exception("Error updating installment");
        }
        header("Location: view_installments.php?poid=" . $_POST['installmentId']); //Redirect back 
    }
    catch(Exception $e){
        //Handle errors, display message etc. 
        echo "Error: " . $e->getMessage();
    }
}
?> 