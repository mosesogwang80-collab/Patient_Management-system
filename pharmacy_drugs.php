<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacy') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$pharmacy_name = $_SESSION['user_name'];
$pharmacy_id = $_SESSION['user_id'];

// Auto-discontinue expired drugs: move to expired_drugs table
$today = date('Y-m-d');
$expired = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' AND expiry_date IS NOT NULL AND expiry_date < '$today'");
while ($d = $expired->fetch_assoc()) {
    $conn->query("INSERT INTO expired_drugs (original_drug_id, name, category, price, stock, expiry_date, supplier, discontinued_by) 
        VALUES ({$d['id']}, '{$d['name']}', '{$d['category']}', {$d['price']}, {$d['stock']}, '{$d['expiry_date']}', '{$d['supplier']}', $pharmacy_id)");
    $conn->query("UPDATE pharmacy_drugs SET status='discontinued' WHERE id={$d['id']}");
}

// Handle edit drug
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_drug'])) {
    $id        = intval($_POST['drug_id']);
    $name      = trim($_POST['name']);
    $category  = trim($_POST['category']);
    $price     = floatval($_POST['price']);
    $stock     = intval($_POST['stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry    = $_POST['expiry_date'] ?: null;
    $supplier  = trim($_POST['supplier']);
    $stmt = $conn->prepare("UPDATE pharmacy_drugs SET name=?, category=?, price=?, stock=?, min_stock=?, expiry_date=?, supplier=? WHERE id=?");
    $stmt->bind_param("ssdiissi", $name, $category, $price, $stock, $min_stock, $expiry, $supplier, $id);
    if ($stmt->execute()) {
        header("Location: pharmacy_drugs.php?success=Drug+updated");
        exit;
    } else {
        header("Location: pharmacy_drugs.php?error=Update+failed");
        exit;
    }
}

// Handle restock drug
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_drug'])) {
    $id          = intval($_POST['drug_id']);
    $quantity    = intval($_POST['quantity']);
    $supplier    = trim($_POST['supplier']);
    $expiry      = $_POST['expiry_date'] ?: null;

    $drug = $conn->query("SELECT stock FROM pharmacy_drugs WHERE id=$id")->fetch_assoc();
    if (!$drug) {
        header("Location: pharmacy_drugs.php?error=Drug+not+found");
        exit;
    }

    $newStock = $drug['stock'] + $quantity;
    $stmt = $conn->prepare("UPDATE pharmacy_drugs SET stock=?, supplier=?, expiry_date=? WHERE id=$id");
    $stmt->bind_param("iss", $newStock, $supplier, $expiry);
    if ($stmt->execute()) {
        header("Location: pharmacy_drugs.php?success=Drug+restocked+successfully");
    } else {
        header("Location: pharmacy_drugs.php?error=Restock+failed");
    }
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM pharmacy_drugs WHERE id=$id");
    header("Location: pharmacy_drugs.php?success=Drug+deleted");
    exit;
}

// Handle stock adjust
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $id    = intval($_POST['drug_id']);
    $qty   = intval($_POST['quantity']);
    $type  = $_POST['adjust_type']; // 'add' or 'subtract'
    $drug  = $conn->query("SELECT stock FROM pharmacy_drugs WHERE id=$id")->fetch_assoc();
    $new   = $type === 'add' ? $drug['stock'] + $qty : max(0, $drug['stock'] - $qty);
    $conn->query("UPDATE pharmacy_drugs SET stock=$new WHERE id=$id");
    header("Location: pharmacy_drugs.php?success=Stock+updated");
    exit;
}

// Fetch drug for edit
$editDrug = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $editDrug = $conn->query("SELECT * FROM pharmacy_drugs WHERE id=$id")->fetch_assoc();
}

// Fetch all drugs (active only)
$drugs = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Inventory - Pharmacy | Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #047857;
            --primary-light: #059669;
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
            background: url('images/hospital1.jpg') center/cover fixed;
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

        .status-instock { background: #c6f6d5; color: #276749; }
        .status-lowstock { background: #fef3c7; color: #92400e; }
        .status-outstock { background: #fed7d7; color: #9b2c2c; }

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

        @media (max-width: 640px) {
            header { flex-direction: column; gap: 10px; text-align: center; }
            header nav { width: 100%; justify-content: center; }
            .card { padding: 18px; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-capsules"></i> Drug Inventory</h1>
    <nav>
        <a href="pharmacy_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="pharmacy_drugs.php"><i class="fas fa-capsules"></i> Drug Inventory</a>
        <a href="expired_drugs.php"><i class="fas fa-trash"></i> Expired Drugs</a>
        <a href="pharmacy_prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a>
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

    <!-- Edit Drug Form -->
    <?php if ($editDrug): ?>
    <div class="card">
        <h2><i class="fas fa-edit"></i> Edit Drug</h2>
        <form method="POST" action="pharmacy_drugs.php">
            <input type="hidden" name="drug_id" value="<?php echo $editDrug['id']; ?>">
            <input type="hidden" name="update_drug" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label>Drug Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($editDrug['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Analgesic" <?php echo $editDrug['category']==='Analgesic'?'selected':''; ?>>Analgesic</option>
                        <option value="Antibiotic" <?php echo $editDrug['category']==='Antibiotic'?'selected':''; ?>>Antibiotic</option>
                        <option value="Antacid" <?php echo $editDrug['category']==='Antacid'?'selected':''; ?>>Antacid</option>
                        <option value="Antihypertensive" <?php echo $editDrug['category']==='Antihypertensive'?'selected':''; ?>>Antihypertensive</option>
                        <option value="Respiratory" <?php echo $editDrug['category']==='Respiratory'?'selected':''; ?>>Respiratory</option>
                        <option value="Vitamin" <?php echo $editDrug['category']==='Vitamin'?'selected':''; ?>>Vitamin</option>
                        <option value="Other" <?php echo $editDrug['category']==='Other'?'selected':''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (USh)</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?php echo $editDrug['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" min="0" value="<?php echo $editDrug['stock']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Min Stock Level</label>
                    <input type="number" name="min_stock" min="0" value="<?php echo $editDrug['min_stock']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?php echo $editDrug['expiry_date']; ?>">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Supplier</label>
                    <input type="text" name="supplier" value="<?php echo htmlspecialchars($editDrug['supplier'] ?? ''); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="pharmacy_drugs.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- Restock Drug Form -->
    <div class="card">
        <h2><i class="fas fa-truck-loading"></i> Restock Drug</h2>
        <form method="POST" action="pharmacy_drugs.php">
            <input type="hidden" name="restock_drug" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label>Drug Name</label>
                    <select name="drug_id" required>
                        <option value="">Select drug</option>
                        <?php
                        $allDrugs = $conn->query("SELECT * FROM pharmacy_drugs WHERE status='active' ORDER BY name");
                        while ($dd = $allDrugs->fetch_assoc()):
                        ?>
                            <option value="<?php echo $dd['id']; ?>"><?php echo htmlspecialchars($dd['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (isset($_POST['drug_id']) && is_numeric($_POST['drug_id'])): ?>
                    <?php $sel = $conn->query("SELECT * FROM pharmacy_drugs WHERE id=".intval($_POST['drug_id']))->fetch_assoc(); ?>
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="text" value="<?php echo $sel['stock']; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Manufacturer / Supplier</label>
                        <input type="text" name="supplier" value="<?php echo htmlspecialchars($sel['supplier'] ?? ''); ?>" placeholder="e.g. MediSupply Inc">
                    </div>
                    <div class="form-group">
                        <label>Quantity to Add</label>
                        <input type="number" name="quantity" min="1" placeholder="Enter quantity" required>
                    </div>
                    <div class="form-group">
                        <label>New Expiry Date</label>
                        <input type="date" name="expiry_date" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-success"><i class="fas fa-truck-loading"></i> Restock Drug</button>
                    </div>
                <?php else: ?>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <p class="text-muted">Select a drug above to restock it.</p>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Drug List -->
    <div class="card">
        <h2><i class="fas fa-list"></i> All Drugs</h2>
        <?php if ($drugs->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Drug Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Min Stock</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Action</th>                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($d = $drugs->fetch_assoc()): ?>
                            <?php
                                $status = $d['stock'] == 0 ? 'outstock' : ($d['stock'] <= $d['min_stock'] ? 'lowstock' : 'instock');
                                $statusLabel = ucfirst($status);
                            ?>
                            <tr>
                                <td>#<?php echo $d['id']; ?></td>
                                <td><?php echo htmlspecialchars($d['name']); ?></td>
                                <td><?php echo htmlspecialchars($d['category']); ?></td>
                                <td>USh <?php echo number_format($d['price'], 2); ?></td>
                                <td><?php echo $d['stock']; ?></td>
                                <td><?php echo $d['min_stock']; ?></td>
                                <td><?php echo $d['expiry_date'] ? date('M d, Y', strtotime($d['expiry_date'])) : '-'; ?></td>
                                <td><span class="status status-<?php echo $status; ?>"><?php echo $statusLabel; ?></span></td>
                                <td>
                                    <a href="pharmacy_drugs.php?edit=<?php echo $d['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="pharmacy_drugs.php?delete=<?php echo $d['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this drug?')"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">No drugs in inventory yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>