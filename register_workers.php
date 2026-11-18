<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Workers - Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 40%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #1e293b;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle floating orbs for modern depth */
        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            z-index: 0;
            pointer-events: none;
        }

        body::before {
            width: 420px;
            height: 420px;
            background: #6366f1;
            top: -120px;
            left: -100px;
        }

        body::after {
            width: 380px;
            height: 380px;
            background: #0ea5e9;
            bottom: -100px;
            right: -80px;
        }

        .card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 520px;
            padding: 48px 40px;
            text-align: center;
        }

        .card h2 {
            color: #0f172a;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            letter-spacing: -0.025em;
        }

        .card h2 i {
            color: #4f46e5;
        }

        .card .subtitle {
            color: #64748b;
            margin-bottom: 36px;
            font-size: 0.975rem;
            font-weight: 400;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .option-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            font-size: 1.05rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: left;
        }

        .option-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.12);
            border-color: transparent;
        }

        .option-btn .icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }

        .option-btn:hover .icon {
            transform: scale(1.08);
        }

        /* Role-specific colors */
        .option-btn.doctor .icon {
            background: #eff6ff;
            color: #2563eb;
        }
        .option-btn.doctor:hover {
            background: linear-gradient(135deg, #eff6ff, white);
        }

        .option-btn.nurse .icon {
            background: #f0fdf4;
            color: #16a34a;
        }
        .option-btn.nurse:hover {
            background: linear-gradient(135deg, #f0fdf4, white);
        }

        .option-btn.cashier .icon {
            background: #f5f3ff;
            color: #7c3aed;
        }
        .option-btn.cashier:hover {
            background: linear-gradient(135deg, #f5f3ff, white);
        }

        .option-btn.pharmacy .icon {
            background: #ecfdf5;
            color: #059669;
        }
        .option-btn.pharmacy:hover {
            background: linear-gradient(135deg, #ecfdf5, white);
        }

        .option-btn.receptionist .icon {
            background: #fffbeb;
            color: #d97706;
        }
        .option-btn.receptionist:hover {
            background: linear-gradient(135deg, #fffbeb, white);
        }

        .option-btn small {
            display: block;
            color: #94a3b8;
            font-weight: 400;
            font-size: 0.82rem;
            margin-top: 2px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 36px;
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            padding: 8px 14px;
            border-radius: 10px;
        }

        .back-link:hover {
            background: #eef2ff;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .card {
                padding: 36px 24px;
            }
            .card h2 {
                font-size: 1.5rem;
            }
            .option-btn {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<div class="card">
    <h2><i class="fas fa-user-plus"></i> Register Workers</h2>
    <p class="subtitle">Select the type of staff member you want to register</p>

    <div class="options">
        <a href="register_doctor.php" class="option-btn doctor">
            <div class="icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div>
                <div>Register Doctor</div>
                <small>Add a new doctor to the system</small>
            </div>
        </a>

        <a href="register_nurse.php" class="option-btn nurse">
            <div class="icon">
                <i class="fas fa-user-nurse"></i>
            </div>
            <div>
                <div>Register Nurse</div>
                <small>Add a new nurse to the system</small>
            </div>
        </a>

        <a href="register_cashier.php" class="option-btn cashier">
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div>Register Cashier</div>
                <small>Add a new cashier to the system</small>
            </div>
        </a>

        <a href="register_pharmacy.php" class="option-btn pharmacy">
            <div class="icon">
                <i class="fas fa-capsules"></i>
            </div>
            <div>
                <div>Register Pharmacist</div>
                <small>Add a new pharmacist to the system</small>
            </div>
        </a>

        <a href="register_receptionist.php" class="option-btn receptionist">
            <div class="icon">
                <i class="fas fa-headset"></i>
            </div>
            <div>
                <div>Register Receptionist</div>
                <small>Add a new receptionist to the system</small>
            </div>
        </a>

        <a href="register_lab_technician.php" class="option-btn" style="--btn-color: teal;">
            <div class="icon" style="color: teal;">
                <i class="fas fa-flask"></i>
            </div>
            <div>
                <div>Register Lab Technician</div>
                <small>Add a new lab technician to the system</small>
            </div>
        </a>
    </div>

    <a href="admin_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
    </a>
</div>

</body>
</html>