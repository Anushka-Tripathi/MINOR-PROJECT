<?php
// ─────────────────────────────────────────────
//  MediCare Pro — Admin API
//  GET/POST /backend/api/admin.php?action=...
// ─────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { jsonResponse(['ok' => true]); }

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$action = sanitize($_GET['action'] ?? 'stats');

match($action) {
    'stats'        => handleStats($pdo),
    'doctors'      => handleDoctors($pdo, $method, $input),
    'patients'     => handlePatients($pdo, $method, $input),
    'departments'  => handleDepartments($pdo, $method, $input),
    'beds'         => handleBeds($pdo, $method, $input),
    'billing'      => handleBilling($pdo, $method, $input),
    'staff'        => handleStaff($pdo, $method, $input),
    'notifications'=> handleNotifications($pdo, $method, $input),
    default        => jsonResponse(['success' => false, 'error' => 'Unknown action.'], 400),
};

// ── Dashboard Stats ───────────────────────────
function handleStats(PDO $pdo): void {
    $totalPatients    = $pdo->query("SELECT COUNT(*) FROM patients WHERE is_active=1")->fetchColumn();
    $totalDoctors     = $pdo->query("SELECT COUNT(*) FROM doctors WHERE is_active=1")->fetchColumn();
    $todayApts        = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date=CURDATE()")->fetchColumn();
    $pendingBills     = $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status='Pending'")->fetchColumn();
    $bedsOccupied     = $pdo->query("SELECT COUNT(*) FROM beds WHERE status='Occupied'")->fetchColumn();
    $bedsTotal        = $pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn();
    $monthRevenue     = $pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM bills WHERE MONTH(paid_at)=MONTH(NOW()) AND YEAR(paid_at)=YEAR(NOW())")->fetchColumn();
    $criticalAlerts   = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0 AND type='alert'")->fetchColumn();

    // Weekly appointments for chart
    $weeklyStmt = $pdo->query("SELECT appointment_date, COUNT(*) as count FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY appointment_date ORDER BY appointment_date ASC");
    $weeklyData = $weeklyStmt->fetchAll();

    // Department-wise appointments
    $deptStmt = $pdo->query("SELECT dep.name, COUNT(a.id) as count FROM appointments a LEFT JOIN departments dep ON dep.id=a.department_id GROUP BY a.department_id, dep.name ORDER BY count DESC LIMIT 6");
    $deptData = $deptStmt->fetchAll();

    // Recent activity
    $recentApts = $pdo->query("SELECT a.id, p.full_name as patient, d.full_name as doctor, a.appointment_date, a.status FROM appointments a JOIN patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id ORDER BY a.created_at DESC LIMIT 8")->fetchAll();

    jsonResponse([
        'success' => true,
        'stats' => [
            'total_patients' => (int)$totalPatients,
            'total_doctors'  => (int)$totalDoctors,
            'today_apts'     => (int)$todayApts,
            'pending_bills'  => (int)$pendingBills,
            'beds_occupied'  => (int)$bedsOccupied,
            'beds_total'     => (int)$bedsTotal,
            'month_revenue'  => (float)$monthRevenue,
            'critical_alerts'=> (int)$criticalAlerts,
            'bed_occupancy'  => $bedsTotal > 0 ? round(($bedsOccupied / $bedsTotal) * 100, 1) : 0,
        ],
        'weekly_apts'   => $weeklyData,
        'dept_breakdown'=> $deptData,
        'recent_apts'   => $recentApts,
    ]);
}

// ── Doctors CRUD ──────────────────────────────
function handleDoctors(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $id   = (int)($_GET['id'] ?? 0);
        $dept = (int)($_GET['dept'] ?? 0);

        if ($id) {
            $stmt = $pdo->prepare("SELECT d.*, dep.name AS department_name FROM doctors d LEFT JOIN departments dep ON dep.id=d.department_id WHERE d.id=?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'data' => $stmt->fetch()]);
        } else {
            $where = $dept ? 'WHERE d.department_id=?' : '';
            $params = $dept ? [$dept] : [];
            $stmt = $pdo->prepare("SELECT d.id, d.full_name, d.email, d.phone, d.specialization, d.qualification, d.experience_years, d.consultation_fee, d.rating, d.is_active, dep.name AS department FROM doctors d LEFT JOIN departments dep ON dep.id=d.department_id {$where} ORDER BY d.full_name");
            $stmt->execute($params);
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }

    } elseif ($method === 'POST') {
        $required = ['full_name', 'email', 'password', 'department_id', 'specialization'];
        foreach ($required as $f) {
            if (empty($input[$f])) jsonResponse(['success' => false, 'error' => "Missing: {$f}"], 400);
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) jsonResponse(['success' => false, 'error' => 'Invalid email.'], 400);

        $hash = password_hash($input['password'], PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO doctors (full_name, email, password_hash, phone, department_id, specialization, qualification, experience_years, license_number, consultation_fee) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            sanitize($input['full_name']),
            strtolower(sanitize($input['email'])),
            $hash,
            sanitize($input['phone'] ?? ''),
            (int)$input['department_id'],
            sanitize($input['specialization']),
            sanitize($input['qualification'] ?? ''),
            (int)($input['experience_years'] ?? 0),
            sanitize($input['license_number'] ?? ''),
            (float)($input['consultation_fee'] ?? 0),
        ]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Doctor added successfully.']);

    } elseif ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'error' => 'Missing doctor ID.'], 400);
        $pdo->prepare("UPDATE doctors SET full_name=?, phone=?, department_id=?, specialization=?, consultation_fee=?, is_active=? WHERE id=?")
        ->execute([sanitize($input['full_name']), sanitize($input['phone']), (int)$input['department_id'], sanitize($input['specialization']), (float)$input['consultation_fee'], (int)($input['is_active'] ?? 1), $id]);
        jsonResponse(['success' => true, 'message' => 'Doctor updated.']);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE doctors SET is_active=0 WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Doctor deactivated.']);
    }
}

// ── Patients CRUD ─────────────────────────────
function handlePatients(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $search = sanitize($_GET['search'] ?? '');
        $limit  = (int)($_GET['limit'] ?? 30);
        $sql = "SELECT id, full_name, email, phone, dob, gender, blood_group, city, is_active, created_at FROM patients";
        $params = [];
        if ($search) {
            $sql .= " WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        $sql .= " ORDER BY created_at DESC LIMIT {$limit}";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $hash = password_hash($input['password'] ?? 'Welcome@123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO patients (full_name, email, password_hash, phone, dob, gender, blood_group, address, city) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([sanitize($input['full_name']), strtolower(sanitize($input['email'])), $hash, sanitize($input['phone'] ?? ''), $input['dob'] ?? null, sanitize($input['gender'] ?? ''), sanitize($input['blood_group'] ?? ''), sanitize($input['address'] ?? ''), sanitize($input['city'] ?? '')]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Patient registered.']);
    }
}

// ── Departments ───────────────────────────────
function handleDepartments(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT d.*, doc.full_name AS head_doctor FROM departments d LEFT JOIN doctors doc ON doc.id=d.head_doctor_id ORDER BY d.name");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $pdo->prepare("INSERT INTO departments (name, description, floor, beds_total) VALUES (?,?,?,?)")
        ->execute([sanitize($input['name']), sanitize($input['description'] ?? ''), sanitize($input['floor'] ?? ''), (int)($input['beds_total'] ?? 0)]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Department created.']);
    }
}

// ── Beds ──────────────────────────────────────
function handleBeds(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $deptId = (int)($_GET['dept'] ?? 0);
        $where = $deptId ? 'WHERE b.department_id=?' : '';
        $params = $deptId ? [$deptId] : [];
        $stmt = $pdo->prepare("SELECT b.*, dep.name AS department, p.full_name AS patient_name FROM beds b JOIN departments dep ON dep.id=b.department_id LEFT JOIN patients p ON p.id=b.patient_id {$where} ORDER BY b.ward_type, b.bed_number");
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE beds SET status=?, patient_id=?, admitted_at=? WHERE id=?")
        ->execute([sanitize($input['status']), !empty($input['patient_id']) ? (int)$input['patient_id'] : null, $input['status'] === 'Occupied' ? date('Y-m-d H:i:s') : null, $id]);
        jsonResponse(['success' => true, 'message' => 'Bed updated.']);
    }
}

// ── Billing ───────────────────────────────────
function handleBilling(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $status = sanitize($_GET['status'] ?? '');
        $where = $status ? 'WHERE b.payment_status=?' : '';
        $params = $status ? [$status] : [];
        $stmt = $pdo->prepare("SELECT b.*, p.full_name AS patient_name FROM bills b JOIN patients p ON p.id=b.patient_id {$where} ORDER BY b.created_at DESC LIMIT 50");
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $billNumber = 'BILL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $total = (float)($input['consultation_fee'] ?? 0) + (float)($input['medicine_charges'] ?? 0) + (float)($input['lab_charges'] ?? 0) + (float)($input['bed_charges'] ?? 0) + (float)($input['other_charges'] ?? 0) - (float)($input['discount'] ?? 0) + (float)($input['tax'] ?? 0);
        $pdo->prepare("INSERT INTO bills (patient_id, appointment_id, bill_number, consultation_fee, medicine_charges, lab_charges, bed_charges, other_charges, discount, tax, total_amount, payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([(int)$input['patient_id'], !empty($input['appointment_id']) ? (int)$input['appointment_id'] : null, $billNumber, (float)($input['consultation_fee'] ?? 0), (float)($input['medicine_charges'] ?? 0), (float)($input['lab_charges'] ?? 0), (float)($input['bed_charges'] ?? 0), (float)($input['other_charges'] ?? 0), (float)($input['discount'] ?? 0), (float)($input['tax'] ?? 0), $total, sanitize($input['payment_method'] ?? 'Cash')]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'bill_number' => $billNumber, 'total' => $total, 'message' => 'Bill created.']);
    } elseif ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE bills SET payment_status=?, paid_amount=?, paid_at=NOW() WHERE id=?")->execute([sanitize($input['payment_status']), (float)($input['paid_amount'] ?? 0), $id]);
        jsonResponse(['success' => true, 'message' => 'Bill updated.']);
    }
}

// ── Staff ─────────────────────────────────────
function handleStaff(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT s.*, dep.name AS department FROM staff s LEFT JOIN departments dep ON dep.id=s.department_id ORDER BY s.workload_score DESC");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $pdo->prepare("INSERT INTO staff (full_name, email, phone, role, department_id, shift) VALUES (?,?,?,?,?,?)")
        ->execute([sanitize($input['full_name']), sanitize($input['email'] ?? ''), sanitize($input['phone'] ?? ''), sanitize($input['role']), !empty($input['department_id']) ? (int)$input['department_id'] : null, sanitize($input['shift'] ?? 'Morning')]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Staff member added.']);
    }
}

// ── Notifications ─────────────────────────────
function handleNotifications(PDO $pdo, string $method, array $input): void {
    if ($method === 'GET') {
        $role = sanitize($_GET['role'] ?? 'admin');
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_role=? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$role]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
    }
}
