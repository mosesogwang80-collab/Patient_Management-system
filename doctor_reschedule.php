<?php
session_start();
require 'db.php';

// Check if doctor is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login_form.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Check if appointment id is set
if(!isset($_GET['id'])){
    header("Location: doctor_dashboard.php?error=No+appointment+selected");
    exit();
}

$appointment_id = intval($_GET['id']);

// Fetch appointment
$stmt = $conn->prepare("SELECT a.*, p.name AS patient_name FROM appointments a JOIN patients p ON a.patient_id=p.id WHERE a.id=? AND a.doctor_id=?");
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$apt = $stmt->get_result()->fetch_assoc();

if(!$apt){
    header("Location: doctor_dashboard.php?error=Appointment+not+found");
    exit();
}

// Handle reschedule form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $new_datetime = $_POST['new_datetime'];
    $stmt = $conn->prepare("UPDATE appointments SET requested_datetime=?, status='rescheduled' WHERE id=? AND doctor_id=?");
    $stmt->bind_param("sii", $new_datetime, $appointment_id, $doctor_id);
    $stmt->execute();

    header("Location: doctor_dashboard.php?success=Appointment+rescheduled");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Reschedule Appointment</title>
<style>
body { font-family:sans-serif; background:aliceblue; padding:50px; }
.container { max-width:400px; margin:auto; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
input, button { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid lightgray; }
button { background:#17a2b8; color:white; border:none; cursor:pointer; }
button:hover { background:#138496; }
a { display:block; margin-top:10px; text-align:center; text-decoration:none; color:blue; }
</style>
</head>
<body>
<div class="container">
<h2>Reschedule Appointment #<?php echo $apt['id']; ?></h2>
<p>Patient: <?php echo htmlspecialchars($apt['patient_name']); ?></p>

<form method="POST">
    <label>New Date & Time:</label>
    <input type="datetime-local" name="new_datetime" required value="<?php echo date('Y-m-d\TH:i', strtotime($apt['requested_datetime'])); ?>">
    <button type="submit">Update</button>
</form>

<a href="javascript:history.back()">Back to Dashboard</a>
</div>
</body>
</html>
