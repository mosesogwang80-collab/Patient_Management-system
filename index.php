<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2b6cb0;
            --accent: #0d9488;
            --text: #1a202c;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            color: var(--text);
            /* Two images side by side with soft blend in the middle */
            background: 
                linear-gradient(to right, 
                    rgba(0,0,0,0.25) 0%, 
                    rgba(0,0,0,0.15) 40%, 
                    rgba(0,0,0,0.15) 60%, 
                    rgba(0,0,0,0.25) 100%),
                url('images/img1.jpg') left center / 50% 100% no-repeat,
                url('images/medical.jpg') right center / 50% 100% no-repeat;
            background-color: #1a365d;
        }

        /* Header */
        header {
            background: rgba(26, 54, 93, 0.92);
            backdrop-filter: blur(8px);
            color: white;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0,0,0,0.15);
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.3px;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        nav a.btn-nav {
            background: var(--accent);
            margin-left: 8px;
        }

        nav a.btn-nav:hover {
            background: #0f766e;
        }

        /* Hero Section */
        .hero {
            min-height: calc(100vh - 70px);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }

        .hero-content {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 50px 60px;
            max-width: 720px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            animation: fadeUp 0.8s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content h1 {
            font-size: 2.6rem;
            color: var(--primary);
            margin-bottom: 16px;
            line-height: 1.25;
            font-weight: 700;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);
        }

        .btn-primary:hover {
            background: #0f766e;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(13, 148, 136, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                padding: 14px 20px;
                flex-direction: column;
                gap: 14px;
            }

            nav {
                justify-content: center;
            }

            .hero-content {
                padding: 36px 28px;
            }

            .hero-content h1 {
                font-size: 1.9rem;
            }

            .hero-content p {
                font-size: 1.05rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <i class="fas fa-hospital"></i>
        Iran-Uganda Hospital
    </div>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="departments.php">Departments</a>
        <a href="specialty.php">Speciality</a>
        <a href="contact.php">Contact</a>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'patient'): ?>
            <a href="patient_dashboard.php">My History</a>
        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'receptionist'): ?>
            <a href="patient_register.php">Register Patient (In-Person)</a>
        <?php else: ?>
            <a href="register.php">Register as Patient</a>
            
        <?php endif; ?>

        <a href="login.php">Login</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-content">
        <h1><marquee>Welcome to Iran-Uganda Hospital</marquee></h1>
        <p>Quality healthcare for everyone. Book appointments online and experience compassionate, modern medical care.</p>

        <div class="hero-buttons">
            <a href="book_appointment.php" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i> Book Appointment
            </a>
            <a href="departments.php" class="btn btn-outline">
                <i class="fas fa-building"></i> Our Departments
            </a>
        </div>
    </div>
</section>

</body>
</html>