<?php
// ─────────────────────────────────────────────
//  MediCare Pro — Login API
//  POST /backend/api/login.php
//  Body: { role, email, password }
// ─────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$input    = getInput();
$role     = sanitize($input['role'] ?? '');
$email    = strtolower(sanitize($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (!in_array($role, ['admin', 'doctor', 'patient'])) {
    jsonResponse(['success' => false, 'error' => 'Invalid role.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'error' => 'Invalid email format.'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'error' => 'Password too short.'], 400);
}

$pdo   = getDB();
$table = match($role) {
    'admin'   => 'admins',
    'doctor'  => 'doctors',
    'patient' => 'patients',
};

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['success' => false, 'error' => 'No account found with this email.'], 401);
}

// Verify password — supports:
// 1. Proper bcrypt hash (production)
// 2. Plain-text stored password (legacy/migration)
// 3. Auto-rehash plain-text to bcrypt on first login
$verified = false;
$needsRehash = false;

if (password_verify($password, $user['password_hash'])) {
    // Standard bcrypt match
    $verified = true;
    // Upgrade cost if needed
    if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 10])) {
        $needsRehash = true;
    }
} elseif ($password === $user['password_hash']) {
    // Plain-text match (seed data / migration) — rehash immediately
    $verified = true;
    $needsRehash = true;
}

if (!$verified) {
    jsonResponse(['success' => false, 'error' => 'Incorrect password.'], 401);
}

// Rehash to bcrypt if needed
if ($needsRehash) {
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $pdo->prepare("UPDATE {$table} SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
}

// Update last_login
$pdo->prepare("UPDATE {$table} SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

// Build session
$_SESSION['medicare_role']    = $role;
$_SESSION['medicare_user_id'] = $user['id'];
$_SESSION['medicare_name']    = $user['full_name'];
$_SESSION['medicare_email']   = $user['email'];

// Payload to return
$payload = [
    'id'        => $user['id'],
    'full_name' => $user['full_name'],
    'email'     => $user['email'],
    'role'      => $role,
];

if ($role === 'doctor') {
    $payload['specialization'] = $user['specialization'] ?? '';
    $payload['department_id']  = $user['department_id'] ?? null;
}

jsonResponse([
    'success'  => true,
    'message'  => 'Login successful.',
    'user'     => $payload,
    'redirect' => '../' . $role . '/dashboard.html',
]);
