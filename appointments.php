<?php
session_start();
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor'){
    header('Location: login.php');
    exit;
}
$doctor_id = $_SESSION['user_id'];


include 'db.php';

$stmt = $conn->prepare("SELECT a.*, p.name AS patient_name, p.phone AS patient_phone
FROM appointments a
JOIN patients p ON a.patient_id = p.id
WHERE a.doctor_id = ? ORDER BY a.created_at DESC");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while($row = $result->fetch_assoc()){ $appointments[] = $row; }
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Doctor Appointments</title>
<link rel="stylesheet" href="hospital.css"></head>
<body>
<div class="container" style="max-width:1000px;margin:20px auto;padding:20px;background:white;border-radius:12px;">
<h2>Your Appointments</h2>
<table border="1" cellpadding="10">
<tr><th>ID</th><th>Patient</th><th>Requested</th><th>Status</th><th>Action</th></tr>
<?php foreach($appointments as $a): ?>
<tr>
<td><?= $a['id'] ?></td>
<td><?= htmlspecialchars($a['patient_name']) ?><br><?= htmlspecialchars($a['patient_phone']) ?></td>
<td><?= $a['requested_datetime'] ?></td>
<td><?= $a['status'] ?></td>
<td>
<form method="post" action="appointments_action.php">
<input type="hidden" name="id" value="<?= $a['id'] ?>">
<select name="action">
<option value="approve">Approve</option>
<option value="decline">Decline</option>
<option value="reschedule">Suggest Reschedule</option>
<option value="confirm_rescheduled">Confirm Rescheduled</option>
</select>
<input type="datetime-local" name="reschedule_time">
<input type="text" name="doctor_note" placeholder="Note">
<button type="submit">Submit</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<a class="btn" href="doctor_dashboard.php">Back</a>
</body>
</html>
