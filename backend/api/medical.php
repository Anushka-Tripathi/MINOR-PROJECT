<?php
// ─────────────────────────────────────────────
//  MediCare Pro — Medical Records API
// ─────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { jsonResponse(['ok' => true]); }

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$action = sanitize($_GET['action'] ?? 'records');

switch ($action) {
    case 'records':       handleRecords($pdo, $method, $input);       break;
    case 'vitals':        handleVitals($pdo, $method, $input);        break;
    case 'labs':          handleLabs($pdo, $method, $input);          break;
    case 'patient':       handlePatientProfile($pdo, $input);         break;
    case 'prescriptions': handlePrescriptions($pdo, $method, $input); break;
    default:              jsonResponse(['success' => false, 'error' => 'Unknown action.'], 400);
}

// ── Medical Records ───────────────────────────
function handleRecords(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $patientId = (int)($_GET['patient_id'] ?? 0);
        $doctorId  = (int)($_GET['doctor_id']  ?? 0);
        $limit     = (int)($_GET['limit'] ?? 20);

        $where = ['1=1']; $params = [];
        if ($patientId) { $where[] = 'mr.patient_id = ?'; $params[] = $patientId; }
        if ($doctorId)  { $where[] = 'mr.doctor_id = ?';  $params[] = $doctorId;  }

        $sql = "SELECT mr.*, p.full_name AS patient_name, d.full_name AS doctor_name
                FROM medical_records mr
                JOIN patients p ON p.id = mr.patient_id
                JOIN doctors  d ON d.id = mr.doctor_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY mr.created_at DESC LIMIT {$limit}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $required = ['patient_id', 'doctor_id', 'diagnosis'];
        foreach ($required as $f) {
            if (empty($input[$f])) jsonResponse(['success' => false, 'error' => "Missing: {$f}"], 400);
        }

        $pdo->prepare("INSERT INTO medical_records
            (patient_id, doctor_id, appointment_id, chief_complaint, history, examination, diagnosis, icd10_code, prescription, instructions, follow_up_date, ai_summary, ai_risk_level)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            (int)$input['patient_id'],
            (int)$input['doctor_id'],
            !empty($input['appointment_id']) ? (int)$input['appointment_id'] : null,
            sanitize($input['chief_complaint'] ?? ''),
            sanitize($input['history'] ?? ''),
            sanitize($input['examination'] ?? ''),
            sanitize($input['diagnosis']),
            sanitize($input['icd10_code'] ?? ''),
            sanitize($input['prescription'] ?? ''),
            sanitize($input['instructions'] ?? ''),
            !empty($input['follow_up_date']) ? $input['follow_up_date'] : null,
            sanitize($input['ai_summary'] ?? ''),
            sanitize($input['ai_risk_level'] ?? 'Low'),
        ]);

        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Record saved.']);
    }
}

// ── Vitals ────────────────────────────────────
function handleVitals(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $patientId = (int)($_GET['patient_id'] ?? 0);
        if (!$patientId) jsonResponse(['success' => false, 'error' => 'Missing patient_id'], 400);

        $stmt = $pdo->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 10");
        $stmt->execute([$patientId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $bmi = null;
        if (!empty($input['weight_kg']) && !empty($input['height_cm'])) {
            $h = $input['height_cm'] / 100;
            $bmi = round($input['weight_kg'] / ($h * $h), 2);
        }

        $pdo->prepare("INSERT INTO vitals
            (patient_id, appointment_id, recorded_by, blood_pressure_systolic, blood_pressure_diastolic, pulse_rate, temperature, spo2, weight_kg, height_cm, bmi, blood_sugar_fasting, ai_risk_flag)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            (int)$input['patient_id'],
            !empty($input['appointment_id']) ? (int)$input['appointment_id'] : null,
            !empty($input['doctor_id']) ? (int)$input['doctor_id'] : null,
            (int)($input['bp_systolic'] ?? 0),
            (int)($input['bp_diastolic'] ?? 0),
            (int)($input['pulse'] ?? 0),
            (float)($input['temperature'] ?? 0),
            (int)($input['spo2'] ?? 0),
            (float)($input['weight'] ?? 0),
            (float)($input['height'] ?? 0),
            $bmi,
            !empty($input['blood_sugar']) ? (float)$input['blood_sugar'] : null,
            sanitize($input['ai_risk_flag'] ?? ''),
        ]);

        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Vitals recorded.']);
    }
}

// ── Lab Reports ───────────────────────────────
function handleLabs(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $patientId = (int)($_GET['patient_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT lr.*, d.full_name AS ordered_by, d.full_name AS doctor_name FROM lab_reports lr LEFT JOIN doctors d ON d.id = lr.doctor_id WHERE lr.patient_id = ? ORDER BY lr.report_date DESC, lr.created_at DESC");
        $stmt->execute([$patientId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $pdo->prepare("INSERT INTO lab_reports (patient_id, doctor_id, appointment_id, report_type, report_date, remarks, ai_interpretation) VALUES (?,?,?,?,?,?,?)")
        ->execute([
            (int)$input['patient_id'],
            !empty($input['doctor_id']) ? (int)$input['doctor_id'] : null,
            !empty($input['appointment_id']) ? (int)$input['appointment_id'] : null,
            sanitize($input['report_type'] ?? ''),
            $input['report_date'] ?? date('Y-m-d'),
            sanitize($input['remarks'] ?? ''),
            sanitize($input['ai_interpretation'] ?? ''),
        ]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Lab report saved.']);
    }
}

// ── Patient Profile ───────────────────────────
function handlePatientProfile(PDO $pdo, array $input): void {
    $id = (int)($_GET['patient_id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'error' => 'Missing patient_id'], 400);

    $patientStmt = $pdo->prepare("SELECT id, full_name, email, phone, dob, gender, blood_group, allergies, chronic_conditions, insurance_provider FROM patients WHERE id = ?");
    $patientStmt->execute([$id]);
    $patientData = $patientStmt->fetch();

    if (!$patientData) jsonResponse(['success' => false, 'error' => 'Patient not found'], 404);

    $vitalsStmt = $pdo->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $vitalsStmt->execute([$id]);
    $latestVitals = $vitalsStmt->fetch();

    $recordsStmt = $pdo->prepare("SELECT mr.*, d.full_name AS doctor_name FROM medical_records mr JOIN doctors d ON d.id = mr.doctor_id WHERE mr.patient_id = ? ORDER BY mr.created_at DESC LIMIT 5");
    $recordsStmt->execute([$id]);
    $recentRecords = $recordsStmt->fetchAll();

    $aptStmt = $pdo->prepare("SELECT a.*, d.full_name AS doctor_name, dep.name AS department FROM appointments a JOIN doctors d ON d.id = a.doctor_id LEFT JOIN departments dep ON dep.id = a.department_id WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date ASC LIMIT 5");
    $aptStmt->execute([$id]);
    $upcomingApts = $aptStmt->fetchAll();

    $labsStmt = $pdo->prepare("SELECT COUNT(*) FROM lab_reports WHERE patient_id = ?");
    $labsStmt->execute([$id]);
    $labsCount = (int)$labsStmt->fetchColumn();

    $rxStmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
    $rxStmt->execute([$id]);
    $rxCount = (int)$rxStmt->fetchColumn();

    jsonResponse([
        'success'        => true,
        'patient'        => $patientData,
        'latest_vitals'  => $latestVitals ?: null,
        'recent_records' => $recentRecords,
        'upcoming_apts'  => $upcomingApts,
        'labs_count'     => $labsCount,
        'rx_count'       => $rxCount,
    ]);
}

// ── Prescriptions ─────────────────────────────
function handlePrescriptions(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $patientId = (int)($_GET['patient_id'] ?? 0);
        $doctorId  = (int)($_GET['doctor_id']  ?? 0);
        $limit     = (int)($_GET['limit'] ?? 30);

        $where = ['1=1']; $params = [];
        if ($patientId) { $where[] = 'pr.patient_id=?'; $params[] = $patientId; }
        if ($doctorId)  { $where[] = 'pr.doctor_id=?';  $params[] = $doctorId;  }

        $stmt = $pdo->prepare("
            SELECT pr.*, p.full_name AS patient_name, d.full_name AS doctor_name
            FROM prescriptions pr
            JOIN patients p ON p.id=pr.patient_id
            JOIN doctors  d ON d.id=pr.doctor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY pr.created_at DESC LIMIT {$limit}");
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $required = ['record_id','patient_id','doctor_id','medicine_name'];
        foreach ($required as $f) {
            if (empty($input[$f])) jsonResponse(['success' => false, 'error' => "Missing: {$f}"], 400);
        }
        $pdo->prepare("INSERT INTO prescriptions (record_id,patient_id,doctor_id,medicine_name,dosage,frequency,duration_days,instructions) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([(int)$input['record_id'],(int)$input['patient_id'],(int)$input['doctor_id'],sanitize($input['medicine_name']),sanitize($input['dosage']??''),sanitize($input['frequency']??''),!empty($input['duration_days'])?(int)$input['duration_days']:null,sanitize($input['instructions']??'')]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Prescription saved.']);
    }
}
