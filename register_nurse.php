<?php
session_start();
if(!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','medical_director'])){ header('Location: login.php'); exit; }
include 'db.php';
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
if(isset($_POST['register_nurse'])){
    $name=trim($_POST['name']); $email=trim($_POST['email']); $password=trim($_POST['password']);
    $department_id = intval($_POST['department_id'] ?? 0) ?: null;
    $salary = trim($_POST['salary'] ?? '');
    $created_at=date('Y-m-d H:i:s');
    $stmt=$conn->prepare("SELECT id FROM users WHERE email=?"); $stmt->bind_param("s",$email); $stmt->execute(); $stmt->store_result();
    if($stmt->num_rows>0){ $err="Email exists"; }
    else {
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $stmt=$conn->prepare("INSERT INTO users (name,email,password,role,status,created_at,department_id,salary) VALUES (?,?,?,?,?,?,?,?)");
        $role='nurse'; $status='pending_approval'; $dept=$department_id?:null; $sal=$salary!==''?$salary:null;
        $stmt->bind_param("ssssssis",$name,$email,$hash,$role,$status,$created_at,$dept,$sal);
        if($stmt->execute()){ $ok="Nurse registered"; } else { $err="Failed"; }
    }
}
?>
<!DOCTYPE html><html><head><title>Register Nurse</title><link rel="stylesheet" href="hospital.css"></head><body>
<div class="container" style="max-width:600px;margin:40px auto;padding:30px;background:white;border-radius:12px;">
<h2>Register Nurse</h2>
<?php if(isset($err)) echo '<p style="color:red">'.$err.'</p>'; ?>
<?php if(isset($ok)) echo '<p style="color:green">'.$ok.'</p>'; ?>
<form method="POST">
<input type="text" name="name" placeholder="Nurse Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="password" placeholder="Password" required>
<input type="number" step="0.01" name="salary" placeholder="Salary">
<select name="department_id"><option value="">Department</option>
<?php while($d=$departments->fetch_assoc()): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endwhile; ?>
</select>
<button type="submit" name="register_nurse">Register Nurse</button>
</form>
<a class="btn" href="admin_dashboard.php">Back</a>
</div></body></html>