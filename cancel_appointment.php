<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient'){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $appointment_id = intval($_POST['appointment_id']);

    $stmt = $conn->prepare("UPDATE appointments SET status='cancelled', updated_at=NOW() WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    header("Location: patient_dashboard.php");
    exit;
}
