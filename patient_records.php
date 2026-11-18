<?php
session_start();
require 'db.php';

// Admin access only
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$patient_id = intval($_GET['id']);

// Handle new record submission
if(isset($_POST['add_record'])){
    $diagnosis = trim($_POST['diagnosis']);
    $treatment = trim($_POST['treatment']);
    $notes = trim($_POST['notes']);
    $record_date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, diagnosis, treatment, notes, record_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $patient_id, $diagnosis, $treatment, $notes, $record_date);
    $stmt->execute();
}

// Fetch patient info
$patient_result = $conn->query("SELECT name FROM patients WHERE id=$patient_id");
$patient = $patient_result->fetch_assoc();

// Fetch existing records
$records = $conn->query("SELECT * FROM medical_records WHERE patient_id=$patient_id ORDER BY record_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Records</title>
<style>
body { font-family:sans-serif; background:aliceblue; padding:20px; }
.container { background:white; padding:20px; border-radius:8px; max-width:900px; margin:auto; }
h2, h3 { color:blue; }
textarea, input { width:100%; padding:8px; margin:5px 0 15px 0; border-radius:5px; border:1px solid lightgray; }
button { padding:10px 20px; background:blue; color:white; border:none; border-radius:5px; cursor:pointer; }
.record { border:1px solid #eee; padding:15px; margin-bottom:15px; border-radius:5px; }
a { color:blue; }
</style>
</head>
<body>
<div class="container">
    <h2>Medical Records for <?= htmlspecialchars($patient['name']) ?></h2>
    <a href="manage_patients.php">Back to Patient List</a>

    <hr>

    <h3>Add New Record</h3>
    <form method="POST">
        <label>Diagnosis:</label>
        <textarea name="diagnosis" rows="3" required></textarea>
        <label>Treatment:</label>
        <textarea name="treatment" rows="3" required></textarea>
        <label>Additional Notes:</label>
        <textarea name="notes" rows="2"></textarea>
        <button type="submit" name="add_record">Add Record</button>
    </form>

    <hr>

    <h3>Existing Records</h3>
    <?php if($records->num_rows > 0): ?>
        <?php while($row = $records->fetch_assoc()): ?>
        <div class="record">
            <p><strong>Date:</strong> <?= $row['record_date'] ?></p>
            <p><strong>Diagnosis:</strong><br><?= nl2br(htmlspecialchars($row['diagnosis'])) ?></p>
            <p><strong>Treatment:</strong><br><?= nl2br(htmlspecialchars($row['treatment'])) ?></p>
            <?php if(!empty($row['notes'])): ?>
            <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($row['notes'])) ?></p>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No medical records found for this patient.</p>
    <?php endif; ?>
</div>
</body>
</html>
