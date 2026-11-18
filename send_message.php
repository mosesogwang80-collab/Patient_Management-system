<?php
session_start();
require 'db.php';

// All authenticated roles can send messages
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sender_id   = $_SESSION['user_id'];
$sender_role = $_SESSION['user_role'];
$sender_name = $_SESSION['user_name'];

$success = '';
$error   = '';

if (isset($_POST['send_message'])) {
    $recipient_role = $_POST['recipient_role'] ?? '';
    $recipient_id   = intval($_POST['recipient_id'] ?? 0);
    $subject        = trim($_POST['subject'] ?? '');
    $body           = trim($_POST['body'] ?? '');

    if ($recipient_id > 0 && $recipient_role !== '' && $subject !== '' && $body !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, sender_role, recipient_id, recipient_role, subject, body) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisss", $sender_id, $sender_role, $recipient_id, $recipient_role, $subject, $body);
        
        if ($stmt->execute()) {
            $success = "Message sent successfully!";
        } else {
            $error = "Failed to send message. Please try again.";
        }
    } else {
        $error = "Please fill in all fields and select a recipient.";
    }
}

// Fetch all users for dropdown, grouped by role
$recipients = [
    'admin'            => $conn->query("SELECT id, name FROM users WHERE role='admin' AND id != $sender_id"),
    'doctor'           => $conn->query("SELECT id, name FROM users WHERE role='doctor' AND id != $sender_id"),
    'nurse'            => $conn->query("SELECT id, name FROM users WHERE role='nurse' AND id != $sender_id"),
    'receptionist'     => $conn->query("SELECT id, name FROM users WHERE role='receptionist' AND id != $sender_id"),
    'cashier'          => $conn->query("SELECT id, name FROM users WHERE role='cashier' AND id != $sender_id"),
    'pharmacy'         => $conn->query("SELECT id, name FROM users WHERE role='pharmacy' AND id != $sender_id"),
    'medical_director' => $conn->query("SELECT id, name FROM users WHERE role='medical_director' AND id != $sender_id"),
    'ceo'              => $conn->query("SELECT id, name FROM users WHERE role='ceo' AND id != $sender_id"),
    'ministry'         => $conn->query("SELECT id, name FROM users WHERE role='ministry' AND id != $sender_id"),
    'patient'          => $conn->query("SELECT id, name FROM patients"),
];

$roleLabels = [
    'admin'            => 'Admin',
    'doctor'           => 'Doctor',
    'nurse'            => 'Nurse',
    'receptionist'     => 'Receptionist',
    'cashier'          => 'Cashier',
    'pharmacy'         => 'Pharmacist',
    'medical_director' => 'Medical Director',
    'ceo'              => 'CEO',
    'ministry'         => 'Ministry Rep',
    'patient'          => 'Patient',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
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
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .header-right a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 9px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            transition: background 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .header-right a:hover {
            background: rgba(255,255,255,0.22);
        }

        .container {
            max-width: 720px;
            margin: 40px auto 50px;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.1);
            padding: 36px 40px;
            border: 1px solid lightgray;
        }

        .card h2 {
            color: midnightblue;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subtitle {
            color: slategray;
            margin-bottom: 28px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: darkslategray;
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid lightgray;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            color: darkslategray;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: steelblue;
            box-shadow: 0 0 0 3px rgba(70, 130, 180, 0.15);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 24px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: midnightblue;
        }
        .btn-primary:hover {
            background: darkblue;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: slategray;
        }
        .btn-secondary:hover {
            background: dimgray;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
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

        .sender-info {
            background: aliceblue;
            border-left: 4px solid steelblue;
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
            font-size: 0.9rem;
            color: darkslategray;
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
                gap: 14px;
                text-align: center;
                padding: 16px 20px;
            }
            .card {
                padding: 28px 22px;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <i class="fas fa-paper-plane"></i>
        <div>
            <h1>Send Message</h1>
            <span>Iran-Uganda Hospital</span>
        </div>
    </div>
    <div class="header-right">
        <a href="javascript:history.back()">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</header>

<div class="container">
    <div class="card">
        <h2><i class="fas fa-envelope"></i> Compose Message</h2>
        <p class="subtitle">Send official communication to any staff member or patient.</p>

        <div class="sender-info">
            <strong>From:</strong> <?= htmlspecialchars($sender_name) ?> 
            <span style="color:slategray;">(<?= ucfirst(str_replace('_', ' ', $sender_role)) ?>)</span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <div class="actions">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="send_message.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Send Another Message
                </a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Recipient</label>
                    <select name="recipient_combo" id="recipientSelect" required onchange="updateRecipientFields()">
                        <option value="">— Select recipient —</option>
                        <?php foreach ($recipients as $role => $result): ?>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <optgroup label="<?= $roleLabels[$role] ?>">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <option value="<?= $role ?>|<?= (int)$row['id'] ?>">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </optgroup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <!-- Hidden fields that will be populated by JavaScript -->
                    <input type="hidden" name="recipient_role" id="recipient_role">
                    <input type="hidden" name="recipient_id" id="recipient_id">
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" required placeholder="Enter message subject">
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="body" required placeholder="Type your message here..."></textarea>
                </div>

                <div class="actions">
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="send_message" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<script>
function updateRecipientFields() {
    const select = document.getElementById('recipientSelect');
    const value = select.value;

    if (value) {
        const parts = value.split('|');
        document.getElementById('recipient_role').value = parts[0];
        document.getElementById('recipient_id').value = parts[1];
    } else {
        document.getElementById('recipient_role').value = '';
        document.getElementById('recipient_id').value = '';
    }
}
</script>

</body>
</html>