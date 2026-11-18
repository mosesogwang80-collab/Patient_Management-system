<?php
session_start();
require 'db.php';

// Check if doctor is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login_form.php");
    exit();
}

if(!isset($_GET['action']) || !isset($_GET['id'])){
    header("Location: doctor_dashboard.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$appointment_id = intval($_GET['id']);
$action = $_GET['action'];

// Fetch the appointment first
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id=? AND doctor_id=?");
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    header("Location: doctor_dashboard.php?error=Appointment+not+found");
    exit();
}

$appointment = $result->fetch_assoc();

// Perform the action
switch($action){
    case 'approve':
        $stmt2 = $conn->prepare("UPDATE appointments SET status='approved', confirmed_datetime=NOW() WHERE id=?");
        $stmt2->bind_param("i", $appointment_id);
        $stmt2->execute();
        break;

    case 'decline':
        $stmt2 = $conn->prepare("UPDATE appointments SET status='declined' WHERE id=?");
        $stmt2->bind_param("i", $appointment_id);
        $stmt2->execute();
        break;

    case 'reschedule':
        // Example: reschedule to +1 day, you can add a proper reschedule form later
        $new_datetime = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($appointment['requested_datetime'])));
        $stmt2 = $conn->prepare("UPDATE appointments SET status='rescheduled', requested_datetime=? WHERE id=?");
        $stmt2->bind_param("si", $new_datetime, $appointment_id);
        $stmt2->execute();
        break;

    default:
        header("Location: doctor_dashboard.php");
        exit();
}

// Redirect back to doctor dashboard after action
header("Location: doctor_dashboard.php?success=Action+performed");
exit();
