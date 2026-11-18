<?php
session_start();
if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='doctor'){ header('Location: login.php'); exit; }
include 'db.php';
$res = $conn->query("SELECT * FROM users WHERE role='nurse' AND status='active'");
?>
<!DOCTYPE html><html><head><title>Nurses</title><link rel="stylesheet" href="hospital.css"></head><body>
<div class="container" style="max-width:900px;margin:20px auto;padding:20px;background:white;border-radius:8px;">
<h2>Nurses</h2>
<table border="1" cellpadding="8" width="100%"><tr><th>Name</th><th>Email</th><th>Department</th></tr>
<?php while($r=$res->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['email']) ?></td><td><?= $r['department_id'] ?></td></tr>
<?php endwhile; ?>
</table>
<a class="btn" href="doctor_dashboard.php">Back</a>
</div></body></html>