<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacy') {
    header("Location: login.php");
    exit;
}
require 'db.php';

$pharmacy_id = $_SESSION['user_id'];
$rx_id = intval($_GET['rx_id'] ?? 0);

// Fetch prescription details
$rx = $conn->query("
    SELECT pr.*, p.name AS patient_name, p.phone AS patient_phone,
           u.name AS doctor_name
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    LEFT JOIN users u ON pr.doctor_id = u.id
    WHERE pr.id = $rx_id
")->fetch_assoc();

if (!$rx) {
    die("Prescription not found.");
}

// Check drug stock availability
$drug = $conn->query("SELECT * FROM pharmacy_drugs WHERE id={$rx['drug_id']}")->fetch_assoc();
$drug_available = $drug && $drug['stock'] > 0;
$drug_stock = $drug ? $drug['stock'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Receipt - Rx #<?php echo $rx_id; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1a202c;
            line-height: 1.6;
            padding: 20px;
            background: #fff;
        }
        .receipt {
            max-width: 700px;
            margin: 0 auto;
            border: 2px solid #2d3748;
            padding: 30px;
            border-radius: 8px;
            background: #fff;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 3px double #2d3748;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .receipt-header h1 {
            color: #047857;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        .receipt-header p {
            color: #4a5568;
            font-size: 0.95rem;
        }
        .receipt-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .detail-group {
            background: #f7fafc;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .detail-group h3 {
            font-size: 0.95rem;
            color: #2d3748;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .detail-row {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .detail-row .label {
            font-weight: 600;
            color: #4a5568;
        }
        .detail-row .value {
            color: #1a202c;
        }
        .drug-section {
            margin-bottom: 25px;
        }
        .drug-section h3 {
            font-size: 1.1rem;
            color: #047857;
            margin-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        th {
            background: #047857;
            color: #fff;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-dispensed { background: #c6f6d5; color: #276749; }
        .status-unavailable { background: #fed7d7; color: #9b2c2c; }
        .status-issued { background: #dbeafe; color: #1e40af; }
        .missing-note {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #9b2c2c;
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .missing-note h4 {
            color: #c53030;
            margin-bottom: 8px;
        }
        .missing-note ul {
            margin-left: 20px;
            margin-bottom: 0;
        }
        .missing-note li {
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            border-top: 2px solid #2d3748;
            padding-top: 15px;
            font-size: 0.85rem;
            color: #4a5568;
        }
        .print-btn {
            display: inline-block;
            background: #047857;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        @media print {
            .no-print, .print-btn { display: none !important; }
            body { background: white !important; }
            .receipt { border: none; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="receipt">
    <div class="receipt-header">
        <h1><i class="fas fa-file-prescription"></i> Prescription Receipt</h1>
        <p>Iran-Uganda Hospital &bull; Pharmacy Department</p>
    </div>

    <div class="receipt-details">
        <div class="detail-group">
            <h3>Patient Information</h3>
            <div class="detail-row"><span class="label">Name:</span> <span class="value"><?php echo htmlspecialchars($rx['patient_name']); ?></span></div>
            <div class="detail-row"><span class="label">Phone:</span> <span class="value"><?php echo htmlspecialchars($rx['patient_phone'] ?? 'N/A'); ?></span></div>
        </div>
        <div class="detail-group">
            <h3>Prescription Details</h3>
            <div class="detail-row"><span class="label">Rx ID:</span> <span class="value">#<?php echo $rx['id']; ?></span></div>
            <div class="detail-row"><span class="label">Doctor:</span> <span class="value"><?php echo htmlspecialchars($rx['doctor_name'] ?? 'Not assigned'); ?></span></div>
            <div class="detail-row"><span class="label">Date Issued:</span> <span class="value"><?php echo date('M d, Y', strtotime($rx['issued_at'])); ?></span></div>
            <div class="detail-row"><span class="label">Status:</span> <span class="value"><span class="status-badge status-<?php echo $rx['status']; ?>"><?php echo ucfirst($rx['status']); ?></span></span></div>
        </div>
    </div>

    <div class="drug-section">
        <h3><i class="fas fa-capsules"></i> Medication</h3>
        <table>
            <thead>
                <tr>
                    <th>Drug</th>
                    <th>Dosage</th>
                    <th>Status</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($rx['drug_name']); ?></td>
                    <td><?php echo htmlspecialchars($rx['dosage']); ?></td>
                    <td>
                        <?php if ($rx['status'] === 'dispensed'): ?>
                            <span class="status-badge status-dispensed">Given to Patient</span>
                        <?php elseif ($rx['status'] === 'issued'): ?>
                            <span class="status-badge status-issued">Prescribed</span>
                        <?php else: ?>
                            <span class="status-badge status-unavailable">Cancelled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($drug_available): ?>
                            <span style="color:#276749; font-weight:600;">In Stock (<?php echo $drug_stock; ?>)</span>
                        <?php else: ?>
                            <span style="color:#c53030; font-weight:600;">Unavailable</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if ($rx['status'] === 'dispensed' && !$drug_available): ?>
        <div class="missing-note">
            <h4><i class="fas fa-exclamation-triangle"></i> Drug Unavailable</h4>
            <p>The prescribed drug <strong><?php echo htmlspecialchars($rx['drug_name']); ?></strong> is currently out of stock at our pharmacy.</p>
            <ul>
                <li>Please visit another pharmacy to obtain this medication.</li>
                <li>Show this receipt to the pharmacist for reference.</li>
                <li>Dosage: <?php echo htmlspecialchars($rx['dosage']); ?></li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="footer">
        <p><strong>Pharmacist:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        <p>Thank you for choosing Iran-Uganda Hospital Pharmacy.</p>
        <p>This receipt is valid for medication collection.</p>
    </div>

    <button class="print-btn no-print" onclick="window.print()"><i class="fas fa-print"></i> Print Receipt</button>
</div>

</body>
</html>
