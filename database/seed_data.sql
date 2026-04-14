-- ============================================================
--  MediCare Pro — Rich Seed Data v2.1
--  IMPORT VIA: phpMyAdmin → Import → Choose file → Go
--  OR: mysql -u root -p medicare_pro < database/seed_data.sql
-- ============================================================

USE medicare_pro;

-- Use DELETE instead of TRUNCATE to avoid FK constraint errors in phpMyAdmin
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM ai_chat_messages;
DELETE FROM ai_chat_sessions;
DELETE FROM ai_logs;
DELETE FROM notifications;
DELETE FROM insurance_claims;
DELETE FROM bills;
DELETE FROM prescriptions;
DELETE FROM lab_reports;
DELETE FROM medical_records;
DELETE FROM vitals;
DELETE FROM appointments;
DELETE FROM beds;
DELETE FROM staff;
DELETE FROM patients;
DELETE FROM doctors;
DELETE FROM departments;
DELETE FROM admins;

-- Reset auto-increment
ALTER TABLE admins          AUTO_INCREMENT = 1;
ALTER TABLE departments     AUTO_INCREMENT = 1;
ALTER TABLE doctors         AUTO_INCREMENT = 1;
ALTER TABLE patients        AUTO_INCREMENT = 1;
ALTER TABLE appointments    AUTO_INCREMENT = 1;
ALTER TABLE vitals          AUTO_INCREMENT = 1;
ALTER TABLE medical_records AUTO_INCREMENT = 1;
ALTER TABLE prescriptions   AUTO_INCREMENT = 1;
ALTER TABLE lab_reports     AUTO_INCREMENT = 1;
ALTER TABLE beds            AUTO_INCREMENT = 1;
ALTER TABLE staff           AUTO_INCREMENT = 1;
ALTER TABLE bills           AUTO_INCREMENT = 1;
ALTER TABLE insurance_claims AUTO_INCREMENT = 1;
ALTER TABLE notifications   AUTO_INCREMENT = 1;
ALTER TABLE ai_logs         AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Admins ────────────────────────────────────────────────
INSERT INTO admins (full_name, email, password_hash, phone) VALUES
('Dr. Ramesh Gupta', 'admin@medicare.com', 'admin123', '9811000001');

-- ── Departments ───────────────────────────────────────────
INSERT INTO departments (name, description, floor, beds_total) VALUES
('Cardiology',       'Heart and cardiovascular system specialists',  'Floor 3',      30),
('Neurology',        'Brain, spine, and nervous system care',        'Floor 4',      20),
('Orthopedics',      'Bone, joint, and musculoskeletal care',        'Floor 2',      25),
('General Medicine', 'Primary care and internal medicine',           'Floor 1',      50),
('Emergency',        'Critical and emergency care',                  'Ground Floor', 40),
('Pediatrics',       'Child and neonatal care',                      'Floor 2',      20),
('Gynecology',       'Women health and maternity',                   'Floor 5',      30),
('Oncology',         'Cancer diagnosis and treatment',               'Floor 6',      15),
('Dermatology',      'Skin, hair, and nail disorders',               'Floor 1',      10),
('Psychiatry',       'Mental health and behavioural medicine',       'Floor 7',       8);

-- ── Doctors ───────────────────────────────────────────────
INSERT INTO doctors (full_name, email, password_hash, phone, department_id, specialization, qualification, experience_years, license_number, consultation_fee, rating) VALUES
('Dr. Anil Mehta',      'doctor@medicare.com',  'admin123', '9876543210', 1, 'Interventional Cardiologist', 'MD, DM Cardiology',  14, 'MCI-CARD-001',  800.00, 4.8),
('Dr. Priya Sharma',    'priya@medicare.com',   'admin123', '9876543211', 2, 'Neurologist',                 'MD, DM Neurology',   10, 'MCI-NEURO-002', 900.00, 4.7),
('Dr. Suresh Iyer',     'suresh@medicare.com',  'admin123', '9876543212', 3, 'Orthopaedic Surgeon',        'MS Orthopaedics',    12, 'MCI-ORTH-003',  750.00, 4.6),
('Dr. Kavitha Nair',    'kavitha@medicare.com', 'admin123', '9876543213', 4, 'General Physician',          'MBBS, MD Medicine',   8, 'MCI-GEN-004',   500.00, 4.5),
('Dr. Rajesh Patel',    'rajesh@medicare.com',  'admin123', '9876543214', 5, 'Emergency Medicine',         'MBBS, DNB Emergency',11, 'MCI-EMER-005',  600.00, 4.9),
('Dr. Sneha Reddy',     'sneha@medicare.com',   'admin123', '9876543215', 6, 'Paediatrician',              'MD Paediatrics',      7, 'MCI-PED-006',   550.00, 4.7),
('Dr. Meera Joshi',     'meera@medicare.com',   'admin123', '9876543216', 7, 'Gynaecologist',              'MD, DGO Gynaecology', 9, 'MCI-GYN-007',   700.00, 4.8),
('Dr. Vikram Singh',    'vikram@medicare.com',  'admin123', '9876543217', 8, 'Oncologist',                 'MD, DM Oncology',    16, 'MCI-ONC-008',  1200.00, 4.9),
('Dr. Ananya Das',      'ananya@medicare.com',  'admin123', '9876543218', 9, 'Dermatologist',              'MD Dermatology',      6, 'MCI-DERM-009',  600.00, 4.6),
('Dr. Kiran Kumar',     'kiran@medicare.com',   'admin123', '9876543219',10, 'Psychiatrist',               'MD Psychiatry',       8, 'MCI-PSY-010',   700.00, 4.5);

-- ── Patients ──────────────────────────────────────────────
INSERT INTO patients (full_name, email, password_hash, phone, dob, gender, blood_group, address, city, state, pin_code, insurance_provider, allergies, chronic_conditions) VALUES
('Priya Verma',     'patient@medicare.com', 'admin123', '9123456789', '1990-05-14', 'Female', 'B+',  '14 Park Street',    'New Delhi', 'Delhi',         '110001', 'Star Health',   'Penicillin', 'Hypertension'),
('Rahul Singh',     'rahul@medicare.com',   'admin123', '9123456790', '1985-08-22', 'Male',   'O+',  '22 MG Road',        'Noida',     'Uttar Pradesh', '201301', 'HDFC Ergo',     NULL,         'Diabetes Type 2'),
('Anita Desai',     'anita@medicare.com',   'admin123', '9123456791', '1978-03-11', 'Female', 'A+',  '5 Nehru Colony',    'Gurgaon',   'Haryana',       '122001', 'Bajaj Allianz', 'Aspirin',    'Asthma'),
('Deepak Malhotra', 'deepak@medicare.com',  'admin123', '9123456792', '1965-11-30', 'Male',   'AB+', '8 DLF Phase 2',     'Gurgaon',   'Haryana',       '122002', NULL,            NULL,         'Coronary Artery Disease'),
('Sunita Rao',      'sunita@medicare.com',  'admin123', '9123456793', '1992-07-18', 'Female', 'O-',  '31 Sector 15',      'Faridabad', 'Haryana',       '121007', 'Star Health',   'Sulfa drugs',NULL),
('Amit Sharma',     'amit@medicare.com',    'admin123', '9123456794', '1988-01-05', 'Male',   'B-',  '17 Civil Lines',    'Jaipur',    'Rajasthan',     '302001', NULL,            NULL,         'Hypothyroidism'),
('Meghna Pillai',   'meghna@medicare.com',  'admin123', '9123456795', '2001-09-25', 'Female', 'A-',  '3 Koramangala',     'Bengaluru', 'Karnataka',     '560034', 'Religare',      NULL,         NULL),
('Sanjay Kapoor',   'sanjay@medicare.com',  'admin123', '9123456796', '1958-04-14', 'Male',   'AB-', '9 Model Town',      'Ludhiana',  'Punjab',        '141002', 'New India',     'NSAIDs',     'COPD, Hypertension'),
('Ritu Bhatia',     'ritu@medicare.com',    'admin123', '9123456797', '1975-12-02', 'Female', 'B+',  '22 Vasant Vihar',   'New Delhi', 'Delhi',         '110057', 'HDFC Ergo',     NULL,         'Migraine'),
('Harsh Agarwal',   'harsh@medicare.com',   'admin123', '9123456798', '1995-06-19', 'Male',   'O+',  '6 Patel Nagar',     'New Delhi', 'Delhi',         '110008', NULL,            NULL,         NULL),
('Kavya Nambiar',   'kavya@medicare.com',   'admin123', '9123456799', '1983-02-28', 'Female', 'A+',  '44 Jubilee Hills',  'Hyderabad', 'Telangana',     '500033', 'Star Health',   'Latex',      'Rheumatoid Arthritis'),
('Mohit Agrawal',   'mohit@medicare.com',   'admin123', '9123456800', '1970-08-08', 'Male',   'O+',  '12 Salt Lake',      'Kolkata',   'West Bengal',   '700064', 'Bajaj Allianz', NULL,         'Diabetes Type 2, Hypertension');

-- ── Beds ──────────────────────────────────────────────────
INSERT INTO beds (department_id, bed_number, ward_type, status, patient_id, admitted_at, expected_discharge, daily_charge) VALUES
(1,'ICU-01','ICU','Occupied',4,'2026-04-06 08:00:00','2026-04-12',3500),
(1,'ICU-02','ICU','Occupied',8,'2026-04-07 10:00:00','2026-04-11',3500),
(1,'ICU-03','ICU','Available',NULL,NULL,NULL,3500),
(1,'ICU-04','ICU','Reserved',NULL,NULL,NULL,3500),
(1,'CAR-01','Private','Occupied',4,'2026-04-06 08:00:00','2026-04-12',2500),
(1,'CAR-02','Semi-Private','Available',NULL,NULL,NULL,1500),
(2,'NEU-01','General','Occupied',9,'2026-04-08 09:00:00','2026-04-13',1200),
(2,'NEU-02','Private','Available',NULL,NULL,NULL,2200),
(3,'ORT-01','General','Occupied',6,'2026-04-05 11:00:00','2026-04-12',1200),
(3,'ORT-02','Semi-Private','Maintenance',NULL,NULL,NULL,1600),
(4,'GEN-01','General','Available',NULL,NULL,NULL,800),
(4,'GEN-02','General','Available',NULL,NULL,NULL,800),
(4,'GEN-03','General','Occupied',12,'2026-04-08 07:00:00','2026-04-10',800),
(5,'ER-01','Emergency','Occupied',5,'2026-04-09 01:00:00','2026-04-10',4000),
(5,'ER-02','Emergency','Available',NULL,NULL,NULL,4000),
(6,'PED-01','General','Occupied',7,'2026-04-07 16:00:00','2026-04-11',1000),
(7,'GYN-01','Private','Available',NULL,NULL,NULL,2000),
(8,'ONC-01','Private','Occupied',11,'2026-04-03 09:00:00','2026-04-15',3000),
(9,'DERM-01','General','Available',NULL,NULL,NULL,700),
(10,'PSY-01','Private','Reserved',NULL,NULL,NULL,1800);

-- ── Staff ─────────────────────────────────────────────────
INSERT INTO staff (full_name, email, phone, role, department_id, shift, workload_score, joined_at) VALUES
('Nurse Geeta Singh',    'geeta@medicare.com',   '9811100001','Nurse',       1,'Morning',    72,'2021-03-15'),
('Nurse Poonam Yadav',   'poonam@medicare.com',  '9811100002','Nurse',       1,'Night',      88,'2020-07-20'),
('Nurse Sundar Rajan',   'sundar@medicare.com',  '9811100003','Nurse',       5,'Rotational', 95,'2019-11-10'),
('Tech. Rohan Das',      'rohan@medicare.com',   '9811100004','Technician',  2,'Morning',    60,'2022-01-05'),
('Tech. Manish Jha',     'manish@medicare.com',  '9811100005','Technician',  4,'Afternoon',  55,'2022-06-18'),
('Pharm. Nidhi Tiwari',  'nidhi@medicare.com',   '9811100006','Pharmacist',  4,'Morning',    70,'2020-09-01'),
('Recep. Anjali Gupta',  'anjali@medicare.com',  '9811100007','Receptionist',4,'Morning',    65,'2023-02-14'),
('Recep. Vikash Kumar',  'vikash@medicare.com',  '9811100008','Receptionist',5,'Rotational', 80,'2021-08-22'),
('Nurse Leela Kumari',   'leela@medicare.com',   '9811100009','Nurse',       6,'Morning',    68,'2021-05-30'),
('Nurse Arjun Menon',    'arjun_n@medicare.com', '9811100010','Nurse',       3,'Afternoon',  74,'2020-12-01'),
('Tech. Bhavna Shah',    'bhavna@medicare.com',  '9811100011','Technician',  8,'Morning',    62,'2022-04-17'),
('Pharm. Dinesh Pandey', 'dinesh@medicare.com',  '9811100012','Pharmacist',  1,'Rotational', 85,'2019-06-03');

-- ── Appointments ──────────────────────────────────────────
INSERT INTO appointments (patient_id, doctor_id, department_id, appointment_date, appointment_time, type, status, chief_complaint, symptoms, ai_priority_score, ai_triage_label, token_number) VALUES
-- Today
(1,  1, 1, CURDATE(), '09:00:00', 'OPD',       'Scheduled',   'Chest pain and shortness of breath', 'Chest tightness, dyspnea on exertion, mild sweating', 82, 'Urgent',   101),
(2,  4, 4, CURDATE(), '09:20:00', 'OPD',       'Confirmed',   'Blood sugar control follow-up',      'Polydipsia, fatigue, blurred vision',                 45, 'Moderate', 102),
(3,  4, 4, CURDATE(), '09:40:00', 'Follow-up', 'Scheduled',   'Asthma management review',           'Wheezing, shortness of breath on exertion',           38, 'Routine',  103),
(5,  5, 5, CURDATE(), '10:00:00', 'Emergency', 'In Progress', 'Severe abdominal pain',              'Acute cramping, nausea, fever 101F',                  90, 'Critical', 104),
(6,  3, 3, CURDATE(), '10:20:00', 'OPD',       'Scheduled',   'Knee pain worsening',                'Right knee swelling, difficulty walking, stiffness',  55, 'Moderate', 105),
(7,  6, 6, CURDATE(), '10:40:00', 'OPD',       'Scheduled',   'Vaccination and growth check',        'Routine check, no active complaints',                 20, 'Routine',  106),
(9,  2, 2, CURDATE(), '11:00:00', 'Follow-up', 'Confirmed',   'Migraine frequency increasing',       'Headache 3-4 times/week, photophobia, nausea',        68, 'Moderate', 107),
(10, 9, 9, CURDATE(), '11:20:00', 'OPD',       'Scheduled',   'Skin rash and itching',               'Erythematous rash, pruritus, localised to forearm',   30, 'Routine',  108),
(11, 7, 7, CURDATE(), '11:40:00', 'OPD',       'Scheduled',   'Menstrual irregularity',              'Irregular cycles, dysmenorrhoea, mood changes',       35, 'Routine',  109),
(12, 1, 1, CURDATE(), '14:00:00', 'Follow-up', 'Scheduled',   'Hypertension management',             'BP 150/95 at home, headache, dizziness',              60, 'Moderate', 110),
-- Yesterday
(1,  4, 4, DATE_SUB(CURDATE(),INTERVAL 1 DAY), '09:00:00', 'OPD',       'Completed', 'BP check',             'Mildly elevated BP',                    30, 'Routine',  98),
(2,  1, 1, DATE_SUB(CURDATE(),INTERVAL 1 DAY), '10:00:00', 'Follow-up', 'Completed', 'Cardiac follow-up',    'Stable angina, no new symptoms',        45, 'Moderate', 99),
(4,  1, 1, DATE_SUB(CURDATE(),INTERVAL 1 DAY), '11:00:00', 'Follow-up', 'Completed', 'Post-cath follow-up',  'Stable, dischargeable',                 70, 'Moderate', 100),
(8,  4, 4, DATE_SUB(CURDATE(),INTERVAL 1 DAY), '14:00:00', 'OPD',       'Completed', 'COPD exacerbation',    'Increased sputum, breathlessness',      75, 'Urgent',   95),
-- 2 days ago
(3,  2, 2, DATE_SUB(CURDATE(),INTERVAL 2 DAY), '09:30:00', 'OPD',       'Completed', 'Migraine headache',    'Severe left-sided headache, vomiting',  65, 'Moderate', 90),
(5,  4, 4, DATE_SUB(CURDATE(),INTERVAL 2 DAY), '10:30:00', 'OPD',       'Completed', 'UTI symptoms',         'Dysuria, frequency, low-grade fever',   50, 'Moderate', 91),
(6,  3, 3, DATE_SUB(CURDATE(),INTERVAL 2 DAY), '11:30:00', 'OPD',       'Completed', 'Lower back pain',      'Chronic low back pain, radiating left', 55, 'Moderate', 92),
-- 3 days ago
(7,  6, 6, DATE_SUB(CURDATE(),INTERVAL 3 DAY), '09:00:00', 'OPD',       'Completed', 'Fever and cold',       'Fever 102F, runny nose, cough',          40, 'Moderate', 85),
(9,  2, 2, DATE_SUB(CURDATE(),INTERVAL 3 DAY), '10:00:00', 'OPD',       'Completed', 'Migraine evaluation',  'New onset severe headaches',            65, 'Urgent',   86),
(10, 4, 4, DATE_SUB(CURDATE(),INTERVAL 3 DAY), '11:00:00', 'OPD',       'Completed', 'General checkup',      'Annual health exam',                    15, 'Routine',  87),
-- Upcoming
(1,  1, 1, DATE_ADD(CURDATE(),INTERVAL 3 DAY), '10:00:00', 'Follow-up', 'Scheduled', 'Cardiac stress test review',  NULL, 65, 'Moderate', 111),
(2,  4, 4, DATE_ADD(CURDATE(),INTERVAL 5 DAY), '09:20:00', 'Follow-up', 'Scheduled', 'HbA1c result review',         NULL, 45, 'Moderate', 112),
(3,  4, 4, DATE_ADD(CURDATE(),INTERVAL 7 DAY), '11:00:00', 'OPD',       'Scheduled', 'Allergy follow-up',           NULL, 30, 'Routine',  113),
(11, 8, 8, DATE_ADD(CURDATE(),INTERVAL 2 DAY), '09:00:00', 'Follow-up', 'Confirmed', 'Chemotherapy session 4',      NULL, 80, 'Urgent',   114),
(12, 1, 1, DATE_ADD(CURDATE(),INTERVAL 4 DAY), '14:00:00', 'Follow-up', 'Scheduled', 'BP and diabetes review',      NULL, 60, 'Moderate', 115);

-- ── Vitals ────────────────────────────────────────────────
INSERT INTO vitals (patient_id, appointment_id, recorded_by, blood_pressure_systolic, blood_pressure_diastolic, pulse_rate, temperature, spo2, weight_kg, height_cm, bmi, blood_sugar_fasting, ai_risk_flag, recorded_at) VALUES
(1,  11, 4, 128, 84,  76,  98.4, 98, 62,  162, 23.6,  92, 'Mild Hypertension',                  DATE_SUB(NOW(),INTERVAL 1 DAY)),
(1,   1, 1, 142, 92,  88,  98.8, 97, 62,  162, 23.6,  95, 'Hypertension, Tachycardia',           NOW()),
(2,  12, 1, 130, 82,  82,  98.6, 98, 84,  172, 28.4, 185, 'Elevated Blood Sugar',                DATE_SUB(NOW(),INTERVAL 1 DAY)),
(3,  15, 2, 118, 76,  72,  99.2, 98, 58,  158, 23.2,  88, NULL,                                  DATE_SUB(NOW(),INTERVAL 2 DAY)),
(4,  13, 1, 135, 88,  96,  98.4, 96, 91,  170, 31.5, 102, 'Hypertension, Pre-diabetic',          DATE_SUB(NOW(),INTERVAL 1 DAY)),
(5,  16, 4, 116, 74,  80, 101.2, 97, 55,  160, 21.5, NULL, 'Fever',                              DATE_SUB(NOW(),INTERVAL 2 DAY)),
(5,   4, 5, 120, 76, 102, 101.8, 97, 55,  160, 21.5, NULL, 'Tachycardia, Fever',                 NOW()),
(6,  17, 3, 122, 80,  78,  98.6, 99, 79,  175, 25.8, NULL, NULL,                                 DATE_SUB(NOW(),INTERVAL 2 DAY)),
(7,  18, 6, 100, 66,  88, 102.0, 98, 20,  105, 18.1, NULL, 'Fever',                              DATE_SUB(NOW(),INTERVAL 3 DAY)),
(8,  14, 4, 148, 94,  92,  98.8, 92, 78,  168, 27.6, NULL, 'Hypertension, Low SpO2',             DATE_SUB(NOW(),INTERVAL 1 DAY)),
(9,  19, 2, 124, 82,  74,  98.4, 99, 60,  164, 22.3, NULL, NULL,                                 DATE_SUB(NOW(),INTERVAL 3 DAY)),
(10, 20, 4, 118, 76,  72,  98.6, 99, 72,  178, 22.7,  88, NULL,                                  DATE_SUB(NOW(),INTERVAL 3 DAY)),
(11,NULL, 8, 110, 70,  84,  98.2, 98, 52,  158, 20.8, NULL, 'Low Weight (Oncology)',             DATE_SUB(NOW(),INTERVAL 5 DAY)),
(12,NULL, 1, 158, 96,  90,  98.6, 95, 95,  174, 31.4, 195, 'Critical Hypertension, High Sugar',  DATE_SUB(NOW(),INTERVAL 1 DAY));

-- ── Medical Records ───────────────────────────────────────
INSERT INTO medical_records (patient_id, doctor_id, appointment_id, chief_complaint, history, examination, diagnosis, icd10_code, prescription, instructions, follow_up_date, ai_summary, ai_risk_level) VALUES
(1,  4, 11, 'BP check',           'Known hypertensive on Amlodipine 5mg',                        'BP 128/84, HR 76, clear lungs',              'Essential Hypertension — well controlled',    'I10',   'Continue Amlodipine 5mg OD',                          'Low salt diet, exercise 30 min daily',       DATE_ADD(CURDATE(),INTERVAL 14 DAY), 'Well-controlled hypertension on current medication. Lifestyle modifications recommended.',  'Low'),
(2,  1, 12, 'Cardiac evaluation', 'Diabetic with HbA1c 8.2, exertional chest discomfort',       'BP 130/82, HR 82, mild cardiomegaly on CXR','Type 2 DM with suspected stable angina',      'I25.1', 'Metformin 500mg BD, Aspirin 75mg OD, Atorvastatin 20mg','Stress test scheduled, avoid strenuous activity',DATE_ADD(CURDATE(),INTERVAL 7 DAY),  'Diabetic with cardiac risk factors. Stable angina suspected. Stress test warranted.',      'High'),
(3,  2, 15, 'Migraine headache',  'Episodic migraines 1-2/month, worsening over 6 months',      'No focal neuro deficits, BP normal',         'Migraine without aura (chronic)',             'G43.9', 'Sumatriptan 50mg PRN, Propranolol 40mg OD',           'Avoid triggers: caffeine, stress',            DATE_ADD(CURDATE(),INTERVAL 30 DAY), 'Chronic migraine with increasing frequency. Prophylactic therapy initiated.',             'Moderate'),
(4,  1, 13, 'Post-cath follow-up','LAD stenting done 6 days ago. No chest pain since.',          'BP 135/88, HR 96, stent site healing well', 'Coronary artery disease — post PCI',          'I25.1', 'Aspirin 150mg OD, Clopidogrel 75mg OD, Ramipril 5mg OD','Absolute bed rest, cardiac rehab in 2 weeks',DATE_ADD(CURDATE(),INTERVAL 5 DAY),  'Post-PCI patient doing well. DAPT ongoing. Close monitoring required.',                   'High'),
(5,  4, 16, 'UTI',                'Burning micturition, increased frequency, 3 days',            'Suprapubic tenderness, no CVA tenderness',   'Urinary tract infection (uncomplicated)',      'N39.0', 'Nitrofurantoin 100mg BD x 5 days',                    'Increase fluid intake, complete antibiotic',  DATE_ADD(CURDATE(),INTERVAL 7 DAY),  'Simple UTI. Short-course antibiotics initiated. Urine culture sent.',                    'Low'),
(6,  3, 17, 'Knee pain',          'Right knee OA with grade 2 changes on X-ray',                'Right knee: crepitus, mild effusion',        'Osteoarthritis right knee — Grade II',        'M17.1', 'Diclofenac 50mg BD with food, Pantoprazole 40mg OD',  'Physio exercises, weight reduction',          DATE_ADD(CURDATE(),INTERVAL 21 DAY), 'Grade II OA confirmed radiologically. NSAIDs with GI protection. Physio advised.',       'Moderate'),
(7,  6, 18, 'Fever and cold',     'High-grade fever x 2 days, rhinorrhoea, mild cough',         'Temp 102F, mild pharyngeal congestion',      'Viral upper respiratory tract infection',      'J06.9', 'Paracetamol 500mg TDS, Cetirizine 10mg HS',           'Rest, oral fluids, follow up if worsening',   DATE_ADD(CURDATE(),INTERVAL 5 DAY),  'Viral URTI — symptomatic management. No antibiotics indicated.',                         'Low'),
(8,  4, 14, 'COPD exacerbation',  'Smoker, COPD GOLD stage 3, increased breathlessness',        'SpO2 92%, bilateral wheeze',                 'COPD exacerbation (moderate)',                'J44.1', 'Prednisolone 40mg OD x5, Azithromycin 500mg OD x3',  'Smoking cessation mandatory, pulmonary rehab',DATE_ADD(CURDATE(),INTERVAL 5 DAY),  'Moderate COPD exacerbation. Steroids and antibiotics started. SpO2 monitoring required.', 'High'),
(9,  2, 19, 'Migraine evaluation','New onset headaches, 3-4 per week, left-sided, 7/10',        'Normal neurological exam, no papilloedema',  'New-onset migraine — MRI ordered',            'G43.0', 'Ibuprofen 400mg PRN, Metoprolol 25mg OD',             'Sleep hygiene, keep headache diary',          DATE_ADD(CURDATE(),INTERVAL 14 DAY), 'Frequent new-onset migraines. MRI Brain ordered. Prophylaxis initiated.',                 'Moderate'),
(12, 1,NULL, 'Hypertensive urgency','BP 190/110 at home, headache',                              'BP 158/96 now, HR 90, fundoscopy normal',    'Hypertensive urgency',                        'I16.0', 'Amlodipine 10mg OD, Telmisartan 40mg OD, Aspirin 75mg','24-hour BP monitoring, strict low-sodium diet',DATE_ADD(CURDATE(),INTERVAL 3 DAY), 'Hypertensive urgency managed. Combination therapy initiated.',                            'Critical');

-- ── Prescriptions ─────────────────────────────────────────
INSERT INTO prescriptions (record_id, patient_id, doctor_id, medicine_name, dosage, frequency, duration_days, instructions) VALUES
(1,  1,  4, 'Amlodipine',     '5mg',   'Once daily morning',         90, 'Take with or without food'),
(2,  2,  1, 'Metformin',      '500mg', 'Twice daily with meals',     30, 'Take with breakfast and dinner'),
(2,  2,  1, 'Aspirin',        '75mg',  'Once daily morning',         30, 'Take after food'),
(2,  2,  1, 'Atorvastatin',   '20mg',  'Once daily at night',        30, 'Take at bedtime'),
(3,  3,  2, 'Sumatriptan',    '50mg',  'As needed for migraine',     15, 'Take at onset, max 2 doses/24h'),
(3,  3,  2, 'Propranolol',    '40mg',  'Once daily',                 30, 'Do not stop abruptly'),
(4,  4,  1, 'Aspirin',        '150mg', 'Once daily morning',         90, 'After food'),
(4,  4,  1, 'Clopidogrel',    '75mg',  'Once daily morning',         90, 'Do not miss any dose'),
(4,  4,  1, 'Ramipril',       '5mg',   'Once daily morning',         90, 'Monitor BP and creatinine'),
(4,  4,  1, 'Atorvastatin',   '40mg',  'Once daily at night',        90, 'High-intensity statin'),
(5,  5,  4, 'Nitrofurantoin', '100mg', 'Twice daily',                 5, 'Take with food, complete course'),
(6,  6,  3, 'Diclofenac',     '50mg',  'Twice daily with food',      14, 'Always take with food'),
(6,  6,  3, 'Pantoprazole',   '40mg',  'Once daily before meals',   14, 'Take 30 min before breakfast'),
(7,  7,  6, 'Paracetamol',    '500mg', 'Three times daily',           5, 'For fever only, max 4g/day'),
(8,  8,  4, 'Prednisolone',   '40mg',  'Once daily morning',          5, 'Do not stop abruptly'),
(8,  8,  4, 'Azithromycin',   '500mg', 'Once daily',                  3, 'Take at same time each day'),
(9,  9,  2, 'Metoprolol',     '25mg',  'Once daily morning',         30, 'Prophylactic migraine prevention'),
(10,12,  1, 'Amlodipine',     '10mg',  'Once daily morning',         30, 'Monitor BP daily'),
(10,12,  1, 'Telmisartan',    '40mg',  'Once daily morning',         30, 'Take on empty stomach');

-- ── Lab Reports ───────────────────────────────────────────
INSERT INTO lab_reports (patient_id, doctor_id, appointment_id, report_type, report_date, remarks, ai_interpretation) VALUES
(1,  4, 11, 'Lipid Profile',        DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'TC 198, LDL 122, HDL 48, TG 145',               'Borderline LDL. HDL slightly low. Recommend dietary changes and exercise.'),
(1,  4, 11, 'Complete Blood Count', DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'Hb 13.2, WBC 7800, Plt 210000',                 'All parameters within normal range.'),
(2,  1, 12, 'HbA1c',               DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'HbA1c: 8.2% (Target <7%)',                       'Suboptimal glycaemic control. Medication adjustment recommended.'),
(2,  1, 12, 'Fasting Blood Sugar',  DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'FBS: 185 mg/dL (Ref: 70-100)',                  'Significantly elevated fasting glucose. Medication review needed.'),
(2,  1, 12, 'ECG',                  DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'NSR, rate 82/min, LVH changes',                 'LVH pattern noted. Echocardiogram recommended.'),
(3,  2, 15, 'MRI Brain (Plain)',    DATE_SUB(CURDATE(),INTERVAL 2 DAY), 'No space occupying lesion, no infarcts',         'Normal MRI Brain. Migraine diagnosis supported.'),
(4,  1, 13, 'Echo Post-PCI',        DATE_SUB(CURDATE(),INTERVAL 6 DAY), 'EF 50%, anterior wall motion improving',         'EF 50% with anterior wall recovery. Good prognosis.'),
(5,  4, 16, 'Urine Culture',        DATE_SUB(CURDATE(),INTERVAL 2 DAY), 'E.coli growth, sensitive to Nitrofurantoin',    'E.coli UTI confirmed. Prescribed Nitrofurantoin is appropriate.'),
(6,  3, 17, 'X-Ray Right Knee',     DATE_SUB(CURDATE(),INTERVAL 2 DAY), 'Grade II OA changes, mild joint space narrowing','Grade II osteoarthritis. Physio and weight loss can slow progression.'),
(8,  4, 14, 'Chest X-Ray',          DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'Hyperinflation, flat diaphragm, no consolidation','CXR consistent with COPD. Pneumonia excluded.'),
(9,  2, 19, 'MRI Brain (Plain)',    DATE_SUB(CURDATE(),INTERVAL 3 DAY), 'Normal study — no mass or vascular lesion',      'Normal MRI. Primary migraine diagnosis confirmed.'),
(11, 8,NULL, 'CA-125 Tumour Marker',DATE_SUB(CURDATE(),INTERVAL 4 DAY), 'CA-125: 68 U/mL (Ref <35)',                      'Elevated CA-125. Partial treatment response. Continue monitoring.'),
(11, 8,NULL, 'CBC Post Chemo',       DATE_SUB(CURDATE(),INTERVAL 4 DAY), 'Hb 9.8, WBC 3200 (low), Plt 95000 (low)',       'Myelosuppression post-chemo. Neutropenia risk. Monitor closely.'),
(12, 1,NULL, 'Renal Function Test',  DATE_SUB(CURDATE(),INTERVAL 1 DAY), 'Creatinine 1.1, Urea 28, eGFR 68',             'Early CKD Stage 2. Important for antihypertensive drug choice.');

-- ── Bills ─────────────────────────────────────────────────
INSERT INTO bills (patient_id, appointment_id, bill_number, consultation_fee, medicine_charges, lab_charges, bed_charges, other_charges, discount, tax, total_amount, paid_amount, payment_method, payment_status, insurance_claimed, ai_fraud_flag) VALUES
(1,  11, 'MCB-2026-0001',  500,  320,  850,     0,  100,   0, 176.7,  1946.7,  1946.7, 'UPI',       'Paid',    0, 0),
(2,  12, 'MCB-2026-0002',  800,  850, 1800,     0,  200,   0, 365.5,  4015.5,  2000.0, 'Card',      'Partial', 0, 0),
(3,  15, 'MCB-2026-0003',  900,  280,  400,     0,   50,   0, 163.5,  1793.5,  1793.5, 'Cash',      'Paid',    0, 0),
(4, NULL, 'MCB-2026-0004', 800, 2400, 4500, 21000,  500,   0,2920.0, 32120.0, 20000.0, 'Insurance', 'Partial', 1, 0),
(5,  16, 'MCB-2026-0005',  500,  180,  300,     0,   50,   0, 103.0,  1133.0,  1133.0, 'Cash',      'Paid',    0, 0),
(6,  17, 'MCB-2026-0006',  750,  620,  850,     0,   80,   0, 230.0,  2530.0,  1265.0, 'UPI',       'Partial', 0, 0),
(7,  18, 'MCB-2026-0007',  550,  120,    0,     0,   50,   0,  72.0,   792.0,   792.0, 'Cash',      'Paid',    0, 0),
(8,  14, 'MCB-2026-0008',  500,  680, 1200,  3500,  200,   0, 608.8,  6688.8,  3000.0, 'Insurance', 'Partial', 1, 0),
(9,  19, 'MCB-2026-0009',  900,  400,  900,     0,  100,   0, 230.0,  2530.0,  2530.0, 'Card',      'Paid',    0, 0),
(11,NULL, 'MCB-2026-0010',1200, 8500, 2800, 21000,  800, 500,3384.0, 37184.0, 20000.0, 'Insurance', 'Partial', 1, 0),
(12,NULL, 'MCB-2026-0011', 800, 1200,  800,     0,  100,   0, 288.0,  3168.0,     0.0, 'Pending',   'Pending', 0, 0),
(2,  12, 'MCB-2026-0012',  800,12500, 3200,     0,  900,   0,1740.0, 19140.0,  5000.0, 'Cash',      'Partial', 0, 1),
(8,  14, 'MCB-2026-0013',  500, 8800,  400,  3500, 2500,   0,1590.0, 17490.0,  2000.0, 'Cash',      'Partial', 0, 1);

-- ── Insurance Claims ──────────────────────────────────────
INSERT INTO insurance_claims (patient_id, bill_id, provider, policy_number, claim_amount, status, ai_approval_score) VALUES
(4,  4, 'New India Assurance', 'NIA-CAR-44291', 30000.00, 'Under Review', 78),
(8,  8, 'Bajaj Allianz',       'BAJ-GEN-18832',  5000.00, 'Approved',     85),
(11,10, 'Star Health',         'STR-ONC-00291', 35000.00, 'Submitted',    62);

-- ── Notifications ─────────────────────────────────────────
INSERT INTO notifications (user_id, user_role, title, message, type, ai_generated) VALUES
(1, 'admin',   'AI Alert: High Bed Occupancy',   'ICU occupancy at 75%. AI predicts full capacity in 8 hours. Consider patient transfers.',           'alert',   1),
(1, 'admin',   'Billing Fraud Flags Detected',    '2 billing records flagged for unusual medicine charges. Review MCB-2026-0012 and MCB-2026-0013.',   'warning', 1),
(1, 'admin',   'Staff Overload Warning',           'Nurse Sundar Rajan (Emergency) workload: 95%. Shift reallocation recommended.',                    'warning', 1),
(1, 'doctor',  'Today\'s Queue Ready',             'You have 10 appointments scheduled today. First patient at 09:00 AM.',                             'info',    0),
(1, 'doctor',  'Critical Patient Alert',           'Patient Deepak Malhotra (Bed ICU-01) — BP elevated. Please review immediately.',                   'alert',   1),
(1, 'patient', 'Appointment Reminder',             'Your appointment with Dr. Anil Mehta is in 3 days at 10:00 AM. Please arrive 15 min early.',       'info',    0),
(1, 'patient', 'Lab Report Ready',                 'Your Lipid Profile report is now available. Go to Lab Reports to view it.',                        'info',    0),
(1, 'patient', 'AI Health Insight',                'Your recent vitals suggest mild hypertension. Consider reducing sodium intake and monitoring BP.', 'info',    1);

-- ── AI Logs ───────────────────────────────────────────────
INSERT INTO ai_logs (module, feature, user_id, user_role, latency_ms, success) VALUES
('admin',   'bed_forecast',       1, 'admin',   1820, 1),
('admin',   'billing_anomaly',    1, 'admin',   2100, 1),
('admin',   'staff_optimization', 1, 'admin',   1650, 1),
('doctor',  'diagnosis_support',  1, 'doctor',  2340, 1),
('doctor',  'drug_interaction',   1, 'doctor',  1420, 1),
('doctor',  'soap_notes',         1, 'doctor',  2080, 1),
('patient', 'symptom_checker',    1, 'patient', 1760, 1),
('patient', 'report_summary',     1, 'patient', 2200, 1),
('patient', 'health_insights',    1, 'patient', 1500, 1);

SELECT CONCAT(
  'Seed data loaded! ',
  (SELECT COUNT(*) FROM patients),    ' patients, ',
  (SELECT COUNT(*) FROM doctors),     ' doctors, ',
  (SELECT COUNT(*) FROM appointments),' appointments, ',
  (SELECT COUNT(*) FROM medical_records), ' records, ',
  (SELECT COUNT(*) FROM lab_reports), ' lab reports, ',
  (SELECT COUNT(*) FROM bills),       ' bills, ',
  (SELECT COUNT(*) FROM beds),        ' beds, ',
  (SELECT COUNT(*) FROM staff),       ' staff.'
) AS status;
