<?php
session_start();
require 'db.php';
require_once 'audit.php';

// Security: Only doctors, admin, and medical directors
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin', 'medical_director'])) {
    header("Location: login.php");
    exit();
}

$doctor_id   = $_SESSION['user_id'];
$user_name   = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_role   = $_SESSION['user_role'];
$success_msg = null;
$error_msg   = null;
$selected_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;

// ========== SAVE MEDICAL RECORD ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_record'])) {
    $patient_id_to_save = (int)$_POST['patient_id'];
    $record_date = trim($_POST['record_date']);
    $diagnosis   = trim($_POST['diagnosis']);
    $treatment   = trim($_POST['treatment']);
    $notes       = trim($_POST['notes']);

    if (empty($patient_id_to_save) || empty($record_date) || empty($diagnosis) || empty($treatment)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, record_date, diagnosis, treatment, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $patient_id_to_save, $doctor_id, $record_date, $diagnosis, $treatment, $notes);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            audit_log($conn, $doctor_id, 'create_medical_record', 'record_id=' . $new_id . ', patient_id=' . $patient_id_to_save);
            header("Location: manage_records.php?patient_id=" . $patient_id_to_save . "&success=1");
            exit();
        } else {
            $error_msg = "Error saving record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ========== SEND TO CASHIER OR PHARMACY ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_role'])) {
    $patient_id   = (int)$_POST['patient_id'];
    $recipient_id = (int)$_POST['recipient_id'];
    $target_role  = $_POST['target_role']; // 'cashier' or 'pharmacy'
    $extra_note   = trim($_POST['extra_note'] ?? '');

    // Get patient name
    $pstmt = $conn->prepare("SELECT name FROM patients WHERE id = ?");
    $pstmt->bind_param("i", $patient_id);
    $pstmt->execute();
    $patient_name = $pstmt->get_result()->fetch_assoc()['name'] ?? 'Unknown Patient';
    $pstmt->close();

    // Get latest record for this patient
    $rstmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_date DESC LIMIT 1");
    $rstmt->bind_param("i", $patient_id);
    $rstmt->execute();
    $latest = $rstmt->get_result()->fetch_assoc();
    $rstmt->close();

    if ($latest) {
        $subject = ($target_role === 'cashier') 
            ? "Billing Request – $patient_name" 
            : "Prescription Request – $patient_name";

        $body  = "Patient: $patient_name\n";
        $body .= "Visit Date: " . date('d M Y', strtotime($latest['record_date'])) . "\n\n";
        $body .= "Diagnosis:\n" . $latest['diagnosis'] . "\n\n";
        $body .= "Treatment Plan:\n" . $latest['treatment'] . "\n\n";
        if (!empty($latest['notes'])) {
            $body .= "Notes:\n" . $latest['notes'] . "\n\n";
        }
        if ($extra_note !== '') {
            $body .= "Additional Message from Doctor:\n" . $extra_note;
        }

        $stmt = $conn->prepare("INSERT INTO messages (sender_id, sender_role, recipient_id, recipient_role, subject, body) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisss", $doctor_id, $user_role, $recipient_id, $target_role, $subject, $body);

        if ($stmt->execute()) {
            $msg_id = $stmt->insert_id;
            audit_log($conn, $doctor_id, 'send_record', 'to_role=' . $target_role . ', recipient_id=' . $recipient_id . ', patient_id=' . $patient_id . ', message_id=' . $msg_id);
            $success_msg = ($target_role === 'cashier') 
                ? "Record successfully sent to Cashier for billing." 
                : "Record successfully sent to Pharmacy for prescription.";
        } else {
            $error_msg = "Failed to send record. Please try again.";
        }
        $stmt->close();
    } else {
        $error_msg = "No medical record found to send.";
    }
}

// ========== SEND TO LAB ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_lab'])) {
    $patient_id    = (int)$_POST['patient_id'];
    $lab_user_id   = (int)($_POST['lab_user_id'] ?? 0);
    $test_id       = (int)($_POST['test_id'] ?? 0);
    $test_name_in  = trim($_POST['test_name'] ?? '');
    $clinical_notes = trim($_POST['clinical_notes'] ?? '');

    $test_name = $test_name_in;
    if ($test_id > 0) {
        $tq = $conn->prepare("SELECT name FROM lab_tests WHERE id=?");
        $tq->bind_param("i", $test_id); $tq->execute();
        $tr = $tq->get_result()->fetch_assoc();
        if ($tr) $test_name = $tr['name'];
        $tq->close();
    }

    if ($patient_id && $lab_user_id && $test_name !== '') {
        $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, doctor_id, test_id, test_name, clinical_notes, status) VALUES (?,?,?,?,?, 'requested')");
        $tid = $test_id > 0 ? $test_id : null;
        $stmt->bind_param("iiiss", $patient_id, $doctor_id, $tid, $test_name, $clinical_notes);
        if ($stmt->execute()) {
            $req_id = $stmt->insert_id;
            $pstmt = $conn->prepare("SELECT name FROM patients WHERE id=?");
            $pstmt->bind_param("i", $patient_id); $pstmt->execute();
            $pname = $pstmt->get_result()->fetch_assoc()['name'] ?? 'Patient'; $pstmt->close();
            $subj = "Lab Test Request - " . $pname;
            $body = "Patient: $pname\nTest: $test_name\nClinical Notes: " . ($clinical_notes ?: 'N/A') . "\n\nPlease process this lab request (Request #$req_id).";
            $msg = $conn->prepare("INSERT INTO messages (sender_id, sender_role, recipient_id, recipient_role, subject, body) VALUES (?, ?, ?, 'lab_technician', ?, ?)");
            $msg->bind_param("isiss", $doctor_id, $user_role, $lab_user_id, $subj, $body);
            $msg->execute(); $msg->close();
            audit_log($conn, $doctor_id, 'lab_request', 'request_id=' . $req_id . ', patient_id=' . $patient_id . ', test=' . $test_name);
            $success_msg = "Lab test request sent to laboratory.";
        } else {
            $error_msg = "Failed to create lab request.";
        }
        $stmt->close();
    } else {
        $error_msg = "Please select lab technician and test.";
    }
}

// ========== DATA FETCHING ==========
$patients_result = $conn->query("SELECT id, name FROM patients ORDER BY name ASC");

$records = [];
$lab_requests = [];
$patient_name = '';
if ($selected_patient_id) {
    $stmt = $conn->prepare("SELECT name FROM patients WHERE id = ?");
    $stmt->bind_param("i", $selected_patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $patient_name = $result->fetch_assoc()['name'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_date DESC");
    $stmt->bind_param("i", $selected_patient_id);
    $stmt->execute();
    $records_result = $stmt->get_result();
    while ($row = $records_result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();

    $lr = $conn->prepare("SELECT lr.*, r.result_text, r.remarks AS result_remarks, r.completed_at FROM lab_requests lr LEFT JOIN lab_results r ON r.request_id=lr.id WHERE lr.patient_id=? ORDER BY lr.requested_at DESC");
    $lr->bind_param("i", $selected_patient_id);
    $lr->execute();
    $lr_res = $lr->get_result();
    while ($row = $lr_res->fetch_assoc()) { $lab_requests[] = $row; }
    $lr->close();
}

// Fetch cashiers, pharmacists, lab techs and tests
$cashiers   = $conn->query("SELECT id, name FROM users WHERE role = 'cashier' AND status = 'active' ORDER BY name");
$pharmacists = $conn->query("SELECT id, name FROM users WHERE role = 'pharmacy' AND status = 'active' ORDER BY name");
$lab_techs = $conn->query("SELECT id, name FROM users WHERE role = 'lab_technician' AND status = 'active' ORDER BY name");
$lab_tests = $conn->query("SELECT id, name, price FROM lab_tests WHERE status='active' ORDER BY name");

if (isset($_GET['success'])) {
    $success_msg = "Medical record saved successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medical Records | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, lightsteelblue, lavender);
            min-height: 100vh;
            color: darkslategray;
            line-height: 1.5;
        }

        header {
            background: linear-gradient(90deg, midnightblue, darkblue);
            color: white;
            padding: 16px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 18px rgba(0,0,0,0.18);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left i {
            font-size: 1.6rem;
            color: gold;
        }

        .header-left h1 {
            font-size: 1.35rem;
            font-weight: 600;
        }

        .header-left span {
            display: block;
            font-size: 0.82rem;
            opacity: 0.85;
        }

        .header-right {
            display: flex;
            gap: 10px;
        }

        .header-right a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.9rem;
        }

        .header-right a:hover {
            background: rgba(255,255,255,0.22);
        }

        .container {
            max-width: 1100px;
            margin: 30px auto 50px;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
            padding: 28px 30px;
            margin-bottom: 24px;
            border: 1px solid lightgray;
        }

        .card h2, .card h3 {
            color: midnightblue;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid lavender;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        label {
            font-weight: 600;
            font-size: 0.9rem;
            color: darkslategray;
            margin-bottom: 7px;
            display: block;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 16px;
            border: 1px solid lightgray;
            border-radius: 9px;
            font-size: 0.95rem;
            font-family: inherit;
            color: darkslategray;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: steelblue;
            box-shadow: 0 0 0 3px rgba(70, 130, 180, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 9px;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary { background: midnightblue; }
        .btn-primary:hover { background: darkblue; }

        .btn-success { background: seagreen; }
        .btn-success:hover { background: darkgreen; }

        .btn-warning { background: darkorange; }
        .btn-warning:hover { background: chocolate; }

        .btn-info { background: steelblue; }
        .btn-info:hover { background: royalblue; }

        .btn-secondary { background: slategray; }
        .btn-secondary:hover { background: dimgray; }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: honeydew;
            color: darkgreen;
            border: 1px solid lightgreen;
        }

        .alert-error {
            background: mistyrose;
            color: darkred;
            border: 1px solid lightcoral;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid lightgray;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th {
            background: midnightblue;
            color: white;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid lavender;
            vertical-align: top;
        }

        tr:nth-child(even) { background: aliceblue; }
        tr:hover { background: lavender; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: slategray;
        }

        .empty-state i {
            font-size: 2.8rem;
            opacity: 0.35;
            margin-bottom: 12px;
            display: block;
        }

        .patient-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: aliceblue;
            color: midnightblue;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 14px;
            padding: 30px;
            width: 90%;
            max-width: 520px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h3 {
            color: midnightblue;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 14px;
                text-align: center;
                padding: 16px 20px;
            }
            .header-right {
                flex-wrap: wrap;
                justify-content: center;
            }
            .card { padding: 22px 18px; }
            .action-buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <i class="fas fa-file-medical"></i>
        <div>
            <h1>Manage Medical Records</h1>
            <span>Iran-Uganda Hospital</span>
        </div>
    </div>
    <div class="header-right">
        <a href="javascript:history.back()"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<div class="container">

    <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Select Patient -->
    <div class="card">
        <h2><i class="fas fa-user-injured"></i> Select Patient</h2>
        <form action="manage_records.php" method="GET">
            <label for="patient_id">Choose a patient to view or add medical records</label>
            <select name="patient_id" id="patient_id" onchange="this.form.submit()">
                <option value="">— Select a Patient —</option>
                <?php 
                if ($patients_result) {
                    $patients_result->data_seek(0);
                    while ($p = $patients_result->fetch_assoc()): 
                ?>
                    <option value="<?= (int)$p['id'] ?>" <?= ($selected_patient_id == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endwhile; } ?>
            </select>
        </form>
    </div>

    <?php if ($selected_patient_id): ?>

        <!-- Add New Record -->
        <div class="card">
            <h3>
                <i class="fas fa-plus-circle"></i> 
                Add New Record for 
                <span class="patient-badge"><i class="fas fa-user"></i> <?= htmlspecialchars($patient_name) ?></span>
            </h3>

            <form action="manage_records.php" method="POST">
                <input type="hidden" name="patient_id" value="<?= $selected_patient_id ?>">

                <label for="record_date">Visit Date <span style="color:crimson;">*</span></label>
                <input type="date" id="record_date" name="record_date" required value="<?= date('Y-m-d') ?>">

                <label for="diagnosis">Diagnosis <span style="color:crimson;">*</span></label>
                <textarea id="diagnosis" name="diagnosis" rows="3" required placeholder="Enter diagnosis..."></textarea>

                <label for="treatment">Treatment Plan <span style="color:crimson;">*</span></label>
                <textarea id="treatment" name="treatment" rows="4" required placeholder="Enter treatment plan..."></textarea>

                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" rows="2" placeholder="Any extra observations..."></textarea>

                <button type="submit" name="save_record" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Medical Record
                </button>
            </form>
        </div>

        <!-- Existing Records + Action Buttons -->
        <div class="card">
            <h3>
                <i class="fas fa-folder-open"></i> 
                Existing Records for <?= htmlspecialchars($patient_name) ?>
            </h3>

            <?php if (!empty($records)): ?>

                <div class="action-buttons" style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-warning" onclick="openSendModal('cashier')">
                        <i class="fas fa-cash-register"></i> Send Records to Cashier
                    </button>
                    <button type="button" class="btn btn-info" onclick="openSendModal('pharmacy')">
                        <i class="fas fa-pills"></i> Send Records to Pharmacy
                    </button>
                    <button type="button" class="btn btn-primary" onclick="openLabModal()">
                        <i class="fas fa-flask"></i> Send to Laboratory
                    </button>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Record Date</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td style="white-space:nowrap; font-weight:600; color:midnightblue;">
                                        <?= htmlspecialchars(date('M d, Y', strtotime($record['record_date']))) ?>
                                    </td>
                                    <td><?= nl2br(htmlspecialchars($record['diagnosis'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($record['treatment'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($record['notes'] ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    No medical records found for this patient yet.
                </div>
            <?php endif; ?>
        </div>

        <!-- Lab Results -->
        <div class="card">
            <h3><i class="fas fa-flask"></i> Laboratory Requests & Results</h3>
            <?php if (!empty($lab_requests)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Request Date</th>
                                <th>Test</th>
                                <th>Status</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lab_requests as $lr): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($lr['requested_at']))) ?></td>
                                    <td><?= htmlspecialchars($lr['test_name']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ', $lr['status']))) ?></td>
                                    <td>
                                        <?php if ($lr['status'] === 'completed' && !empty($lr['result_text'])): ?>
                                            <strong>Result:</strong> <?= nl2br(htmlspecialchars($lr['result_text'])) ?>
                                            <?php if (!empty($lr['result_remarks'])): ?><br><em>Remarks: <?= htmlspecialchars($lr['result_remarks']) ?></em><?php endif; ?>
                                            <?php if (!empty($lr['completed_at'])): ?><br><small>Completed: <?= htmlspecialchars($lr['completed_at']) ?></small><?php endif; ?>
                                        <?php else: ?>
                                            <em>Awaiting lab result</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-flask"></i>No lab requests for this patient yet.</div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- ==================== SEND TO CASHIER / PHARMACY MODAL ==================== -->
<div id="sendModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle"><i class="fas fa-paper-plane"></i> Send Record</h3>

        <form method="POST">
            <input type="hidden" name="patient_id" value="<?= $selected_patient_id ?>">
            <input type="hidden" name="target_role" id="target_role">
            <input type="hidden" name="send_to_role" value="1">

            <label id="recipientLabel">Select Recipient</label>
            <select name="recipient_id" id="recipientSelect" required>
                <option value="">— Select —</option>
            </select>

            <label>Additional Note (optional)</label>
            <textarea name="extra_note" rows="3" placeholder="Any extra instruction for the recipient..."></textarea>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeSendModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== SEND TO LAB MODAL ==================== -->
<div id="labModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-flask"></i> Send to Laboratory</h3>
        <form method="POST">
            <input type="hidden" name="patient_id" value="<?= $selected_patient_id ?>">
            <input type="hidden" name="send_to_lab" value="1">
            <label>Select Lab Technician</label>
            <select name="lab_user_id" required>
                <option value="">— Select —</option>
                <?php if ($lab_techs) { $lab_techs->data_seek(0); while($lt=$lab_techs->fetch_assoc()): ?>
                    <option value="<?= (int)$lt['id'] ?>"><?= htmlspecialchars($lt['name']) ?></option>
                <?php endwhile; } ?>
            </select>
            <label>Select Test</label>
            <select name="test_id" id="lab_test_id" onchange="onLabTestChange(this)">
                <option value="">— Select Test —</option>
                <?php if ($lab_tests) { $lab_tests->data_seek(0); while($t=$lab_tests->fetch_assoc()): ?>
                    <option value="<?= (int)$t['id'] ?>" data-name="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endwhile; } ?>
            </select>
            <label>Or enter test name</label>
            <input type="text" name="test_name" id="lab_test_name" placeholder="Test name">
            <label>Clinical Notes</label>
            <textarea name="clinical_notes" rows="3" placeholder="Clinical notes for lab..."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeLabModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Request</button>
            </div>
        </form>
    </div>
</div>

<script>
// Cashier & Pharmacist data from PHP
const cashiers = [
    <?php 
    if ($cashiers) {
        $cashiers->data_seek(0);
        $list = [];
        while ($c = $cashiers->fetch_assoc()) {
            $list[] = '{id:' . (int)$c['id'] . ', name:"' . addslashes($c['name']) . '"}';
        }
        echo implode(',', $list);
    }
    ?>
];

const pharmacists = [
    <?php 
    if ($pharmacists) {
        $pharmacists->data_seek(0);
        $list = [];
        while ($p = $pharmacists->fetch_assoc()) {
            $list[] = '{id:' . (int)$p['id'] . ', name:"' . addslashes($p['name']) . '"}';
        }
        echo implode(',', $list);
    }
    ?>
];

function openSendModal(role) {
    const modal = document.getElementById('sendModal');
    const title = document.getElementById('modalTitle');
    const label = document.getElementById('recipientLabel');
    const select = document.getElementById('recipientSelect');
    const targetRole = document.getElementById('target_role');

    targetRole.value = role;
    select.innerHTML = '<option value="">— Select —</option>';

    if (role === 'cashier') {
        title.innerHTML = '<i class="fas fa-cash-register"></i> Send to Cashier for Billing';
        label.textContent = 'Select Cashier';
        cashiers.forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
    } else {
        title.innerHTML = '<i class="fas fa-pills"></i> Send to Pharmacy for Prescription';
        label.textContent = 'Select Pharmacist';
        pharmacists.forEach(p => {
            select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
    }

    modal.classList.add('show');
}

function closeSendModal() {
    document.getElementById('sendModal').classList.remove('show');
}

function openLabModal() { document.getElementById('labModal').classList.add('show'); }
function closeLabModal() { document.getElementById('labModal').classList.remove('show'); }
function onLabTestChange(sel){ var opt=sel.options[sel.selectedIndex]; if(opt && opt.dataset.name){ document.getElementById('lab_test_name').value=opt.dataset.name; } }
// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

</body>
</html>