<?php
session_start();
require 'db.php';
require 'helpers.php'; // Include helpers for functions like redirect_with_message

// Security check: only admins and doctors can access
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'doctor', 'nurse'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['user_role'];

$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

$editing_patient = null;

// Handle POST requests for Create, Update, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Create or Update a Patient ---
    if (isset($_POST['save_patient'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $patient_id = $_POST['patient_id'] ?? null;

        if (empty($name) || empty($email)) {
            redirect_with_message('manage_patients.php', 'Name and Email are required.', 'error');
        }

        if ($patient_id) { // Update existing patient
            $stmt = $conn->prepare("UPDATE patients SET name=?, email=?, phone=?, date_of_birth=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $dob, $patient_id);
            $success_msg = "Patient updated successfully!";
        } else { // Insert new patient
            $stmt = $conn->prepare("INSERT INTO patients (name, email, phone, date_of_birth) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $dob);
            $success_msg = "Patient created successfully!";
        }

        if ($stmt->execute()) {
            redirect_with_message('manage_patients.php', $success_msg);
        } else {
            redirect_with_message('manage_patients.php', 'Error: ' . $stmt->error, 'error');
        }
        $stmt->close();
    }

    // --- Delete a Patient ---
    if (isset($_POST['delete_patient'])) {
        $patient_id = $_POST['patient_id'];
        // Note: You might want to add a check here to prevent deleting patients with existing medical records.
        $stmt = $conn->prepare("DELETE FROM patients WHERE id=?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            redirect_with_message('manage_patients.php', 'Patient deleted successfully!');
        } else {
            redirect_with_message('manage_patients.php', 'Error deleting patient: ' . $stmt->error, 'error');
        }
        $stmt->close();
    }
}

// Handle GET request for editing a specific patient
if (isset($_GET['edit'])) {
    $patient_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $editing_patient = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch all patients for display
if ($user_role === 'admin') {
    // Admins see all patients
    $patients = $conn->query("SELECT p.id, p.name, p.email, p.phone, p.date_of_birth, (SELECT u.name FROM appointments a JOIN users u ON a.doctor_id=u.id WHERE a.patient_id=p.id ORDER BY a.id DESC LIMIT 1) AS doctor_name FROM patients p ORDER BY p.name ASC");
} else {
    // Doctors only see patients they have records for
    $doctor_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT DISTINCT p.id, p.name, p.email, p.phone, p.date_of_birth FROM patients p JOIN medical_records mr ON p.id = mr.patient_id WHERE mr.doctor_id = ? ORDER BY p.name ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $patients = $stmt->get_result();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Patients</title> 
<style>
    body { 
        font-family: 'Segoe UI', sans-serif; 
        color: #333; 
        margin: 0;
        background-image: url('images/admin_background.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        padding: 20px 0; /* Add some padding to the top and bottom */
    }
    .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 1100px; margin: auto; }
    h2, h3 { color: darkblue; }
    .message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    form { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #fafafa; }
    form label { display: block; margin-bottom: 8px; font-weight: bold; }
    form input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid lightgray; border-radius: 5px; box-sizing: border-box; }
    form button { background-color: blue; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    form button:hover { background-color: darkblue; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
    th { background-color: blue; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .actions { display: flex; align-items: center; gap: 8px; }
    .actions a, .actions button { padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 0.9em; cursor: pointer; text-align: center; }
    .actions .edit-btn { color: white; background-color: blue; border: 1px solid blue; }
    .actions .delete-btn { color: white; background-color: red; border: 1px solid red; font-family: inherit; }
    .actions form { margin: 0; }
    .dashboard-link { display: inline-block; margin-bottom: 20px; color: blue; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <a href="javascript:history.back()" class="dashboard-link">&larr; Back to Dashboard</a>
    <h2>Manage Patients</h2>

    <?php if ($message) echo '<p class="message">' . htmlspecialchars($message) . '</p>'; ?>
    <?php if ($error) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>

    <form action="manage_patients.php" method="POST">
        <h3><?php echo $editing_patient ? 'Edit Patient' : 'Add New Patient'; ?></h3>
        <input type="hidden" name="patient_id" value="<?php echo $editing_patient['id'] ?? ''; ?>">
        
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($editing_patient['name'] ?? ''); ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($editing_patient['email'] ?? ''); ?>" required>

        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($editing_patient['phone'] ?? ''); ?>">

        <label for="date_of_birth">Date of Birth:</label>
        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($editing_patient['date_of_birth'] ?? ''); ?>">

        <button type="submit" name="save_patient"><?php echo $editing_patient ? 'Update Patient' : 'Save Patient'; ?></button>
        <?php if ($editing_patient): ?>
            <a href="manage_patients.php" style="margin-left: 10px;">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <h3>Existing Patients</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Date of Birth</th>
            <th>Allocated Doctor</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $patients->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['date_of_birth'] ? date('M d, Y', strtotime($row['date_of_birth'])) : 'N/A') ?></td>
            <td><?= htmlspecialchars($row['doctor_name'] ?? 'None') ?></td>
            <td class="actions">
                <a href="manage_patients.php?edit=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                <form action="manage_patients.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this patient? This might affect existing medical records.');">
                    <input type="hidden" name="patient_id" value="<?= $row['id'] ?>">
                    <button type="submit" name="delete_patient" class="delete-btn">Delete</button>
                </form>
                <a href="manage_records.php?edit=0&patient_id=<?= $row['id'] ?>">Add Record</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
