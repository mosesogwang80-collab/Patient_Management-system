<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Iran-Uganda Hospital</title>
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
            max-width: 900px;
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

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .contact-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .contact-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .contact-card .icon {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            color: white;
            flex-shrink: 0;
        }

        .contact-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .contact-card .detail {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 0.25rem;
        }

        .contact-card .detail a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .contact-card .detail a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .contact-card .detail strong {
            color: var(--text);
        }

        /* Map Card */
        .map-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .map-card .card-header {
            background: #f8fafc;
        }

        .map-card .card-body {
            padding: 0;
        }

        .map-embed {
            width: 100%;
            height: 350px;
            border: none;
        }

        /* Form Card */
        .form-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .form-card .card-header {
            background: #f8fafc;
        }

        .form-card .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text);
            background: var(--surface);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 0, 139, 0.15);
        }

        .form-group textarea {
            min-height: 140px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
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

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-card .card-body {
                padding: 1.25rem;
            }

            .map-embed {
                height: 250px;
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
        <a href="services.php"><i class="fas fa-stethoscope"></i> <span>Services</span></a>
        <a href="contact.php" class="active"><i class="fas fa-envelope"></i> <span>Contact</span></a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> <span>Staff Login</span></a>
    </nav>
</header>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-address-book"></i> Contact Us</h1>
        <p class="subtitle">Get in touch with Iran-Uganda Hospital</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem;">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($_GET['success']); ?></span>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem;">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($_GET['error']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Contact Information Cards -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-info-circle"></i> Contact Information</h2>
        </div>
        <div class="card-body">
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="icon" style="background: var(--primary);"><i class="fas fa-users"></i></div>
                    <h3>Group / Team</h3>
                    <p class="detail"><strong>Group 6 Ndejje University Kampala Campus Weekend MIS class of 2026</strong></p>
                </div>

                <div class="contact-card">
                    <div class="icon" style="background: var(--success);"><i class="fas fa-phone"></i></div>
                    <h3>Phone</h3>
                    <p class="detail"><a href="tel:0788179038">+256788 179 038</a></p>
                    <p class="detail"><a href="tel:0755539617">+256755 539 617</a></p>
                </div>

                <div class="contact-card">
                    <div class="icon" style="background: var(--info);"><i class="fab fa-x-twitter"></i></div>
                    <h3>X (Twitter)</h3>
                    <p class="detail"><a href="https://x.com/Iran-Ugandahospital" target="_blank">@Iran-Ugandahospital</a></p>
                </div>

                <div class="contact-card">
                    <div class="icon" style="background: var(--danger);"><i class="fas fa-envelope"></i></div>
                    <h3>Email</h3>
                    <p class="detail"><a href="mailto:info@iranugandahospital.com">info@iranugandahospital.com</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Location & Map -->
    <div class="map-card">
        <div class="card-header">
            <h2><i class="fas fa-map-marker-alt"></i> Our Location</h2>
        </div>
        <div class="card-body">
            <iframe 
                class="map-embed"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.840049857733!2d32.6100000!3d0.3400000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177dfe6b7b7b7b7b%3A0x7b7b7b7b7b7b7b7b!2sIran-Uganda%20Hospital!5e0!3m2!1sen!2sug!4v1700000000000!5m2!1sen!2sug"
                allowfullscreen="" 
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>

    <!-- Send Message Form -->
    <div class="form-card">
        <div class="card-header">
            <h2><i class="fas fa-paper-plane"></i> Send Us a Message</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="contact_process.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Your name" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+256 XXX XXX XXX">
                </div>
                <div class="form-group">
                    <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a topic</option>
                        <option value="general">General Inquiry</option>
                        <option value="appointment">Appointment Booking</option>
                        <option value="medical_records">Medical Records</option>
                        <option value="billing">Billing & Payments</option>
                        <option value="complaint">Complaint / Feedback</option>
                        <option value="partnership">Partnership / Collaboration</option>
                        <option value="careers">Career Opportunities</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message"><i class="fas fa-comment"></i> Message</label>
                    <textarea id="message" name="message" placeholder="How can we help you?" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- Emergency Notice -->
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="card-body" style="display: flex; align-items: flex-start; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: var(--danger); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.25rem;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 style="color: var(--danger); margin-bottom: 0.5rem; font-size: 1.1rem;">Medical Emergency?</h3>
                <p style="color: var(--text); margin: 0; line-height: 1.6;">For life-threatening emergencies, please call our <strong>24/7 Emergency Hotline: <a href="tel:0788179038" style="color: var(--danger);">+256788 179 038</a></strong> or proceed directly to our Emergency Department on Katali Road, Nakawa Division, Kampala.</p>
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