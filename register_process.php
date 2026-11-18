<?php
session_start();
require_once __DIR__ . "/db.php";

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

// Check if email already exists
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
    $_SESSION['reg_error'] = "Email already registered.";
    header("Location: register.php");
    exit;
}

// Insert user
$hashed_password = password_hash($password, PASSWORD_DEFAULT); // Securely hash the password
$stmt = $pdo->prepare("
    INSERT INTO users (name, email, phone, password, role, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 'pending_approval', NOW(), NOW())
");

$stmt->execute([$name, $email, $phone, $hashed_password, $role]);

$_SESSION['reg_success'] = "Account created successfully! You can now login.";
header("Location: register.php");
exit;

