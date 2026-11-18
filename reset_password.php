<?php
require 'db.php';
session_start();

if(!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_table'])){
    header("Location: forgot_password.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $new_password = trim($_POST['password']);
    $email = $_SESSION['reset_email'];
    $table = $_SESSION['reset_table'];
 
    // Security enhancement: Whitelist table names to prevent potential SQL injection
    $allowed_tables = ['users', 'patients'];
    if (in_array($table, $allowed_tables)) {
        // Securely hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
 
        // Use backticks around table name for safety
        $stmt = $conn->prepare("UPDATE `$table` SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed_password, $email);
 
        if($stmt->execute()){
            unset($_SESSION['reset_email'], $_SESSION['reset_table']);
            $success = "Password updated successfully! <a href='login.php'>Login Now</a>";
        } else {
            $error = "Failed to reset password!";
        }
    } else {
        $error = "An invalid request was made. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<style>
body { font-family:sans-serif; background:aliceblue; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.container { background:white; padding:30px; border-radius:8px; box-shadow:0 2px 15px rgba(0,0,0,0.1); width:350px; }
h2 { color:blue; text-align:center; margin-top:0; }
input { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid lightgray; }
button { width:100%; padding:10px; background:blue; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:darkblue; }
.error-msg { color:red; text-align:center; margin-bottom:10px; }
.success-msg { color:green; text-align:center; margin-bottom:10px; }
</style>
</head>
<body>
<div class="container">
<h2>Reset Password</h2>
<?php if(isset($error)) echo '<p class="error-msg">'.htmlspecialchars($error).'</p>'; ?>
<?php if(isset($success)) echo '<p class="success-msg">'.$success.'</p>'; ?>
<form method="POST" action="reset_password.php">
    <input type="password" name="password" placeholder="Enter new password" required>
    <button type="submit">Reset Password</button>
</form>
</div>
</body>
</html>
