<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: login_form.php");
    exit;
}
require 'db.php';

$doctor_id   = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'];
$pharmacy_id = $_SESSION['user_id'];

// Auto-discontinue expired drugs
$today = date('Y-m-d');
$expired = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' AND expiry_date IS NOT NULL AND expiry_date < '$today'");
while ($d = $expired->fetch_assoc()) {
    $conn->query("INSERT INTO expired_drugs (original_drug_id, name, category, price, stock, expiry_date, supplier, discontinued_by) 
        VALUES ({$d['id']}, '{$d['name']}', '{$d['category']}', {$d['price']}, {$d['stock']}, '{$d['expiry_date']}', '{$d['supplier']}', $pharmacy_id)");
    $conn->query("UPDATE pharmacy_drugs SET status='discontinued' WHERE id={$d['id']}");
}

// Handle issue prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_rx'])) {
    $patient_id = intval($_POST['patient_id']);
    $drug_id    = intval($_POST['drug_id']);
    $dosage     = trim($_POST['dosage']);
    $drugName   = trim($_POST['drug_name']);
    $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, drug_id, drug_name, dosage, status) VALUES (?,?,?,?,?,'issued')");
    $stmt->bind_param("iiiss", $patient_id, $doctor_id, $drug_id, $drugName, $dosage);
    if ($stmt->execute()) {
        header("Location: doctor_prescriptions.php?success=Prescription+sent+to+pharmacy");
        exit;
    } else {
        header("Location: doctor_prescriptions.php?error=Failed+to+issue+prescription");
        exit;
    }
}

// Handle cancel prescription (doctor only)
if (isset($_GET['cancel_rx'])) {
    $id = intval($_GET['cancel_rx']);
    $conn->query("UPDATE prescriptions SET status='cancelled' WHERE id=$id AND doctor_id=$doctor_id");
    header("Location: doctor_prescriptions.php?success=Prescription+cancelled");
    exit;
}

// Fetch prescriptions issued by this doctor
$prescriptions = $conn->query("
    SELECT pr.*, p.name AS patient_name
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    WHERE pr.doctor_id = $doctor_id
    ORDER BY pr.issued_at DESC
");

// Fetch patients and drugs for dropdown
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name");
$drugs    = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' ORDER BY name");

// Stats
$issued_count  = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE status='issued' AND doctor_id=$doctor_id")->fetch_assoc()['c'];
$dispensed_count = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE status='dispensed' AND doctor_id=$doctor_id")->fetch_assoc()['c'];
$total_rx      = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE doctor_id=$doctor_id")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - Doctor | Iran-Uganda Hospital</title>
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; border-radius: 10px; padding: 18px 20px; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .stat-card .label { font-size: 0.85rem; color: var(--muted); margin-bottom: 6px; font-weight: 500; }
        .stat-card .value { font-size: 1.7rem; font-weight: 700; color: var(--primary); }
        .stat-card .sub { font-size: 0.8rem; color: var(--muted); margin-top: 4px; }
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
        .status-issued { background: #dbeafe; color: #1e40af; }
        .status-dispensed { background: #c6f6d5; color: #276749; }
        .status-cancelled { background: #fed7d7; color: #9b2c2c; }
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
            .stats-grid { grid-template-columns: 1fr; }
            .card { padding: 18px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-user-md"></i> Prescriptions</h1>
    <nav>
        <a href="doctor_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="doctor_clearances.php"><i class="fas fa-paper-plane"></i> Clearances</a>
        <a href="doctor_prescriptions.php" class="btn-info"><i class="fas fa-file-prescription"></i> Prescriptions</a>
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

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Prescriptions</div>
            <div class="value"><?php echo $total_rx; ?></div>
            <div class="sub">Issued by you</div>
        </div>
        <div class="stat-card">
            <div class="label">Issued</div>
            <div class="value"><?php echo $issued_count; ?></div>
            <div class="sub">Pending at pharmacy</div>
        </div>
        <div class="stat-card">
            <div class="label">Dispensed</div>
            <div class="value"><?php echo $dispensed_count; ?></div>
            <div class="sub">Given to patients</div>
        </div>
    </div>

    <!-- Issue Prescription -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Issue New Prescription</h2>
        <p>Send a prescription to the pharmacy for the patient.</p>
        <form method="POST" action="doctor_prescriptions.php">
            <input type="hidden" name="issue_rx" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label>Patient</label>
                    <select name="patient_id" required>
                        <option value="">Select patient</option>
                        <?php while ($p = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Drug</label>
                    <select name="drug_id" required>
                        <option value="">Select drug</option>
                        <?php while ($d = $drugs->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Drug Name (custom)</label>
                    <input type="text" name="drug_name" placeholder="e.g. Paracetamol 500mg" required>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Dosage</label>
                    <input type="text" name="dosage" placeholder="e.g. 1 tablet twice daily" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Issue Prescription</button>
        </form>
    </div>

    <!-- Prescription List -->
    <div class="card">
        <h2><i class="fas fa-list"></i> My Prescriptions</h2>
        <button class="btn btn-sm btn-print no-print" style="float:right; margin-bottom:12px;" onclick="window.print()"><i class="fas fa-print"></i> Print Prescriptions</button>
        <?php if ($prescriptions->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Rx ID</th>
                            <th>Patient</th>
                            <th>Drug</th>
                            <th>Dosage</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $prescriptions->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['drug_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['dosage']); ?></td>
                                <td>
                                    <span class="status status-<?php echo $r['status']; ?>">
                                        <?php echo ucfirst($r['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($r['issued_at'])); ?></td>
                                <td>
                                    <?php if ($r['status'] === 'issued'): ?>
                                        <a href="doctor_prescriptions.php?cancel_rx=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this prescription?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled><?php echo ucfirst($r['status']); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No prescriptions issued yet.</div>
        <?php endif; ?>
    </div>

    <!-- Printable Version -->
    <div class="print-section">
        <h1>Prescriptions Report</h1>
        <p>Doctor: <?php echo htmlspecialchars($doctor_name); ?></p>
        <p>Generated on: <?php echo date('M d, Y H:i'); ?></p>
        <table>
            <thead>
                <tr>
                    <th>Rx ID</th>
                    <th>Patient</th>
                    <th>Drug</th>
                    <th>Dosage</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $printRx = $conn->query("
                    SELECT pr.*, p.name AS patient_name
                    FROM prescriptions pr
                    JOIN patients p ON pr.patient_id = p.id
                    WHERE pr.doctor_id = $doctor_id
                    ORDER BY pr.issued_at DESC
                ");
                while ($r = $printRx->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $r['id']; ?></td>
                        <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['drug_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['dosage']); ?></td>
                        <td><?php echo ucfirst($r['status']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($r['issued_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
