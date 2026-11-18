<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Iran-Uganda Hospital</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2b6cb0;
            --card-bg: rgba(255, 255, 255, 0.94);
            --border: #e2e8f0;
            --text: #2d3748;
            --muted: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: url('images/depart.jpg') center/cover fixed;
            background-color: #e2e8f0;
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header */
        header {
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }

        header .logo {
            font-size: 1.3rem;
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
        }

        header nav a:hover {
            background: rgba(255,255,255,0.15);
        }

        /* Main Container */
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px 50px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 36px 40px;
            backdrop-filter: blur(6px);
        }

        .card h2 {
            color: var(--primary);
            font-size: 1.6rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card .subtitle {
            color: var(--muted);
            margin-bottom: 28px;
            font-size: 0.95rem;
        }

        /* Departments List */
        .departments-list {
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .departments-list li {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: all 0.2s;
        }

        .departments-list li:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.07);
            border-color: var(--primary-light);
        }

        .departments-list .icon {
            background: #ebf4ff;
            color: var(--primary-light);
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .departments-list .content h3 {
            font-size: 1.05rem;
            color: var(--primary);
            margin-bottom: 3px;
        }

        .departments-list .content p {
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Button */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            padding: 11px 20px;
            background: var(--primary-light);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--primary);
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .card {
                padding: 26px 22px;
            }

            header {
                padding: 14px 18px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <i class="fas fa-hospital"></i>
        Iran-Uganda Hospital
    </div>
    <nav>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
    </nav>
</header>

<div class="container">
    <div class="card">
        <h2><i class="fas fa-building"></i> Our Departments</h2>
        <p class="subtitle">We offer specialized care across multiple medical departments.</p>

        <ul class="departments-list">
            <li>
                <div class="icon"><i class="fas fa-stethoscope"></i></div>
                <div class="content">
                    <h3>General Medicine</h3>
                    <p>Primary care and routine checkups</p>
                </div>
            </li>

            <li>
                <div class="icon"><i class="fas fa-heartbeat"></i></div>
                <div class="content">
                    <h3>Cardiology</h3>
                    <p>Heart care and cardiovascular treatment</p>
                </div>
            </li>

            <li>
                <div class="icon"><i class="fas fa-brain"></i></div>
                <div class="content">
                    <h3>Neurology</h3>
                    <p>Brain and nervous system care</p>
                </div>
            </li>

            <li>
                <div class="icon"><i class="fas fa-bone"></i></div>
                <div class="content">
                    <h3>Orthopedics</h3>
                    <p>Bones, joints and musculoskeletal care</p>
                </div>
            </li>

            <li>
                <div class="icon"><i class="fas fa-baby"></i></div>
                <div class="content">
                    <h3>Pediatrics</h3>
                    <p>Specialized care for children</p>
                </div>
            </li>

            <li>
                <div class="icon"><i class="fas fa-tooth"></i></div>
                <div class="content">
                    <h3>Dental Services</h3>
                    <p>Teeth and oral healthcare</p>
                </div>
            </li>

            <li>
                <div class="icon"><i class="fas fa-syringe"></i></div>
                <div class="content">
                    <h3>Surgery</h3>
                    <p>Operative and surgical procedures</p>
                </div>
            </li>
        </ul>

        <a href="index.php" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

</body>
</html>