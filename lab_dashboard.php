<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'lab_technician') {
    header("Location: login.php"); exit;
}
require 'db.php';
require_once 'audit.php';
$lab_id = $_SESSION['user_id'];
$lab_name = htmlspecialchars($_SESSION['user_name'] ?? 'Lab Technician');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_result'])) {
    $req_id = intval($_POST['request_id']);
    $result_text = trim($_POST['result_text'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    if ($req_id && $result_text !== '') {
        $stmt = $conn->prepare("INSERT INTO lab_results (request_id, result_text, remarks, completed_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE result_text=VALUES(result_text), remarks=VALUES(remarks), completed_by=VALUES(completed_by), completed_at=CURRENT_TIMESTAMP");
        $stmt->bind_param("issi", $req_id, $result_text, $remarks, $lab_id);
        $stmt->execute();
        $stmt->close();
        $conn->query("UPDATE lab_requests SET status='completed', updated_at=NOW() WHERE id=$req_id");
        $info = $conn->query("SELECT lr.patient_id, lr.doctor_id, lr.test_name, p.name AS patient_name FROM lab_requests lr JOIN patients p ON lr.patient_id=p.id WHERE lr.id=$req_id")->fetch_assoc();
        if ($info) {
            $subj = "Lab Result Ready - " . $info['patient_name'];
            $body = "Lab test results are ready.\nPatient: " . $info['patient_name'] . "\nTest: " . $info['test_name'] . "\n\nResult:\n" . $result_text . ($remarks ? "\n\nRemarks: " . $remarks : "");
            $msg = $conn->prepare("INSERT INTO messages (sender_id, sender_role, recipient_id, recipient_role, subject, body) VALUES (?, 'lab_technician', ?, 'doctor', ?, ?)");
            $msg->bind_param("iiss", $lab_id, $info['doctor_id'], $subj, $body);
            $msg->execute();
            $msg->close();
            audit_log($conn, $lab_id, 'lab_result_submitted', 'request_id=' . $req_id . ', patient_id=' . $info['patient_id']);
        }
        $success = "Result submitted and sent to doctor.";
    } else { $error = "Result text is required."; }
}

$requests = $conn->query("SELECT lr.*, p.name AS patient_name, u.name AS doctor_name, r.result_text, r.remarks AS result_remarks, r.completed_at FROM lab_requests lr JOIN patients p ON lr.patient_id=p.id JOIN users u ON lr.doctor_id=u.id LEFT JOIN lab_results r ON r.request_id=lr.id ORDER BY FIELD(lr.status,'requested','sample_collected','in_progress','completed','cancelled'), lr.requested_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lab Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,lightcyan,aliceblue);margin:0;color:darkslategray;}
header{background:teal;color:white;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;}
header a{color:white;text-decoration:none;margin-left:12px;}
.container{max-width:1100px;margin:20px auto;padding:0 20px;}
.card{background:white;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
table{width:100%;border-collapse:collapse;} th,td{padding:10px;border-bottom:1px solid lightgray;text-align:left;vertical-align:top;} th{background:teal;color:white;}
.status{padding:3px 8px;border-radius:12px;font-size:0.85em;font-weight:600;}
.status-requested{background:lightyellow;} .status-completed{background:lightgreen;} .status-in_progress{background:lightblue;}
textarea{width:100%;padding:8px;border:1px solid lightgray;border-radius:6px;}
.btn{background:teal;color:white;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;}
.alert{padding:10px;border-radius:6px;margin-bottom:12px;} .alert-success{background:honeydew;color:darkgreen;} .alert-error{background:mistyrose;color:darkred;}
@media print {
    .no-print { display: none !important; }
    header { display: none !important; }
    body { background: white !important; color: black !important; }
    .card { background: white !important; box-shadow: none !important; border: 1px solid gray; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid black; padding: 8px; }
}
</style>
</head>
<body>
<header>
  <h2><i class="fas fa-flask"></i> Lab Technician - <?= $lab_name ?></h2>
  <div><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</header>
<div class="container">
  <div class="card">
    <h3>Lab Requests</h3>
    <button class="no-print" style="float:right; margin-bottom:12px; padding:6px 12px; background:#2563eb; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.875rem;" onclick="window.print()">
        <i class="fas fa-print"></i> Print Requests
    </button>
    <?php if(isset($success)) echo '<div class="alert alert-success">'.htmlspecialchars($success).'</div>'; ?>
    <?php if(isset($error)) echo '<div class="alert alert-error">'.htmlspecialchars($error).'</div>'; ?>
    <table>
      <tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Test</th><th>Notes</th><th>Status</th><th>Result / Action</th></tr>
      <?php if($requests && $requests->num_rows): while($r=$requests->fetch_assoc()): ?>
      <tr>
        <td>#<?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['patient_name']) ?></td>
        <td><?= htmlspecialchars($r['doctor_name']) ?></td>
        <td><?= htmlspecialchars($r['test_name']) ?></td>
        <td><?= nl2br(htmlspecialchars($r['clinical_notes'] ?? '')) ?></td>
        <td><span class="status status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
        <td>
          <?php if($r['status']==='completed' && $r['result_text']): ?>
            <strong>Result:</strong><br><?= nl2br(htmlspecialchars($r['result_text'])) ?>
            <?php if($r['result_remarks']): ?><br><em>Remarks: <?= htmlspecialchars($r['result_remarks']) ?></em><?php endif; ?>
            <br><small><?= htmlspecialchars($r['completed_at']) ?></small>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
              <textarea name="result_text" rows="3" placeholder="Enter lab result..." required></textarea>
              <input type="text" name="remarks" placeholder="Remarks (optional)" style="width:100%;margin:6px 0;padding:6px;border:1px solid lightgray;border-radius:6px;">
              <button class="btn" type="submit" name="submit_result"><i class="fas fa-paper-plane"></i> Submit & Send to Doctor</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="7">No lab requests.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
</body></html>