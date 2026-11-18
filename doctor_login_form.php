<?php
session_start();
require 'db.php'; // Database connection

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='doctor' AND status='active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $doctor = $result->fetch_assoc();
        if (password_verify($password, $doctor['password'])) {
            $_SESSION['user_id'] = $doctor['id'];
            $_SESSION['user_name'] = $doctor['name'];
            $_SESSION['user_role'] = 'doctor';
            header("Location: doctor_dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Login</title>
<style>
body { font-family:sans-serif; background:aliceblue; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.container { background:white; padding:30px; border-radius:8px; box-shadow:0 2px 15px rgba(0,0,0,0.1); width:350px; }
h2 { color:green; text-align:center; margin-top:0; }
input { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid lightgray; }
button { width:100%; padding:10px; background:green; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:#218838; }
.error-msg { color:red; text-align:center; margin-bottom:10px; }
a { display:block; text-align:center; margin-top:10px; text-decoration:none; color:green; }
</style>
</head>
<body>
<div class="container">
<h2>Doctor Login</h2>
<?php if(isset($error)) echo '<p class="error-msg">'.htmlspecialchars($error).'</p>'; ?>
<form method="POST" action="doctor_login_form.php">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
<a href="register.php">New Patient? Register Here</a>
<a href="forgot_password.php">Forgot Password?</a>
</div>
</body>
</html>

