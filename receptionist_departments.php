<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'receptionist') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$receptionist_name = $_SESSION['user_name'];

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// Count patients per department (approx via appointments)
$deptStats = [];
$deptResult = $conn->query("SELECT department_id, COUNT(*) as cnt FROM appointments GROUP BY department_id");
while ($row = $deptResult->fetch_assoc()) {
    $deptStats[$row['department_id']] = $row['cnt'];
}

// Fetch all patients for directing
$patients = $conn->query("SELECT * FROM patients ORDER BY name");

// Handle direct patient to department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $dept_id    = intval($_POST['department_id']);
    $issue      = trim($_POST['issue']);
    // Create a pending appointment to direct patient
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, department_id, issue, requested_datetime, status) VALUES (?, NULL, ?, ?, NOW(), 'pending')");
    $stmt->bind_param("iis", $patient_id, $dept_id, $issue);
    if ($stmt->execute()) {
        header("Location: receptionist_departments.php?success=Patient+directed+to+department");
        exit;
    } else {
        header("Location: receptionist_departments.php?error=Direction+failed");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Receptionist | Iran-Uganda Hospital</title>
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

        /* Department Cards Grid */
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .dept-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
        }

        .dept-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border-color: var(--primary-light);
        }

        .dept-card .icon {
            font-size: 2rem;
            color: var(--primary-light);
            margin-bottom: 12px;
        }

        .dept-card h3 {
            color: var(--primary);
            font-size: 1.15rem;
            margin-bottom: 6px;
        }

        .dept-card p {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 16px;
            flex: 1;
        }

        .dept-card .dept-meta {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .dept-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .dept-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        @media (max-width: 640px) {
            header { flex-direction: column; gap: 10px; text-align: center; }
            header nav { width: 100%; justify-content: center; }
            .card { padding: 18px; }
            .dept-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-building"></i> Hospital Departments</h1>
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
        <p>View all departments and direct patients to the appropriate department.</p>
    </div>

    <!-- Direct Patient -->
    <div class="card">
        <h2><i class="fas fa-sign-in-alt"></i> Direct Patient to Department</h2>
        <form method="POST" action="receptionist_departments.php">
            <input type="hidden" name="direct_patient" value="1">
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
                        <?php
                        $departments->data_seek(0);
                        while ($d = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Issue / Reason</label>
                    <input type="text" name="issue" placeholder="e.g. Chest pain, Follow-up, Routine checkup" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Direct Patient</button>
        </form>
    </div>

    <!-- Department Directory -->
    <div class="card">
        <h2><i class="fas fa-building"></i> All Departments</h2>
        <?php if ($departments->num_rows > 0): ?>
            <div class="dept-grid">
                <?php
                $departments->data_seek(0);
                $deptIcons = [
                    'General Medicine' => 'fa-stethoscope',
                    'Cardiology' => 'fa-heartbeat',
                    'Neurology' => 'fa-brain',
                    'Orthopedics' => 'fa-bone',
                    'Pediatrics' => 'fa-baby',
                    'Emergency' => 'fa-ambulance',
                    'Dental Services' => 'fa-tooth',
                    'Surgery' => 'fa-syringe',
                ];
                while ($d = $departments->fetch_assoc()):
                    $icon = $deptIcons[$d['name']] ?? 'fa-hospital';
                    $count = isset($deptStats[$d['id']]) ? $deptStats[$d['id']] : 0;
                ?>
                <div class="dept-card">
                    <div class="icon"><i class="fas <?php echo $icon; ?>"></i></div>
                    <h3><?php echo htmlspecialchars($d['name']); ?></h3>
                    <p><?php echo htmlspecialchars($d['description'] ?? 'Specialized medical care'); ?></p>
                    <div class="dept-meta">
                        <i class="fas fa-users"></i> <?php echo $count; ?> appointment(s)
                    </div>
                    <div class="dept-actions">
                        <button class="btn btn-info btn-sm" onclick="directToDept(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars($d['name']); ?>')">
                            <i class="fas fa-user-plus"></i> Direct Patient
                        </button>
                        <a href="receptionist_appointments.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-check"></i> Book Appt
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">No departments found.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    function directToDept(deptId, deptName) {
        if (confirm('Direct a patient to ' + deptName + '?')) {
            // Store dept id and redirect to appointments with pre-selection
            localStorage.setItem('targetDept', deptId);
            window.location.href = 'receptionist_appointments.php';
        }
    }
</script>

</body>
</html>