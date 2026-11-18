<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','medical_director'])) {
    header('Location: login.php');
    exit;
}
include 'db.php';

// Handle staff management actions (approve/reject/activate/deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_action'], $_POST['staff_id'])) {
    $staff_id = (int) $_POST['staff_id'];
    $action   = $_POST['staff_action'];
    $new_status = '';
    if ($action === 'approve')       { $new_status = 'active'; }
    elseif ($action === 'reject')    { $new_status = 'inactive'; }
    elseif ($action === 'activate')  { $new_status = 'active'; }
    elseif ($action === 'deactivate'){ $new_status = 'inactive'; }
    if ($new_status !== '' && $staff_id > 0) {
        $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $staff_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: workers.php');
    exit;
}

$doctors       = $conn->query("SELECT * FROM users WHERE role='doctor' ORDER BY name");
$nurses        = $conn->query("SELECT * FROM users WHERE role='nurse' ORDER BY name");
$cashiers      = $conn->query("SELECT * FROM users WHERE role='cashier' ORDER BY name");
$pharmacists   = $conn->query("SELECT * FROM users WHERE role='pharmacy' ORDER BY name");
$receptionists = $conn->query("SELECT * FROM users WHERE role='receptionist' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Hospital Staff - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: slateblue;
            --primary-dark: darkslateblue;
            --success: seagreen;
            --danger: crimson;
            --warning: darkgoldenrod;
            --info: darkcyan;
            --bg: whitesmoke;
            --card: white;
            --border: lightgray;
            --text: darkslategray;
            --muted: slategray;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Header */
        header {
            background: var(--primary);
            color: white;
            padding: 18px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
        }

        header h1 {
            font-size: 1.45rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 9px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        header a:hover {
            background: rgba(255,255,255,0.28);
        }

        /* Container */
        .container {
            max-width: 1180px;
            margin: 30px auto 50px;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h2 {
            font-size: 1.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text);
        }

        .page-header p {
            color: var(--muted);
            margin-top: 6px;
            font-size: 1rem;
        }

        /* Summary Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 18px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--card);
            border-radius: 14px;
            padding: 22px 20px;
            border: 1px solid var(--border);
            box-shadow: 0 3px 10px rgba(0,0,0,0.04);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.09);
        }

        .stat-card .icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            margin-bottom: 14px;
        }

        .stat-card .icon.doctors       { background: crimson; }
        .stat-card .icon.nurses        { background: mediumvioletred; }
        .stat-card .icon.cashiers      { background: slateblue; }
        .stat-card .icon.pharmacists   { background: seagreen; }
        .stat-card .icon.receptionists { background: darkgoldenrod; }

        .stat-card .label {
            font-size: 0.88rem;
            color: var(--muted);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .stat-card .value {
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--text);
        }

        /* Section Cards */
        .section {
            background: var(--card);
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
            padding: 26px 28px;
            margin-bottom: 28px;
            border: 1px solid var(--border);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--border);
        }

        .section-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .section-header.doctors h3       { color: crimson; }
        .section-header.nurses h3        { color: mediumvioletred; }
        .section-header.cashiers h3      { color: slateblue; }
        .section-header.pharmacists h3   { color: seagreen; }
        .section-header.receptionists h3 { color: darkgoldenrod; }

        .section-header .count {
            background: whitesmoke;
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 3px 12px;
            border-radius: 20px;
            margin-left: auto;
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th {
            color: white;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .section.doctors th       { background: crimson; }
        .section.nurses th        { background: mediumvioletred; }
        .section.cashiers th      { background: slateblue; }
        .section.pharmacists th   { background: seagreen; }
        .section.receptionists th { background: darkgoldenrod; }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) {
            background: whitesmoke;
        }

        tr:hover {
            background: gainsboro;
        }

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-active {
            background: palegreen;
            color: darkgreen;
        }

.badge-inactive {
    background: lightpink;
    color: darkred;
}

/* Action column */
.actions-cell {
    white-space: nowrap;
    display: flex;
    gap: 6px;
    justify-content: center;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 7px;
    border: none;
    color: white;
    font-size: 0.95rem;
    cursor: pointer;
    opacity: 0.9;
    transition: opacity 0.15s, transform 0.1s;
}

.btn-action:hover {
    opacity: 1;
    transform: translateY(-1px);
}

.btn-action:active {
    transform: scale(0.95);
}

.btn-approve   { background: seagreen; }
.btn-reject    { background: crimson; }
.btn-activate  { background: darkcyan; }
.btn-deactivate{ background: darkorange; }

        /* Empty State */
        .empty {
            text-align: center;
            padding: 36px 20px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .empty i {
            font-size: 2rem;
            margin-bottom: 12px;
            opacity: 0.4;
            display: block;
        }

        /* Back Button */
        .actions {
            margin-top: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 9px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            color: white;
            background: var(--primary);
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 700px) {
            header {
                flex-direction: column;
                gap: 14px;
                text-align: center;
                padding: 16px 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .section {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-users"></i> All Hospital Staff</h1>
    <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</header>

<div class="container">

    <div class="page-header">
        <h2><i class="fas fa-id-card-clip"></i> Staff Directory</h2>
        <p>Complete overview of all hospital workers grouped by role</p>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon doctors"><i class="fas fa-user-md"></i></div>
            <div class="label">Doctors</div>
            <div class="value"><?php echo $doctors->num_rows; ?></div>
        </div>
        <div class="stat-card">
            <div class="icon nurses"><i class="fas fa-user-nurse"></i></div>
            <div class="label">Nurses</div>
            <div class="value"><?php echo $nurses->num_rows; ?></div>
        </div>
        <div class="stat-card">
            <div class="icon cashiers"><i class="fas fa-money-bill-wave"></i></div>
            <div class="label">Cashiers</div>
            <div class="value"><?php echo $cashiers->num_rows; ?></div>
        </div>
        <div class="stat-card">
            <div class="icon pharmacists"><i class="fas fa-capsules"></i></div>
            <div class="label">Pharmacists</div>
            <div class="value"><?php echo $pharmacists->num_rows; ?></div>
        </div>
        <div class="stat-card">
            <div class="icon receptionists"><i class="fas fa-desktop"></i></div>
            <div class="label">Receptionists</div>
            <div class="value"><?php echo $receptionists->num_rows; ?></div>
        </div>
    </div>

    <!-- Doctors -->
    <div class="section doctors">
        <div class="section-header doctors">
            <h3><i class="fas fa-user-md"></i> Doctors</h3>
            <span class="count"><?php echo $doctors->num_rows; ?> staff</span>
        </div>
        <?php if ($doctors->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
<th>Department</th>
<th>Status</th>
<th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $doctors->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['department_id'] ? htmlspecialchars($row['department_id']) : 'General'; ?></td>
<td>
    <span class="badge <?php echo strtolower($row['status']) === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
        <?php echo htmlspecialchars($row['status']); ?>
    </span>
</td>
<td class="actions-cell">
    <?php $s = strtolower($row['status']); ?>
    <?php if ($s === 'pending_approval'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Approve this staff member?');">
            <input type="hidden" name="staff_action" value="approve">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-approve" title="Approve"><i class="fas fa-check"></i></button>
        </form>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Reject this staff member?');">
            <input type="hidden" name="staff_action" value="reject">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-reject" title="Reject"><i class="fas fa-times"></i></button>
        </form>
    <?php elseif ($s === 'active'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Deactivate this staff member?');">
            <input type="hidden" name="staff_action" value="deactivate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-deactivate" title="Deactivate"><i class="fas fa-user-slash"></i></button>
        </form>
    <?php else: ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Activate this staff member?');">
            <input type="hidden" name="staff_action" value="activate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-activate" title="Activate"><i class="fas fa-user-check"></i></button>
        </form>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                No doctors found
            </div>
        <?php endif; ?>
    </div>

    <!-- Nurses -->
    <div class="section nurses">
        <div class="section-header nurses">
            <h3><i class="fas fa-user-nurse"></i> Nurses</h3>
            <span class="count"><?php echo $nurses->num_rows; ?> staff</span>
        </div>
        <?php if ($nurses->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
<th>Department</th>
<th>Status</th>
<th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $nurses->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['department_id'] ? htmlspecialchars($row['department_id']) : 'General'; ?></td>
<td>
    <span class="badge <?php echo strtolower($row['status']) === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
        <?php echo htmlspecialchars($row['status']); ?>
    </span>
</td>
<td class="actions-cell">
    <?php $s = strtolower($row['status']); ?>
    <?php if ($s === 'pending_approval'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Approve this staff member?');">
            <input type="hidden" name="staff_action" value="approve">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-approve" title="Approve"><i class="fas fa-check"></i></button>
        </form>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Reject this staff member?');">
            <input type="hidden" name="staff_action" value="reject">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-reject" title="Reject"><i class="fas fa-times"></i></button>
        </form>
    <?php elseif ($s === 'active'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Deactivate this staff member?');">
            <input type="hidden" name="staff_action" value="deactivate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-deactivate" title="Deactivate"><i class="fas fa-user-slash"></i></button>
        </form>
    <?php else: ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Activate this staff member?');">
            <input type="hidden" name="staff_action" value="activate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-activate" title="Activate"><i class="fas fa-user-check"></i></button>
        </form>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                No nurses found
            </div>
        <?php endif; ?>
    </div>

    <!-- Cashiers -->
    <div class="section cashiers">
        <div class="section-header cashiers">
            <h3><i class="fas fa-money-bill-wave"></i> Cashiers</h3>
            <span class="count"><?php echo $cashiers->num_rows; ?> staff</span>
        </div>
        <?php if ($cashiers->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
<th>Department</th>
<th>Status</th>
<th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $cashiers->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['department_id'] ? htmlspecialchars($row['department_id']) : 'General'; ?></td>
<td>
    <span class="badge <?php echo strtolower($row['status']) === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
        <?php echo htmlspecialchars($row['status']); ?>
    </span>
</td>
<td class="actions-cell">
    <?php $s = strtolower($row['status']); ?>
    <?php if ($s === 'pending_approval'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Approve this staff member?');">
            <input type="hidden" name="staff_action" value="approve">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-approve" title="Approve"><i class="fas fa-check"></i></button>
        </form>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Reject this staff member?');">
            <input type="hidden" name="staff_action" value="reject">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-reject" title="Reject"><i class="fas fa-times"></i></button>
        </form>
    <?php elseif ($s === 'active'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Deactivate this staff member?');">
            <input type="hidden" name="staff_action" value="deactivate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-deactivate" title="Deactivate"><i class="fas fa-user-slash"></i></button>
        </form>
    <?php else: ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Activate this staff member?');">
            <input type="hidden" name="staff_action" value="activate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-activate" title="Activate"><i class="fas fa-user-check"></i></button>
        </form>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                No cashiers found
            </div>
        <?php endif; ?>
    </div>

    <!-- Pharmacists -->
    <div class="section pharmacists">
        <div class="section-header pharmacists">
            <h3><i class="fas fa-capsules"></i> Pharmacists</h3>
            <span class="count"><?php echo $pharmacists->num_rows; ?> staff</span>
        </div>
        <?php if ($pharmacists->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
<th>Department</th>
<th>Status</th>
<th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $pharmacists->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['department_id'] ? htmlspecialchars($row['department_id']) : 'General'; ?></td>
<td>
    <span class="badge <?php echo strtolower($row['status']) === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
        <?php echo htmlspecialchars($row['status']); ?>
    </span>
</td>
<td class="actions-cell">
    <?php $s = strtolower($row['status']); ?>
    <?php if ($s === 'pending_approval'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Approve this staff member?');">
            <input type="hidden" name="staff_action" value="approve">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-approve" title="Approve"><i class="fas fa-check"></i></button>
        </form>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Reject this staff member?');">
            <input type="hidden" name="staff_action" value="reject">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-reject" title="Reject"><i class="fas fa-times"></i></button>
        </form>
    <?php elseif ($s === 'active'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Deactivate this staff member?');">
            <input type="hidden" name="staff_action" value="deactivate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-deactivate" title="Deactivate"><i class="fas fa-user-slash"></i></button>
        </form>
    <?php else: ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Activate this staff member?');">
            <input type="hidden" name="staff_action" value="activate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-activate" title="Activate"><i class="fas fa-user-check"></i></button>
        </form>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                No pharmacists found
            </div>
        <?php endif; ?>
    </div>

    <!-- Receptionists -->
    <div class="section receptionists">
        <div class="section-header receptionists">
            <h3><i class="fas fa-desktop"></i> Receptionists</h3>
            <span class="count"><?php echo $receptionists->num_rows; ?> staff</span>
        </div>
        <?php if ($receptionists->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
<th>Department</th>
<th>Status</th>
<th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $receptionists->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['department_id'] ? htmlspecialchars($row['department_id']) : 'General'; ?></td>
<td>
    <span class="badge <?php echo strtolower($row['status']) === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
        <?php echo htmlspecialchars($row['status']); ?>
    </span>
</td>
<td class="actions-cell">
    <?php $s = strtolower($row['status']); ?>
    <?php if ($s === 'pending_approval'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Approve this staff member?');">
            <input type="hidden" name="staff_action" value="approve">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-approve" title="Approve"><i class="fas fa-check"></i></button>
        </form>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Reject this staff member?');">
            <input type="hidden" name="staff_action" value="reject">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-reject" title="Reject"><i class="fas fa-times"></i></button>
        </form>
    <?php elseif ($s === 'active'): ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Deactivate this staff member?');">
            <input type="hidden" name="staff_action" value="deactivate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-deactivate" title="Deactivate"><i class="fas fa-user-slash"></i></button>
        </form>
    <?php else: ?>
        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Activate this staff member?');">
            <input type="hidden" name="staff_action" value="activate">
            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
            <button type="submit" class="btn-action btn-activate" title="Activate"><i class="fas fa-user-check"></i></button>
        </form>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                No receptionists found
            </div>
        <?php endif; ?>
    </div>

    <div class="actions">
        <a href="javascript:history.back()" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>