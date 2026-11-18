<?php
session_start();
if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='doctor'){ header('Location: login.php'); exit; }
include 'db.php';
$doctor_id = $_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $patient_id = intval($_POST['patient_id']);
    $nurse_id = intval($_POST['nurse_id']);
    $notes = trim($_POST['notes']);
    $stmt = $conn->prepare("INSERT INTO nurse_assignments (patient_id,nurse_id,assigned_by,notes) VALUES (?,?,?,?)");
    $stmt->bind_param("iiis",$patient_id,$nurse_id,$doctor_id,$notes);
    $stmt->execute();
    $msg="Task assigned to nurse.";
    $stmt = $conn->prepare("INSERT INTO messages (sender_id,recipient_id,subject,body) VALUES (?,?,?,?)");
    $subj="New task assigned";
    $stmt->bind_param("iiss",$doctor_id,$nurse_id,$subj,$notes);
    $stmt->execute();
}
$patients = $conn->query("SELECT id,name FROM patients ORDER BY name");
$nurses = $conn->query("SELECT id,name FROM users WHERE role='nurse' AND status='active'");
?>
<!DOCTYPE html><html><head><title>Assign Nurse</title><link rel="stylesheet" href="hospital.css"></head>
<body style="background:linear-gradient(135deg,aliceblue,lightcyan);">
<div class="container" style="max-width:700px;margin:40px auto;padding:30px;background:white;border-radius:12px;">
<h2>Assign Nurse to Patient</h2>
<?php if(isset($msg)) echo '<p style="color:green">'.$msg.'</p>'; ?>
<form method="POST">
<label>Patient</label>
<select name="patient_id" required><?php while($p=$patients->fetch_assoc()): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endwhile; ?></select>
<label>Nurse</label>
<select name="nurse_id" required><?php while($n=$nurses->fetch_assoc()): ?><option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['name']) ?></option><?php endwhile; ?></select>
<label>Notes</label>
<textarea name="notes" rows="3"></textarea>
<button type="submit">Assign</button>
</form>
<a class="btn" href="doctor_dashboard.php">Back</a>
</div></body></html>