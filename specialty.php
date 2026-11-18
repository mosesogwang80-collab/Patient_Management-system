<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialties | Iran-Uganda Hospital</title>
    <link rel="stylesheet" href="hospital.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Specialty page styles */
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            min-height: 100vh;
            color: #1a3c34;
        }

        header {
            background: linear-gradient(90deg, #1b5e20, #2e7d32);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }

        header span {
            font-size: 1.4rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        nav a:hover {
            background: rgba(255,255,255,0.15);
        }

        .container {
            max-width: 960px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        .page-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 2.5rem;
            overflow: hidden;
        }

        h2 {
            margin: 0 0 0.5rem;
            font-size: 1.85rem;
            color: #1b5e20;
            font-weight: 700;
        }

        .subtitle {
            color: #4a7c6f;
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }

        .specialty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }

        .specialty-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .specialty-item {
            background: #f1f8f4;
            border: 1px solid #c8e6c9;
            border-radius: 12px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            transition: all 0.25s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .specialty-item:hover {
            background: #e8f5e9;
            border-color: #81c784;
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(46, 125, 50, 0.16);
        }

        .specialty-item:active {
            transform: translateY(-1px);
        }

        .specialty-icon {
            width: 46px;
            height: 46px;
            background: #2e7d32;
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.25);
        }

        .specialty-info {
            flex: 1;
        }

        .specialty-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1b5e20;
            margin-bottom: 0.3rem;
            display: block;
        }

        .specialty-desc {
            font-size: 0.86rem;
            color: #4a7c6f;
            margin: 0 0 0.65rem;
            line-height: 1.45;
        }

        .specialty-meta {
            display: flex;
            gap: 0.75rem;
            font-size: 0.78rem;
            color: #5a8a77;
            margin-bottom: 0.6rem;
        }

        .specialty-meta span {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .specialty-note {
            background: #e8f5e9;
            border-left: 4px solid #81c784;
            border-radius: 0 8px 8px 0;
            padding: 0.55rem 0.75rem;
            font-size: 0.8rem;
            color: #2e5a3f;
            line-height: 1.5;
        }

        .specialty-note strong {
            color: #1b5e20;
        }

        .specialty-arrow {
            flex-shrink: 0;
            color: #81c784;
            font-size: 1rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .specialty-item:hover .specialty-arrow {
            opacity: 1;
        }

        .specialty-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 0.55rem;
        }

        .specialty-tags span {
            background: #dcedd9;
            color: #2e5a3f;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0.18rem 0.5rem;
            border-radius: 12px;
            line-height: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #2e7d32;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.6rem;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s, transform 0.15s;
        }

        .btn:hover {
            background: #1b5e20;
            transform: translateY(-1px);
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
                gap: 0.8rem;
                text-align: center;
            }
            nav a {
                margin: 0 0.5rem;
            }
            .page-card {
                padding: 1.75rem 1.25rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <span>Iran-Uganda Hospital</span>
        <nav>
            <a href="index.php">Home</a>
        </nav>
    </header>

    <div class="container">
        <div class="page-card">
            <h2>Our Specialties</h2>
            <p class="subtitle">Comprehensive care across key medical disciplines</p>

            <div class="specialty-grid">
                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">❤️</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Heart Care</div>
                            <div class="specialty-desc">Cardiology: diagnosis, treatment and prevention of heart and cardiovascular diseases.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Mon–Fri, 8am–5pm</span>
                                <span><i class="far fa-user-md"></i> Cardiologist on call</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Chest pain</span>
                                <span>High blood pressure</span>
                                <span>Irregular heartbeat</span>
                                <span>Heart failure</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Seek emergency care immediately for chest pain, shortness of breath, or pain spreading to the arm or jaw.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">🧠</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Brain & Nerve Care</div>
                            <div class="specialty-desc">Neurology: care for disorders of the brain, spinal cord and nervous system.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Tue–Sat, 9am–4pm</span>
                                <span><i class="far fa-user-md"></i> Neurologist available</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Headaches</span>
                                <span>Seizures</span>
                                <span>Stroke care</span>
                                <span>Nerve pain</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Sudden weakness, slurred speech, or confusion may signal a stroke, act fast and call for help.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">🦴</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Bone & Joint Care</div>
                            <div class="specialty-desc">Orthopedics treatment of bones, joints, muscles and sports-related injuries.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Mon–Fri, 8am–5pm</span>
                                <span><i class="far fa-user-md"></i> Orthopedic surgeon available</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Fractures</span>
                                <span>Arthritis</span>
                                <span>Back pain</span>
                                <span>Joint replacement</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Persistent joint pain or difficulty moving should be evaluated early to prevent long-term damage.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">👶</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Child Care</div>
                            <div class="specialty-desc">Pediatrics: comprehensive healthcare for infants, children and adolescents.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Mon–Sat, 8am–6pm</span>
                                <span><i class="far fa-user-md"></i> Pediatrician on duty</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Vaccinations</span>
                                <span>Growth checks</span>
                                <span>Infections</span>
                                <span>Nutrition</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Regular well-child visits and immunizations protect your child's long-term health and development.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">🚑</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Emergency Trauma</div>
                            <div class="specialty-desc">Immediate care for critical injuries and life-threatening conditions, available 24/7.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Open 24 hours</span>
                                <span><i class="far fa-user-md"></i> Emergency team ready</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Accidents</span>
                                <span>Severe bleeding</span>
                                <span>Burns</span>
                                <span>Chest trauma</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> In a medical emergency, call for help right away do not delay treatment.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">🦷</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Dental Care</div>
                            <div class="specialty-desc">Oral health services including cleanings, fillings, extractions and dental surgery.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Mon–Fri, 8am–4pm</span>
                                <span><i class="far fa-user-md"></i> Dentist available</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Toothache</span>
                                <span>Cavities</span>
                                <span>Gum disease</span>
                                <span>Dentures</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Routine dental checkups every six months prevent cavities, gum disease and tooth loss.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">🤰</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Maternity Care</div>
                            <div class="specialty-desc">Prenatal care, delivery support and postpartum care for mothers and newborns.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> Mon–Sat, 8am–5pm</span>
                                <span><i class="far fa-user-md"></i> Obstetrician &amp; midwife</span>
                            </div>
                            <div class="specialty-tags">
                                <span>Pregnancy checkups</span>
                                <span>Ultrasound</span>
                                <span>Labor &amp; delivery</span>
                                <span>Newborn care</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Early and regular prenatal care greatly improves outcomes for both mother and baby.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>

                <a href="departments.php" class="specialty-link">
                    <div class="specialty-item">
                        <div class="specialty-icon">🏥</div>
                        <div class="specialty-info">
                            <div class="specialty-name">Surgical Care</div>
                            <div class="specialty-desc">Safe operative procedures from minor surgeries to major operations with full aftercare.</div>
                            <div class="specialty-meta">
                                <span><i class="far fa-clock"></i> By appointment</span>
                                <span><i class="far fa-user-md"></i> Surgical team on standby</span>
                            </div>
                            <div class="specialty-tags">
                                <span>General surgery</span>
                                <span>Laparoscopy</span>
                                <span>Wound care</span>
                                <span>Pre/post-op care</span>
                            </div>
                            <div class="specialty-note">
                                <strong>Note:</strong> Follow all pre-operative instructions and attend follow-up visits for the best recovery.
                            </div>
                        </div>
                        <div class="specialty-arrow"><i class="fas fa-chevron-right"></i></div>
                    </div>
                </a>
            </div>

            <a class="btn" href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>