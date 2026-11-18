<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'receptionist') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$receptionist_name = $_SESSION['user_name'];

// Stats
$total_appts  = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$pending_appts= $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='pending'")->fetch_assoc()['c'];
$confirmed    = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='approved'")->fetch_assoc()['c'];
$total_patients= $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];

// Fetch recent appointments
$appts = $conn->query("
    SELECT a.*, p.name AS patient_name, d.name AS doctor_name, dept.name AS department_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    LEFT JOIN users d ON a.doctor_id = d.id
    LEFT JOIN departments dept ON a.department_id = dept.id
    ORDER BY a.created_at DESC
    LIMIT 10
");

// Message handlers
$receptionist_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt_read = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=? AND recipient_id=?");
    $stmt_read->bind_param("ii", $msg_id, $receptionist_id);
    $stmt_read->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt_delete = $conn->prepare("DELETE FROM messages WHERE id=? AND recipient_id=?");
    $stmt_delete->bind_param("ii", $delete_id, $receptionist_id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_id'])) {
    $original_sender_id = intval($_POST['reply_to_id']);
    $reply_subject = "Re: " . $_POST['reply_subject'];
    $reply_body = $_POST['reply_body'];
    $stmt_reply = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'receptionist', ?)");
    $stmt_reply->bind_param("iissi", $receptionist_id, $original_sender_id, $reply_subject, $reply_body, $_POST['recipient_role'] ?? 'admin');
    if ($stmt_reply->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send reply.']);
    }
    exit();
}

// Fetch messages
$messages = $conn->prepare("
    SELECT m.id, m.subject, m.body, m.created_at AS sent_at, m.is_read, m.sender_id, u.name AS sender_name, m.sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.created_at DESC
");
$messages->bind_param("i", $receptionist_id);
$messages->execute();
$messages = $messages->get_result();

// Unread count
$unread_count = $conn->query("SELECT COUNT(*) c FROM messages WHERE recipient_id=$receptionist_id AND is_read=0")->fetch_assoc()['c'];

// Website contact inquiries (general inquiries handled by receptionist)
$general_inquiries = null;
$tableCheck = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $general_inquiries = $conn->query("SELECT * FROM contact_messages WHERE subject = 'general' ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #b45309;
            --primary-light: #d97706;
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
            background: url('images/depart.jpg') center/cover fixed;
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

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #c6f6d5; color: #276749; }
        .status-declined { background: #fed7d7; color: #9b2c2c; }
        .status-rescheduled { background: #b2f5ea; color: #234e52; }
        .status-cancelled { background: #fed7d7; color: #9b2c2c; }
        .status-completed { background: #c6f6d5; color: #276749; }

        /* Alerts */
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
        @media print {
            .no-print { display: none !important; }
            header, nav, .btn, .modal { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid gray; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 8px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-user-doctor"></i> Receptionist Dashboard</h1>
    <nav>
        <a href="receptionist_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="receptionist_appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="receptionist_departments.php"><i class="fas fa-building"></i> Departments</a>
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
        <h2><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($receptionist_name); ?></h2>
        <p>Direct patients to departments, manage appointments, and confirm bookings online.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Appointments</div>
            <div class="value"><?php echo $total_appts; ?></div>
            <div class="sub">All bookings</div>
        </div>
        <div class="stat-card">
            <div class="label">Pending</div>
            <div class="value"><?php echo $pending_appts; ?></div>
            <div class="sub">Awaiting confirmation</div>
        </div>
        <div class="stat-card">
            <div class="label">Confirmed</div>
            <div class="value"><?php echo $confirmed; ?></div>
            <div class="sub">Approved appointments</div>
        </div>
        <div class="stat-card">
            <div class="label">Patients</div>
            <div class="value"><?php echo $total_patients; ?></div>
            <div class="sub">Registered patients</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            <a href="receptionist_appointments.php" class="btn btn-primary" style="width:100%; justify-content:center;">
                <i class="fas fa-calendar-check"></i> Manage Appointments
            </a>
            <a href="receptionist_departments.php" class="btn btn-info" style="width:100%; justify-content:center;">
                <i class="fas fa-building"></i> View Departments
            </a>
            <a href="patient_register.php" class="btn btn-success" style="width:100%; justify-content:center;">
                <i class="fas fa-user-plus"></i> Register Patient
            </a>
            <a href="book_appointment.php" class="btn btn-secondary" style="width:100%; justify-content:center;">
                <i class="fas fa-calendar-plus"></i> Book Appointment
            </a>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="card">
        <h2><i class="fas fa-clock"></i> Recent Appointments</h2>
        <button class="btn btn-sm no-print" style="float:right; margin-bottom:12px; background:#2563eb;" onclick="window.print()">
            <i class="fas fa-print"></i> Print Appointments
        </button>
        <?php if ($appts->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Appt ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Date/Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($a = $appts->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $a['id']; ?></td>
                                <td><?php echo htmlspecialchars($a['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($a['doctor_name'] ?: 'Unassigned'); ?></td>
                                <td><?php echo htmlspecialchars($a['department_name'] ?: 'General'); ?></td>
                                <td><?php echo date('M d, Y \a\t h:i A', strtotime($a['requested_datetime'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo $a['status']; ?>">
                                        <?php echo ucfirst($a['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No appointments yet.</div>
        <?php endif; ?>
    </div>

    <!-- General Inquiries (Website) -->
    <div class="card">
        <h2><i class="fas fa-inbox"></i> General Inquiries (Website)</h2>
        <p style="color: var(--muted); margin: -8px 0 16px;">Public contact form submissions marked as General Inquiry.</p>
        <?php if ($general_inquiries && $general_inquiries->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($inq = $general_inquiries->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime($inq['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($inq['name']); ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>"><?php echo htmlspecialchars($inq['email']); ?></a></td>
                                <td><?php echo $inq['phone'] ? htmlspecialchars($inq['phone']) : '—'; ?></td>
                                <td style="white-space: pre-wrap; max-width: 420px;"><?php echo htmlspecialchars($inq['message']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No general inquiries yet.</div>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <div class="card">
        <h2><i class="fas fa-envelope"></i> My Messages (<span id="unread-count"><?php echo $unread_count; ?></span> unread)</h2>
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
                            <tr class="<?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                                <td><?php echo htmlspecialchars($msg['sender_name']); ?> <small style="color:var(--muted);">[<?php echo htmlspecialchars($msg['sender_role']); ?>]</small></td>
                                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                        onclick='openMessageModal(
                                            <?= $msg["id"] ?>,
                                            <?= $msg["sender_id"] ?>,
                                            <?= json_encode($msg["subject"]) ?>,
                                            <?= json_encode($msg["body"]) ?>,
                                            <?= $msg["is_read"] ? "true" : "false" ?>,
                                            <?= json_encode($msg["sender_role"]) ?>
                                        )'>
                                        <i class="fas fa-eye"></i> Read
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteMessage(<?= $msg['id'] ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No messages yet.</div>
        <?php endif; ?>
    </div>

</div>

<!-- Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeMessageModal()">&times;</span>
        <h3 id="messageSubject"></h3>
        <p id="messageBody" style="margin: 16px 0 20px; white-space: pre-wrap; line-height: 1.6;"></p>
        <hr style="border: none; border-top: 1px solid var(--border); margin: 20px 0;">
        <button class="btn btn-primary" onclick="toggleReplyForm()">
            <i class="fas fa-reply"></i> Reply
        </button>
        <form id="replyForm" style="display: none; margin-top: 20px;">
            <input type="hidden" id="reply_to_id" name="reply_to_id">
            <input type="hidden" id="reply_subject" name="reply_subject">
            <input type="hidden" id="reply_recipient_role" name="recipient_role">
            <label style="display:block; margin-bottom:6px; font-weight:500;">Your Reply</label>
            <textarea name="reply_body" id="reply_body" required placeholder="Type your reply here..."></textarea>
            <div class="modal-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleReplyForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMessageModal(id, senderId, subject, message, isRead, senderRole) {
    document.getElementById('messageSubject').innerText = subject;
    document.getElementById('messageBody').innerText = message;
    document.getElementById('messageModal').classList.add('show');
    document.getElementById('reply_to_id').value = senderId;
    document.getElementById('reply_subject').value = subject;
    document.getElementById('reply_recipient_role').value = senderRole;
    document.getElementById('replyForm').style.display = 'none';
    if (!isRead) {
        const formData = new FormData();
        formData.append('mark_read_id', id);
        fetch('receptionist_dashboard.php', { method: 'POST', body: formData });
    }
}
function closeMessageModal() {
    document.getElementById('messageModal').classList.remove('show');
    location.reload();
}
function toggleReplyForm() {
    const form = document.getElementById('replyForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
function deleteMessage(id) {
    if (confirm("Are you sure you want to delete this message?")) {
        const formData = new FormData();
        formData.append('delete_message_id', id);
        fetch('receptionist_dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { location.reload(); }
        });
    }
}
document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('receptionist_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Reply sent successfully!');
            closeMessageModal();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    });
});
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
}
</script>
</body>
</html>