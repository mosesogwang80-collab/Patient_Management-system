<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medical_director') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$md_id   = $_SESSION['user_id'];
$md_name = $_SESSION['user_name'];

// ============ WORKER APPROVAL ============
$pending_workers = $conn->query("
    SELECT * FROM users 
    WHERE status = 'pending_approval' 
    ORDER BY created_at DESC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_worker'])) {
    $worker_id = intval($_POST['worker_id']);
    $conn->query("UPDATE users SET status = 'active' WHERE id = $worker_id");
    header("Location: medical_director_dashboard.php?success=Worker+approved+successfully");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_worker'])) {
    $worker_id = intval($_POST['worker_id']);
    $conn->query("UPDATE users SET status = 'inactive' WHERE id = $worker_id");
    header("Location: medical_director_dashboard.php?success=Worker+rejected");
    exit;
}

// ============ MESSAGES ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $msg_id, $md_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $delete_id, $md_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    if ($recipient_id > 0 && $subject !== '' && $body !== '') {
        $rec_role = $conn->query("SELECT role FROM users WHERE id = $recipient_id")->fetch_assoc()['role'] ?? 'admin';
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'medical_director', ?)");
        $stmt->bind_param("iisss", $md_id, $recipient_id, $subject, $body, $rec_role);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
    exit;
}

// Fetch all active staff for compose (exclude self)
$all_staff = $conn->query("SELECT id, name, role FROM users WHERE id != $md_id AND status = 'active' ORDER BY role, name");

$messages = $conn->prepare("
    SELECT m.id, m.subject, m.body, m.created_at AS sent_at, m.is_read, m.sender_id, 
           u.name AS sender_name, m.sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.created_at DESC
");
$messages->bind_param("i", $md_id);
$messages->execute();
$messages = $messages->get_result();

$unread_count = $conn->query("SELECT COUNT(*) AS c FROM messages WHERE recipient_id = $md_id AND is_read = 0")->fetch_assoc()['c'];

// ============ ANALYTICS ============
$total_patients      = $conn->query("SELECT COUNT(*) AS total FROM patients")->fetch_assoc()['total'];
$admitted_patients   = $conn->query("SELECT COUNT(*) AS total FROM patients WHERE patient_status = 'admitted'")->fetch_assoc()['total'];
$discharged_patients = $conn->query("SELECT COUNT(*) AS total FROM patients WHERE patient_status = 'discharged'")->fetch_assoc()['total'];
$outpatient_patients = $conn->query("SELECT COUNT(*) AS total FROM patients WHERE patient_status = 'outpatient'")->fetch_assoc()['total'];
$total_bills         = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM medical_bills")->fetch_assoc()['total'];
$unpaid_bills        = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM medical_bills WHERE status = 'unpaid'")->fetch_assoc()['total'];
$paid_bills          = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM medical_bills WHERE status = 'paid'")->fetch_assoc()['total'];
$total_doctors       = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'doctor'")->fetch_assoc()['total'];
$total_appointments  = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()['total'];
$total_pending_apt   = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'pending'")->fetch_assoc()['total'];
$total_approved_apt  = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'approved'")->fetch_assoc()['total'];
$total_pharmacists   = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'pharmacy'")->fetch_assoc()['total'];
$total_cashiers      = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'cashier'")->fetch_assoc()['total'];
$total_nurses        = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'nurse'")->fetch_assoc()['total'];
$total_receptionists = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'receptionist'")->fetch_assoc()['total'];
$total_pending_workers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'pending_approval'")->fetch_assoc()['total'];

// Monthly appointments (last 6 months)
$monthly_apt = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $row = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_assoc();
    $monthly_apt[] = [
        'month' => date('M Y', strtotime("$month-01")),
        'count' => (int)$row['c']
    ];
}

$bills_by_status = [
    'paid'   => (int)$conn->query("SELECT COUNT(*) AS c FROM medical_bills WHERE status = 'paid'")->fetch_assoc()['c'],
    'unpaid' => (int)$conn->query("SELECT COUNT(*) AS c FROM medical_bills WHERE status = 'unpaid'")->fetch_assoc()['c'],
];

$patient_status_chart = [
    'admitted'   => (int)$admitted_patients,
    'discharged' => (int)$discharged_patients,
    'outpatient' => (int)$outpatient_patients,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Director Dashboard | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: darkslateblue;
            --primary-light: slateblue;
            --success: seagreen;
            --danger: crimson;
            --warning: darkgoldenrod;
            --info: darkcyan;
            --bg: whitesmoke;
            --card: white;
            --border: lightgray;
            --text: darkslategray;
            --muted: slategray;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/product.jpeg') center/cover fixed;
            background-color: gainsboro;
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
            box-shadow: 0 3px 12px rgba(0,0,0,0.15);
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
            background: rgba(255,255,255,0.18);
        }

        header nav a.logout {
            background: rgba(255,255,255,0.12);
        }

        .container {
            max-width: 1400px;
            margin: 28px auto 50px;
            padding: 0 20px;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            padding: 26px 28px;
            margin-bottom: 28px;
            border: 1px solid var(--border);
        }

        .card h2 {
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
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

        .stat-card.danger .label,
        .stat-card.danger .value {
            color: var(--danger);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 8px;
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
        .btn-success:hover { background: darkgreen; }

        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: darkred; }

        .btn-info { background: var(--info); }
        .btn-info:hover { background: teal; }

        .btn-secondary { background: slategray; }
        .btn-secondary:hover { background: dimgray; }

        .btn-sm { padding: 5px 12px; font-size: 0.82rem; }

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

        tr:nth-child(even) { background: whitesmoke; }
        tr:hover { background: gainsboro; }

        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active { background: palegreen; color: darkgreen; }
        .status-inactive { background: lightpink; color: darkred; }
        .status-pending,
        .status-pending_approval { background: lightgoldenrodyellow; color: darkgoldenrod; }

        .alert {
            padding: 13px 18px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-weight: 500;
        }

        .alert-success { background: palegreen; color: darkgreen; }
        .alert-error { background: lightpink; color: darkred; }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 12px;
            opacity: 0.35;
            display: block;
        }

        .unread {
            font-weight: 600;
            background: aliceblue !important;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Modal - forced hidden by default */
        .modal {
            display: none !important;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex !important;
        }

        .modal-content {
            background: white;
            border-radius: 14px;
            padding: 28px;
            width: 90%;
            max-width: 520px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h3 {
            color: var(--primary);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-content textarea {
            width: 100%;
            min-height: 130px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        @media print {
            .no-print, header, nav, .btn, .modal { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid gray; }
            th, td { border: 1px solid black; padding: 8px; }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 14px;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-user-shield"></i> Medical Director Dashboard</h1>
    <nav>
        <a href="medical_director_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="workers.php"><i class="fas fa-user-plus"></i> Staff</a>
        <a href="manage_records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
        <a href="medical_director_reports.php?period=daily"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="send_message.php"><i class="fas fa-envelope"></i> Messages</a>
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

    <!-- Welcome -->
    <div class="card">
        <h2><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($md_name); ?></h2>
        <p>Full hospital overview, worker approvals and communication with staff.</p>
    </div>

    <!-- Analytics -->
    <div class="card">
        <h2><i class="fas fa-chart-line"></i> Hospital Analytics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Patients</div>
                <div class="value"><?= $total_patients ?></div>
                <div class="sub">Admitted: <?= $admitted_patients ?> • Discharged: <?= $discharged_patients ?> • Out: <?= $outpatient_patients ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Bills</div>
                <div class="value">USh <?= number_format($total_bills, 0) ?></div>
                <div class="sub">Paid: USh <?= number_format($paid_bills, 0) ?> • Unpaid: USh <?= number_format($unpaid_bills, 0) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Doctors</div>
                <div class="value"><?= $total_doctors ?></div>
                <div class="sub">Medical staff</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Appointments</div>
                <div class="value"><?= $total_appointments ?></div>
                <div class="sub">Pending: <?= $total_pending_apt ?> • Approved: <?= $total_approved_apt ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Cashiers</div>
                <div class="value"><?= $total_cashiers ?></div>
                <div class="sub">Billing & payments</div>
            </div>
            <div class="stat-card">
                <div class="label">Pharmacists</div>
                <div class="value"><?= $total_pharmacists ?></div>
                <div class="sub">Drugs & prescriptions</div>
            </div>
            <div class="stat-card">
                <div class="label">Nurses</div>
                <div class="value"><?= $total_nurses ?></div>
                <div class="sub">Patient care</div>
            </div>
            <div class="stat-card">
                <div class="label">Receptionists</div>
                <div class="value"><?= $total_receptionists ?></div>
                <div class="sub">Front desk</div>
            </div>
            <div class="stat-card danger">
                <div class="label">Pending Worker Approvals</div>
                <div class="value"><?= $total_pending_workers ?></div>
                <div class="sub">Awaiting your decision</div>
            </div>
        </div>
        <button class="btn btn-sm btn-secondary no-print" style="margin-top:18px;" onclick="window.print()">
            <i class="fas fa-print"></i> Print Analytics
        </button>
    </div>

    <!-- Reports -->
    <div class="card">
        <h2><i class="fas fa-file-alt"></i> Generate Reports</h2>
        <p style="margin-bottom:14px; color:var(--muted);">Generate daily, weekly, or monthly records reports.</p>
        <a href="medical_director_reports.php?period=daily" class="btn btn-primary"><i class="fas fa-calendar-day"></i> Daily Report</a>
        <a href="medical_director_reports.php?period=weekly" class="btn btn-info"><i class="fas fa-calendar-week"></i> Weekly Report</a>
        <a href="medical_director_reports.php?period=monthly" class="btn btn-success"><i class="fas fa-calendar-alt"></i> Monthly Report</a>
    </div>

    <!-- Charts -->
    <div class="card">
        <h2><i class="fas fa-chart-bar"></i> Hospital Charts</h2>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
            <div class="chart-container">
                <canvas id="monthlyAppointments"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="billsStatus"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="patientStatus"></canvas>
            </div>
        </div>
    </div>

    <!-- Pending Worker Approvals -->
    <div class="card">
        <h2><i class="fas fa-user-clock"></i> Pending Worker Approvals</h2>
        <p style="margin-bottom:18px; color:var(--muted);">Workers added by admin require your approval before they can log in.</p>

        <?php if ($pending_workers->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Added</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($w = $pending_workers->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $w['id'] ?></td>
                                <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                                <td><?= htmlspecialchars($w['email']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($w['role'])) ?></td>
                                <td><?= htmlspecialchars($w['department_id'] ?? '—') ?></td>
                                <td><?= date('M d, Y H:i', strtotime($w['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this worker?')">
                                        <input type="hidden" name="worker_id" value="<?= $w['id'] ?>">
                                        <button type="submit" name="approve_worker" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline; margin-left:4px;" onsubmit="return confirm('Reject this worker?')">
                                        <input type="hidden" name="worker_id" value="<?= $w['id'] ?>">
                                        <button type="submit" name="reject_worker" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                No pending worker approvals
            </div>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
            <h2 style="margin:0; border:none; padding:0;">
                <i class="fas fa-envelope"></i> Messages
                (<span id="unread-count"><?= $unread_count ?></span> unread)
            </h2>
            <button type="button" class="btn btn-info no-print" onclick="openComposeModal()">
                <i class="fas fa-paper-plane"></i> Compose
            </button>
        </div>

        <?php if ($messages->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($msg = $messages->fetch_assoc()): ?>
                            <tr class="<?= $msg['is_read'] ? '' : 'unread' ?>">
                                <td>
                                    <?= htmlspecialchars($msg['sender_name']) ?>
                                    <small style="color:var(--muted);">[<?= htmlspecialchars($msg['sender_role']) ?>]</small>
                                </td>
                                <td><?= htmlspecialchars($msg['subject']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($msg['sent_at'])) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                        onclick='openMessageModal(
                                            <?= (int)$msg["id"] ?>,
                                            <?= json_encode($msg["subject"]) ?>,
                                            <?= json_encode($msg["body"]) ?>,
                                            <?= $msg["is_read"] ? "true" : "false" ?>
                                        )'>
                                        <i class="fas fa-eye"></i> Read
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteMessage(<?= (int)$msg['id'] ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No messages yet</div>
        <?php endif; ?>
    </div>

</div>

<!-- Read Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <h3 id="messageSubject"></h3>
        <p id="messageBody" style="margin:16px 0 20px; white-space:pre-wrap; line-height:1.6;"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary btn-sm" onclick="closeMessageModal()">Close</button>
        </div>
    </div>
</div>

<!-- Compose Message Modal (HIDDEN by default) -->
<div id="composeModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-paper-plane"></i> Compose Message</h3>
        <form id="composeForm">
            <input type="hidden" name="send_message" value="1">
            <div class="form-group">
                <label>To</label>
                <select name="recipient_id" required>
                    <option value="">Select recipient</option>
                    <?php 
                    if ($all_staff) {
                        $all_staff->data_seek(0);
                        while ($s = $all_staff->fetch_assoc()): 
                    ?>
                        <option value="<?= (int)$s['id'] ?>">
                            <?= htmlspecialchars($s['name']) ?> (<?= ucfirst(htmlspecialchars($s['role'])) ?>)
                        </option>
                    <?php endwhile; } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="Enter subject">
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="body" required placeholder="Type your message..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeComposeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Charts
const monthlyAptData   = <?= json_encode(array_column($monthly_apt, 'count')) ?>;
const monthlyAptLabels = <?= json_encode(array_column($monthly_apt, 'month')) ?>;
const billsStatusData  = [<?= $bills_by_status['paid'] ?>, <?= $bills_by_status['unpaid'] ?>];
const patientStatusData = [<?= $patient_status_chart['admitted'] ?>, <?= $patient_status_chart['discharged'] ?>, <?= $patient_status_chart['outpatient'] ?>];

new Chart(document.getElementById('monthlyAppointments'), {
    type: 'bar',
    data: {
        labels: monthlyAptLabels,
        datasets: [{
            label: 'Appointments',
            data: monthlyAptData,
            backgroundColor: 'rgba(72, 61, 139, 0.7)',
            borderColor: 'darkslateblue',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { title: { display: true, text: 'Appointments - Last 6 Months' } }
    }
});

new Chart(document.getElementById('billsStatus'), {
    type: 'pie',
    data: {
        labels: ['Paid', 'Unpaid'],
        datasets: [{
            data: billsStatusData,
            backgroundColor: ['seagreen', 'crimson']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { title: { display: true, text: 'Bills by Status' } }
    }
});

new Chart(document.getElementById('patientStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Admitted', 'Discharged', 'Outpatient'],
        datasets: [{
            data: patientStatusData,
            backgroundColor: ['darkgoldenrod', 'crimson', 'darkcyan']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { title: { display: true, text: 'Patient Status' } }
    }
});

// ===== MESSAGE MODALS =====
function openComposeModal() {
    const modal = document.getElementById('composeModal');
    modal.classList.add('show');
}

function closeComposeModal() {
    const modal = document.getElementById('composeModal');
    modal.classList.remove('show');
    document.getElementById('composeForm').reset();
}

function openMessageModal(id, subject, body, isRead) {
    document.getElementById('messageSubject').innerText = subject;
    document.getElementById('messageBody').innerText = body;
    document.getElementById('messageModal').classList.add('show');

    if (isRead === false || isRead === 'false') {
        const formData = new FormData();
        formData.append('mark_read_id', id);
        fetch('medical_director_dashboard.php', { method: 'POST', body: formData })
            .then(() => {
                const badge = document.getElementById('unread-count');
                if (badge) {
                    let count = parseInt(badge.innerText) || 0;
                    if (count > 0) badge.innerText = count - 1;
                }
            });
    }
}

function closeMessageModal() {
    document.getElementById('messageModal').classList.remove('show');
}

function deleteMessage(id) {
    if (!confirm('Delete this message?')) return;
    const formData = new FormData();
    formData.append('delete_message_id', id);
    fetch('medical_director_dashboard.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Failed to delete');
        });
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Handle compose form submit
document.getElementById('composeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('medical_director_dashboard.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Message sent successfully!');
                closeComposeModal();
                location.reload();
            } else {
                alert(data.error || 'Failed to send message');
            }
        })
        .catch(() => alert('Network error'));
});

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    });
}, 5000);
</script>
</body>
</html>