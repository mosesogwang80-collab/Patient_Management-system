<?php
session_start();
require 'db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password']; // if using hashed password, use password_verify()

    // fetch patient by email
    $stmt = $conn->prepare("SELECT * FROM patients WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if($patient){
        // Assuming you store password in plaintext (not recommended). If hashed, use password_verify
        if($patient['password'] === $password){
            $_SESSION['user_id'] = $patient['id'];
            $_SESSION['user_name'] = $patient['name'];
            $_SESSION['user_role'] = 'patient'; // Add user_role to the session
            header("Location: patient_dashboard.php");
            exit;
        } else {
            header("Location: patient_login_form.php?error=Incorrect+password");
            exit;
        }
    } else {
        header("Location: patient_login_form.php?error=Email+not+found");
        exit;
    }
} else {
    header("Location: patient_login_form.php");
    exit;
}

