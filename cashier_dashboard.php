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

// Stats
$total_billed   = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills")->fetch_assoc()['t'];
$collected      = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills WHERE status='paid'")->fetch_assoc()['t'];
$pending_amt    = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM medical_bills WHERE status='unpaid'")->fetch_assoc()['t'];
$total_patients = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];

// Handle create bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bill'])) {
    $patient_id = intval($_POST['patient_id']);
    $amount     = floatval($_POST['amount']);
    $desc       = trim($_POST['description']);
    $stmt = $conn->prepare("INSERT INTO medical_bills (patient_id, amount, description, status) VALUES (?, ?, ?, 'unpaid')");
    $stmt->bind_param("ids", $patient_id, $amount, $desc);
    if ($stmt->execute()) {
        $bill_id = $stmt->insert_id;
        audit_log($conn, $cashier_id, 'create_bill', 'bill_id=' . $bill_id . ', patient_id=' . $patient_id . ', amount=' . $amount);
        header("Location: cashier_dashboard.php?success=Bill+created+successfully");
        exit;
    } else {
        header("Location: cashier_dashboard.php?error=Failed+to+create+bill");
        exit;
    }
}

// Message handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt_read = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=? AND recipient_id=?");
    $stmt_read->bind_param("ii", $msg_id, $cashier_id);
    $stmt_read->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt_delete = $conn->prepare("DELETE FROM messages WHERE id=? AND recipient_id=?");
    $stmt_delete->bind_param("ii", $delete_id, $cashier_id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_id'])) {
    $original_sender_id = intval($_POST['reply_to_id']);
    $reply_subject = "Re: " . $_POST['reply_subject'];
    $reply_body = $_POST['reply_body'];
    $recipient_role = $_POST['recipient_role'] ?? 'doctor';

    $stmt_reply = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'cashier', ?)");
    $stmt_reply->bind_param("iisss", $cashier_id, $original_sender_id, $reply_subject, $reply_body, $recipient_role);
    
    if ($stmt_reply->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send reply.']);
    }
    exit();
}

// Fetch patients for dropdown
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name");

// Fetch recent bills
$bills = $conn->query("
    SELECT b.*, p.name AS patient_name
    FROM medical_bills b
    JOIN patients p ON b.patient_id = p.id
    ORDER BY b.created_at DESC
    LIMIT 10
");

// Fetch messages
$messages = $conn->prepare("
    SELECT m.id, m.subject, m.body, m.created_at AS sent_at, m.is_read, m.sender_id, 
           u.name AS sender_name, m.sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.created_at DESC
");
$messages->bind_param("i", $cashier_id);
$messages->execute();
$messages = $messages->get_result();

// Unread count
$unread_count = $conn->query("SELECT COUNT(*) c FROM messages WHERE recipient_id=$cashier_id AND is_read=0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard | Iran-Uganda Hospital</title>
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
            --card-bg: rgba(255, 255, 255, 0.95);
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
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 90;
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
            padding: 0 20px 50px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 24px 28px;
            margin-bottom: 28px;
            backdrop-filter: blur(6px);
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
        .btn-secondary { background: #718096; }
        .btn-secondary:hover { background: #4a5568; }
        .btn-sm { padding: 5px 11px; font-size: 0.82rem; }

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

        .status-paid { background: #c6f6d5; color: #276749; }
        .status-unpaid { background: #fed7d7; color: #9b2c2c; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text);
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--muted);
        }

        .unread {
            font-weight: 600;
            background: #ebf8ff !important;
        }

        /* ========== PROPER MODAL STYLES ========== */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 14px;
            padding: 30px;
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            position: relative;
            animation: modalFadeIn 0.25s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .modal-content h3 {
            color: var(--primary);
            font-size: 1.3rem;
            margin-bottom: 16px;
            padding-right: 30px;
        }

        .close {
            position: absolute;
            top: 18px;
            right: 22px;
            font-size: 1.6rem;
            color: #a0aec0;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }

        .close:hover {
            color: var(--danger);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .card {
                padding: 18px;
            }
            .modal-content {
                padding: 24px 18px;
            }
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
    <h1><i class="fas fa-money-bill-wave"></i> Cashier Dashboard</h1>
    <nav>
        <a href="cashier_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="cashier_billing.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
        <a href="cashier_payments.php"><i class="fas fa-wallet"></i> Payments</a>
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
        <h2><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($cashier_name); ?></h2>
        <p>Manage patient billing and receive payments here.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Billed</div>
            <div class="value">USh <?php echo number_format($total_billed, 0); ?></div>
            <div class="sub">All medical bills</div>
        </div>
        <div class="stat-card">
            <div class="label">Collected</div>
            <div class="value">USh <?php echo number_format($collected, 0); ?></div>
            <div class="sub">Paid bills</div>
        </div>
        <div class="stat-card">
            <div class="label">Pending</div>
            <div class="value">USh <?php echo number_format($pending_amt, 0); ?></div>
            <div class="sub">Unpaid bills</div>
        </div>
        <div class="stat-card">
            <div class="label">Patients</div>
            <div class="value"><?php echo $total_patients; ?></div>
            <div class="sub">Registered patients</div>
        </div>
    </div>

    <!-- Quick Bill Create -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Create New Bill</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Patient</label>
                    <select name="patient_id" required>
                        <option value="">Select patient</option>
                        <?php 
                        $patients->data_seek(0);
                        while ($p = $patients->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (USh)</label>
                    <input type="number" name="amount" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="e.g. Consultation fee, Lab tests" required>
                </div>
            </div>
            <button type="submit" name="create_bill" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Bill
            </button>
        </form>
    </div>

    <!-- Recent Bills -->
    <div class="card">
        <h2><i class="fas fa-clock"></i> Recent Bills</h2>
        <button class="btn btn-sm no-print" style="float:right; margin-bottom:12px; background:#2563eb;" onclick="window.print()">
            <i class="fas fa-print"></i> Print Bills
        </button>
        <?php if ($bills->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($b = $bills->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['patient_name']); ?></td>
                                <td>USh <?php echo number_format($b['amount'], 0); ?></td>
                                <td><?php echo htmlspecialchars($b['description'] ?: '—'); ?></td>
                                <td>
                                    <span class="status status-<?php echo $b['status']; ?>">
                                        <?php echo ucfirst($b['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                                <td>
                                    <a href="cashier_payments.php?pay_bill=<?php echo $b['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-wallet"></i> Pay
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No bills yet.</div>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <div class="card">
        <h2>
            <i class="fas fa-envelope"></i> My Messages 
            (<span id="unread-count"><?php echo $unread_count; ?></span> unread)
        </h2>

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
                                <td>
                                    <?php echo htmlspecialchars($msg['sender_name']); ?>
                                    <small style="color:var(--muted);">[<?php echo htmlspecialchars($msg['sender_role']); ?>]</small>
                                </td>
                                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                        onclick='openMessageModal(
                                            <?= (int)$msg["id"] ?>,
                                            <?= (int)$msg["sender_id"] ?>,
                                            <?= json_encode($msg["subject"]) ?>,
                                            <?= json_encode($msg["body"]) ?>,
                                            <?= $msg["is_read"] ? "true" : "false" ?>,
                                            <?= json_encode($msg["sender_role"]) ?>
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
            <div class="empty-state">No messages yet.</div>
        <?php endif; ?>
    </div>

</div>

<!-- ==================== MESSAGE MODAL (PROPER OVERLAY) ==================== -->
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
            
            <div class="form-group">
                <label>Your Reply</label>
                <textarea name="reply_body" id="reply_body" rows="4" required placeholder="Type your reply here..."></textarea>
            </div>

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
    document.getElementById('reply_body').value = '';

    if (isRead === false || isRead === 'false') {
        const formData = new FormData();
        formData.append('mark_read_id', id);
        fetch('cashier_dashboard.php', { method: 'POST', body: formData })
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

function toggleReplyForm() {
    const form = document.getElementById('replyForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function deleteMessage(id) {
    if (!confirm("Are you sure you want to delete this message?")) return;

    const formData = new FormData();
    formData.append('delete_message_id', id);
    fetch('cashier_dashboard.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Failed to delete message');
        });
}

document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('cashier_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Reply sent successfully!');
            closeMessageModal();
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(() => alert('Network error'));
});

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Auto-dismiss alerts
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