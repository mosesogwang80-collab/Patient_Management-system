<?php
session_start();
require 'db.php';

// Only allow admin access
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$doctor_id = intval($_GET['id']);

// Handle form submission
if(isset($_POST['update_doctor'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=? AND role='doctor'");
    $stmt->bind_param("sssi", $name, $email, $status, $doctor_id);
    if($stmt->execute()){
        $success = "Doctor updated successfully!";
    } else {
        $error = "Failed to update doctor.";
    }
}

// Fetch doctor details
$result = $conn->query("SELECT * FROM users WHERE id=$doctor_id AND role='doctor'");
$doctor = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Doctor</title>
<style>
body { font-family:sans-serif; background:aliceblue; padding:20px; }
.container { background:white; padding:20px; border-radius:8px; max-width:500px; margin:auto; }
h2 { color:blue; }
input, select { width:100%; padding:8px; margin:5px 0 15px 0; border-radius:5px; border:1px solid lightgray; }
button { padding:10px 20px; background:blue; color:white; border:none; border-radius:5px; cursor:pointer; }
.error-msg { color:red; }
.success-msg { color:green; }
a { color:blue; }
</style>
</head>
<body>
<div class="container">
    <h2>Edit Doctor (ID: <?= $doctor_id ?>)</h2>
    <?php if(isset($error)) echo '<p class="error-msg">'.$error.'</p>'; ?>
    <?php if(isset($success)) echo '<p class="success-msg">'.$success.'</p>'; ?>

    <form method="POST">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" required>
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" required>
        <label>Status:</label>
        <select name="status">
            <option value="active" <?= $doctor['status'] == 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $doctor['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" name="update_doctor">Update Doctor</button>
    </form>
    <br>
    <a href="javascript:history.back()">Back to Dashboard</a>
</div>
</body>
</html>
