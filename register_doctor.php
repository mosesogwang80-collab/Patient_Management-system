<?php
session_start();
if(!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','medical_director'])){ header('Location: login.php'); exit; }
include 'db.php';
if(isset($_POST['register_doctor'])){
    $name = trim($_POST['name']); $email=trim($_POST['email']); $password=trim($_POST['password']);
    $department_id = intval($_POST['department_id'] ?? 0) ?: null;
    $specialty = trim($_POST['specialty'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $created_at = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?"); $stmt->bind_param("s",$email); $stmt->execute(); $stmt->store_result();
    if($stmt->num_rows>0){ $doctor_error="Email already exists!"; }
    else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,status,created_at,department_id,specialty,salary) VALUES (?,?,?,?,?,?,?,?,?)");
        $role='doctor'; $status='pending_approval'; $dept=$department_id?:null; $sal=$salary!==''?$salary:null;
        $stmt->bind_param("ssssssiss",$name,$email,$password_hashed,$role,$status,$created_at,$dept,$specialty,$sal);
        if($stmt->execute()){ $doctor_success="Doctor registered successfully!"; } else { $doctor_error="Failed to register doctor!"; }
    }
}
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<!DOCTYPE html><html><head><title>Register Doctor</title><link rel="stylesheet" href="hospital.css"></head><body>
<div class="container" style="max-width:600px;margin:40px auto;padding:30px;background:white;border-radius:12px;">
<h2>Register New Doctor</h2>
<?php if(isset($doctor_error)) echo '<p style="color:red">'.$doctor_error.'</p>'; ?>
<?php if(isset($doctor_success)) echo '<p style="color:green">'.$doctor_success.'</p>'; ?>
<form method="POST">
<input type="text" name="name" placeholder="Doctor Name" required>
<input type="email" name="email" placeholder="Doctor Email" required>
<input type="text" name="password" placeholder="Password" required>
<input type="text" name="specialty" placeholder="Specialty">
<input type="number" step="0.01" name="salary" placeholder="Salary">
<select name="department_id"><option value="">Department</option>
<?php while($d=$departments->fetch_assoc()): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endwhile; ?>
</select>
<button type="submit" name="register_doctor">Register Doctor</button>
</form>
<a class="btn" href="admin_dashboard.php">Back</a>
</div></body></html>