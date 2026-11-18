<?php
$page_title = "About Us - Iran-Uganda Hospital";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
            line-height: 1.6;
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

        .header-left h1 i {
            color: gold;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .page-header h1 i {
            color: gold;
            font-size: 2rem;
        }

        .page-header .subtitle {
            color: #111827;
            font-size: 1.25rem;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(255,255,255,0.9);
            letter-spacing: 0.01em;
        }

        /* Card */
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .card-header h2 {
            font-size: 1.15rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-body p {
            margin-bottom: 1rem;
            color: var(--text);
            line-height: 1.7;
        }

        .card-body p:last-child {
            margin-bottom: 0;
        }

        .card-body ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .card-body li {
            margin-bottom: 0.5rem;
            color: var(--text);
            line-height: 1.6;
        }

        .card-body li strong {
            color: var(--primary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card .icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card .sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Values Grid */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .value-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .value-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .value-card .icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--success), var(--success-hover));
            color: white;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .value-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .value-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
            padding-left: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.75rem;
            top: 0.25rem;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid var(--surface);
            box-shadow: 0 0 0 2px var(--border);
        }

        .timeline-item .year {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .timeline-item h4 {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .timeline-item p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Leadership */
        .leadership-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .leader-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.2s;
        }

        .leader-card:hover {
            transform: translateY(-3px);
        }

        .leader-card .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }

        .leader-card h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .leader-card .role {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
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

        .alert-info {
            background: #e8f4fd;
            color: #1a365d;
            border: 1px solid #b2d8f8;
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

            .page-header h1 {
                font-size: 1.75rem;
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

            .card-body {
                padding: 1rem;
            }

            .stat-card, .value-card, .leader-card {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <h1><i class="fas fa-hospital"></i> Iran-Uganda Hospital</h1>
    </div>
    <nav>
        <a href="index.php"><i class="fas fa-home"></i> <span>Home</span></a>
        <a href="about.php" class="active"><i class="fas fa-info-circle"></i> <span>About Us</span></a>
        <a href="services.php"><i class="fas fa-stethoscope"></i> <span>Services</span></a>
        <a href="contact.php"><i class="fas fa-envelope"></i> <span>Contact</span></a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> <span>Staff Login</span></a>
    </nav>
</header>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-info-circle"></i> About Us</h1>
        <p class="subtitle">Leading healthcare excellence in Uganda since 2018</p>
    </div>

    <!-- Key Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="label">Established</div>
            <div class="value">2018</div>
            <div class="sub">Years of service</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-bed"></i></div>
            <div class="label">Bed Capacity</div>
            <div class="value">200+</div>
            <div class="sub">Inpatient beds</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-md"></i></div>
            <div class="label">Specialists</div>
            <div class="value">50+</div>
            <div class="sub">Medical consultants</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="label">Patients Served</div>
            <div class="value">150K+</div>
            <div class="sub">Annually</div>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-bullseye"></i> Our Mission & Vision</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <h3 style="color: var(--primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-flag"></i> Mission</h3>
                    <p>To provide accessible, high-quality, patient-centered healthcare services to all communities in Uganda and beyond, leveraging advanced medical technology, evidence-based practices, and compassionate care delivered by a dedicated team of professionals.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-eye"></i> Vision</h3>
                    <p>To be the leading referral hospital in the East African region, recognized for clinical excellence, innovation in healthcare delivery, medical education, and research setting the standard for compassionate, ethical, and sustainable healthcare.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Core Values -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-gem"></i> Our Core Values</h2>
        </div>
        <div class="card-body">
            <div class="values-grid">
                <div class="value-card">
                    <div class="icon"><i class="fas fa-heart"></i></div>
                    <h3>Compassion</h3>
                    <p>We treat every patient with empathy, dignity, and respect, recognizing the unique circumstances of each individual.</p>
                </div>
                <div class="value-card">
                    <div class="icon"><i class="fas fa-award"></i></div>
                    <h3>Excellence</h3>
                    <p>We pursue the highest standards of clinical quality, safety, and continuous improvement in all we do.</p>
                </div>
                <div class="value-card">
                    <div class="icon"><i class="fas fa-handshake"></i></div>
                    <h3>Integrity</h3>
                    <p>We uphold honesty, transparency, and ethical conduct in every interaction with patients, staff, and partners.</p>
                </div>
                <div class="value-card">
                    <div class="icon"><i class="fas fa-lightbulb"></i></div>
                    <h3>Innovation</h3>
                    <p>We embrace new technologies, research, and creative solutions to improve healthcare outcomes.</p>
                </div>
                <div class="value-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <h3>Collaboration</h3>
                    <p>We work together across disciplines and with our community partners to achieve the best results.</p>
                </div>
                <div class="value-card">
                    <div class="icon"><i class="fas fa-leaf"></i></div>
                    <h3>Stewardship</h3>
                    <p>We responsibly manage our resources human, financial, and environmentalfor sustainable impact.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Story -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-book-open"></i> Our Story & Milestones</h2>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1.5rem;">Iran-Uganda Hospital was established in 2018 through a strategic partnership between the Islamic Republic of Iran and the Republic of Uganda, reflecting a shared commitment to strengthening healthcare infrastructure and capacity in the region. Located on Katali Road, Nakawa Division, Kampala City, the hospital has grown from a specialized referral center into a comprehensive tertiary care institution.</p>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="year">2018</div>
                    <h4>Foundation & Opening</h4>
                    <p>Hospital officially commissioned; initial departments include Internal Medicine, Surgery, Pediatrics, and Obstetrics/Gynecology.</p>
                </div>
                <div class="timeline-item">
                    <div class="year">2019</div>
                    <h4>Diagnostic Imaging Expansion</h4>
                    <p>Launch of MRI, CT Scan, and Digital X-Ray suites; establishment of modern pathology laboratory.</p>
                </div>
                <div class="timeline-item">
                    <div class="year">2020</div>
                    <h4>COVID-19 Response</h4>
                    <p>Designated as a national COVID-19 treatment center; ICU capacity expanded to 20 beds with ventilator support.</p>
                </div>
                <div class="timeline-item">
                    <div class="year">2021</div>
                    <h4>Cardiac & Oncology Centers</h4>
                    <p>Inauguration of the Cardiac Catheterization Lab and Comprehensive Cancer Care Unit with chemotherapy and radiotherapy.</p>
                </div>
                <div class="timeline-item">
                    <div class="year">2022</div>
                    <h4>Medical Education Partnership</h4>
                    <p>Affiliation with Makerere University and Iranian medical universities for residency training and research collaboration.</p>
                </div>
                <div class="timeline-item">
                    <div class="year">2023</div>
                    <h4>Digital Transformation</h4>
                    <p>Implementation of integrated Hospital Information System (HIS), Electronic Medical Records (EMR), and Telemedicine platform.</p>
                </div>
                <div class="timeline-item">
                    <div class="year">2024</div>
                    <h4>Regional Referral Status</h4>
                    <p>Officially designated as a Regional Referral Hospital serving Eastern Uganda; outreach clinics established in 5 districts.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Specialized Departments -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-clinic-medical"></i> Specialized Clinical Departments</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.75rem;">
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-heartbeat"></i></div>
                    <div><strong>Cardiology & Cardiothoracic Surgery</strong><br><small style="color: var(--text-muted);">Interventional cardiology, cardiac cath lab, open heart surgery</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: var(--success); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-ribcage"></i></div>
                    <div><strong>Oncology & Hematology</strong><br><small style="color: var(--text-muted);">Chemotherapy, radiotherapy, bone marrow transplant</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: var(--warning); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-brain"></i></div>
                    <div><strong>Neurology & Neurosurgery</strong><br><small style="color: var(--text-muted);">Stroke unit, epilepsy monitoring, brain & spine surgery</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: var(--danger); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-baby"></i></div>
                    <div><strong>Neonatology & Pediatrics</strong><br><small style="color: var(--text-muted);">NICU Level III, pediatric surgery, pediatric cardiology</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: var(--info); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-kidneys"></i></div>
                    <div><strong>Nephrology & Dialysis</strong><br><small style="color: var(--text-muted);">Hemodialysis, peritoneal dialysis, kidney transplant</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: #6d28d9; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-lungs"></i></div>
                    <div><strong>Pulmonology & Respiratory ICU</strong><br><small style="color: var(--text-muted);">Bronchoscopy, sleep lab, mechanical ventilation</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: #0891b2; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-bone"></i></div>
                    <div><strong>Orthopedics & Trauma</strong><br><small style="color: var(--text-muted);">Joint replacement, arthroscopy, spine surgery</small></div>
                </div>
                <div style="display: flex; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="width: 40px; height: 40px; background: #be185d; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-female"></i></div>
                    <div><strong>Maternal-Fetal Medicine</strong><br><small style="color: var(--text-muted);">High-risk obstetrics, fetal surgery, IVF center</small></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leadership -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-tie"></i> Hospital Leadership</h2>
        </div>
        <div class="card-body">
            <div class="leadership-grid">
                <div class="leader-card">
                    <div class="avatar">MD</div>
                    <h4>Dr. Ogwang Moses</h4>
                    <div class="role">Chief Executive Officer</div>
                </div>
                <div class="leader-card">
                    <div class="avatar">MD</div>
                    <h4>Dr. Joab Kyomuhendo</h4>
                    <div class="role">Medical Director</div>
                </div>
                <div class="leader-card">
                    <div class="avatar">RN</div>
                    <h4>Matron Deborah Kwagala</h4>
                    <div class="role">Director of Nursing</div>
                </div>
                <div class="leader-card">
                    <div class="avatar">PhD</div>
                    <h4>Dr. Hellen Nantege</h4>
                    <div class="role">Director of Clinical Services</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accreditations & Partnerships -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-certificate"></i> Accreditations & Strategic Partnerships</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <h4 style="color: var(--primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-shield-alt"></i> National Accreditations</h4>
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        <li>Uganda Ministry of Health Licensed Tertiary Hospital</li>
                        <li>Uganda Medical & Dental Practitioners Council Full Accreditation</li>
                        <li>Allied Health Professionals Council Training Institution</li>
                        <li>National Drug Authority  Licensed Pharmacy & Store</li>
                    </ul>
                </div>
                <div style="padding: 1rem; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <h4 style="color: var(--primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-globe"></i> International Partnerships</h4>
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        <li>Tehran University of Medical Sciences (Iran)</li>
                        <li>Shahid Beheshti University of Medical Sciences (Iran)</li>
                        <li>Makerere University College of Health Sciences (Uganda)</li>
                        <li>World Health Organization (WHO)  Collaborating Center</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Community Impact -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-hands-helping"></i> Community Outreach & Impact</h2>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1rem;">Iran-Uganda Hospital extends its mission beyond hospital walls through robust community health programs, ensuring healthcare reaches underserved populations across the region.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="display: flex; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: var(--success); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h4 style="font-size: 1rem; color: var(--text);">Free Medical Camps</h4>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">Quarterly outreach camps in rural districts providing free consultations, screenings, and medications to 2,000+ patients annually.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: var(--info); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-graduation-cap"></i></div>
                    <div>
                        <h4 style="font-size: 1rem; color: var(--text);">Health Education</h4>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">School health programs, maternal health workshops, and community health worker training across 50+ villages.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: var(--warning); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-ambulance"></i></div>
                    <div>
                        <h4 style="font-size: 1rem; color: var(--text);">Mobile Clinics</h4>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">Two fully equipped mobile units delivering primary care, immunizations, and antenatal services to hard-to-reach areas monthly.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: var(--danger); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-seedling"></i></div>
                    <div>
                        <h4 style="font-size: 1rem; color: var(--text);">Environmental Health</h4>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">Water sanitation projects, medical waste management training, and tree planting initiatives with local communities.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white; border: none;">
        <div class="card-body" style="padding: 2.5rem;">
            <h2 style="color: white; margin-bottom: 0.75rem; font-size: 1.75rem;">Experience World-Class Healthcare</h2>
            <p style="font-size: 1.15rem; font-weight: 600; color: white; margin-bottom: 1.5rem; max-width: 600px; margin-left: auto; margin-right: auto; text-shadow: 0 2px 4px rgba(0,0,0,0.4);">Whether you need a routine check-up, specialized treatment, or emergency care, our team is here for you 24/7.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-outline" style="border-color: white; color: white;">
                    <i class="fas fa-phone"></i> Contact Us
                </a>
                <a href="services.php" class="btn" style="background: white; color: var(--primary);">
                    <i class="fas fa-stethoscope"></i> Our Services
                </a>
                <a href="login.php" class="btn" style="background: gold; color: var(--primary);">
                    <i class="fas fa-sign-in-alt"></i> Staff Portal
                </a>
            </div>
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