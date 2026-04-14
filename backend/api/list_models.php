<?php
// ─────────────────────────────────────────────────────────
//  MediCare Pro — List Available Gemini Models
//  GET /backend/api/list_models.php
// ─────────────────────────────────────────────────────────

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$apiKey = GEMINI_API_KEY;
$url    = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}&pageSize=50";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['success' => false, 'error' => "cURL error: $err"]);
    exit;
}

$resp = json_decode($raw, true);

if ($code !== 200) {
    echo json_encode([
        'success' => false,
        'error'   => $resp['error']['message'] ?? "HTTP $code",
        'raw'     => $raw,
    ]);
    exit;
}

$models = $resp['models'] ?? [];

// Find which ones support generateContent
$supported = array_filter($models, function($m) {
    return in_array('generateContent', $m['supportedGenerationMethods'] ?? []);
});

// Also try a quick test call on the first supported model
$testModel = null;
foreach ($supported as $m) {
    $name = str_replace('models/', '', $m['name']);
    if (strpos($name, 'gemini') !== false) {
        $testModel = $name;
        break;
    }
}

echo json_encode([
    'success'         => true,
    'models'          => array_values($supported),
    'recommended'     => $testModel,
    'total_available' => count($supported),
    'api_key_prefix'  => substr($apiKey, 0, 12) . '...',
]);
