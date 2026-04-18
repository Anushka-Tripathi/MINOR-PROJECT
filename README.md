# MediCare Pro — AI-Powered Hospital Management System
## v2.0 | Patent-Ready Architecture

---

## Project Structure

```
Medicare-Pro/
├── index.html                      # Main landing page
├── README.md
│
├── admin/
│   ├── login.html                  # Admin login
│   └── dashboard.html              # Full admin command centre
│
├── doctor/
│   ├── login.html                  # Doctor login
│   └── dashboard.html              # Clinical suite with AI tools
│
├── patient/
│   ├── login.html                  # Patient login
│   └── dashboard.html              # Patient care portal
│
├── assets/
│   ├── css/styles.css              # Unified design system (2400+ lines)
│   └── js/medicare.js             # Global JS utilities & AI helpers
│
├── backend/
│   ├── config/
│   │   └── db.php                 # PDO singleton, helpers, constants
│   └── api/
│       ├── login.php              # MySQL-backed auth for all roles
│       ├── ai.php                 # Anthropic AI proxy (15+ features)
│       ├── admin.php              # Admin CRUD API
│       ├── appointments.php       # Appointments CRUD
│       └── medical.php            # Records, vitals, labs API
│
└── database/
    └── schema.sql                 # Complete normalized schema + seed data
```

---

## Quick Setup

### 1. Database
```sql
mysql -u root -p < database/schema.sql
```

### 2. PHP Configuration
Edit `backend/config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'medicare_pro');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('GEMINI_API_KEY', 'sk-ant-api...');  // ← Add your key
```

### 3. Web Server
- Use XAMPP / WAMP / Laragon (Windows) or LAMP (Linux)
- Place project in `htdocs/` or `/var/www/html/`
- Access at `http://localhost/Medicare-Pro/`

### 4. Get Gemini API Key
- Visit https://aistudio.google.com
- Create a new API key
- Paste in `db.php` as shown above

---

## Demo Credentials
| Role    | Email                   | Password  |
|---------|-------------------------|-----------|
| Admin   | admin@medicare.com      | admin123  |
| Doctor  | doctor@medicare.com     | admin123  |
| Patient | patient@medicare.com    | admin123  |

---

## AI Features (15+)

### Admin Module
| Feature | Description |
|---------|-------------|
| Bed Prediction Engine | 8-hour ward occupancy forecast by ward type |
| Staff Workload Optimizer | Fatigue scoring + shift rebalancing |
| Billing Fraud Detector | Anomaly detection across all invoices |
| Executive Analytics Summary | AI-generated KPI report with action items |
| Insurance Verification | AI-scored claim approval probability |
| Admin AI Chat | Operations assistant chatbot |

### Doctor Module
| Feature | Description |
|---------|-------------|
| Differential Diagnosis AI | Symptom-based ranked diagnoses + probability % |
| Drug Interaction Checker | Multi-drug safety check with severity ratings |
| SOAP Note Generator | Structured clinical documentation in seconds |
| Patient Risk Stratification | Clinical risk score 0–100 with contributing factors |
| Prescription Assistant | AI-suggested medicines with contraindication alerts |
| Clinical AI Copilot Chat | Evidence-based medical Q&A assistant |

### Patient Module
| Feature | Description |
|---------|-------------|
| AI Symptom Checker | Condition assessment + urgency triage |
| Smart Appointment Booking | AI routes to right doctor/department |
| Lab Report Summarizer | Plain-language test result explanation |
| AI Medication Reminders | Personalized schedule + missed dose guidance |
| Health Insights | Personalized wellness score + recommendations |
| Patient AI Chat | 24/7 personal health assistant |

---

## Technology Stack
- **Frontend**: HTML5, CSS3 (CSS Variables, Grid, Flexbox), Vanilla JS
- **Backend**: PHP 8+ (PDO, REST APIs, session auth)
- **Database**: MySQL 8 (12 normalized tables, foreign keys, indexing)
- **AI Layer**: Gemini API (gemini-2.5-flash)
- **Icons**: Font Awesome 6.5
- **Fonts**: Sora + DM Mono (Google Fonts)
- **Auth**: bcrypt password hashing, PHP sessions

---

## Patent-Ready Innovations

1. **Unified AI Proxy Architecture** — Single PHP endpoint routing 15+ medical AI tasks to Gemini with role-based system prompts and structured JSON output
2. **Triage-Integrated Booking** — AI urgency scoring embedded in appointment creation flow
3. **Clinical Risk Score Layer** — Real-time patient risk stratification computed per consultation
4. **Bed Prediction Model** — Time-of-day aware occupancy forecasting with ward-level granularity
5. **Billing Anomaly Detection** — AI pattern recognition layer on top of traditional billing system

---

## Database Schema (12 Tables)
- `admins`, `doctors`, `patients` — User tables with bcrypt auth
- `departments` — Hospital departments with head doctor linkage
- `appointments` — Full scheduling with AI triage fields
- `vitals` — Clinical measurements with AI risk flags
- `medical_records` — EMR with AI summaries and ICD-10 codes
- `prescriptions` — Medicine records with AI interaction flags
- `lab_reports` — Test results with AI interpretations
- `beds` — Real-time bed management with discharge predictions
- `staff` — Staff with AI workload scores
- `bills`, `insurance_claims` — Financial records with fraud flags
- `ai_logs`, `ai_chat_sessions`, `ai_chat_messages` — Full AI audit trail
- `notifications` — System alerts including AI-generated ones

---

© 2026 MediCare Pro. All rights reserved.
