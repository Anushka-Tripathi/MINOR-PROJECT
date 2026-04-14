<?php
// ═══════════════════════════════════════════════════════════
//  MediCare Pro — Database & API Configuration
// ═══════════════════════════════════════════════════════════

// ── MySQL ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'medicare_pro');
define('DB_USER',    'root');
define('DB_PASS',    '');           // XAMPP default is empty — change if you set a MySQL password
define('DB_CHARSET', 'utf8mb4');

// ── Google Gemini AI ──────────────────────────────────────
define('GEMINI_API_KEY', 'AIzaSyCR336xwAwWcfapHQraN8GofdquzmEaMCs');
define('GEMINI_MODEL',   'gemini-2.5-flash');   // Confirmed available for this key
define('GEMINI_BASE_URL','https://generativelanguage.googleapis.com/v1beta/models');

// ─────────────────────────────────────────────────────────

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Output as JSON so frontend can handle it
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed. Check DB_PASS in db.php. Details: ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}

function jsonResponse(array $data, int $code = 200): void {
    // CORS — allow all origins so frontend JS can call from any path
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getInput(): array {
    // Support both JSON body and form POST
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }
    return $_POST ?? [];
}

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)));
}
