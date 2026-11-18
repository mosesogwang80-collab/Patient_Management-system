<?php
session_start();
require 'db.php'; // Make sure this points to your database connection

// Open to public for online registration
$receptionist_name = isset($_SESSION['user_name']) && $_SESSION['user_role'] === 'receptionist'
    ? $_SESSION['user_name'] : null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $created_at = date('Y-m-d H:i:s');

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM patients WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $error = "Email already registered!";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO patients (name,email,password,phone,dob,gender,address,created_at) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss",$name,$email,$password_hashed,$phone,$dob,$gender,$address,$created_at);

        if($stmt->execute()){
            $patient_id = $stmt->insert_id;

            // Auto-login after registration
            $_SESSION['user_id'] = $patient_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'patient';

            // Redirect to patient dashboard
            header("Location: patient_dashboard.php");
            exit();
        } else {
            $error = "Registration failed. Try again!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Registration</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: aliceblue; margin: 0; padding: 40px 0; }
.container { background-color: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); max-width: 500px; margin: auto; }
h2 { color: darkblue; text-align: center; margin-bottom: 25px; }
.error-msg { color: red; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
.success-msg { color: green; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
form { display: flex; flex-direction: column; }
label { margin-bottom: 5px; font-weight: bold; color: #555; }
input, select, textarea { padding: 12px; margin-bottom: 15px; border: 1px solid lightgray; border-radius: 5px; font-size: 16px; box-sizing: border-box; width: 100%; }
input:focus, select:focus, textarea:focus { border-color: blue; outline: none; box-shadow: 0 0 5px rgba(0, 123, 255, 0.25); }
button { background-color: blue; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background-color 0.3s; }
button:hover { background-color: darkblue; }
a { color: blue; text-decoration: none; margin-top: 20px; display: block; text-align: center; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
<h2><?php echo $receptionist_name ? 'Register New Patient' : 'Patient Registration'; ?></h2>
<?php echo $receptionist_name ? '<p style="margin-bottom:20px;color:#64748b;">Registered by: '.htmlspecialchars($receptionist_name).'</p>' : '<p style="margin-bottom:20px;color:#64748b;">Create your account to book appointments online.</p>'; ?>

<?php if(isset($error)) echo '<p class="error-msg">'.htmlspecialchars($error).'</p>'; ?>
<?php if(isset($success)) echo '<p class="success-msg">'.htmlspecialchars($success).'</p>'; ?>

<form method="POST" action="patient_register.php">
    <label>Name:</label>
    <input type="text" name="name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Password:</label>
    <input type="password" name="password" required>

    <label>Phone:</label>
    <input type="text" name="phone" required>

    <label>Date of Birth:</label>
    <input type="date" name="dob" required>

    <label>Gender:</label>
    <select name="gender" required>
        <option value="">-- Select Gender --</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>

    <label>Address:</label>
    <textarea name="address" rows="2"></textarea>

    <button type="submit"><?php echo $receptionist_name ? 'Register Patient' : 'Register & Login'; ?></button>
</form>

<?php if($receptionist_name): ?>
<a href="receptionist_dashboard.php" style="display:block;text-align:center;margin-top:20px;color:blue;">&larr; Back to Receptionist Dashboard</a>
<?php else: ?>
<a href="login.php">Already have an account? Login</a>
<?php endif; ?>
</div>
</body>
</html>

