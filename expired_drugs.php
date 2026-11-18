<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacy') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$pharmacy_name = $_SESSION['user_name'];
$pharmacy_id   = $_SESSION['user_id'];

// Fetch all expired/discontinued drugs awaiting disposal
$expiredDrugs = $conn->query("SELECT * FROM expired_drugs ORDER BY expired_at DESC");

// Handle permanent disposal (delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispose_drug'])) {
    $id = intval($_POST['expired_id']);
    $conn->query("DELETE FROM expired_drugs WHERE id = $id");
    header("Location: expired_drugs.php?disposed=Drug+disposed+successfully");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Drugs - Pharmacy Dashboard</title>
    <link rel="icon" type="image/png" href="logo.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: darkblue;
            --primary-hover: midnightblue;
            --success: seagreen;
            --success-hover: darkgreen;
            --danger: crimson;
            --danger-hover: darkred;
            --warning: darkgoldenrod;
            --bg: whitesmoke;
            --surface: white;
            --text: darkslategray;
            --text-muted: slategray;
            --border: lightgray;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
            --font: 'Inter', system-ui, -apple-system, sans-serif;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font);
            background: url('images/product.jpeg') center/cover fixed no-repeat;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* ========== HEADER ========== */
        header {
            background: darkslategray;
            color: white;
            padding: 0.9rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-left h1 {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .role-badge {
            display: inline-block;
            background: rgba(0, 0, 139, 0.4);
            color: skyblue;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
        }

        header nav {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        header nav a {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 1rem;
            color: lightgray;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        header nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        header nav a.active {
            background: var(--primary);
            color: white;
        }

        header nav a.logout {
            color: lightcoral;
            margin-left: 0.5rem;
        }

        header nav a.logout:hover {
            background: rgba(220, 38, 38, 0.2);
            color: lightpink;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }

        .page-header {
            margin-bottom: 1.75rem;
        }

        .page-header h1 {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .page-header p {
            color: #111827;
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 0.3rem;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
        }

        /* Alerts */
        .alert {
            padding: 0.9rem 1.15rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .alert-success {
            background: honeydew;
            color: darkgreen;
            border: 1px solid palegreen;
        }

        .alert-danger {
            background: mistyrose;
            color: darkred;
            border: 1px solid lightpink;
        }

        /* Card */
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 1.1rem 1.35rem;
            border-bottom: 1px solid var(--border);
            background: whitesmoke;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h2 {
            font-size: 1.05rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 0;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead {
            background: whitesmoke;
        }

        th {
            padding: 0.9rem 1.1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: whitesmoke;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.5rem 0.9rem;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-hover);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3.5rem 1.5rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: lightgray;
            display: block;
        }

        .empty-state p {
            font-size: 1rem;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 900px) {
            header {
                flex-direction: column;
                gap: 0.9rem;
                padding: 1rem;
                text-align: center;
            }

            header nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .header-left {
                flex-direction: column;
                gap: 0.4rem;
            }
        }

        @media (max-width: 600px) {
            .main-content {
                padding: 1.25rem 1rem 2rem;
            }

            header nav a span {
                display: none;
            }

            header nav a {
                padding: 0.55rem 0.75rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <h1><i class="fas fa-capsules"></i> Pharmacy</h1>
        <span class="role-badge"><?php echo htmlspecialchars($pharmacy_name); ?></span>
    </div>
    <nav>
        <a href="pharmacy_dashboard.php">
            <i class="fas fa-chart-pie"></i>
            <span>Overview</span>
        </a>
        <a href="pharmacy_drugs.php">
            <i class="fas fa-capsules"></i>
            <span>Drug Inventory</span>
        </a>
        <a href="expired_drugs.php" class="active">
            <i class="fas fa-trash"></i>
            <span>Expired Drugs</span>
        </a>
        <a href="pharmacy_prescriptions.php">
            <i class="fas fa-file-prescription"></i>
            <span>Prescriptions</span>
        </a>
        <a href="logout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</header>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-trash-alt"></i> Expired Drugs</h1>
        <p>Expired and discontinued medications awaiting safe disposal.</p>
    </div>

    <?php if (isset($_GET['disposed'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_GET['disposed']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Drugs Awaiting Disposal</h2>
        </div>
        <div class="card-body">
            <?php if ($expiredDrugs->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Drug Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Expiry Date</th>
                                <th>Expired At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($d = $expiredDrugs->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $d['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($d['category']); ?></td>
                                    <td>USh <?php echo number_format($d['price'], 2); ?></td>
                                    <td><?php echo $d['stock']; ?></td>
                                    <td><?php echo $d['expiry_date'] ? date('M d, Y', strtotime($d['expiry_date'])) : '-'; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($d['expired_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Dispose of this drug permanently? This cannot be undone.');">
                                            <input type="hidden" name="expired_id" value="<?php echo $d['id']; ?>">
                                            <button type="submit" name="dispose_drug" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Dispose
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
                    <i class="fas fa-check-circle"></i>
                    <p>No drugs awaiting disposal.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

</body>
</html>