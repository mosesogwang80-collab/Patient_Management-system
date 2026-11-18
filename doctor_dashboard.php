<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login_form.php");
    exit();
}

$doctor_id   = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'];

// Fetch appointments
$stmt = $conn->prepare("
    SELECT a.*, p.name AS patient_name 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    ORDER BY a.requested_datetime DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Fetch messages
$stmt_msg = $conn->prepare("
    SELECT m.id, m.subject, m.body, m.created_at AS sent_at, m.is_read, m.sender_id, u.name AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.created_at DESC
");
$stmt_msg->bind_param("i", $doctor_id);
$stmt_msg->execute();
$messages = $stmt_msg->get_result();

// Fetch all workers for compose message dropdown (exclude self)
$all_workers = $conn->query("SELECT id, name, role FROM users WHERE id != $doctor_id ORDER BY role, name");

// Handle AJAX reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule_id'])) {
    $apt_id = intval($_POST['reschedule_id']);
    $new_datetime = $_POST['new_datetime'];

    $stmt2 = $conn->prepare("UPDATE appointments SET requested_datetime = ?, status = 'rescheduled' WHERE id = ? AND doctor_id = ?");
    $stmt2->bind_param("sii", $new_datetime, $apt_id, $doctor_id);
    $stmt2->execute();

    echo json_encode(['success' => true]);
    exit();
}

// Mark message as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt_read = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?");
    $stmt_read->bind_param("ii", $msg_id, $doctor_id);
    $stmt_read->execute();
    echo json_encode(['success' => true]);
    exit();
}

// Delete message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt_delete = $conn->prepare("DELETE FROM messages WHERE id = ? AND recipient_id = ?");
    $stmt_delete->bind_param("ii", $delete_id, $doctor_id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
    exit();
}

// Reply to message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_id'])) {
    $original_sender_id = intval($_POST['reply_to_id']);
    $reply_subject = "Re: " . $_POST['reply_subject'];
    $reply_body = $_POST['reply_body'];
    $current_doctor_id = $_SESSION['user_id'];

    $stmt_reply = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
    $stmt_reply->bind_param("iiss", $current_doctor_id, $original_sender_id, $reply_subject, $reply_body);

    if ($stmt_reply->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send reply.']);
    }
    exit();
}

// Send new message to any worker
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    if ($recipient_id > 0 && !empty($subject) && !empty($body)) {
        $rec_role = $conn->query("SELECT role FROM users WHERE id=$recipient_id")->fetch_assoc()['role'] ?? 'admin';
        $stmt_send = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'doctor', ?)");
        $stmt_send->bind_param("iisss", $doctor_id, $recipient_id, $subject, $body, $rec_role);
        if ($stmt_send->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid recipient or missing fields']);
    }
    exit();
}

// Admit patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admit_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $conn->query("UPDATE patients SET patient_status='admitted' WHERE id=$patient_id");
    header("Location: doctor_dashboard.php?success=Patient+admitted");
    exit();
}

// Discharge patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discharge_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $conn->query("UPDATE patients SET patient_status='discharged' WHERE id=$patient_id");
    header("Location: doctor_dashboard.php?success=Patient+discharged");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-image: url('images/medical-equipment.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: #e2e8f0;
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Header */
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
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        header a:hover {
            background: rgba(255,255,255,0.15);
        }

        /* Main Container */
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

        /* Quick Links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
        }

        .quick-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary-light);
            color: white;
            text-decoration: none;
            padding: 14px 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s;
            text-align: center;
        }

        .quick-links a:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #276749;
        }

        .alert-error {
            background: #fed7d7;
            color: #9b2c2c;
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
            background: #f8fafc;
        }

        tr:hover {
            background: #edf2f7;
        }

        /* Status badges */
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background: #fefcbf; color: #975a16; }
        .status-approved { background: #c6f6d5; color: #276749; }
        .status-declined { background: #fed7d7; color: #9b2c2c; }
        .status-rescheduled { background: #b2f5ea; color: #234e52; }
.status-admitted { background: #fef3c7; color: #92400e; }
.status-discharged { background: #e2e8f0; color: #64748b; }

        /* Action Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
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

.btn-warning { background: #d69e2e; }
.btn-warning:hover { background: #b45309; }

.btn-secondary {
            background: #718096;
        }
        .btn-secondary:hover {
            background: #4a5568;
        }

        /* Unread message row */
        .unread {
            font-weight: 600;
            background: #fffaf0 !important;
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
            color: #a0aec0;
            cursor: pointer;
            line-height: 1;
        }

        .modal .close:hover {
            color: #2d3748;
        }

        .modal h3 {
            margin-bottom: 16px;
            color: var(--primary);
            font-size: 1.25rem;
        }

        .modal label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .modal input[type="datetime-local"],
        .modal textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            margin-bottom: 16px;
        }

        .modal textarea {
            resize: vertical;
            min-height: 100px;
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
        @media (max-width: 640px) {
            header {
                padding: 14px 18px;
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .quick-links {
                grid-template-columns: 1fr 1fr;
            }

            .card {
                padding: 18px;
            }
        }
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            header, nav, .btn, .modal { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid #ccc; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 8px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-user-md"></i> Welcome, <?php echo htmlspecialchars($doctor_name); ?></h1>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">

    <!-- Quick Links -->
    <div class="card">
        <h2><i class="fas fa-bolt"></i> Quick Links</h2>
        <div class="quick-links">
            <a href="assign_nurse.php"><i class="fas fa-user-nurse"></i> Assign Nurse</a>
            <a href="nurses.php"><i class="fas fa-users"></i> View Nurses</a>
            <a href="manage_patients.php"><i class="fas fa-hospital-user"></i> Patients & History</a>
            <a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="manage_records.php"><i class="fas fa-file-medical"></i> Send to Cashier</a>
            <a href="manage_records.php"><i class="fas fa-prescription"></i> Send to Pharmacy</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- Appointments -->
    <div class="card">
        <h2><i class="fas fa-calendar-alt"></i> My Appointments</h2>
        <button class="btn btn-sm btn-print no-print" style="float:right; margin-bottom:12px;" onclick="window.print()"><i class="fas fa-print"></i> Print Appointments</button>

        <?php if ($appointments->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Requested</th>
                            <th>Confirmed</th>
                            <th>Status / Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                <td><?php echo $row['requested_datetime']; ?></td>
                                <td><?php echo $row['confirmed_datetime'] ?: '—'; ?></td>
                                <td>
                                    <span class="status status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                    <?php if ($row['status'] == 'pending' || $row['status'] == 'rescheduled'): ?>
                                        <div style="margin-top: 8px;">
                                            <a href="doctor_action.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success">Approve</a>
                                            <a href="doctor_action.php?action=decline&id=<?php echo $row['id']; ?>" class="btn btn-danger">Decline</a>
                                            <button class="btn btn-info" onclick="openModal(<?php echo $row['id']; ?>)">Reschedule</button>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                    // Fetch patient status
                                    $pat_stmt = $conn->prepare("SELECT patient_status FROM patients WHERE id=?");
                                    $pat_stmt->bind_param("i", $row['patient_id']);
                                    $pat_stmt->execute();
                                    $pat_status = $pat_stmt->get_result()->fetch_assoc()['patient_status'] ?? 'outpatient';
                                    ?>

                                    <div style="margin-top: 8px;">
                                        <?php if ($pat_status === 'outpatient' && in_array($row['status'], ['approved', 'completed'])): ?>
                                            <form method="POST" action="doctor_dashboard.php" style="display:inline;">
                                                <input type="hidden" name="patient_id" value="<?php echo $row['patient_id']; ?>">
                                                <button type="submit" name="admit_patient" class="btn btn-warning" onclick="return confirm('Admit this patient?')">
                                                    <i class="fas fa-bed"></i> Admit
                                                </button>
                                            </form>
                                        <?php elseif ($pat_status === 'admitted'): ?>
                                            <form method="POST" action="doctor_dashboard.php" style="display:inline;">
                                                <input type="hidden" name="patient_id" value="<?php echo $row['patient_id']; ?>">
                                                <button type="submit" name="discharge_patient" class="btn btn-danger" onclick="return confirm('Discharge this patient?')">
                                                    <i class="fas fa-hospital"></i> Discharge
                                                </button>
                                            </form>
                                        <?php elseif ($pat_status === 'discharged'): ?>
                                            <span style="font-size:0.8rem;color:#64748b;">Discharged</span>
                                        <?php endif; ?>
                                    </div>
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

    <!-- Messages -->
    <div class="card">
        <h2><i class="fas fa-envelope"></i> My Messages (<?= $conn->query("SELECT COUNT(*) c FROM messages WHERE recipient_id=$doctor_id AND is_read=0")->fetch_assoc()['c'] ?> unread)</h2>

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
                                <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <button class="btn btn-primary" 
                                        onclick='openMessageModal(
                                            <?php echo $msg["id"]; ?>, 
                                            <?php echo $msg["sender_id"]; ?>, 
                                            <?php echo json_encode($msg["subject"]); ?>, 
                                            <?php echo json_encode($msg["body"]); ?>, 
                                            <?php echo $msg["is_read"] ? "true" : "false"; ?>
                                        )'>
                                        Read
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteMessage(<?php echo $msg['id']; ?>)">Delete</button>
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

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Reschedule Appointment</h3>
        <form id="rescheduleForm">
            <input type="hidden" name="reschedule_id" id="reschedule_id">
            <label>New Date & Time</label>
            <input type="datetime-local" name="new_datetime" id="new_datetime" required>
            <div class="modal-actions">
                <button type="submit" class="btn btn-info">Update Appointment</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
        <p id="rescheduleMsg" style="color: var(--success); margin-top: 12px; font-weight: 500;"></p>
    </div>
</div>

<!-- Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeMessageModal()">&times;</span>
        <h3 id="messageSubject"></h3>
        <p id="messageBody" style="margin: 16px 0; white-space: pre-wrap; line-height: 1.6;"></p>
        <hr style="border: none; border-top: 1px solid var(--border); margin: 20px 0;">
        <button class="btn btn-primary" onclick="toggleReplyForm()">Reply</button>

        <form id="replyForm" style="display: none; margin-top: 20px;">
            <input type="hidden" id="reply_to_id" name="reply_to_id">
            <input type="hidden" id="reply_subject" name="reply_subject">
            <label>Your Reply</label>
            <textarea name="reply_body" id="reply_body" required placeholder="Type your reply here..."></textarea>
            <div class="modal-actions">
                <button type="submit" class="btn btn-success">Send Reply</button>
                <button type="button" class="btn btn-secondary" onclick="toggleReplyForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('reschedule_id').value = id;
        document.getElementById('rescheduleModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('rescheduleModal').classList.remove('show');
        document.getElementById('rescheduleMsg').innerText = '';
    }

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
            fetch('doctor_dashboard.php', { method: 'POST', body: formData });
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
            fetch('doctor_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Message deleted successfully!");
                    location.reload();
                }
            });
        }
    }

    // Reschedule form
    document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('doctor_dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('rescheduleMsg').innerText = "Appointment rescheduled successfully!";
                setTimeout(() => location.reload(), 1000);
            }
        });
    });

    // Reply form
    document.getElementById('replyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('doctor_dashboard.php', {
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
</script>

</body>
</html>