-- ============================================================
--  MediCare Pro — Hospital Management System
--  Complete Database Schema v2.0
--  Supports: Admin, Doctor, Patient modules + AI Layer
-- ============================================================

CREATE DATABASE IF NOT EXISTS medicare_pro;
USE medicare_pro;

-- ────────────────────────────────────────────────────────────
--  CORE USER TABLES
-- ────────────────────────────────────────────────────────────

CREATE TABLE admins (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(20),
  profile_pic   VARCHAR(255),
  last_login    TIMESTAMP NULL,
  is_active     TINYINT(1) DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  description TEXT,
  head_doctor_id INT NULL,
  floor       VARCHAR(30),
  beds_total  INT DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE doctors (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  full_name        VARCHAR(120) NOT NULL,
  email            VARCHAR(150) NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  phone            VARCHAR(20),
  department_id    INT,
  specialization   VARCHAR(150),
  qualification    VARCHAR(255),
  experience_years INT DEFAULT 0,
  license_number   VARCHAR(80) UNIQUE,
  profile_pic      VARCHAR(255),
  bio              TEXT,
  consultation_fee DECIMAL(10,2) DEFAULT 0.00,
  available_days   VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  available_from   TIME DEFAULT '09:00:00',
  available_to     TIME DEFAULT '17:00:00',
  rating           DECIMAL(3,2) DEFAULT 0.00,
  total_reviews    INT DEFAULT 0,
  last_login       TIMESTAMP NULL,
  is_active        TINYINT(1) DEFAULT 1,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

ALTER TABLE departments
  ADD FOREIGN KEY (head_doctor_id) REFERENCES doctors(id) ON DELETE SET NULL;

CREATE TABLE patients (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(120) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  phone          VARCHAR(20),
  dob            DATE,
  gender         ENUM('Male','Female','Other','Prefer not to say'),
  blood_group    VARCHAR(10),
  address        TEXT,
  city           VARCHAR(80),
  state          VARCHAR(80),
  pin_code       VARCHAR(15),
  emergency_contact_name  VARCHAR(120),
  emergency_contact_phone VARCHAR(20),
  insurance_provider      VARCHAR(150),
  insurance_policy_number VARCHAR(80),
  profile_pic    VARCHAR(255),
  allergies      TEXT,
  chronic_conditions TEXT,
  last_login     TIMESTAMP NULL,
  is_active      TINYINT(1) DEFAULT 1,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ────────────────────────────────────────────────────────────
--  APPOINTMENTS & SCHEDULING
-- ────────────────────────────────────────────────────────────

CREATE TABLE appointments (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  patient_id          INT NOT NULL,
  doctor_id           INT NOT NULL,
  department_id       INT,
  appointment_date    DATE NOT NULL,
  appointment_time    TIME NOT NULL,
  duration_minutes    INT DEFAULT 20,
  type                ENUM('OPD','Emergency','Follow-up','Telemedicine') DEFAULT 'OPD',
  status              ENUM('Scheduled','Confirmed','In Progress','Completed','Cancelled','No-Show') DEFAULT 'Scheduled',
  chief_complaint     TEXT,
  symptoms            TEXT,
  ai_priority_score   DECIMAL(5,2) DEFAULT 0.00,
  ai_triage_label     VARCHAR(50),
  ai_suggested_dept   VARCHAR(120),
  notes               TEXT,
  cancellation_reason TEXT,
  token_number        INT,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id)    REFERENCES patients(id)    ON DELETE CASCADE,
  FOREIGN KEY (doctor_id)     REFERENCES doctors(id)     ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ────────────────────────────────────────────────────────────
--  CLINICAL / EMR
-- ────────────────────────────────────────────────────────────

CREATE TABLE vitals (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  appointment_id  INT,
  recorded_by     INT COMMENT 'doctor_id',
  blood_pressure_systolic  INT,
  blood_pressure_diastolic INT,
  pulse_rate      INT,
  temperature     DECIMAL(5,2),
  spo2            INT,
  weight_kg       DECIMAL(6,2),
  height_cm       DECIMAL(6,2),
  bmi             DECIMAL(5,2),
  blood_sugar_fasting     DECIMAL(6,2),
  blood_sugar_postprandial DECIMAL(6,2),
  ai_risk_flag    VARCHAR(80),
  recorded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id)     REFERENCES patients(id)     ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  FOREIGN KEY (recorded_by)    REFERENCES doctors(id)      ON DELETE SET NULL
);

CREATE TABLE medical_records (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  doctor_id       INT NOT NULL,
  appointment_id  INT,
  chief_complaint TEXT,
  history         TEXT,
  examination     TEXT,
  diagnosis       TEXT,
  icd10_code      VARCHAR(20),
  prescription    TEXT,
  instructions    TEXT,
  follow_up_date  DATE,
  ai_summary      TEXT,
  ai_risk_level   ENUM('Low','Moderate','High','Critical') DEFAULT 'Low',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id)     REFERENCES patients(id)     ON DELETE CASCADE,
  FOREIGN KEY (doctor_id)      REFERENCES doctors(id)      ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

CREATE TABLE prescriptions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  record_id       INT NOT NULL,
  patient_id      INT NOT NULL,
  doctor_id       INT NOT NULL,
  medicine_name   VARCHAR(200) NOT NULL,
  dosage          VARCHAR(100),
  frequency       VARCHAR(100),
  duration_days   INT,
  instructions    TEXT,
  ai_interaction_flag VARCHAR(255),
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (record_id)   REFERENCES medical_records(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id)  REFERENCES patients(id)        ON DELETE CASCADE,
  FOREIGN KEY (doctor_id)   REFERENCES doctors(id)         ON DELETE CASCADE
);

CREATE TABLE lab_reports (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  doctor_id       INT,
  appointment_id  INT,
  report_type     VARCHAR(150),
  report_file     VARCHAR(255),
  report_date     DATE,
  remarks         TEXT,
  ai_interpretation TEXT,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id)     REFERENCES patients(id)     ON DELETE CASCADE,
  FOREIGN KEY (doctor_id)      REFERENCES doctors(id)      ON DELETE SET NULL,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- ────────────────────────────────────────────────────────────
--  HOSPITAL OPERATIONS
-- ────────────────────────────────────────────────────────────

CREATE TABLE beds (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  department_id   INT NOT NULL,
  bed_number      VARCHAR(20) NOT NULL,
  ward_type       ENUM('General','Semi-Private','Private','ICU','NICU','Emergency') DEFAULT 'General',
  status          ENUM('Available','Occupied','Reserved','Maintenance') DEFAULT 'Available',
  patient_id      INT NULL,
  admitted_at     TIMESTAMP NULL,
  expected_discharge DATE,
  ai_discharge_prediction DATE,
  daily_charge    DECIMAL(10,2) DEFAULT 0.00,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id)    REFERENCES patients(id)    ON DELETE SET NULL
);

CREATE TABLE staff (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) UNIQUE,
  phone         VARCHAR(20),
  role          ENUM('Nurse','Technician','Pharmacist','Receptionist','Cleaner','Security','Other'),
  department_id INT,
  shift         ENUM('Morning','Afternoon','Night','Rotational') DEFAULT 'Morning',
  workload_score INT DEFAULT 0 COMMENT 'AI computed 0-100',
  is_active     TINYINT(1) DEFAULT 1,
  joined_at     DATE,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ────────────────────────────────────────────────────────────
--  BILLING & FINANCE
-- ────────────────────────────────────────────────────────────

CREATE TABLE bills (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  appointment_id  INT,
  bill_number     VARCHAR(30) UNIQUE,
  consultation_fee DECIMAL(10,2) DEFAULT 0.00,
  medicine_charges DECIMAL(10,2) DEFAULT 0.00,
  lab_charges     DECIMAL(10,2) DEFAULT 0.00,
  bed_charges     DECIMAL(10,2) DEFAULT 0.00,
  other_charges   DECIMAL(10,2) DEFAULT 0.00,
  discount        DECIMAL(10,2) DEFAULT 0.00,
  tax             DECIMAL(10,2) DEFAULT 0.00,
  total_amount    DECIMAL(10,2) NOT NULL,
  paid_amount     DECIMAL(10,2) DEFAULT 0.00,
  payment_method  ENUM('Cash','Card','UPI','Insurance','Net Banking') DEFAULT 'Cash',
  payment_status  ENUM('Pending','Partial','Paid','Refunded','Disputed') DEFAULT 'Pending',
  insurance_claimed TINYINT(1) DEFAULT 0,
  ai_fraud_flag   TINYINT(1) DEFAULT 0,
  ai_flag_reason  VARCHAR(255),
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  paid_at         TIMESTAMP NULL,
  FOREIGN KEY (patient_id)     REFERENCES patients(id)     ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

CREATE TABLE insurance_claims (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  bill_id         INT NOT NULL,
  provider        VARCHAR(150),
  policy_number   VARCHAR(80),
  claim_amount    DECIMAL(10,2),
  status          ENUM('Submitted','Under Review','Approved','Rejected','Settled') DEFAULT 'Submitted',
  ai_approval_score DECIMAL(5,2) DEFAULT 0.00,
  submitted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  settled_at      TIMESTAMP NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (bill_id)    REFERENCES bills(id)    ON DELETE CASCADE
);

-- ────────────────────────────────────────────────────────────
--  AI LAYER TABLES
-- ────────────────────────────────────────────────────────────

CREATE TABLE ai_chat_sessions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  user_role       ENUM('admin','doctor','patient'),
  session_token   VARCHAR(64),
  started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ended_at        TIMESTAMP NULL
);

CREATE TABLE ai_chat_messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  session_id  INT NOT NULL,
  role        ENUM('user','assistant'),
  content     TEXT,
  tokens_used INT DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES ai_chat_sessions(id) ON DELETE CASCADE
);

CREATE TABLE ai_logs (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  module        ENUM('admin','doctor','patient','system'),
  feature       VARCHAR(120),
  user_id       INT,
  user_role     VARCHAR(40),
  input_tokens  INT DEFAULT 0,
  output_tokens INT DEFAULT 0,
  latency_ms    INT DEFAULT 0,
  success       TINYINT(1) DEFAULT 1,
  error_msg     TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  user_role   ENUM('admin','doctor','patient'),
  title       VARCHAR(255),
  message     TEXT,
  type        ENUM('info','warning','alert','success') DEFAULT 'info',
  is_read     TINYINT(1) DEFAULT 0,
  ai_generated TINYINT(1) DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ────────────────────────────────────────────────────────────
--  SEED DATA
-- ────────────────────────────────────────────────────────────

-- ── Seed passwords stored as plain text ──────────────────────────────────────
-- login.php detects plain-text on first login and auto-upgrades to bcrypt.
-- Password for ALL demo accounts: admin123

INSERT INTO admins (full_name, email, password_hash) VALUES
('Dr. Ramesh Gupta', 'admin@medicare.com', 'admin123');

INSERT INTO departments (name, description, floor, beds_total) VALUES
('Cardiology', 'Heart and cardiovascular system specialists', 'Floor 3', 30),
('Neurology', 'Brain, spine, and nervous system care', 'Floor 4', 20),
('Orthopedics', 'Bone, joint, and musculoskeletal care', 'Floor 2', 25),
('General Medicine', 'Primary care and internal medicine', 'Floor 1', 50),
('Emergency', 'Critical and emergency care', 'Ground Floor', 40),
('Pediatrics', 'Child and neonatal care', 'Floor 2', 20),
('Gynecology', 'Women health and maternity', 'Floor 5', 30);

INSERT INTO doctors (full_name, email, password_hash, phone, department_id, specialization, qualification, experience_years, license_number, consultation_fee) VALUES
('Dr. Anil Mehta',   'doctor@medicare.com',  'admin123', '9876543210', 1, 'Interventional Cardiologist', 'MD, DM Cardiology',  14, 'MCI-CARD-001', 800.00),
('Dr. Priya Sharma', 'priya@medicare.com',   'admin123', '9876543211', 2, 'Neurologist',                 'MD, DM Neurology',   10, 'MCI-NEURO-002', 900.00),
('Dr. Suresh Iyer',  'suresh@medicare.com',  'admin123', '9876543212', 3, 'Orthopaedic Surgeon',         'MS Orthopaedics',    12, 'MCI-ORTH-003', 750.00),
('Dr. Kavitha Nair', 'kavitha@medicare.com', 'admin123', '9876543213', 4, 'General Physician',           'MBBS, MD Medicine',   8, 'MCI-GEN-004',  500.00);

INSERT INTO patients (full_name, email, password_hash, phone, dob, gender, blood_group, city) VALUES
('Priya Verma', 'patient@medicare.com', 'admin123', '9123456789', '1990-05-14', 'Female', 'B+', 'Delhi'),
('Rahul Singh',  'rahul@medicare.com',  'admin123', '9123456790', '1985-08-22', 'Male',   'O+', 'Noida');
