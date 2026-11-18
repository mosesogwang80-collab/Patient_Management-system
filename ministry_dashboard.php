<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ministry') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$min_id   = $_SESSION['user_id'];
$min_name = $_SESSION['user_name'];

// ============ MONITORING ONLY (VIEW-ALL) ============
$total_patients     = $conn->query("SELECT COUNT(*) as total FROM patients")->fetch_assoc()['total'];
$admitted_patients  = $conn->query("SELECT COUNT(*) as total FROM patients WHERE patient_status='admitted'")->fetch_assoc()['total'];
$discharged_patients= $conn->query("SELECT COUNT(*) as total FROM patients WHERE patient_status='discharged'")->fetch_assoc()['total'];
$outpatient_patients= $conn->query("SELECT COUNT(*) as total FROM patients WHERE patient_status='outpatient'")->fetch_assoc()['total'];
$total_bills        = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM medical_bills")->fetch_assoc()['total'];
$unpaid_bills       = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM medical_bills WHERE status='unpaid'")->fetch_assoc()['total'];
$paid_bills         = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM medical_bills WHERE status='paid'")->fetch_assoc()['total'];
$total_doctors      = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='doctor'")->fetch_assoc()['total'];
$total_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments")->fetch_assoc()['total'];
$total_pending_apt  = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='pending'")->fetch_assoc()['total'];
$total_approved_apt = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='approved'")->fetch_assoc()['total'];
$total_pharmacists  = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='pharmacy'")->fetch_assoc()['total'];
$total_cashiers     = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='cashier'")->fetch_assoc()['total'];
$total_nurses       = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='nurse'")->fetch_assoc()['total'];
$total_receptionists= $conn->query("SELECT COUNT(*) as total FROM users WHERE role='receptionist'")->fetch_assoc()['total'];

// ============ MESSAGES WITH CEO AND MEDICAL DIRECTOR ONLY ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt_read = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=? AND recipient_id=?");
    $stmt_read->bind_param("ii", $msg_id, $min_id);
    $stmt_read->execute();
    echo json_encode(['success' => true]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt_delete = $conn->prepare("DELETE FROM messages WHERE id=? AND recipient_id=?");
    $stmt_delete->bind_param("ii", $delete_id, $min_id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    // Ministry can only message CEO or Medical Director
    $allowed_roles = ['ceo','medical_director'];
    $rec_role = $conn->query("SELECT role FROM users WHERE id=$recipient_id")->fetch_assoc()['role'] ?? '';
    if ($recipient_id > 0 && !empty($subject) && !empty($body) && in_array($rec_role, $allowed_roles)) {
        $stmt_send = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'ministry', ?)");
        $stmt_send->bind_param("iisss", $min_id, $recipient_id, $subject, $body, $rec_role);
        if ($stmt_send->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Ministry can only message CEO or Medical Director']);
    }
    exit();
}

// Fetch CEO and Medical Director for dropdown + messaging
$ceo_row = $conn->query("SELECT id, name FROM users WHERE role='ceo' ORDER BY name");
$md_row  = $conn->query("SELECT id, name FROM users WHERE role='medical_director' ORDER BY name");

// Fetch messages from/to CEO or MD only
$messages = $conn->prepare("
    SELECT m.id, m.subject, m.body, m.created_at AS sent_at, m.is_read, m.sender_id, u.name AS sender_name, m.sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ? AND (m.sender_role = 'ceo' OR m.sender_role = 'medical_director')
    ORDER BY m.created_at DESC
");
$messages->bind_param("i", $min_id);
$messages->execute();
$messages = $messages->get_result();

$unread_count = $conn->query("SELECT COUNT(*) c FROM messages WHERE recipient_id=$min_id AND is_read=0 AND (sender_role='ceo' OR sender_role='medical_director')")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ministry of Health Dashboard | Iran-Uganda Hospital</title>
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
            --card-bg: rgba(255, 255, 255, 0.93);
            --border: #e2e8f0;
            --text: #2d3748;
            --muted: #718096;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/product.jpeg') center/cover fixed;
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
        }
        header h1 { font-size: 1.4rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        header nav a { color: white; text-decoration: none; font-weight: 500; padding: 8px 14px; border-radius: 6px; transition: background 0.2s; margin-left: 6px; }
        header nav a:hover { background: rgba(255,255,255,0.15); }
        header nav a.logout { background: rgba(255,255,255,0.12); }
        .container { max-width: 1200px; margin: 28px auto; padding: 0 20px 50px; }
        .card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 24px 28px; margin-bottom: 28px; backdrop-filter: blur(5px); }
        .card h2 { color: var(--primary); font-size: 1.25rem; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 8px; }
        .stat-card { background: white; border-radius: 10px; padding: 18px 20px; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .stat-card .label { font-size: 0.85rem; color: var(--muted); margin-bottom: 6px; font-weight: 500; }
        .stat-card .value { font-size: 1.7rem; font-weight: 700; color: var(--primary); }
        .stat-card .sub { font-size: 0.8rem; color: var(--muted); margin-top: 4px; }
        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 7px; font-size: 0.875rem; font-weight: 500; text-decoration: none; color: white; border: none; cursor: pointer; transition: all 0.2s; margin-right: 6px; margin-top: 4px; }
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
        .status-active { background: #c6f6d5; color: #276749; }
        .status-inactive { background: #fed7d7; color: #9b2c2c; }
        .alert { padding: 13px 18px; border-radius: 8px; margin-bottom: 22px; font-weight: 500; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 6px; color: var(--text); }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; font-family: inherit; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .empty-state { text-align: center; padding: 30px; color: var(--muted); }
        .unread { font-weight: 600; background: #fffaf0 !important; }
        .no-print { display: inline; }
        .readonly-badge { background: var(--warning); color: white; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 600; margin-left: 8px; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 28px; width: 90%; max-width: 520px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); max-height: 90vh; overflow-y: auto; }
        .modal-content h3 { color: var(--primary); margin-bottom: 18px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        @media print {
            .no-print { display: none !important; }
            header, nav, .btn, .modal { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid #ccc; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 8px; }
        }
        @media (max-width: 768px) {
            header { flex-direction: column; align-items: flex-start; gap: 14px; }
            header nav { width: 100%; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-hospital"></i> Ministry of Health <span class="readonly-badge">Monitoring Only</span></h1>
    <nav>
        <a href="ministry_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
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

    <!-- Monitoring Stats (View-Only) -->
    <div class="card">
        <h2><i class="fas fa-chart-area"></i> Hospital Monitoring (View-Only)</h2>
        <p>Ministry of Health oversight — read-only access to hospital statistics.</p>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Patients</div>
                <div class="value"><?= $total_patients ?></div>
                <div class="sub">Admitted: <?= $admitted_patients ?> • Discharged: <?= $discharged_patients ?> • Out: <?= $outpatient_patients ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Bills</div>
                <div class="value">USh <?= number_format($total_bills, 2) ?></div>
                <div class="sub">Paid: USh <?= number_format($paid_bills, 2) ?> • Unpaid: USh <?= number_format($unpaid_bills, 2) ?></div>
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
                <div class="sub">Appointments & directing</div>
            </div>
        </div>
        <button class="btn btn-sm btn-print no-print" style="margin-top:16px;" onclick="window.print()"><i class="fas fa-print"></i> Print Monitoring Report</button>
    </div>

    <!-- Messages (CEO & Medical Director Only) -->
    <div class="card">
        <h2><i class="fas fa-envelope"></i> Messages (CEO & Medical Director Only) (<?= $unread_count ?> unread)</h2>
        <p class="text-muted" style="font-size:0.85rem;">Ministry can only communicate with the CEO and Medical Director.</p>
        <a class="btn btn-sm btn-info no-print" style="float:right; margin-bottom:12px;" onclick="openComposeModal()"><i class="fas fa-paper-plane"></i> Compose</a>
        <?php if ($messages->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>From</th><th>Subject</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($msg = $messages->fetch_assoc()): ?>
                            <tr class="<?= $msg['is_read'] ? '' : 'unread' ?>">
                                <td><?= htmlspecialchars($msg['sender_name']) ?> <small style="color:var(--muted);">[<?= htmlspecialchars($msg['sender_role']) ?>]</small></td>
                                <td><?= htmlspecialchars($msg['subject']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($msg['sent_at'])) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick='openMessageModal(<?= (int)$msg["id"] ?>, <?= (int)$msg["sender_id"] ?>, <?= json_encode($msg["subject"]) ?>, <?= json_encode($msg["body"]) ?>, <?= $msg["is_read"] ? "true" : "false" ?>)'><i class="fas fa-eye"></i> Read</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteMessage(<?= (int)$msg['id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No messages with CEO or Medical Director yet.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Read Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <h3 id="messageSubject"></h3>
        <p id="messageBody" style="margin:16px 0 20px; white-space:pre-wrap;"></p>
        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
    </div>
</div>

<!-- Compose Message Modal (CEO & MD Only) -->
<div id="composeModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-paper-plane"></i> Compose Message</h3>
        <form id="composeForm">
            <input type="hidden" name="send_message" value="1">
            <div class="form-group">
                <label>To</label>
                <select name="recipient_id" required>
                    <option value="">Select recipient</option>
                    <?php while ($c = $ceo_row->fetch_assoc()): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (CEO)</option>
                    <?php endwhile; ?>
                    <?php while ($m = $md_row->fetch_assoc()): ?>
                        <option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?> (Medical Director)</option>
                    <?php endwhile; ?>
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Message</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMessageModal(id, senderId, subject, body, isRead) {
    document.getElementById('messageSubject').innerText = subject;
    document.getElementById('messageBody').innerText = body;
    document.getElementById('messageModal').classList.add('show');
    if (isRead === false || isRead === 'false') {
        const formData = new FormData();
        formData.append('mark_read_id', id);
        fetch('ministry_dashboard.php', { method: 'POST', body: formData })
            .then(() => { const b=document.getElementById('unread-count'); if(b){ let c=parseInt(b.innerText)||0; if(c>0)b.innerText=c-1; } });
    }
}
function closeMessageModal(){ document.getElementById('messageModal').classList.remove('show'); }
function openComposeModal(){ document.getElementById('composeModal').classList.add('show'); }
function closeComposeModal(){ document.getElementById('composeModal').classList.remove('show'); document.getElementById('composeForm').reset(); }
function deleteMessage(id){
    if(!confirm("Delete this message?")) return;
    const fd=new FormData(); fd.append('delete_message_id', id);
    fetch('ministry_dashboard.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
}
window.addEventListener('click',function(e){ if(e.target.classList.contains('modal')) e.target.classList.remove('show'); });

document.getElementById('composeForm').addEventListener('submit',function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fetch('ministry_dashboard.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){ alert('Message sent!'); closeComposeModal(); location.reload(); }
        else alert(d.error||'Failed to send');
    }).catch(()=>alert('Network error'));
});

// Auto-dismiss alerts after 5 seconds
setTimeout(()=>{
    document.querySelectorAll('.alert').forEach(a=>{ a.style.transition='opacity 0.5s ease'; a.style.opacity='0'; setTimeout(()=>a.remove(),500); });
},5000);
</script>
</body>
</html>
