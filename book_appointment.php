<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient'){
    ?>
    <!DOCTYPE html><html><head><title>Book Appointment</title><link rel="stylesheet" href="hospital.css"></head><body style="background:linear-gradient(135deg,lightblue,aliceblue);">
    <div class="container" style="max-width:600px;margin:40px auto;padding:30px;background:white;border-radius:12px;">
    <h2>Book Appointment</h2>
    <p>Are you registered? Please login or register to book.</p>
    <a class="btn" href="login.php">Login</a>
    <a class="btn" href="register.php">Register</a>
    </div></body></html>
    <?php
    exit;
}
$patient_id = $_SESSION['user_id'];
$doctors_result = $conn->query("SELECT id, name FROM users WHERE role='doctor' AND status='active'");
$doctors = [];
while($row = $doctors_result->fetch_assoc()){ $doctors[] = $row; }
?>
<!DOCTYPE html><html><head><title>Book Appointment</title><link rel="stylesheet" href="hospital.css"></head><body style="background:linear-gradient(135deg,lightblue,aliceblue);">
<div class="container" style="max-width:700px;margin:40px auto;padding:30px;background:white;border-radius:12px;">
<h2>Book Appointment</h2>
<form action="booking_process.php" method="POST">
<label>Issue / Complaint:</label>
<textarea name="note" rows="4" required></textarea><br><br>
<label>Appointment Date & Time:</label>
<input type="datetime-local" name="appointment_time" required>
<button type="submit">Book Appointment</button>
</form>
</div></body></html>