<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medical_director') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$period = $_GET['period'] ?? 'daily';
if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
    $period = 'daily';
}

if ($period === 'daily') {
    $label = 'Daily Report';
    $date_cond = "DATE(created_at)=CURDATE()";
} elseif ($period === 'weekly') {
    $label = 'Weekly Report';
    $date_cond = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} else {
    $label = 'Monthly Report';
    $date_cond = "MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())";
}

$new_patients   = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE $date_cond")->fetch_assoc()['c'];
$total_appts    = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE $date_cond")->fetch_assoc()['c'];
$pending_appts  = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE $date_cond AND status='pending'")->fetch_assoc()['c'];
$approved_appts = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE $date_cond AND status='approved'")->fetch_assoc()['c'];
$declined_appts = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE $date_cond AND status='declined'")->fetch_assoc()['c'];
$completed_appts= $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE $date_cond AND status='completed'")->fetch_assoc()['c'];
$records        = $conn->query("SELECT COUNT(*) AS c FROM medical_records WHERE $date_cond")->fetch_assoc()['c'];
$bills_total    = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills WHERE $date_cond")->fetch_assoc()['t'];
$bills_paid     = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills WHERE $date_cond AND status='paid'")->fetch_assoc()['t'];
$bills_unpaid   = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills WHERE $date_cond AND status='unpaid'")->fetch_assoc()['t'];

$generated_on = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($label); ?> | Medical Director</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: darkslateblue;
            --border: lightgray;
            --text: darkslategray;
            --muted: slategray;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: whitesmoke;
            color: var(--text);
            line-height: 1.5;
            padding: 24px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            border: 1px solid var(--border);
        }
        h1 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 6px;
        }
        .meta { color: var(--muted); margin-bottom: 18px; }
        .actions { margin-bottom: 20px; }
        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            background: var(--primary);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 8px;
        }
        .btn-secondary { background: slategray; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 14px; border: 1px solid var(--border); text-align: left; }
        th { background: var(--primary); color: white; }
        tr:nth-child(even) { background: whitesmoke; }
        @media print {
            .actions { display: none; }
            body { background: white; padding: 0; }
            .container { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1><?php echo htmlspecialchars($label); ?></h1>
    <div class="meta">Generated: <?php echo htmlspecialchars($generated_on); ?></div>
    <div class="actions">
        <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print / Save</button>
        <a class="btn btn-secondary" href="javascript:history.back()">Back to Dashboard</a>
    </div>
    <table>
        <tr><th>Metric</th><th>Value</th></tr>
        <tr><td>New Patients</td><td><?php echo (int)$new_patients; ?></td></tr>
        <tr><td>Total Appointments</td><td><?php echo (int)$total_appts; ?></td></tr>
        <tr><td>Pending Appointments</td><td><?php echo (int)$pending_appts; ?></td></tr>
        <tr><td>Approved Appointments</td><td><?php echo (int)$approved_appts; ?></td></tr>
        <tr><td>Declined Appointments</td><td><?php echo (int)$declined_appts; ?></td></tr>
        <tr><td>Completed Appointments</td><td><?php echo (int)$completed_appts; ?></td></tr>
        <tr><td>Medical Records Added</td><td><?php echo (int)$records; ?></td></tr>
        <tr><td>Total Bills Amount</td><td>USh <?php echo number_format((float)$bills_total, 2); ?></td></tr>
        <tr><td>Paid Bills Amount</td><td>USh <?php echo number_format((float)$bills_paid, 2); ?></td></tr>
        <tr><td>Unpaid Bills Amount</td><td>USh <?php echo number_format((float)$bills_unpaid, 2); ?></td></tr>
    </table>
</div>
</body>
</html>
