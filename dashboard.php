<?php
session_start();
require 'db.php'; // Use the standard database connection

// Use the existing session check for admin role
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header('Location: login.php'); // Redirect to the general login
    exit;
}

// Use the existing $conn variable for database queries
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0];
$pending = $conn->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetch_row()[0];
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Dashboard</title></head>
<body>
<h1>System Analytics</h1>
<ul>
<li>Total Users: <?= $total_users ?></li>
<li>Total Patients: <?= $total_patients ?></li>
<li>Total Appointments: <?= $total_appointments ?></li>
<li>Pending Approvals: <?= $pending ?></li>
</ul>
<p><a href="users.php">Manage Users</a></p>
<p><a href="appointments.php">View All Appointments</a></p>
</body>
</html>
