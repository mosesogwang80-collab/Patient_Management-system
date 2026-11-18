<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient'){
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

if(isset($_GET['id'])){
    $appointment_id = intval($_GET['id']);

    // Update status to cancelled
    $stmt = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE id=? AND patient_id=?");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
}

header("Location: patient_dashboard.php?success=Appointment+cancelled");
exit();

