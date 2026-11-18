<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ceo') {
    header('Location: login.php');
    exit;
}
include 'db.php';

$ceo_name = htmlspecialchars($_SESSION['user_name'] ?? 'CEO');

// Core counts
$total_doctors     = $conn->query("SELECT COUNT(*) c FROM users WHERE role='doctor'")->fetch_assoc()['c'];
$total_nurses      = $conn->query("SELECT COUNT(*) c FROM users WHERE role='nurse'")->fetch_assoc()['c'];
$total_patients    = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$total_staff       = $conn->query("SELECT COUNT(*) c FROM users WHERE role IN ('doctor','nurse','receptionist','pharmacy','cashier')")->fetch_assoc()['c'];

// Financial
$total_collected   = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM medical_bills WHERE status='paid'")->fetch_assoc()['t'];
$total_unpaid      = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM medical_bills WHERE status='unpaid'")->fetch_assoc()['t'];
$total_bills       = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM medical_bills")->fetch_assoc()['t'];

// Appointments
$total_appointments = $conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'];
$pending_apt        = $conn->query("SELECT COUNT(*) c FROM appointments WHERE status='pending'")->fetch_assoc()['c'];
$approved_apt       = $conn->query("SELECT COUNT(*) c FROM appointments WHERE status='approved'")->fetch_assoc()['c'];

// Patient status
$admitted     = $conn->query("SELECT COUNT(*) c FROM patients WHERE patient_status='admitted'")->fetch_assoc()['c'];
$discharged   = $conn->query("SELECT COUNT(*) c FROM patients WHERE patient_status='discharged'")->fetch_assoc()['c'];
$outpatient   = $conn->query("SELECT COUNT(*) c FROM patients WHERE patient_status='outpatient'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | CEO Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, lightsteelblue, lavender);
            min-height: 100vh;
            color: darkslategray;
            line-height: 1.5;
        }

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
            font-size: 1.7rem;
            color: gold;
        }

        .header-left h1 {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .header-left span {
            display: block;
            font-size: 0.85rem;
            opacity: 0.85;
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

        .container {
            max-width: 1200px;
            margin: 35px auto 50px;
            padding: 0 24px;
        }

        .page-header {
            background: white;
            border-radius: 16px;
            padding: 26px 32px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid lightgray;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h2 {
            font-size: 1.55rem;
            color: midnightblue;
            font-weight: 700;
        }

        .page-header p {
            color: slategray;
            margin-top: 4px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary { background: midnightblue; }
        .btn-primary:hover { background: darkblue; }

        .btn-secondary { background: slategray; }
        .btn-secondary:hover { background: dimgray; }

        .section-title {
            font-size: 1.2rem;
            color: midnightblue;
            font-weight: 700;
            margin: 30px 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 10px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 22px 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
            border: 1px solid lightgray;
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
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
            width: 46px;
            height: 46px;
            background: lavender;
            color: midnightblue;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 12px;
        }

        .stat-card .label {
            font-size: 0.88rem;
            color: slategray;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: midnightblue;
        }

        .stat-card .sub {
            font-size: 0.8rem;
            color: dimgray;
            margin-top: 4px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 24px;
            margin-top: 10px;
        }

        .chart-card {
            background: white;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
            border: 1px solid lightgray;
        }

        .chart-card h3 {
            font-size: 1.05rem;
            color: midnightblue;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 260px;
            width: 100%;
        }

        .footer-note {
            text-align: center;
            margin-top: 40px;
            color: slategray;
            font-size: 0.9rem;
        }

        @media print {
            header, .actions, .btn { display: none !important; }
            body { background: white; }
            .stat-card, .chart-card { box-shadow: none; border: 1px solid darkgray; }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 14px;
                text-align: center;
                padding: 16px 20px;
            }
            .page-header {
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
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <i class="fas fa-file-alt"></i>
        <div>
            <h1>Executive Reports</h1>
            <span>Iran-Uganda Hospital</span>
        </div>
    </div>
    
</header>

<div class="container">

    <div class="page-header">
        <div>
            <h2>Hospital Performance Report</h2>
            <p>Prepared for <?= $ceo_name ?> • Real-time overview</p>
        </div>
        <div class="actions">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
            <a href="export_audit.php" class="btn btn-secondary">
                <i class="fas fa-file-export"></i> Export Audit CSV
            </a>
            <a href="ceo_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Key Metrics -->
    <h2 class="section-title">
        <i class="fas fa-chart-pie"></i> Key Performance Indicators
    </h2>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-md"></i></div>
            <div class="label">Doctors</div>
            <div class="value"><?= $total_doctors ?></div>
            <div class="sub">Active medical specialists</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-nurse"></i></div>
            <div class="label">Nurses</div>
            <div class="value"><?= $total_nurses ?></div>
            <div class="sub">Nursing staff</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-hospital-user"></i></div>
            <div class="label">Total Patients</div>
            <div class="value"><?= $total_patients ?></div>
            <div class="sub">Registered in system</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="label">Total Staff</div>
            <div class="value"><?= $total_staff ?></div>
            <div class="sub">All operational staff</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="label">Amount Collected</div>
            <div class="value">USh <?= number_format($total_collected, 0) ?></div>
            <div class="sub">Paid medical bills</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="label">Unpaid Bills</div>
            <div class="value">USh <?= number_format($total_unpaid, 0) ?></div>
            <div class="sub">Outstanding amount</div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <div class="label">Appointments</div>
            <div class="value"><?= $total_appointments ?></div>
            <div class="sub">Pending: <?= $pending_apt ?> • Approved: <?= $approved_apt ?></div>
        </div>

        <div class="stat-card">
            <div class="icon"><i class="fas fa-bed"></i></div>
            <div class="label">Patient Status</div>
            <div class="value"><?= $admitted ?></div>
            <div class="sub">Admitted • <?= $discharged ?> Discharged • <?= $outpatient ?> Outpatient</div>
        </div>
    </div>

    <!-- Charts -->
    <h2 class="section-title">
        <i class="fas fa-chart-bar"></i> Visual Insights
    </h2>

    <div class="charts-grid">
        <div class="chart-card">
            <h3>Staff Distribution</h3>
            <div class="chart-container">
                <canvas id="staffChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>Patient Status</h3>
            <div class="chart-container">
                <canvas id="patientChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>Billing Overview</h3>
            <div class="chart-container">
                <canvas id="billingChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>Appointments Status</h3>
            <div class="chart-container">
                <canvas id="appointmentChart"></canvas>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Iran-Uganda Hospital • Confidential Executive Report • Generated for CEO
    </div>

</div>

<script>
// Staff Distribution
new Chart(document.getElementById('staffChart'), {
    type: 'bar',
    data: {
        labels: ['Doctors', 'Nurses'],
        datasets: [{
            label: 'Staff Count',
            data: [<?= $total_doctors ?>, <?= $total_nurses ?>],
            backgroundColor: ['steelblue', 'seagreen'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Patient Status
new Chart(document.getElementById('patientChart'), {
    type: 'doughnut',
    data: {
        labels: ['Admitted', 'Discharged', 'Outpatient'],
        datasets: [{
            data: [<?= $admitted ?>, <?= $discharged ?>, <?= $outpatient ?>],
            backgroundColor: ['darkorange', 'seagreen', 'steelblue']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Billing Overview
new Chart(document.getElementById('billingChart'), {
    type: 'pie',
    data: {
        labels: ['Collected', 'Unpaid'],
        datasets: [{
            data: [<?= $total_collected ?>, <?= $total_unpaid ?>],
            backgroundColor: ['seagreen', 'crimson']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Appointments
new Chart(document.getElementById('appointmentChart'), {
    type: 'bar',
    data: {
        labels: ['Pending', 'Approved'],
        datasets: [{
            label: 'Appointments',
            data: [<?= $pending_apt ?>, <?= $approved_apt ?>],
            backgroundColor: ['darkorange', 'seagreen'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>

</body>
</html>