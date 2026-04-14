<?php
// ─────────────────────────────────────────────
//  MediCare Pro — Appointments API
// ─────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { jsonResponse(['ok' => true]); }

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();

switch ($method) {
    case 'GET':  handleGet($pdo);          break;
    case 'POST': handlePost($pdo, $input); break;
    case 'PUT':  handlePut($pdo, $input);  break;
    default:     jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
}

function handleGet(PDO $pdo): void {
    $role     = sanitize($_GET['role']      ?? '');
    $userId   = (int)($_GET['user_id']      ?? 0);
    $status   = sanitize($_GET['status']    ?? '');
    $date     = sanitize($_GET['date']      ?? '');
    $doctorId = (int)($_GET['doctor_id']    ?? 0);

    $where  = ['1=1'];
    $params = [];

    if ($role === 'doctor' && $doctorId) {
        $where[] = 'a.doctor_id = ?';  $params[] = $doctorId;
    }
    if ($role === 'patient' && $userId) {
        $where[] = 'a.patient_id = ?'; $params[] = $userId;
    }
    if ($status) {
        $where[] = 'a.status = ?';     $params[] = $status;
    }
    if ($date) {
        $where[] = 'a.appointment_date = ?'; $params[] = $date;
    }

    // NOTE: patients table has dob not age — calculate age with TIMESTAMPDIFF
    $sql = "SELECT
                a.*,
                p.full_name   AS patient_name,
                p.phone       AS patient_phone,
                p.gender      AS patient_gender,
                p.blood_group AS patient_blood_group,
                TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS patient_age,
                d.full_name   AS doctor_name,
                d.specialization,
                dep.name      AS department_name
            FROM appointments a
            JOIN patients    p   ON p.id  = a.patient_id
            JOIN doctors     d   ON d.id  = a.doctor_id
            LEFT JOIN departments dep ON dep.id = a.department_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.appointment_date DESC, a.appointment_time ASC
            LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

function handlePost(PDO $pdo, array $input): void {
    $required = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time'];
    foreach ($required as $f) {
        if (empty($input[$f])) {
            jsonResponse(['success' => false, 'error' => "Missing field: {$f}"], 400);
        }
    }

    // Get doctor's department_id if not supplied
    $deptId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
    if (!$deptId) {
        $deptId = $pdo->prepare("SELECT department_id FROM doctors WHERE id=? LIMIT 1");
        $deptId->execute([(int)$input['doctor_id']]);
        $deptId = (int)($deptId->fetchColumn() ?: 0) ?: null;
    }

    // Next token for that doctor that day
    $tokenStmt = $pdo->prepare("SELECT COALESCE(MAX(token_number),0)+1 FROM appointments WHERE doctor_id=? AND appointment_date=?");
    $tokenStmt->execute([(int)$input['doctor_id'], $input['appointment_date']]);
    $token = (int)$tokenStmt->fetchColumn();

    $pdo->prepare("INSERT INTO appointments
        (patient_id, doctor_id, department_id, appointment_date, appointment_time,
         type, chief_complaint, symptoms, ai_priority_score, ai_triage_label, token_number)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)")
    ->execute([
        (int)$input['patient_id'],
        (int)$input['doctor_id'],
        $deptId,
        $input['appointment_date'],
        $input['appointment_time'],
        sanitize($input['type']            ?? 'OPD'),
        sanitize($input['chief_complaint'] ?? ''),
        sanitize($input['symptoms']        ?? ''),
        (float)($input['ai_priority_score'] ?? 0),
        sanitize($input['ai_triage_label'] ?? 'Routine'),
        $token,
    ]);

    $id = $pdo->lastInsertId();
    jsonResponse(['success' => true, 'id' => $id, 'token_number' => $token, 'message' => 'Appointment booked successfully.']);
}

function handlePut(PDO $pdo, array $input): void {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) { jsonResponse(['success' => false, 'error' => 'Missing appointment ID.'], 400); }

    $allowed = ['Scheduled','Confirmed','In Progress','Completed','Cancelled','No-Show'];
    $status  = sanitize($input['status'] ?? '');
    if (!in_array($status, $allowed)) {
        jsonResponse(['success' => false, 'error' => 'Invalid status.'], 400);
    }

    $pdo->prepare("UPDATE appointments SET status=?, notes=?, updated_at=NOW() WHERE id=?")
        ->execute([$status, sanitize($input['notes'] ?? ''), $id]);

    jsonResponse(['success' => true, 'message' => 'Appointment updated.']);
}
