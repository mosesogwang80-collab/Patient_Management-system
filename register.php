<?php
session_start();
require 'db.php';

// Open to public for online registration
$receptionist_name = isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'receptionist'
    ? $_SESSION['user_name'] : null;

$error = null;
$success = null;

// If user is already logged in as patient, redirect them
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'patient' && !$receptionist_name) {
    header("Location: patient_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if email already exists in EITHER table
        $sql = "SELECT email FROM users WHERE email = ? UNION SELECT email FROM patients WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO patients (name, email, phone, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $phone, $password_hashed);

            if ($stmt->execute()) {
                $new_patient_id = $stmt->insert_id;

                if (!$receptionist_name) {
                    // Auto-login after public registration
                    $_SESSION['user_id'] = $new_patient_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'patient';

                    header("Location: patient_dashboard.php");
                    exit();
                } else {
                    $success = "Patient registered successfully!";
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Iran-Uganda Hospital</title>
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
            background: url('images/hospital1.jpg') center/cover fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 440px;
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
            font-size: 1.45rem;
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
            margin-bottom: 16px;
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

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-register:hover {
            background: #0f766e;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);
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
    </style>
</head>
<body>

<div class="register-card">
    <div class="logo">
        <i class="fas fa-user-plus"></i>
        <h1><?php echo $receptionist_name ? 'Register Patient' : 'Patient Registration'; ?></h1>
        <p><?php echo $receptionist_name ? 'Registered by: '.htmlspecialchars($receptionist_name) : 'Create your account'; ?></p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Enter your full name" required>
            </div>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" placeholder="Enter your phone number" required>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Minimum 8 characters" required>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Re-enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn-register">
            <i class="fas fa-user-check"></i> Register Patient
        </button>
    </form>

    <div class="links" style="margin-top: 30px;">
        <?php if($receptionist_name): ?>
            <a href="receptionist_dashboard.php" style="color: var(--primary-light);"><i class="fas fa-arrow-left"></i> Back to Receptionist Dashboard</a>
            <br>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Already have an account? Login here</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>