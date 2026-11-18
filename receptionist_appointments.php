<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'receptionist') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$receptionist_name = $_SESSION['user_name'];

// Handle confirm appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_appt'])) {
    $apptId = intval($_POST['appt_id']);
    $status = $_POST['status']; // 'approved' or 'declined'
    $conn->query("UPDATE appointments SET status='$status', confirmed_datetime=NOW() WHERE id=$apptId");
    header("Location: receptionist_appointments.php?success=Appointment+updated");
    exit;
}

// Handle cancel
if (isset($_GET['cancel_appt'])) {
    $id = intval($_GET['cancel_appt']);
    $conn->query("UPDATE appointments SET status='cancelled' WHERE id=$id");
    header("Location: receptionist_appointments.php?success=Appointment+cancelled");
    exit;
}

// Fetch all appointments
$appts = $conn->query("
    SELECT a.*, p.name AS patient_name, d.name AS doctor_name, dept.name AS department_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    LEFT JOIN users d ON a.doctor_id = d.id
    LEFT JOIN departments dept ON a.department_id = dept.id
    ORDER BY a.created_at DESC
");

// Fetch patients and doctors for new booking
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name");
$doctors  = $conn->query("SELECT id, name FROM users WHERE role='doctor' ORDER BY name");
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// Handle new booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appt'])) {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id  = intval($_POST['doctor_id']);
    $dept_id    = intval($_POST['department_id']);
    $issue      = trim($_POST['issue']);
    $datetime   = $_POST['requested_datetime'];
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, department_id, issue, requested_datetime, status) VALUES (?,?,?,?,?,'pending')");
    $stmt->bind_param("iiiss", $patient_id, $doctor_id, $dept_id, $issue, $datetime);
    if ($stmt->execute()) {
        header("Location: receptionist_appointments.php?success=Appointment+booked");
        exit;
    } else {
        header("Location: receptionist_appointments.php?error=Booking+failed");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Receptionist | Iran-Uganda Hospital</title>
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

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }

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
        .form-group select {
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
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 520px;
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
        .modal .close:hover { color: #2d3748; }
        .modal h3 { margin-bottom: 16px; color: var(--primary); font-size: 1.25rem; }
        .modal-actions { display: flex; gap: 10px; margin-top: 8px; }

        @media (max-width: 640px) {
            header { flex-direction: column; gap: 10px; text-align: center; }
            header nav { width: 100%; justify-content: center; }
            .card { padding: 18px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-calendar-check"></i> Appointments</h1>
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

    <!-- Book New Appointment -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Book New Appointment</h2>
        <form method="POST" action="receptionist_appointments.php">
            <input type="hidden" name="book_appt" value="1">
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
                    <label>Department</label>
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php while ($d = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Doctor</label>
                    <select name="doctor_id" required>
                        <option value="">Select doctor</option>
                        <?php $doctors->data_seek(0); while ($d = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Issue / Reason</label>
                    <input type="text" name="issue" placeholder="e.g. Chest pain, Routine checkup" required>
                </div>
                <div class="form-group">
                    <label>Date & Time</label>
                    <input type="datetime-local" name="requested_datetime" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Book Appointment</button>
        </form>
    </div>

    <!-- All Appointments -->
    <div class="card">
        <h2><i class="fas fa-list"></i> All Appointments</h2>
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
                            <th>Action</th>
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
                                <td>
                                    <?php if ($a['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success" onclick="openConfirmModal(<?php echo $a['id']; ?>, 'approved')">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="openConfirmModal(<?php echo $a['id']; ?>, 'declined')">
                                            <i class="fas fa-times"></i> Decline
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled><?php echo ucfirst($a['status']); ?></button>
                                    <?php endif; ?>
                                    <a href="receptionist_appointments.php?cancel_appt=<?php echo $a['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?')">
                                        <i class="fas fa-trash"></i> Cancel
                                    </a>
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
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeConfirmModal()">&times;</span>
        <h3 id="confirmTitle"><i class="fas fa-check-circle"></i> Confirm Appointment</h3>
        <form method="POST" action="receptionist_appointments.php">
            <input type="hidden" name="confirm_appt" value="1">
            <input type="hidden" name="appt_id" id="confirm_appt_id">
            <div class="form-group">
                <label>Set Status</label>
                <select name="status" id="confirm_status" required>
                    <option value="approved">Confirmed (Approved)</option>
                    <option value="declined">Declined</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openConfirmModal(apptId, status) {
        document.getElementById('confirm_appt_id').value = apptId;
        document.getElementById('confirm_status').value = status;
        document.getElementById('confirmTitle').innerHTML = status === 'approved'
            ? '<i class="fas fa-check-circle"></i> Confirm Appointment'
            : '<i class="fas fa-times-circle"></i> Decline Appointment';
        document.getElementById('confirmModal').classList.add('show');
    }
    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('show');
    }
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    }
</script>

</body>
</html>