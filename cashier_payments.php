<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cashier') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$cashier_name = $_SESSION['user_name'];

// Fetch paid payments history
$payments = $conn->query("
    SELECT b.id, b.amount, b.description, b.created_at, p.name AS patient_name
    FROM medical_bills b
    JOIN patients p ON b.patient_id = p.id
    WHERE b.status = 'paid'
    ORDER BY b.created_at DESC
");

// Stats
$total_collected = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills WHERE status='paid'")->fetch_assoc()['t'];
$paid_count      = $conn->query("SELECT COUNT(*) AS c FROM medical_bills WHERE status='paid'")->fetch_assoc()['c'];
$unpaid_count    = $conn->query("SELECT COUNT(*) AS c FROM medical_bills WHERE status='unpaid'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Cashier | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6d28d9;
            --primary-light: #7c3aed;
            --success: #2f855a;
            --danger: #c53030;
            --warning: #d69e2e;
            --info: #319795;
            --bg: #f7fafc;
            --card-bg: rgba(255, 255, 255, 0.92);
            --border: #e2e8f0;
            --text: #2d3748;
            --muted: #718096;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/medical-equipment.jpg') center/cover fixed;
            background-color: #e2e8f0;
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background 0.2s;
            margin-left: 6px;
        }

        header nav a:hover {
            background: rgba(255,255,255,0.15);
        }

        header nav a.logout {
            background: rgba(255,255,255,0.12);
        }

        .container {
            max-width: 1200px;
            margin: 28px auto;
            padding: 0 20px 40px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 24px 28px;
            margin-bottom: 28px;
            backdrop-filter: blur(4px);
        }

        .card h2 {
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 18px 20px;
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .stat-card .label {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .stat-card .value {
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card .sub {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 7px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 6px;
            margin-top: 4px;
        }

        .btn-primary { background: var(--primary-light); }
        .btn-primary:hover { background: var(--primary); }

        .btn-success { background: var(--success); }
        .btn-success:hover { background: #276749; }

        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #9b2c2c; }

        .btn-info { background: var(--info); }
        .btn-info:hover { background: #2c7a7b; }

        .btn-secondary {
            background: #718096;
        }
        .btn-secondary:hover {
            background: #4a5568;
        }

        .btn-sm { padding: 5px 11px; font-size: 0.82rem; }

        /* Tables */
        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th {
            background: var(--primary);
            color: white;
            padding: 13px 16px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
        }

        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #edf2f7; }

        .status-paid { background: #c6f6d5; color: #276749; }

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--muted);
        }

        @media (max-width: 640px) {
            header { flex-direction: column; gap: 10px; text-align: center; }
            header nav { width: 100%; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .card { padding: 18px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-wallet"></i> Payments Received</h1>
    <nav>
        <a href="cashier_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="cashier_billing.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
        <a href="cashier_payments.php"><i class="fas fa-wallet"></i> Payments</a>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<div class="container">
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Collected</div>
            <div class="value">USh <?php echo number_format($total_collected, 2); ?></div>
            <div class="sub">All payments received</div>
        </div>
        <div class="stat-card">
            <div class="label">Paid Bills</div>
            <div class="value"><?php echo $paid_count; ?></div>
            <div class="sub">Fully settled</div>
        </div>
        <div class="stat-card">
            <div class="label">Unpaid Bills</div>
            <div class="value"><?php echo $unpaid_count; ?></div>
            <div class="sub">Still outstanding</div>
        </div>
    </div>

    <!-- Payments History -->
    <div class="card">
        <h2><i class="fas fa-history"></i> Payment History</h2>
        <a href="export_payments.php" class="btn btn-sm btn-primary no-print" style="float:right; margin-bottom:12px;"><i class="fas fa-file-export"></i> Export CSV</a>
        <?php if ($payments->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $payments->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><?php echo htmlspecialchars($p['patient_name']); ?></td>
                                <td>USh <?php echo number_format($p['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($p['description'] ?: '-'); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($p['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No payments recorded yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>