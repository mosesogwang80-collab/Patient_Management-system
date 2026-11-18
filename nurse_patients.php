<?php
session_start();
if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='nurse'){ header('Location: login.php'); exit; }
include 'db.php';
$nurse_id = $_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])){
    $pid = intval($_POST['patient_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE patients SET patient_status=? WHERE id=?");
    $stmt->bind_param("si",$status,$pid);
    $stmt->execute();
}
$res = $conn->query("SELECT p.id,p.name,p.patient_type,p.patient_status FROM patients p JOIN nurse_assignments na ON p.id=na.patient_id WHERE na.nurse_id=$nurse_id");
?>
<!DOCTYPE html><html><head><title>My Patients</title><link rel="stylesheet" href="hospital.css"></head>
<body style="background:linear-gradient(135deg,lightcyan,aliceblue);">
<header style="display:flex;justify-content:space-between;align-items:center;padding:15px 30px;background:teal;color:white;"><span>My Patients</span><a href="nurse_dashboard.php" style="color:white;">Back</a></header>
<div class="container" style="max-width:900px;margin:20px auto;padding:20px;background:white;border-radius:12px;">
<table border="1" cellpadding="8" width="100%"><tr><th>Name</th><th>Type</th><th>Status</th></tr>
<?php while($r=$res->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($r['name']) ?></td><td><?= $r['patient_type'] ?></td><td><?= $r['patient_status'] ?></td>
<td>
<form method="POST" action="nurse_patients.php">
<input type="hidden" name="patient_id" value="<?= $r['id'] ?>">
<select name="status"><option value="admitted">Admit</option><option value="discharged">Discharge</option><option value="outpatient">Outpatient</option></select>
<button type="submit" name="update_status">Update</button>
</form>
</td></tr>
<?php endwhile; ?>
</table>
</div></body></html>