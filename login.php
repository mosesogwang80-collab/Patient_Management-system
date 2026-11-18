<?php
session_start();
require 'db.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "
        SELECT id, name, password, role, 'user' as source FROM users WHERE email=? AND status='active'
        UNION ALL
        SELECT id, name, password, 'patient' as role, 'patient' as source FROM patients WHERE email=?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'doctor') {
                header("Location: doctor_dashboard.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'nurse') {
                header("Location: nurse_dashboard.php");
            } elseif ($user['role'] === 'cashier') {
                header("Location: cashier_dashboard.php");
            } elseif ($user['role'] === 'pharmacy') {
                header("Location: pharmacy_dashboard.php");
            } elseif ($user['role'] === 'receptionist') {
                header("Location: receptionist_dashboard.php");
            } elseif ($user['role'] === 'medical_director') {
                header("Location: medical_director_dashboard.php");
            } elseif ($user['role'] === 'ministry') {
                header("Location: ministry_dashboard.php");
            } elseif ($user['role'] === 'lab_technician') {
                header("Location: lab_dashboard.php");
            } elseif ($user['role'] === 'ceo') {
                header("Location: ceo_dashboard.php");
            } else {
                header("Location: patient_dashboard.php");
            }
            exit();
        }
    }

    $error = "Invalid email or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2b6cb0;
            --accent: #0d9488;
            --danger: #c53030;
            --border: #e2e8f0;
            --text: #2d3748;
            --muted: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/medical.jpg') center/cover fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px 36px;
            animation: fadeUp 0.5s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo i {
            font-size: 2.4rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .logo h1 {
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: 700;
        }

        .logo p {
            color: var(--muted);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        .error-msg {
            background: #fff5f5;
            color: var(--danger);
            border: 1px solid #feb2b2;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(26, 54, 93, 0.25);
        }

        .links {
            margin-top: 24px;
            text-align: center;
            font-size: 0.9rem;
        }

        .links a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 12px 0;
            color: var(--muted);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo">
        <i class="fas fa-hospital"></i>
        <h1>Iran-Uganda Hospital</h1>
        <p>Sign in to your account</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a>
            <div class="divider">or</div>
            <a href="register.php">New Patient? Register Here</a>
        </div>
</div>

</body>
</html>