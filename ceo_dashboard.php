<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ceo') {
    header('Location: login.php');
    exit;
}
include 'db.php';

$ceo_name = htmlspecialchars($_SESSION['user_name'] ?? 'CEO');

$total_doctors   = $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor'")->fetch_assoc()['c'];
$total_nurses    = $conn->query("SELECT COUNT(*) c FROM users WHERE role='nurse'")->fetch_assoc()['c'];
$total_patients  = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$total_staff     = $conn->query("SELECT COUNT(*) c FROM users WHERE role IN ('doctor','nurse','receptionist','pharmacy','cashier')")->fetch_assoc()['c'];
$total_appointments = $conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'];
$pending_appointments = $conn->query("SELECT COUNT(*) c FROM appointments WHERE status='pending'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEO Dashboard | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, lightsteelblue, lavender);
            min-height: 100vh;
            color: darkslategray;
            line-height: 1.5;
        }

        /* Header */
        header {
            background: linear-gradient(90deg, midnightblue, darkblue);
            color: white;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-left i {
            font-size: 1.8rem;
            color: gold;
        }

        .header-left h1 {
            font-size: 1.45rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .header-left span {
            display: block;
            font-size: 0.85rem;
            opacity: 0.85;
            font-weight: 400;
        }

        .header-right a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 9px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            transition: background 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .header-right a:hover {
            background: rgba(255,255,255,0.22);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 35px auto 50px;
            padding: 0 24px;
        }

        /* Welcome Card */
        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid lightgray;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .welcome-card h2 {
            font-size: 1.6rem;
            color: midnightblue;
            font-weight: 700;
        }

        .welcome-card p {
            color: slategray;
            margin-top: 6px;
            font-size: 1rem;
        }

        .badge {
            background: midnightblue;
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* Analytics Section */
        .section-title {
            font-size: 1.25rem;
            color: midnightblue;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 24px 22px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
            border: 1px solid lightgray;
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: midnightblue;
        }

        .stat-card .icon {
            width: 48px;
            height: 48px;
            background: lavender;
            color: midnightblue;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 14px;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: slategray;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: midnightblue;
        }

        .stat-card .sub {
            font-size: 0.8rem;
            color: dimgray;
            margin-top: 4px;
        }

        /* Menu Section */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            border-radius: 14px;
            padding: 26px 24px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
            border: 1px solid lightgray;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .menu-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 32px rgba(0,0,0,0.13);
            border-color: steelblue;
        }

        .menu-card .menu-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, midnightblue, darkblue);
            color: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
        }

        .menu-card h3 {
            font-size: 1.15rem;
            color: midnightblue;
            font-weight: 700;
        }

        .menu-card p {
            font-size: 0.9rem;
            color: slategray;
            line-height: 1.4;
        }

        .menu-card .arrow {
            margin-top: auto;
            color: steelblue;
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Footer note */
        .footer-note {
            text-align: center;
            margin-top: 40px;
            color: slategray;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 14px;
                text-align: center;
                padding: 16px 20px;
            }

            .welcome-card {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 500px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        @media print {
            .no-print { display: none !important; }
            header { display: none !important; }
            body { background: white !important; color: black !important; }
            .welcome-card, .stat-card, .menu-card { background: white !important; box-shadow: none !important; border: 1px solid gray; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 8px; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <i class="fas fa-crown"></i>
        <div>
            <h1>CEO Dashboard</h1>
            <span>Iran-Uganda Hospital</span>
        </div>
    </div>
    <div class="header-right">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<div class="container">

    <!-- Welcome -->
    <div class="welcome-card">
        <div>
            <h2>Welcome, <?= $ceo_name ?></h2>
            <p>Executive overview of hospital operations and strategic control.</p>
        </div>
        <div class="badge">
            <i class="fas fa-user-tie"></i> Chief Executive Officer
        </div>
    </div>

    <!-- Analytics -->
    <h2 class="section-title">
        <i class="fas fa-chart-line"></i> Key Analytics
    </h2>
    <button class="no-print" style="float:right; margin-bottom:12px; padding:6px 12px; background:#2563eb; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.875rem;" onclick="window.print()">
        <i class="fas fa-print"></i> Print Analytics
    </button>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-md"></i></div>
            <div class="label">Total Doctors</div>
            <div class="value"><?= $total_doctors ?></div>
            <div class="sub">Medical specialists</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-nurse"></i></div>
            <div class="label">Total Nurses</div>
            <div class="value"><?= $total_nurses ?></div>
            <div class="sub">Care team</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-hospital-user"></i></div>
            <div class="label">Total Patients</div>
            <div class="value"><?= $total_patients ?></div>
            <div class="sub">Registered patients</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="label">Total Staff</div>
            <div class="value"><?= $total_staff ?></div>
            <div class="sub">All active staff</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <div class="label">Appointments</div>
            <div class="value"><?= $total_appointments ?></div>
            <div class="sub">Pending: <?= $pending_appointments ?></div>
        </div>
    </div>

    <!-- Executive Menu -->
    <h2 class="section-title">
        <i class="fas fa-briefcase"></i> Executive Menu
    </h2>

    <div class="menu-grid">
        <a href="mission_vision.php" class="menu-card">
            <div class="menu-icon"><i class="fas fa-bullseye"></i></div>
            <h3>Mission & Vision</h3>
            <p>Review and update the hospital’s core mission and long-term vision.</p>
            <div class="arrow">Open <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="strategic_plan.php" class="menu-card">
            <div class="menu-icon"><i class="fas fa-chess"></i></div>
            <h3>Strategic Plan</h3>
            <p>Access and manage the hospital’s strategic roadmap and goals.</p>
            <div class="arrow">Open <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="policies.php" class="menu-card">
            <div class="menu-icon"><i class="fas fa-gavel"></i></div>
            <h3>Policies</h3>
            <p>View and oversee institutional policies and governance documents.</p>
            <div class="arrow">Open <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="reports.php" class="menu-card">
            <div class="menu-icon"><i class="fas fa-file-alt"></i></div>
            <h3>Reports</h3>
            <p>Generate and review high-level operational and financial reports.</p>
            <div class="arrow">Open <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="send_message.php" class="menu-card">
            <div class="menu-icon"><i class="fas fa-comments"></i></div>
            <h3>Communicate</h3>
            <p>Send official messages and communicate with key hospital leadership.</p>
            <div class="arrow">Open <i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <div class="footer-note">
        Iran-Uganda Hospital • Confidential Executive Portal
    </div>

</div>

</body>
</html>