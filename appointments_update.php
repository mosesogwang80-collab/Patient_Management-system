<?php
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$appointment_id = intval($_POST['appointment_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$appointment_id) { exit("Invalid request"); }

// get patient id
$stmt = $conn->prepare("SELECT patient_id FROM appointments WHERE id=?");
$stmt->bind_param("i",$appointment_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$patient_id = $res ? $res['patient_id'] : 0;
$doctor_id = $_SESSION['user_id'];

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE appointments SET status='approved', confirmed_datetime=NOW(), updated_at=NOW() WHERE id=?");
    $stmt->bind_param("i",$appointment_id); $stmt->execute();
    $msg="Your appointment has been approved.";
} elseif ($action === 'decline') {
    $stmt = $conn->prepare("UPDATE appointments SET status='declined', updated_at=NOW() WHERE id=?");
    $stmt->bind_param("i",$appointment_id); $stmt->execute();
    $msg="Your appointment has been declined.";
} elseif ($action === 'reschedule') {
    $new_time = $_POST['new_time'] ?? '';
    if (!$new_time) exit("Missing new schedule time");
    $stmt = $conn->prepare("UPDATE appointments SET status='rescheduled', reschedule_suggestion=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("si",$new_time,$appointment_id); $stmt->execute();
    $msg="Your appointment has been rescheduled to $new_time.";
}

if($patient_id){
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?,?,?,?)");
    $subj = "Appointment Update";
    $stmt->bind_param("iiss",$doctor_id,$patient_id,$subj,$msg);
    $stmt->execute();
}

header("Location: appointments.php");
exit;
