<?php
session_start();
require 'db.php';

// Only allow admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Handle AJAX for marking message as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt_read = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=? AND recipient_id=?");
    $stmt_read->bind_param("ii", $msg_id, $admin_id);
    $stmt_read->execute();
    echo json_encode([
        'success' => $stmt_read->affected_rows > 0,
        'affected' => $stmt_read->affected_rows
    ]);
    exit();
}

// Handle AJAX for deleting a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt_delete = $conn->prepare("DELETE FROM messages WHERE id=? AND recipient_id=?");
    $stmt_delete->bind_param("ii", $delete_id, $admin_id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
    exit();
}

// Handle AJAX for replying to a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_id'])) {
    $original_sender_id = intval($_POST['reply_to_id']);
    $reply_subject = "Re: " . $_POST['reply_subject'];
    $reply_body = $_POST['reply_body'];

    $stmt_reply = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
    $stmt_reply->bind_param("iiss", $admin_id, $original_sender_id, $reply_subject, $reply_body);
    
    if ($stmt_reply->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send reply.']);
    }
    exit();
}

// Handle doctor deletion
if (isset($_GET['delete_doctor'])) {
    $doc_id = intval($_GET['delete_doctor']);
    $conn->query("DELETE FROM users WHERE id=$doc_id AND role='doctor'");
    header("Location: admin_dashboard.php?success=Doctor deleted successfully");
    exit();
}

// Analytics counts
$total_patients     = $conn->query("SELECT COUNT(*) as total FROM patients")->fetch_assoc()['total'];
$inpatients         = $conn->query("SELECT COUNT(*) as total FROM patients WHERE patient_type='inpatient'")->fetch_assoc()['total'];
$outpatients        = $conn->query("SELECT COUNT(*) as total FROM patients WHERE patient_type='outpatient'")->fetch_assoc()['total'];
$total_bills        = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM medical_bills")->fetch_assoc()['total'];
$unpaid_bills       = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM medical_bills WHERE status='unpaid'")->fetch_assoc()['total'];
$total_doctors      = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='doctor'")->fetch_assoc()['total'];
$total_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments")->fetch_assoc()['total'];
$total_pending      = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='pending'")->fetch_assoc()['total'];
$total_approved     = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='approved'")->fetch_assoc()['total'];
$total_declined     = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='declined'")->fetch_assoc()['total'];

$total_cashiers     = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='cashier'")->fetch_assoc()['total'];
$total_pharmacists  = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='pharmacy'")->fetch_assoc()['total'];
$total_receptionists= $conn->query("SELECT COUNT(*) as total FROM users WHERE role='receptionist'")->fetch_assoc()['total'];
$total_nurses       = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='nurse'")->fetch_assoc()['total'];

$doctors = $conn->query("SELECT * FROM users WHERE role='doctor' ORDER BY name");

// Fetch messages for the admin
$stmt_msg = $conn->prepare("
    SELECT m.id, m.subject, m.body, m.created_at AS sent_at, m.is_read, m.sender_id, u.name AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.created_at DESC
");
$stmt_msg->bind_param("i", $admin_id);
$stmt_msg->execute();
$messages = $stmt_msg->get_result();

// Website contact inquiries (general inquiries viewable by admin)
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
    <title>Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: navy;
            --primary-light: royalblue;
            --success: seagreen;
            --danger: firebrick;
            --warning: goldenrod;
            --info: teal;
            --bg: whitesmoke;
            --card-bg: rgba(255, 255, 255, 0.93);
            --border: lightgray;
            --text: darkslategray;
            --muted: gray;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/product.jpeg') center/cover fixed;
            background-color: lightgray;
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Header */
        header {
            background: var(--primary);
            color: white;
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            flex-wrap: wrap;
            gap: 12px;
        }

        header h1 {
            font-size: 1.35rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header nav {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        header nav a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 7px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        header nav a:hover {
            background: rgba(255,255,255,0.15);
        }

        header nav a.logout {
            background: rgba(255,255,255,0.12);
        }

        /* Main Container */
        .container {
            max-width: 1280px;
            margin: 28px auto;
            padding: 0 20px 50px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 24px 28px;
            margin-bottom: 28px;
            backdrop-filter: blur(5px);
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

        /* Analytics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 8px;
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
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card .sub {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 4px;
        }

        .stat-card a {
            text-decoration: none;
            color: inherit;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
        }

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

        tr:nth-child(even) {
            background: whitesmoke;
        }

        tr:hover {
            background: aliceblue;
        }

        /* Status */
        .status {
            display: inline-block;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active {
            background: lightgreen;
            color: darkgreen;
        }

        .status-inactive {
            background: mistyrose;
            color: darkred;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 7px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary-light); }
        .btn-primary:hover { background: var(--primary); }

        .btn-success { background: var(--success); }
        .btn-success:hover { background: darkgreen; }

        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: darkred; }

        .btn-info { background: var(--info); }
        .btn-info:hover { background: darkcyan; }

        .btn-secondary {
            background: gray;
        }
        .btn-secondary:hover {
            background: dimgray;
        }

        .btn-sm {
            padding: 5px 11px;
            font-size: 0.82rem;
        }

        /* Unread message */
        .unread {
            font-weight: 600;
            background: floralwhite !important;
        }

        /* Alerts */
        .alert {
            padding: 13px 18px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-weight: 500;
        }

        .alert-success {
            background: lightgreen;
            color: darkgreen;
        }

        .alert-error {
            background: mistyrose;
            color: darkred;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 480px;
            padding: 28px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: slideIn 0.25s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal .close {
            position: absolute;
            top: 16px;
            right: 20px;
            font-size: 1.6rem;
            color: lightslategray;
            cursor: pointer;
            line-height: 1;
        }

        .modal .close:hover {
            color: darkslategray;
        }

        .modal h3 {
            margin-bottom: 16px;
            color: var(--primary);
            font-size: 1.25rem;
        }

        .modal textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            margin-bottom: 16px;
            resize: vertical;
            min-height: 110px;
            font-family: inherit;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            header nav {
                width: 100%;
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
        /* Print styles */
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
    <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
    <nav>
        <a href="manage_patients.php"><i class="fas fa-hospital-user"></i> Patients</a>
        <a href="manage_records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
        <a href="send_message.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="register_workers.php"><i class="fas fa-user-plus"></i> Register Staff</a>
        <a href="workers.php"><i class="fas fa-users"></i> Staff</a>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<div class="container">

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <!-- Analytics -->
    <div class="card">
        <h2><i class="fas fa-chart-line"></i> Analytics Overview</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <a href="manage_patients.php">
                    <div class="label">Total Patients</div>
                    <div class="value"><?= $total_patients ?></div>
                    <div class="sub">In: <?= $inpatients ?> • Out: <?= $outpatients ?></div>
                </a>
            </div>

            <div class="stat-card">
                <div class="label">Total Doctors</div>
                <div class="value"><?= $total_doctors ?></div>
            </div>

            <div class="stat-card">
                <div class="label">Total Appointments</div>
                <div class="value"><?= $total_appointments ?></div>
                <div class="sub">
                    Pending: <?= $total_pending ?> • 
                    Approved: <?= $total_approved ?> • 
                    Declined: <?= $total_declined ?>
                </div>
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
                <div class="label">Receptionists</div>
                <div class="value"><?= $total_receptionists ?></div>
                <div class="sub">Appointments & directing</div>
            </div>

            <div class="stat-card">
                <div class="label">Nurses</div>
                <div class="value"><?= $total_nurses ?></div>
                <div class="sub">Patient care</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="manage_records.php" class="btn btn-primary">
                <i class="fas fa-notes-medical"></i> Manage Medical Records
            </a>
    
        </div>
    </div>

    <!-- Doctors List -->
    <div class="card">
        <h2><i class="fas fa-user-md"></i> Doctors</h2>
        <button class="btn btn-sm btn-print no-print" style="float:right; margin-bottom:12px;" onclick="window.print()"><i class="fas fa-print"></i> Print Doctors</button>
        <?php if ($doctors->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $doctors->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <span class="status status-<?= strtolower($row['status']) ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_doctor.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="admin_dashboard.php?delete_doctor=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this doctor?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No doctors registered yet.</div>
        <?php endif; ?>
    </div>

    <!-- General Inquiries (Website) -->
    <div class="card">
        <h2><i class="fas fa-inbox"></i> General Inquiries (Website)</h2>
        <p style="color: var(--muted); margin: -8px 0 16px;">Public contact form submissions marked as General Inquiry (receptionist handles; admin can view).</p>
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
                                <td><?= date('Y-m-d H:i', strtotime($inq['created_at'])) ?></td>
                                <td><?= htmlspecialchars($inq['name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($inq['email']) ?>"><?= htmlspecialchars($inq['email']) ?></a></td>
                                <td><?= $inq['phone'] ? htmlspecialchars($inq['phone']) : '—' ?></td>
                                <td style="white-space: pre-wrap; max-width: 420px;"><?= htmlspecialchars($inq['message']) ?></td>
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
        <h2><i class="fas fa-envelope"></i> My Messages (<?= $conn->query("SELECT COUNT(*) c FROM messages WHERE recipient_id=$admin_id AND is_read=0")->fetch_assoc()['c'] ?> unread)</h2>

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
                                <td><?= htmlspecialchars($msg['sender_name']) ?></td>
                                <td><?= htmlspecialchars($msg['subject']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($msg['sent_at'])) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                        onclick='openMessageModal(
                                            <?= $msg["id"] ?>,
                                            <?= $msg["sender_id"] ?>,
                                            <?= json_encode($msg["subject"]) ?>,
                                            <?= json_encode($msg["body"]) ?>,
                                            <?= $msg["is_read"] ? "true" : "false" ?>
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
        
        <button class="btn btn-primary" onclick="toggleReplyForm()">
            <i class="fas fa-reply"></i> Reply
        </button>

        <form id="replyForm" style="display: none; margin-top: 20px;">
            <input type="hidden" id="reply_to_id" name="reply_to_id">
            <input type="hidden" id="reply_subject" name="reply_subject">
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
function openMessageModal(id, senderId, subject, message, isRead) {
    document.getElementById('messageSubject').innerText = subject;
    document.getElementById('messageBody').innerText = message;
    document.getElementById('messageModal').classList.add('show');

    document.getElementById('reply_to_id').value = senderId;
    document.getElementById('reply_subject').value = subject;
    document.getElementById('replyForm').style.display = 'none';

    if (!isRead) {
        const formData = new FormData();
        formData.append('mark_read_id', id);

        fetch('admin_dashboard.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to mark message as read', data);
                    alert('Could not mark message as read. Please try again.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error while marking message as read.');
            });
    }
}

function closeMessageModal() {
    document.getElementById('messageModal').classList.remove('show');
    // Short delay so the mark-read request can finish before reload
    setTimeout(() => location.reload(), 200);
}

function toggleReplyForm() {
    const form = document.getElementById('replyForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function deleteMessage(id) {
    if (confirm("Are you sure you want to delete this message?")) {
        const formData = new FormData();
        formData.append('delete_message_id', id);
        fetch('admin_dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('admin_dashboard.php', {
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

// Close modal when clicking outside
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
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