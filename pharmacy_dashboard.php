<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacy') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$pharmacy_name = $_SESSION['user_name'];
$pharmacy_id   = $_SESSION['user_id'];

// Auto-discontinue expired drugs
$today = date('Y-m-d');
$expired = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' AND expiry_date IS NOT NULL AND expiry_date < '$today'");
while ($d = $expired->fetch_assoc()) {
    $conn->query("INSERT INTO expired_drugs (original_drug_id, name, category, price, stock, expiry_date, supplier, discontinued_by) 
        VALUES ({$d['id']}, '{$d['name']}', '{$d['category']}', {$d['price']}, {$d['stock']}, '{$d['expiry_date']}', '{$d['supplier']}', $pharmacy_id)");
    $conn->query("UPDATE pharmacy_drugs SET status='discontinued' WHERE id={$d['id']}");
}

// Stats
$total_drugs   = $conn->query("SELECT COUNT(*) AS c FROM pharmacy_drugs WHERE status='active'")->fetch_assoc()['c'];
$low_stock     = $conn->query("SELECT COUNT(*) AS c FROM pharmacy_drugs WHERE status='active' AND stock <= min_stock")->fetch_assoc()['c'];
$out_of_stock  = $conn->query("SELECT COUNT(*) AS c FROM pharmacy_drugs WHERE status='active' AND stock = 0")->fetch_assoc()['c'];
$total_rx      = $conn->query("SELECT COUNT(*) AS c FROM prescriptions")->fetch_assoc()['c'];

// Handle add drug
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_drug'])) {
    $name      = trim($_POST['name']);
    $category  = trim($_POST['category']);
    $price     = floatval($_POST['price']);
    $stock     = intval($_POST['stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry    = $_POST['expiry_date'] ?: null;
    $supplier  = trim($_POST['supplier']);
    $stmt = $conn->prepare("INSERT INTO pharmacy_drugs (name, category, price, stock, min_stock, expiry_date, supplier) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdiiss", $name, $category, $price, $stock, $min_stock, $expiry, $supplier);
    if ($stmt->execute()) {
        header("Location: pharmacy_dashboard.php?success=Drug+added+successfully");
        exit;
    } else {
        header("Location: pharmacy_dashboard.php?error=Failed+to+add+drug");
        exit;
    }
}

// Handle delete drug
if (isset($_GET['delete_drug'])) {
    $id = intval($_GET['delete_drug']);
    $conn->query("DELETE FROM pharmacy_drugs WHERE id=$id");
    header("Location: pharmacy_dashboard.php?success=Drug+deleted");
    exit;
}

// Fetch drugs
$drugs = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' ORDER BY name");

// Fetch recent prescriptions
$rx = $conn->query("
    SELECT pr.*, p.name AS patient_name
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    ORDER BY pr.issued_at DESC
    LIMIT 8
");

// Fetch all users for compose message dropdown
$all_users = $conn->query("SELECT id, name, role FROM users WHERE id != $pharmacy_id ORDER BY name");

// Message handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = intval($_POST['mark_read_id']);
    $stmt_read = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=? AND recipient_id=?");
    $stmt_read->bind_param("ii", $msg_id, $pharmacy_id);
    $stmt_read->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = intval($_POST['delete_message_id']);
    $stmt_delete = $conn->prepare("DELETE FROM messages WHERE id=? AND recipient_id=?");
    $stmt_delete->bind_param("ii", $delete_id, $pharmacy_id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    if ($recipient_id > 0 && !empty($subject) && !empty($body)) {
        $rec_role = $conn->query("SELECT role FROM users WHERE id=$recipient_id")->fetch_assoc()['role'] ?? 'admin';
        $stmt_send = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'pharmacy', ?)");
        $stmt_send->bind_param("iisss", $pharmacy_id, $recipient_id, $subject, $body, $rec_role);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_id'])) {
    $original_sender_id = intval($_POST['reply_to_id']);
    $reply_subject = "Re: " . trim($_POST['reply_subject'] ?? '');
    $reply_body    = trim($_POST['reply_body'] ?? '');
    $rec_role      = trim($_POST['recipient_role'] ?? 'admin');

    if ($original_sender_id > 0 && $reply_body !== '') {
        $stmt_reply = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sender_role, recipient_role) VALUES (?, ?, ?, ?, 'pharmacy', ?)");
        $stmt_reply->bind_param("iisss", $pharmacy_id, $original_sender_id, $reply_subject, $reply_body, $rec_role);
        if ($stmt_reply->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send reply.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing reply data']);
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
$messages->bind_param("i", $pharmacy_id);
$messages->execute();
$messages = $messages->get_result();

// Unread count
$unread_count = $conn->query("SELECT COUNT(*) c FROM messages WHERE recipient_id=$pharmacy_id AND is_read=0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard - Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: seagreen;
            --primary-light: mediumseagreen;
            --success: seagreen;
            --danger: crimson;
            --warning: goldenrod;
            --info: darkcyan;
            --bg: whitesmoke;
            --card-bg: white;
            --border: lightgray;
            --text: darkslategray;
            --muted: slategray;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/hospital1.jpg') center/cover fixed;
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
        .btn-success:hover { background: darkgreen; }

        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: darkred; }

        .btn-info { background: var(--info); }
        .btn-info:hover { background: teal; }

        .btn-secondary {
            background: slategray;
        }
        .btn-secondary:hover {
            background: dimgray;
        }

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

        tr:nth-child(even) { background: whitesmoke; }
        tr:hover { background: gainsboro; }

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-instock { background: palegreen; color: darkgreen; }
        .status-lowstock { background: lightgoldenrodyellow; color: darkgoldenrod; }
        .status-outstock { background: lightpink; color: darkred; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success { background: palegreen; color: darkgreen; }
        .alert-error { background: lightpink; color: darkred; }

        .form-group {
            margin-bottom: 16px;
        }

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

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 28px;
            width: 90%;
            max-width: 520px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-content h3 {
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-content textarea {
            width: 100%;
            min-height: 120px;
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
        .unread {
            background: aliceblue !important;
            font-weight: 600;
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
    <h1><i class="fas fa-capsules"></i> Pharmacy Dashboard</h1>
    <nav>
        <a href="pharmacy_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="pharmacy_drugs.php"><i class="fas fa-capsules"></i> Drug Inventory</a>
        <a href="pharmacy_prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a>
        <a href="expired_drugs.php" style="background:crimson;"><i class="fas fa-trash"></i> Expired Drugs</a>
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
        <h2><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($pharmacy_name); ?></h2>
        <p>Manage drug inventory and prescriptions here.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Drugs</div>
            <div class="value"><?php echo $total_drugs; ?></div>
            <div class="sub">In inventory</div>
        </div>
        <div class="stat-card">
            <div class="label">Low Stock</div>
            <div class="value"><?php echo $low_stock; ?></div>
            <div class="sub">Below minimum level</div>
        </div>
        <div class="stat-card">
            <div class="label">Out of Stock</div>
            <div class="value"><?php echo $out_of_stock; ?></div>
            <div class="sub">Needs restocking</div>
        </div>
        <div class="stat-card">
            <div class="label">Prescriptions</div>
            <div class="value"><?php echo $total_rx; ?></div>
            <div class="sub">Total issued</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <button class="btn btn-sm no-print" style="margin-bottom:12px; background:slateblue; color:white;" onclick="window.print()">
            <i class="fas fa-print"></i> Print Dashboard
        </button>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="pharmacy_drugs.php" class="btn btn-primary"><i class="fas fa-capsules"></i> Manage Drugs</a>
        </div>
    </div>

    <!-- Add Drug -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Add New Drug</h2>
        <form method="POST" action="pharmacy_dashboard.php">
            <div class="form-grid">
                <div class="form-group">
                    <label>Drug Name</label>
                    <input type="text" name="name" placeholder="e.g. Paracetamol 500mg" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select category</option>
                        <option>Analgesic</option>
                        <option>Antibiotic</option>
                        <option>Antacid</option>
                        <option>Antihypertensive</option>
                        <option>Respiratory</option>
                        <option>Vitamin</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (USh)</label>
                    <input type="number" name="price" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" min="0" placeholder="0" required>
                </div>
                <div class="form-group">
                    <label>Min Stock Level</label>
                    <input type="number" name="min_stock" min="0" value="10" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Supplier</label>
                    <input type="text" name="supplier" placeholder="Supplier name">
                </div>
            </div>
            <button type="submit" name="add_drug" class="btn btn-primary">
                <i class="fas fa-save"></i> Add Drug
            </button>
        </form>
    </div>

    <!-- Drug Inventory -->
    <div class="card">
        <h2><i class="fas fa-clock"></i> Drug Inventory</h2>
        <?php if ($drugs->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Drug Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Min Stock</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($d = $drugs->fetch_assoc()): ?>
                            <?php
                                $status = $d['stock'] == 0 ? 'outstock' : ($d['stock'] <= $d['min_stock'] ? 'lowstock' : 'instock');
                                $statusLabel = ucfirst($status);
                            ?>
                            <tr>
                                <td>#<?php echo $d['id']; ?></td>
                                <td><?php echo htmlspecialchars($d['name']); ?></td>
                                <td><?php echo htmlspecialchars($d['category']); ?></td>
                                <td>USh <?php echo number_format($d['price'], 2); ?></td>
                                <td><?php echo $d['stock']; ?></td>
                                <td><?php echo $d['min_stock']; ?></td>
                                <td><?php echo $d['expiry_date'] ? date('M d, Y', strtotime($d['expiry_date'])) : '-'; ?></td>
                                <td><span class="status status-<?php echo $status; ?>"><?php echo $statusLabel; ?></span></td>
                                <td>
                                    <a href="pharmacy_dashboard.php?delete_drug=<?php echo $d['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this drug?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    <a href="pharmacy_drugs.php?edit=<?php echo $d['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No drugs in inventory yet.</div>
        <?php endif; ?>
    </div>

    <!-- Recent Prescriptions -->
    <div class="card">
        <h2><i class="fas fa-file-prescription"></i> Recent Prescriptions</h2>
        <?php if ($rx->num_rows > 0): ?>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $rx->fetch_assoc()): ?>
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
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No prescriptions yet.</div>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <div class="card">
        <h2>
            <i class="fas fa-envelope"></i> My Messages
            (<span id="unread-count"><?php echo $unread_count; ?></span> unread)
        </h2>
        <button class="btn btn-sm btn-info" style="float:right; margin-bottom:12px;" onclick="openComposeModal()">
            <i class="fas fa-paper-plane"></i> Compose Message
        </button>

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

<!-- Message Modal (Read + Reply) -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <h3 id="messageSubject"></h3>
        <p id="messageBody" style="margin: 16px 0 20px; white-space: pre-wrap; line-height: 1.6;"></p>

        <hr style="border: none; border-top: 1px solid lightgray; margin: 20px 0;">

        <div id="replySection">
            <h4 style="margin-bottom:12px; color:seagreen;">Reply</h4>
            <form id="replyForm">
                <input type="hidden" name="reply_to_id" id="replyToId">
                <input type="hidden" name="reply_subject" id="replySubject">
                <input type="hidden" name="recipient_role" id="replyRole">
                <div class="form-group">
                    <textarea name="reply_body" id="replyBody" required placeholder="Type your reply..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeMessageModal()">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-reply"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Compose Message Modal -->
<div id="composeModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-paper-plane"></i> Compose New Message</h3>
        <form id="composeForm">
            <input type="hidden" name="send_message" value="1">
            <div class="form-group">
                <label>To</label>
                <select name="recipient_id" required>
                    <option value="">Select recipient</option>
                    <?php
                    $all_users->data_seek(0);
                    while ($u = $all_users->fetch_assoc()): ?>
                        <option value="<?php echo (int)$u['id']; ?>">
                            <?php echo htmlspecialchars($u['name']); ?> (<?php echo ucfirst($u['role']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="Enter subject">
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="body" required placeholder="Type your message here..."></textarea>
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
function openMessageModal(id, senderId, subject, body, isRead, senderRole) {
    document.getElementById('messageSubject').innerText = subject;
    document.getElementById('messageBody').innerText = body;

    document.getElementById('replyToId').value = senderId;
    document.getElementById('replySubject').value = subject;
    document.getElementById('replyRole').value = senderRole || 'admin';
    document.getElementById('replyBody').value = '';

    document.getElementById('messageModal').classList.add('show');

    if (isRead === false || isRead === 'false') {
        const formData = new FormData();
        formData.append('mark_read_id', id);
        fetch('pharmacy_dashboard.php', { method: 'POST', body: formData })
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

function openComposeModal() {
    document.getElementById('composeModal').classList.add('show');
}

function closeComposeModal() {
    document.getElementById('composeModal').classList.remove('show');
    document.getElementById('composeForm').reset();
}

function deleteMessage(id) {
    if (!confirm("Are you sure you want to delete this message?")) return;

    const formData = new FormData();
    formData.append('delete_message_id', id);

    fetch('pharmacy_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Failed to delete message');
    })
    .catch(() => alert('Network error'));
}

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

document.getElementById('composeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('pharmacy_dashboard.php', {
        method: 'POST',
        body: formData
    })
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
    .catch(() => alert('Network error while sending message'));
});

document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('reply_to_id', document.getElementById('replyToId').value);

    fetch('pharmacy_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Reply sent successfully!');
            closeMessageModal();
        } else {
            alert(data.error || 'Failed to send reply');
        }
    })
    .catch(() => alert('Network error while sending reply'));
});

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
