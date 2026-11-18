<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cashier') {
    header("Location: login.php");
    exit;
}
require 'db.php';
require_once 'audit.php';

$cashier_id   = $_SESSION['user_id'];
$cashier_name = $_SESSION['user_name'];

// Handle mark bill paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $bill_id = intval($_POST['bill_id']);
    $method  = trim($_POST['method']);
    $conn->query("UPDATE medical_bills SET status='paid' WHERE id=$bill_id");
    audit_log($conn, $cashier_id, 'record_payment', 'bill_id=' . $bill_id . ', method=' . $method . ', source=clearances');
    header("Location: cashier_clearances.php?success=Payment+recorded");
    exit;
}

// Handle create clearance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_clearance'])) {
    $bill_id           = intval($_POST['bill_id']);
    $auth_type         = trim($_POST['authorization_type']);
    $department        = trim($_POST['department']);
    $patient_id = 0;
    $bp = $conn->prepare("SELECT patient_id FROM medical_bills WHERE id=?");
    if ($bp) {
        $bp->bind_param("i", $bill_id);
        $bp->execute();
        $br = $bp->get_result()->fetch_assoc();
        if ($br) { $patient_id = intval($br['patient_id']); }
        $bp->close();
    }
    $stmt = $conn->prepare("INSERT INTO payment_clearances (patient_id, bill_id, authorized_by, authorization_type, department, status) VALUES (?,?,?,?,?,'pending')");
    $stmt->bind_param("iiiss", $patient_id, $bill_id, $cashier_id, $auth_type, $department);
    if ($stmt->execute()) {
        $cid = $stmt->insert_id;
        audit_log($conn, $cashier_id, 'create_clearance', 'clearance_id=' . $cid . ', bill_id=' . $bill_id . ', patient_id=' . $patient_id . ', type=' . $auth_type . ', department=' . $department);
        header("Location: cashier_clearances.php?success=Clearance+sent+to+".ucfirst($department));
        exit;
    } else {
        header("Location: cashier_clearances.php?error=Failed+to+create+clearance");
        exit;
    }
}

// Handle complete clearance (mark as done by department)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_clearance'])) {
    $clearance_id = intval($_POST['clearance_id']);
    $conn->query("UPDATE payment_clearances SET status='completed' WHERE id=$clearance_id");
    audit_log($conn, $cashier_id, 'complete_clearance', 'clearance_id=' . $clearance_id . ', by=cashier');
    header("Location: cashier_clearances.php?success=Clearance+marked+as+completed");
    exit;
}

// Fetch unpaid bills
$unpaidBills = $conn->query("
    SELECT b.*, p.name AS patient_name
    FROM medical_bills b
    JOIN patients p ON b.patient_id = p.id
    WHERE b.status='unpaid'
    ORDER BY b.created_at DESC
");

// Fetch paid bills for clearance creation
$paidBills = $conn->query("
    SELECT b.*, p.name AS patient_name
    FROM medical_bills b
    JOIN patients p ON b.patient_id = p.id
    WHERE b.status='paid'
    ORDER BY b.created_at DESC
");

// Fetch all clearances
$clearances = $conn->query("
    SELECT pc.*, p.name AS patient_name, u.name AS cashier_name
    FROM payment_clearances pc
    JOIN patients p ON pc.patient_id = p.id
    JOIN users u ON pc.authorized_by = u.id
    ORDER BY pc.created_at DESC
");

// Fetch patients for dropdown
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Clearances - Cashier | Iran-Uganda Hospital</title>
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
        .status-paid { background: #c6f6d5; color: #276749; }
        .status-unpaid { background: #fed7d7; color: #9b2c2c; }
        .status-pending { background: #fefcbf; color: #975a16; }
        .status-completed { background: #c6f6d5; color: #276749; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 6px; color: var(--text); }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; font-family: inherit; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .empty-state { text-align: center; padding: 30px; color: var(--muted); }
        .print-section { display: none; }
        @media print {
            .no-print { display: none !important; }
            .print-section { display: block !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid #ccc; }
            header, nav, .btn, .modal, .status-paid, .status-unpaid { display: none !important; }
            table { border-collapse: collapse; }
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
    <h1><i class="fas fa-money-bill-wave"></i> Payment Clearances</h1>
    <nav>
        <a href="cashier_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="cashier_billing.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
        <a href="cashier_payments.php"><i class="fas fa-wallet"></i> Payments</a>
        <a href="cashier_clearances.php" class="btn-info"><i class="fas fa-paper-plane"></i> Clearances</a>
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

    <!-- Unpaid Bills: Record Payment -->
    <div class="card">
        <h2><i class="fas fa-wallet"></i> Record Payment & Send Clearance</h2>
        <?php if ($unpaidBills->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($b = $unpaidBills->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['patient_name']); ?></td>
                                <td>USh <?php echo number_format($b['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($b['description'] ?: '-'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="openPayModal(<?php echo $b['id']; ?>, '<?php echo number_format($b['amount'], 2); ?>')">
                                        <i class="fas fa-wallet"></i> Receive Payment
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No unpaid bills.</div>
        <?php endif; ?>
    </div>

    <!-- Payment Modal -->
    <div id="payModal" class="modal" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:20px; display:none;">
        <div class="modal-content" style="background:white; border-radius:12px; width:100%; max-width:480px; padding:28px; position:relative; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
            <span class="close" onclick="closePayModal()" style="position:absolute; top:16px; right:20px; font-size:1.6rem; color:#a0aec0; cursor:pointer;">&times;</span>
            <h3 style="margin-bottom:16px; color:var(--primary); font-size:1.25rem;"><i class="fas fa-wallet"></i> Receive Payment</h3>
            <form method="POST" action="cashier_clearances.php">
                <input type="hidden" name="bill_id" id="pay_bill_id">
                <input type="hidden" name="mark_paid" value="1">
                <div class="form-group">
                    <label>Bill Amount (USh)</label>
                    <input type="text" id="pay_bill_amount" readonly>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="method" required>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Insurance">Insurance</option>
                    </select>
                </div>
                <div class="modal-actions" style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" class="btn btn-secondary" onclick="closePayModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Clearance from Paid Bills -->
    <div class="card">
        <h2><i class="fas fa-paper-plane"></i> Send Payment Authorization</h2>
        <p>Authorize the patient for admission, drugs, or discharge and notify the relevant department.</p>
        <?php if ($paidBills->num_rows > 0): ?>
            <form method="POST" action="cashier_clearances.php">
                <input type="hidden" name="create_clearance" value="1">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Patient / Bill</label>
                        <select name="bill_id" required>
                            <option value="">Select a paid bill</option>
                            <?php while ($b = $paidBills->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>">#<?php echo $b['id']; ?> - <?php echo htmlspecialchars($b['patient_name']); ?> (USh <?php echo number_format($b['amount'], 2); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Authorization Type</label>
                        <select name="authorization_type" required>
                            <option value="admit">Admit Patient</option>
                            <option value="drugs">Dispense Drugs</option>
                            <option value="discharge">Discharge Patient</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notify Department</label>
                        <select name="department" required>
                            <option value="pharmacy">Pharmacy</option>
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Authorization</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state">No paid bills available. Record a payment first.</div>
        <?php endif; ?>
    </div>

    <!-- All Clearances -->
    <div class="card">
        <h2><i class="fas fa-list"></i> All Payment Clearances</h2>
        <button class="btn btn-sm btn-print no-print" onclick="window.print()"><i class="fas fa-print"></i> Print Clearances</button>
        <?php if ($clearances->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Bill</th>
                            <th>Authorization</th>
                            <th>Department</th>
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
                                <td><?php echo $c['bill_id'] ? '#'.$c['bill_id'] : '-'; ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($c['authorization_type'])); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($c['department'])); ?></td>
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
                                            <button type="submit" name="complete_clearance" class="btn btn-sm btn-success" onclick="return confirm('Mark this clearance as completed?')">
                                                <i class="fas fa-check"></i> Complete
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
            <div class="empty-state">No clearances yet.</div>
        <?php endif; ?>
    </div>

    <!-- Printable Version -->
    <div class="print-section">
        <h1>Payment Clearances Report</h1>
        <p>Generated on: <?php echo date('M d, Y H:i'); ?></p>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Authorization</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Authorized By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $printClearances = $conn->query("
                    SELECT pc.*, p.name AS patient_name, u.name AS cashier_name
                    FROM payment_clearances pc
                    JOIN patients p ON pc.patient_id = p.id
                    JOIN users u ON pc.authorized_by = u.id
                    ORDER BY pc.created_at DESC
                ");
                while ($c = $printClearances->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['patient_name']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($c['authorization_type'])); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($c['department'])); ?></td>
                        <td><?php echo ucfirst($c['status']); ?></td>
                        <td><?php echo htmlspecialchars($c['cashier_name']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openPayModal(billId, amount) {
        document.getElementById('pay_bill_id').value = billId;
        document.getElementById('pay_bill_amount').value = 'USh ' + amount;
        document.getElementById('payModal').style.display = 'flex';
    }
    function closePayModal() {
        document.getElementById('payModal').style.display = 'none';
    }
    window.onclick = function(e) {
        if (e.target.id === 'payModal') {
            closePayModal();
        }
    }

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
