<?php
session_start();
include 'db.php'; // Connect to doctor_patient database

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check users table for email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $user = $result->fetch_assoc();

        // Plain password check (replace with password_verify if hashed)
        if($password === $user['password']){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Block pending approval workers from logging in
            if($user['status'] === 'pending_approval'){
                header("Location: login_form.php?error=Your+account+is+pending+Medical+Director+approval");
                exit;
            }

            // Redirect based on role
            if($user['role'] == 'doctor'){
                header("Location: doctor_dashboard.php");
                exit;
            } elseif($user['role'] == 'admin'){
                header("Location: admin_dashboard.php");
                exit;
            } elseif($user['role'] == 'cashier'){
                header("Location: cashier_dashboard.php");
                exit;
            } elseif($user['role'] == 'pharmacy'){
                header("Location: pharmacy_dashboard.php");
                exit;
            } elseif($user['role'] == 'receptionist'){
                header("Location: receptionist_dashboard.php");
                exit;
            } elseif($user['role'] == 'nurse'){
                header("Location: nurse_dashboard.php");
                exit;
            } elseif($user['role'] == 'medical_director'){
                header("Location: medical_director_dashboard.php");
                exit;
            } elseif($user['role'] == 'ministry'){
                header("Location: ministry_dashboard.php");
                exit;
            } elseif($user['role'] == 'ceo'){
                header("Location: admin_dashboard.php");
                exit;
            } else {
                header("Location: login_form.php?error=Role not allowed");
                exit;
            }
        } else {
            header("Location: login_form.php?error=Invalid password");
            exit;
        }
    } else {
        header("Location: login_form.php?error=User not found");
        exit;
    }
} else {
    header("Location: login_form.php");
    exit;
}

