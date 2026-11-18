<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: login_form.php");
    exit;
}
require 'db.php';

$doctor_id   = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'];

// Handle admit patient (also complete clearance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admit_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $clearance_id = intval($_POST['clearance_id']);
    $conn->query("UPDATE patients SET patient_status='admitted' WHERE id=$patient_id");
    $conn->query("UPDATE payment_clearances SET status='completed' WHERE id=$clearance_id AND department='doctor' AND authorization_type='admit'");
    header("Location: doctor_clearances.php?success=Patient+admitted+and+clearance+completed");
    exit;
}

// Handle discharge patient (also complete clearance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discharge_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $clearance_id = intval($_POST['clearance_id']);
    $conn->query("UPDATE patients SET patient_status='discharged' WHERE id=$patient_id");
    $conn->query("UPDATE payment_clearances SET status='completed' WHERE id=$clearance_id AND department='doctor' AND authorization_type='discharge'");
    header("Location: doctor_clearances.php?success=Patient+discharged+and+clearance+completed");
    exit;
}

// Handle complete clearance without changing patient status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_clearance'])) {
    $clearance_id = intval($_POST['clearance_id']);
    $conn->query("UPDATE payment_clearances SET status='completed' WHERE id=$clearance_id AND department='doctor'");
    header("Location: doctor_clearances.php?success=Clearance+marked+as+completed");
    exit;
}

// Fetch admit/discharge clearances for doctor
$clearances = $conn->query("
    SELECT pc.*, p.name AS patient_name, u.name AS cashier_name, b.amount AS bill_amount
    FROM payment_clearances pc
    JOIN patients p ON pc.patient_id = p.id
    JOIN users u ON pc.authorized_by = u.id
    LEFT JOIN medical_bills b ON pc.bill_id = b.id
    WHERE pc.department='doctor' AND pc.authorization_type IN ('admit','discharge')
    ORDER BY pc.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Clearances - Doctor | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2b6cb0;
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
        .btn-warning { background: #d69e2e; }
        .btn-warning:hover { background: #b45309; }
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
        .status-admitted { background: #fef3c7; color: #92400e; }
        .status-discharged { background: #e2e8f0; color: #64748b; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 6px; color: var(--text); }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; font-family: inherit; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
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
    <h1><i class="fas fa-user-md"></i> Patient Clearances</h1>
    <nav>
        <a href="doctor_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="doctor_clearances.php" class="btn-info"><i class="fas fa-paper-plane"></i> Clearances</a>
        <a href="doctor_prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a>
        <a href="manage_records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
        <a href="manage_patients.php"><i class="fas fa-hospital-user"></i> Patients</a>
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
        <h2><i class="fas fa-list"></i> Admit / Discharge Clearances</h2>
        <button class="btn btn-sm btn-print no-print" style="float:right; margin-bottom:12px;" onclick="window.print()"><i class="fas fa-print"></i> Print Clearances</button>
        <p>Clearances sent by the cashier authorizing patient admission or discharge after payment is confirmed.</p>
        <?php if ($clearances->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Bill Amount</th>
                            <th>Authorization</th>
                            <th>Patient Status</th>
                            <th>Clearance Status</th>
                            <th>Authorized By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $clearances->fetch_assoc()): ?>
                            <?php
                            $pat_stmt = $conn->prepare("SELECT patient_status FROM patients WHERE id=?");
                            $pat_stmt->bind_param("i", $c['patient_id']);
                            $pat_stmt->execute();
                            $pat_status = $pat_stmt->get_result()->fetch_assoc()['patient_status'] ?? 'outpatient';
                            ?>
                            <tr>
                                <td>#<?php echo $c['id']; ?></td>
                                <td><?php echo htmlspecialchars($c['patient_name']); ?></td>
                                <td>USh <?php echo number_format($c['bill_amount'] ?? 0, 2); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($c['authorization_type'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo $pat_status; ?>">
                                        <?php echo ucfirst($pat_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-<?php echo $c['status']; ?>">
                                        <?php echo ucfirst($c['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($c['cashier_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></td>
                                <td>
                                    <?php if ($c['status'] === 'pending'): ?>
                                        <?php if ($c['authorization_type'] === 'admit' && $pat_status === 'outpatient'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Admit this patient?')">
                                                <input type="hidden" name="patient_id" value="<?php echo $c['patient_id']; ?>">
                                                <input type="hidden" name="clearance_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" name="admit_patient" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-bed"></i> Admit
                                                </button>
                                            </form>
                                        <?php elseif ($c['authorization_type'] === 'discharge' && $pat_status === 'admitted'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Discharge this patient?')">
                                                <input type="hidden" name="patient_id" value="<?php echo $c['patient_id']; ?>">
                                                <input type="hidden" name="clearance_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" name="discharge_patient" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-hospital"></i> Discharge
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="clearance_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" name="complete_clearance" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
                <p>No admit/discharge clearances awaiting action.</p>
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
