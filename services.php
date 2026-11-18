<?php
$page_title = "Our Services - Iran-Uganda Hospital";
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
            --info: darkcyan;
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

        /* Service Grid */
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
        }

        .service-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .service-card .icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
            flex-shrink: 0;
        }

        .service-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .service-card .description {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.6;
            flex-grow: 1;
            margin-bottom: 1rem;
        }

        .service-card .features {
            list-style: none;
            padding: 0;
            margin: 0;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
        }

        .service-card .features li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0;
            font-size: 0.85rem;
            color: var(--text);
        }

        .service-card .features li i {
            font-size: 0.7rem;
            color: var(--success);
        }

        /* CTA Section */
        .cta-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            text-align: center;
        }

        .cta-card .card-body {
            padding: 2.5rem;
        }

        .cta-card h2 {
            color: white;
            margin-bottom: 0.75rem;
            font-size: 1.75rem;
        }

        .cta-card p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

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
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary);
        }

        .btn-white {
            background: white;
            color: var(--primary);
        }

        .btn-white:hover {
            background: #f0f0f0;
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

            .service-card {
                padding: 1.25rem;
            }

            .cta-card .card-body {
                padding: 1.5rem;
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
        <a href="about.php"><i class="fas fa-info-circle"></i> <span>About Us</span></a>
        <a href="services.php" class="active"><i class="fas fa-stethoscope"></i> <span>Services</span></a>
        <a href="contact.php"><i class="fas fa-envelope"></i> <span>Contact</span></a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> <span>Staff Login</span></a>
    </nav>
</header>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-stethoscope"></i> Our Services</h1>
        <p class="subtitle">Comprehensive, specialized healthcare under one roof</p>
    </div>

    <!-- Emergency & Critical Care -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-ambulance"></i> Emergency & Critical Care</h2>
        </div>
        <div class="card-body">
            <div class="service-grid">
                <div class="service-card">
                    <div class="icon" style="background: var(--danger);"><i class="fas fa-ambulance"></i></div>
                    <h3>24/7 Emergency Department</h3>
                    <p class="description">Fully equipped emergency unit with triage, resuscitation bays, and dedicated trauma team operating round the clock.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Level II Trauma Center</li>
                        <li><i class="fas fa-check"></i> Pediatric emergency area</li>
                        <li><i class="fas fa-check"></i> On-site ambulance services</li>
                        <li><i class="fas fa-check"></i> Disaster response coordination</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--danger-hover);"><i class="fas fa-procedures"></i></div>
                    <h3>Intensive Care Units (ICU)</h3>
                    <p class="description">Advanced critical care with 20+ ICU beds including Medical, Surgical, Cardiac, and Neonatal ICUs.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Ventilator & ECMO support</li>
                        <li><i class="fas fa-check"></i> Continuous renal replacement</li>
                        <li><i class="fas fa-check"></i> 1:1 nursing ratio</li>
                        <li><i class="fas fa-check"></i> 24/7 intensivist coverage</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Specialized Clinical Centers -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-clinic-medical"></i> Specialized Clinical Centers</h2>
        </div>
        <div class="card-body">
            <div class="service-grid">
                <div class="service-card">
                    <div class="icon" style="background: var(--primary);"><i class="fas fa-heartbeat"></i></div>
                    <h3>Cardiology & Cardiothoracic Surgery</h3>
                    <p class="description">Comprehensive heart care from prevention to complex surgery, including the region's only cardiac catheterization lab.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Interventional cardiology (angioplasty, stents)</li>
                        <li><i class="fas fa-check"></i> Electrophysiology & ablation</li>
                        <li><i class="fas fa-check"></i> Open heart & bypass surgery</li>
                        <li><i class="fas fa-check"></i> Valve repair & replacement (TAVR)</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--success);"><i class="fas fa-ribcage"></i></div>
                    <h3>Oncology & Hematology</h3>
                    <p class="description">Multidisciplinary cancer center offering chemotherapy, radiotherapy, immunotherapy, and bone marrow transplant.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Linear accelerator radiotherapy</li>
                        <li><i class="fas fa-check"></i> Targeted therapy & immunotherapy</li>
                        <li><i class="fas fa-check"></i> Autologous/allogeneic BMT</li>
                        <li><i class="fas fa-check"></i> Palliative care & survivorship</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--warning);"><i class="fas fa-brain"></i></div>
                    <h3>Neurology & Neurosurgery</h3>
                    <p class="description">Advanced brain and spine care with dedicated stroke unit, epilepsy monitoring, and minimally invasive neurosurgery.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Thrombectomy for acute stroke</li>
                        <li><i class="fas fa-check"></i> Epilepsy surgery program</li>
                        <li><i class="fas fa-check"></i> Deep brain stimulation (DBS)</li>
                        <li><i class="fas fa-check"></i> Neuro-intensive care unit</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--danger);"><i class="fas fa-baby"></i></div>
                    <h3>Neonatology & Pediatrics</h3>
                    <p class="description">Level III NICU, pediatric surgery, and pediatric cardiology serving the youngest patients with family-centered care.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> 30-bed Level III NICU</li>
                        <li><i class="fas fa-check"></i> Pediatric cardiac surgery</li>
                        <li><i class="fas fa-check"></i> Pediatric oncology unit</li>
                        <li><i class="fas fa-check"></i> Child development clinic</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--info);"><i class="fas fa-kidneys"></i></div>
                    <h3>Nephrology & Dialysis</h3>
                    <p class="description">Comprehensive kidney care including hemodialysis, peritoneal dialysis, and the national kidney transplant program.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> 40-station hemodialysis unit</li>
                        <li><i class="fas fa-check"></i> CAPD & APD peritoneal dialysis</li>
                        <li><i class="fas fa-check"></i> Living & deceased donor transplant</li>
                        <li><i class="fas fa-check"></i> Plasmapheresis & immunoadsorption</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: #6d28d9;"><i class="fas fa-lungs"></i></div>
                    <h3>Pulmonology & Respiratory ICU</h3>
                    <p class="description">Advanced respiratory care with bronchoscopy suite, sleep lab, and dedicated respiratory intensive care.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> EBUS & navigational bronchoscopy</li>
                        <li><i class="fas fa-check"></i> Sleep study & CPAP titration</li>
                        <li><i class="fas fa-check"></i> Non-invasive ventilation unit</li>
                        <li><i class="fas fa-check"></i> Lung cancer screening program</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: #0891b2;"><i class="fas fa-bone"></i></div>
                    <h3>Orthopedics & Trauma Surgery</h3>
                    <p class="description">Joint replacement, sports medicine, spine surgery, and complex trauma reconstruction with modern operating theaters.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Robotic-assisted joint replacement</li>
                        <li><i class="fas fa-check"></i> Arthroscopic surgery (knee, shoulder, hip)</li>
                        <li><i class="fas fa-check"></i> Spinal fusion & deformity correction</li>
                        <li><i class="fas fa-check"></i> Hand & microsurgery</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: #be185d;"><i class="fas fa-female"></i></div>
                    <h3>Maternal-Fetal Medicine & IVF</h3>
                    <p class="description">High-risk obstetrics, fetal surgery, and a full-service IVF center with embryology lab and genetics counseling.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Fetal echocardiography & surgery</li>
                        <li><i class="fas fa-check"></i> IVF, ICSI, PGD/PGS</li>
                        <li><i class="fas fa-check"></i> Fertility preservation</li>
                        <li><i class="fas fa-check"></i> Level III maternity unit</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostic & Therapeutic Services -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-microscope"></i> Diagnostic & Therapeutic Services</h2>
        </div>
        <div class="card-body">
            <div class="service-grid">
                <div class="service-card">
                    <div class="icon" style="background: var(--primary);"><i class="fas fa-x-ray"></i></div>
                    <h3>Diagnostic Imaging</h3>
                    <p class="description">State-of-the-art imaging with 3T MRI, 128-slice CT, digital X-ray, ultrasound, and interventional radiology.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> 3T MRI & 128-slice CT</li>
                        <li><i class="fas fa-check"></i> Digital mammography & DEXA</li>
                        <li><i class="fas fa-check"></i> PET-CT (planned 2025)</li>
                        <li><i class="fas fa-check"></i> Interventional radiology suite</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--success);"><i class="fas fa-flask"></i></div>
                    <h3>Pathology & Laboratory Medicine</h3>
                    <p class="description">Fully automated core lab, histopathology, cytogenetics, molecular diagnostics, and blood bank services.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Automated biochemistry & hematology</li>
                        <li><i class="fas fa-check"></i> Molecular PCR & gene sequencing</li>
                        <li><i class="fas fa-check"></i> Flow cytometry & immunology</li>
                        <li><i class="fas fa-check"></i> 24/7 blood bank & apheresis</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--warning);"><i class="fas fa-pills"></i></div>
                    <h3>Pharmacy & Clinical Pharmacology</h3>
                    <p class="description">Hospital pharmacy with sterile compounding, clinical pharmacy services, drug information center, and anticoagulation clinic.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Sterile IV admixture & TPN</li>
                        <li><i class="fas fa-check"></i> Chemotherapy preparation unit</li>
                        <li><i class="fas fa-check"></i> Therapeutic drug monitoring</li>
                        <li><i class="fas fa-check"></i> Medication reconciliation</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--info);"><i class="fas fa-running"></i></div>
                    <h3>Rehabilitation & Physiotherapy</h3>
                    <p class="description">Comprehensive rehab including physiotherapy, occupational therapy, speech therapy, and cardiac/pulmonary rehab.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Hydrotherapy pool & gym</li>
                        <li><i class="fas fa-check"></i> Robotic gait training</li>
                        <li><i class="fas fa-check"></i> Pediatric developmental therapy</li>
                        <li><i class="fas fa-check"></i> Prosthetics & orthotics workshop</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Support & Community Services -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-hands-helping"></i> Support & Community Services</h2>
        </div>
        <div class="card-body">
            <div class="service-grid">
                <div class="service-card">
                    <div class="icon" style="background: var(--primary);"><i class="fas fa-user-md"></i></div>
                    <h3>Outpatient & Specialty Clinics</h3>
                    <p class="description">Over 30 specialty clinics running daily with consultant-led teams, including telemedicine follow-up options.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Consultant-led specialty clinics</li>
                        <li><i class="fas fa-check"></i> Telemedicine & virtual visits</li>
                        <li><i class="fas fa-check"></i> Same-day diagnostic pathways</li>
                        <li><i class="fas fa-check"></i> Nurse-led chronic disease clinics</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--success);"><i class="fas fa-shield-alt"></i></div>
                    <h3>Preventive Health & Screening</h3>
                    <p class="description">Executive health check-ups, cancer screening packages, vaccination center, and occupational health services.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Executive & corporate packages</li>
                        <li><i class="fas fa-check"></i> Cancer screening (breast, cervical, colon, prostate)</li>
                        <li><i class="fas fa-check"></i> Travel & childhood immunizations</li>
                        <li><i class="fas fa-check"></i> Pre-employment & periodic medicals</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--warning);"><i class="fas fa-ambulance"></i></div>
                    <h3>Ambulance & Referral Services</h3>
                    <p class="description">Fleet of ALS/BLS ambulances with critical care transport capability, serving the region and inter-hospital transfers.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Advanced Life Support (ALS) units</li>
                        <li><i class="fas fa-check"></i> Neonatal & pediatric transport</li>
                        <li><i class="fas fa-check"></i> Air ambulance coordination</li>
                        <li><i class="fas fa-check"></i> 24/7 dispatch center</li>
                    </ul>
                </div>
                <div class="service-card">
                    <div class="icon" style="background: var(--info);"><i class="fas fa-graduation-cap"></i></div>
                    <h3>Medical Education & Research</h3>
                    <p class="description">Teaching hospital affiliated with Makerere University and Iranian medical universities; residency programs and clinical trials.</p>
                    <ul class="features">
                        <li><i class="fas fa-check"></i> Residency programs (8 specialties)</li>
                        <li><i class="fas fa-check"></i> Nursing & allied health training</li>
                        <li><i class="fas fa-check"></i> Clinical research unit & ethics board</li>
                        <li><i class="fas fa-check"></i> Simulation & skills center</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="card cta-card">
        <div class="card-body">
            <h2>Ready to Experience World-Class Care?</h2>
            <p>Whether you need a routine check-up, specialized treatment, or emergency care, our team is here for you 24/7.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-outline">
                    <i class="fas fa-phone"></i> Contact Us
                </a>
                <a href="about.php" class="btn btn-white">
                    <i class="fas fa-info-circle"></i> About Us
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