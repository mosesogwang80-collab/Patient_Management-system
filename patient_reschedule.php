<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient'){
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// If reschedule form is submitted
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $appointment_id = intval($_POST['appointment_id']);
    $new_datetime = $_POST['new_datetime'];

    $stmt = $conn->prepare("UPDATE appointments SET requested_datetime=?, status='rescheduled' WHERE id=? AND patient_id=?");
    $stmt->bind_param("sii", $new_datetime, $appointment_id, $patient_id);
    $stmt->execute();

    header("Location: patient_dashboard.php?success=Appointment+rescheduled");
    exit();
}

// If user clicked reschedule link
if(isset($_GET['id'])){
    $appointment_id = intval($_GET['id']);

    // Fetch appointment
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id=? AND patient_id=?");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $apt = $stmt->get_result()->fetch_assoc();

    if(!$apt){
        header("Location: patient_dashboard.php?error=Appointment+not+found");
        exit();
    }
} else {
    header("Location: patient_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reschedule Appointment</title>
<style>
body { font-family:sans-serif; background:aliceblue; padding:50px; }
.container { max-width:400px; margin:auto; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
input, button { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid lightgray; }
button { background:#17a2b8; color:white; border:none; cursor:pointer; }
button:hover { background:#138496; }
a { display:inline-block; margin-top:10px; text-decoration:none; color:blue; }
</style>
</head>
<body>
<div class="container">
<h2>Reschedule Appointment #<?php echo $apt['id']; ?></h2>
<form method="POST">
    <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
    <label>New Date & Time:</label>
    <input type="datetime-local" name="new_datetime" required value="<?php echo date('Y-m-d\TH:i', strtotime($apt['requested_datetime'])); ?>">
    <button type="submit">Update</button>
</form>
<a href="javascript:history.back()">Back to Dashboard</a>
</div>
</body>
</html>

