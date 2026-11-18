<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'nurse') {
    header('Location: login.php');
    exit;
}
include 'db.php';

$nurse_id = $_SESSION['user_id'];
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $pid = intval($_POST['patient_id']);
    $notes = trim($_POST['notes']);

    $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, record_date, diagnosis, treatment, notes) VALUES (?,?,CURDATE(),'Nurse update',?,?)");
    $stmt->bind_param("iiss", $pid, $nurse_id, $notes, $notes);
    $stmt->execute();
    $msg = "Progress updated successfully.";
}

$assignments = $conn->query("SELECT na.*, p.name AS patient_name FROM nurse_assignments na JOIN patients p ON na.patient_id=p.id WHERE na.nurse_id=$nurse_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/new_doctor_background.jpg') center/cover fixed;
            background-color: lightgray;
            color: darkslategray;
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Header */
        header {
            background: teal;
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }

        header .logo {
            font-size: 1.35rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        header nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        header nav a:hover {
            background: darkcyan;
        }

        /* Main Container */
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px 50px;
        }

        .card {
            background: rgba(255, 255, 255, 0.93);
            border-radius: 12px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
            padding: 28px 32px;
            backdrop-filter: blur(5px);
        }

        .card h2 {
            color: teal;
            font-size: 1.4rem;
            margin-bottom: 22px;
            padding-bottom: 12px;
            border-bottom: 2px solid lightgray;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Success Message */
        .success-msg {
            background: honeydew;
            color: darkgreen;
            border: 1px solid darkseagreen;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-weight: 500;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th {
            background: teal;
            color: white;
            padding: 13px 16px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid lightgray;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: whitesmoke;
        }

        tr:hover {
            background: mintcream;
        }

        /* Form inside table */
        textarea {
            width: 100%;
            min-height: 70px;
            padding: 10px;
            border: 1px solid lightgray;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            resize: vertical;
            margin-bottom: 8px;
        }

        textarea:focus {
            outline: none;
            border-color: teal;
            box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: teal;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover {
            background: darkcyan;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: gray;
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .card {
                padding: 22px;
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
    <div class="logo">
        <i class="fas fa-user-nurse"></i>
        Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>
    </div>
    <nav>
        <a href="nurse_dashboard.php"><i class="fas fa-tasks"></i> Tasks</a>
        <a href="nurse_patients.php"><i class="fas fa-hospital-user"></i> Patients</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<div class="container">
    <div class="card">
        <h2><i class="fas fa-clipboard-list"></i> Assigned Patients</h2>
        <button class="btn no-print" style="float:right; margin-bottom:12px; background:#2563eb;" onclick="window.print()">
            <i class="fas fa-print"></i> Print Patients
        </button>

        <?php if (isset($msg)): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($assignments->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Current Notes</th>
                            <th>Update Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($a = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($a['notes'] ?: 'No notes yet') ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="patient_id" value="<?= $a['patient_id'] ?>">
                                        <textarea name="notes" placeholder="Write progress notes here..." required></textarea>
                                        <button type="submit" name="update_progress" class="btn">
                                            <i class="fas fa-save"></i> Update
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
                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>No patients assigned to you yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>