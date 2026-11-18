<?php
session_start();
include 'db.php';

if(!isset($_SESSION['patient_id'])){
    header("Location: login.php");
    exit;
}

if(isset($_GET['id'])){
    $appointment_id = $_GET['id'];
    $patient_id = $_SESSION['patient_id'];

    // Update appointment status to Approved if patient confirms reschedule
    $sql = "UPDATE appointments 
            SET status = 'Approved', requested_datetime = reschedule_suggestion, reschedule_suggestion = NULL
            WHERE id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    if($stmt->execute()){
        header("Location: patient_dashboard.php");
        exit;
    } else {
        echo "Error confirming reschedule.";
    }
} else {
    header("Location: patient_dashboard.php");
    exit;
}
?>
