<?php
session_start();
require 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if patient is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient'){
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['user_name'];

// Fetch upcoming appointments for this patient
$stmt = $conn->prepare("
    SELECT a.*, u.name AS doctor_name 
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id=?
    ORDER BY a.requested_datetime DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Fetch list of active doctors for booking
$doctors_result = $conn->query("SELECT id, name FROM users WHERE role='doctor' AND status='active'");
$doctors = [];
if ($doctors_result) {
    while($row = $doctors_result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Check for expired pending appointments and notify patients
$now = date('Y-m-d H:i:s');
$expired = $conn->prepare("
    SELECT a.id, a.patient_id, a.requested_datetime, a.status, a.issue
    FROM appointments a
    WHERE a.status = 'pending' AND a.requested_datetime < ?
");
$expired->bind_param("s", $now);
$expired->execute();
$expired_result = $expired->get_result();

while ($expired_apt = $expired_result->fetch_assoc()) {
    // Check if already notified about this expiry
    $already = $conn->prepare("SELECT id FROM messages WHERE recipient_id = ? AND subject LIKE ? AND is_read = 0 LIMIT 1");
    $subj = "%Appointment Expired#" . $expired_apt['id'] . "%";
    $already->bind_param("is", $expired_apt['patient_id'], $subj);
    $already->execute();
    if ($already->get_result()->num_rows === 0) {
        $stmt_msg = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, sender_role, recipient_role, subject, body) VALUES (0, ?, 'system', 'patient', ?, ?)");
        $subj = "Appointment Expired#" . $expired_apt['id'];
        $body = "Your appointment #" . $expired_apt['id'] . " scheduled for " . date('M d, Y \a\t h:i A', strtotime($expired_apt['requested_datetime'])) . " has passed without approval. Please reschedule your appointment.";
        $stmt_msg->bind_param("iss", $expired_apt['patient_id'], $subj, $body);
        $stmt_msg->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* ... existing CSS ... */
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 0;
    background-image: url('images/patient.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}
header { background-color: darkblue; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
header h1 { margin: 0; font-size: 24px; }
header a { color: white; text-decoration: none; font-weight: bold; }
.container { max-width: 1200px; margin: 20px auto; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; padding: 20px; }
.card { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
h2 { color: darkblue; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
input, select, textarea { width: calc(100% - 24px); padding: 12px; margin-bottom: 15px; border: 1px solid lightgray; border-radius: 5px; }
button { width: 100%; padding: 12px; background-color: blue; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
button:hover { background-color: darkblue; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
th { background-color: blue; color: white; }
tr:nth-child(even) { background-color: #f9f9f9; }
.status-pending { color:gold; font-weight:bold; }
.status-approved { color:green; font-weight:bold; }
.status-declined { color:red; font-weight:bold; }
.status-rescheduled { color:#17a2b8; font-weight:bold; }
.status-cancelled { color:gray; font-weight:bold; }
.status-expired { color:#c53030; font-weight:bold; }
a.action-btn { padding:5px 10px; border-radius:4px; color:white; text-decoration:none; margin-right:5px; }
        .success-msg { color: green; background-color: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 15px; grid-column: 1 / -1; }
        .error-msg { color: red; background-color: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; grid-column: 1 / -1; }
        @media print {
            .no-print { display: none !important; }
            header { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { background: white !important; box-shadow: none !important; border: 1px solid gray; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 8px; }
        }
</style>
<link rel="stylesheet" href="hospital.css">
<body>

<header>
    <h1>Welcome, <?php echo htmlspecialchars($patient_name); ?>!</h1>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">

    <?php
    if(isset($_GET['success'])){
        echo '<p class="success-msg">'.htmlspecialchars($_GET['success']).'</p>';
    }
    if(isset($_GET['error'])){
        echo '<p class="error-msg">'.htmlspecialchars($_GET['error']).'</p>';
    }
    ?>

    <div class="card">
        <h2>Book Appointment</h2>
        <form action="booking_process.php" method="POST">
            <label>Issue / Complaint:</label>
            <textarea name="note" rows="3" required></textarea>

            <label>Appointment Date & Time:</label>
            <input type="datetime-local" name="appointment_time" required>

            <button type="submit">Book Appointment</button>
        </form>
    </div>

    <div class="card">
        <h2>My Appointments</h2>
        <button class="no-print" style="float:right; margin-bottom:12px; padding:6px 12px; background:#2563eb; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.875rem;" onclick="window.print()">
            <i class="fas fa-print"></i> Print Appointments
        </button>
        <?php if($appointments->num_rows>0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Doctor</th>
                <th>Requested</th>
                <th>Confirmed</th>
                <th>Status / Actions</th>
            </tr>
            <?php while($row = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                <td><?php echo $row['requested_datetime']; ?></td>
                <td><?php echo $row['confirmed_datetime']; ?></td>
                <td class="status-<?php echo $row['status']; ?>">
                    <?php echo ucfirst($row['status']); ?>
                    <?php
                    $is_expired = $row['status'] === 'pending' && strtotime($row['requested_datetime']) < strtotime(date('Y-m-d H:i:s'));
                    if ($is_expired): ?>
                        <br><br>
                        <span style="color:#c53030;font-weight:bold;">⏰ Expired</span><br>
                        <form action="patient_reschedule.php?id=<?php echo $row['id']; ?>" method="GET" style="display:inline;">
                            <button type="submit" class="action-btn" style="background:#17a2b8; border:none; cursor:pointer;">Reschedule</button>
                        </form>
                    <?php elseif($row['status'] == 'approved' || $row['status'] == 'pending'): ?>
                        <br><br>
                        <form action="reschedule_appointment.php" method="POST" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                            <input type="datetime-local" name="new_time" required>
                            <button type="submit" class="action-btn" style="background:#17a2b8; border:none; cursor:pointer;">Reschedule</button>
                        </form>
                        <form action="cancel_appointment.php" method="POST" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="action-btn" style="background:red; border:none; cursor:pointer;">Cancel</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>No appointments yet.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
