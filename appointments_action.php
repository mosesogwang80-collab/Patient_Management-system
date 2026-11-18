<?php
session_start();
require_once __DIR__ . "/db.php";
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') { header("Location: login.php"); exit; }
$doctor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT a.*, p.name AS patient_name, p.phone AS patient_phone, p.email AS patient_email FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? ORDER BY a.created_at DESC");
$stmt->bind_param("i",$doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while($row = $result->fetch_assoc()){ $appointments[] = $row; }
?>
<!DOCTYPE html><html><head><title>Manage Appointments</title><link rel="stylesheet" href="hospital.css"></head><body>
<div class="container" style="max-width:1000px;margin:20px auto;padding:20px;background:white;border-radius:12px;">
<h2>Manage Appointments</h2>
<table border="1" cellpadding="8" width="100%"><tr><th>Patient</th><th>Date</th><th>Status</th><th>Action</th></tr>
<?php foreach($appointments as $a): ?>
<tr>
<td><?= htmlspecialchars($a['patient_name']) ?><br><?= htmlspecialchars($a['patient_phone']) ?></td>
<td><?= $a['requested_datetime'] ?></td>
<td><?= $a['status'] ?></td>
<td>
<?php if($a['status']==='pending'): ?>
<form method="POST" action="appointments_update.php" style="display:inline;">
<input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
<input type="hidden" name="action" value="approve">
<button class="btn">Approve</button>
</form>
<form method="POST" action="appointments_update.php" style="display:inline;">
<input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
<input type="hidden" name="action" value="decline">
<button class="btn">Decline</button>
</form>
<form method="POST" action="appointments_update.php" style="margin-top:10px;">
<input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
<input type="hidden" name="action" value="reschedule">
<input type="datetime-local" name="new_time" required>
<button class="btn">Reschedule</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<a class="btn" href="doctor_dashboard.php">Back</a>
</div></body></html>