-- ============================================================
-- patient_system database schema
-- Copy and paste this entire file into phpMyAdmin / MySQL to
-- create the database and all required tables with seed data.
-- Default password for every seeded account: password123
-- ============================================================

CREATE DATABASE IF NOT EXISTS doctor_patient
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE doctor_patient;

-- ---------- Departments ----------
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Users (staff) ----------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','nurse','receptionist','cashier','pharmacy','medical_director','ceo','ministry') NOT NULL,
    department_id INT NULL,
    specialty VARCHAR(100) NULL,
    salary DECIMAL(10,2) NULL,
    available TINYINT(1) DEFAULT 1,
    status ENUM('active','inactive','pending_approval') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ---------- Patients ----------
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    date_of_birth DATE,
    patient_type ENUM('inpatient','outpatient') DEFAULT 'outpatient',
    patient_status ENUM('outpatient','admitted','discharged') DEFAULT 'outpatient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Appointments ----------
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    department_id INT NULL,
    issue VARCHAR(255),
    requested_datetime DATETIME NOT NULL,
    confirmed_datetime DATETIME NULL,
    status ENUM('pending','approved','declined','rescheduled','cancelled','completed') DEFAULT 'pending',
    patient_note TEXT,
    reschedule_suggestion DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ---------- Medical records ----------
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    record_date DATE NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    notes TEXT,
    sent_to_cashier TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------- Messages ----------
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    sender_role VARCHAR(30) NULL,
    recipient_role VARCHAR(30) NULL,
    subject VARCHAR(200),
    body TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Notifications ----------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_phone VARCHAR(30),
    message TEXT,
    channel VARCHAR(30) DEFAULT 'sms',
    status VARCHAR(30) DEFAULT 'queued',
    meta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Medical bills ----------
CREATE TABLE IF NOT EXISTS medical_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    status ENUM('paid','unpaid') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- ---------- Pharmacy Drugs ----------
CREATE TABLE IF NOT EXISTS pharmacy_drugs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 10,
    expiry_date DATE NULL,
    supplier VARCHAR(150) NULL,
    status ENUM('active','discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Expired Drugs (Discontinued) ----------
CREATE TABLE IF NOT EXISTS expired_drugs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_drug_id INT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    expiry_date DATE NULL,
    supplier VARCHAR(150) NULL,
    expired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    discontinued_by INT NULL,
    FOREIGN KEY (discontinued_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------- Prescriptions ----------
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    drug_id INT NULL,
    drug_name VARCHAR(150) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    status ENUM('issued','dispensed','cancelled') DEFAULT 'issued',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dispensed_at DATETIME NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (drug_id) REFERENCES pharmacy_drugs(id) ON DELETE SET NULL
);

-- ---------- Nurse assignments ----------
CREATE TABLE IF NOT EXISTS nurse_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    nurse_id INT NOT NULL,
    assigned_by INT NULL,
    notes TEXT,
    sent_to_cashier TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (nurse_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- Payment Clearances ----------
-- Cashier authorizes patient for admit/drugs/discharge and notifies pharmacy/doctor/nurse
CREATE TABLE IF NOT EXISTS payment_clearances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    bill_id INT NULL,
    authorized_by INT NOT NULL,
    authorization_type ENUM('admit','drugs','discharge') NOT NULL,
    department ENUM('pharmacy','doctor','nurse') NOT NULL,
    status ENUM('pending','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (bill_id) REFERENCES medical_bills(id) ON DELETE SET NULL,
    FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100),
    detail TEXT,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Seed data
-- ============================================================

INSERT INTO departments (name, description) VALUES
 ('General Medicine', 'General outpatient care'),
 ('Cardiology', 'Heart and cardiovascular'),
 ('Neurology', 'Brain and nervous system'),
 ('Orthopedics', 'Bones and joints'),
 ('Pediatrics', 'Children care'),
 ('Emergency', 'Emergency and trauma')
ON DUPLICATE KEY UPDATE name=name;

-- Staff accounts (password = password123)
INSERT INTO users (name,email,password,role,department_id,status) VALUES
 ('System Admin','admin@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','admin',NULL,'active'),
 ('Dr. Alice Heart','alice@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','doctor',2,'active'),
 ('Dr. Bob Brain','bob@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','doctor',3,'active'),
 ('Nurse Carol','carol@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','nurse',1,'active'),
 ('Receptionist Dave','dave@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','receptionist',1,'active'),
 ('Medical Director Eve','eve@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','medical_director',NULL,'active'),
 ('CEO Frank','frank@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','ceo',NULL,'active'),
  ('Ministry Rep Grace','grace@moh.gov','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','ministry',NULL,'active'),
  ('Cashier Sam','sam@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','cashier',NULL,'active'),
  ('Pharmacy Paul','paul@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','pharmacy',NULL,'active')
ON DUPLICATE KEY UPDATE email=email;

-- Sample patient (password = password123)
INSERT INTO patients (name,email,password,phone,date_of_birth) VALUES
 ('John Patient','patient@hospital.com','$2y$10$Oq.pZ6DHUqv1AjYxgZYTa./rZp4OMTE2xyu1QKZohM52u4KsDUEJG','0700000000','1990-01-01')
ON DUPLICATE KEY UPDATE email=email;

-- Sample pharmacy drugs
INSERT INTO pharmacy_drugs (name, category, price, stock, min_stock, expiry_date, supplier) VALUES
 ('Paracetamol 500mg', 'Analgesic', 5.00, 150, 20, '2027-12-31', 'MediSupply Inc'),
 ('Amoxicillin 250mg', 'Antibiotic', 12.00, 45, 15, '2026-10-15', 'PharmaCorp'),
 ('Ibuprofen 400mg', 'Analgesic', 8.00, 30, 25, '2026-11-30', 'MediSupply Inc'),
 ('Omeprazole 20mg', 'Antacid', 10.00, 8, 20, '2027-01-20', 'HealthPharma'),
 ('Amlodipine 5mg', 'Antihypertensive', 15.00, 60, 30, '2026-09-05', 'CardioMeds'),
 ('Salbutamol Inhaler', 'Respiratory', 25.00, 12, 10, '2026-08-15', 'LungCare')
ON DUPLICATE KEY UPDATE name=name;
