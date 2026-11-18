<?php
require 'db.php';
session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);

    // Check if user exists in either patients or users table
    $stmt = $conn->prepare("SELECT 'patients' AS table_name, id FROM patients WHERE email=? 
                            UNION 
                            SELECT 'users', id FROM users WHERE email=?");
    $stmt->bind_param("ss",$email,$email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $row = $result->fetch_assoc();
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_table'] = $row['table_name'];

        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<style>
body { font-family:sans-serif; background:aliceblue; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.container { background:white; padding:30px; border-radius:8px; box-shadow:0 2px 15px rgba(0,0,0,0.1); width:350px; }
h2 { color:blue; text-align:center; margin-top:0; }
input { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid lightgray; }
button { width:100%; padding:10px; background:blue; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:darkblue; }
.error-msg { color:red; text-align:center; margin-bottom:10px; }
a { display:block; text-align:center; margin-top:10px; text-decoration:none; color:blue; }
</style>
</head>
<body>
<div class="container">
<h2>Forgot Password</h2>
<?php if(isset($error)) echo '<p class="error-msg">'.htmlspecialchars($error).'</p>'; ?>
<form method="POST" action="forgot_password.php">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Submit</button>
</form>
<a href="login.php">Back to Login</a>
</div>
</body>
</html>
