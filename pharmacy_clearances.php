<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacy') {
    header("Location: login.php");
    exit;
}
require 'db.php';
require_once 'audit.php';

$pharmacy_id   = $_SESSION['user_id'];
$pharmacy_name = $_SESSION['user_name'];

// Mark drug clearance as completed (drugs dispensed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_clearance'])) {
    $clearance_id = intval($_POST['clearance_id']);
    $conn->query("UPDATE payment_clearances SET status='completed' WHERE id=$clearance_id AND department='pharmacy'");
    audit_log($conn, $pharmacy_id, 'complete_pharmacy_clearance', 'clearance_id=' . $clearance_id);
    header("Location: pharmacy_clearances.php?success=Drug+clearance+marked+as+completed");
    exit;
}

// Fetch drug clearances for pharmacy
$clearances = $conn->query("SELECT pc.*, p.name AS patient_name, u.name AS cashier_name, b.amount AS bill_amount FROM payment_clearances pc JOIN patients p ON pc.patient_id = p.id JOIN users u ON pc.authorized_by = u.id LEFT JOIN medical_bills b ON pc.bill_id = b.id WHERE pc.department='pharmacy' ORDER BY pc.created_at DESC");

// Fetch patients for reference
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Clearances - Pharmacy | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #047857;
            --primary-light: #059669;
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
            background: url('images/hospital1.jpg') center/cover fixed;
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
        header h1 { font-size: 1.4rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        header nav a { color: white; text-decoration: none; font-weight: 500; padding: 8px 14px; border-radius: 6px; transition: background 0.2s; margin-left: 6px; }
        header nav a:hover { background: rgba(255,255,255,0.15); }
        header nav a.logout { background: rgba(255,255,255,0.12); }
        .container { max-width: 1200px; margin: 28px auto; padding: 0 20px 40px; }
        .card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 24px 28px; margin-bottom: 28px; backdrop-filter: blur(4px); }
        .card h2 { color: var(--primary); font-size: 1.25rem; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 7px; font-size: 0.875rem; font-weight: 500; text-decoration: none; color: white; border: none; cursor: pointer; transition: all 0.2s; margin-right: 6px; margin-top: 4px; }
        .btn-primary { background: var(--primary-light); }
        .btn-primary:hover { background: var(--primary); }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #276749; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #9b2c2c; }
        .btn-info { background: var(--info); }
        .btn-info:hover { background: #2c7a7b; }
        .btn-secondary { background: #718096; }
        .btn-secondary:hover { background: #4a5568; }
        .btn-sm { padding: 5px 11px; font-size: 0.82rem; }
        .btn-print { background: #4a5568; }
        .btn-print:hover { background: #2d3748; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th { background: var(--primary); color: white; padding: 13px 16px; text-align: left; font-weight: 600; }
        td { padding: 13px 16px; border-bottom: 1px solid var(--border); }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #edf2f7; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-pending { background: #fefcbf; color: #975a16; }
        .status-completed { background: #c6f6d5; color: #276749; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }
        .empty-state { text-align: center; padding: 30px; color: var(--muted); }
        .no-print { display: inline; }
        @media print {
            .no-print { display: none !important; }
            header, nav, .btn, .modal { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid #ccc; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 8px; }
        }
        @media (max-width: 640px) {
            header { flex-direction: column; gap: 10px; text-align: center; }
            header nav { width: 100%; justify-content: center; }
            .card { padding: 18px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-capsules"></i> Drug Clearances</h1>
    <nav>
        <a href="pharmacy_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="pharmacy_drugs.php"><i class="fas fa-capsules"></i> Drug Inventory</a>
        <a href="expired_drugs.php"><i class="fas fa-trash"></i> Expired Drugs</a>
        <a href="pharmacy_prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a>
        <a href="pharmacy_clearances.php" class="btn-info"><i class="fas fa-paper-plane"></i> Clearances</a>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<div class="container">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-list"></i> Drug Clearances Awaiting Dispensing</h2>
        <button class="btn btn-sm btn-print no-print" style="float:right; margin-bottom:12px;" onclick="window.print()"><i class="fas fa-print"></i> Print Clearances</button>
        <p>Clearances sent by the cashier authorizing drug dispensing for patients whose bills are paid.</p>
        <?php if ($clearances->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Bill Amount</th>
                            <th>Authorization</th>
                            <th>Status</th>
                            <th>Authorized By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $clearances->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $c['id']; ?></td>
                                <td><?php echo htmlspecialchars($c['patient_name']); ?></td>
                                <td>USh <?php echo number_format($c['bill_amount'] ?? 0, 2); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($c['authorization_type'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo $c['status']; ?>">
                                        <?php echo ucfirst($c['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($c['cashier_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></td>
                                <td>
                                    <?php if ($c['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="clearance_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" name="complete_clearance" class="btn btn-sm btn-success" onclick="return confirm('Mark this drug clearance as completed (drugs dispensed)?')">
                                                <i class="fas fa-check"></i> Drugs Given
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Completed</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="font-size:48px; margin-bottom:15px; color:#ccc;"></i>
                <p>No drug clearances awaiting dispensing.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

</body>
</html>
