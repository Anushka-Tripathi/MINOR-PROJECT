<?php
// ═══════════════════════════════════════════════════════════
//  MediCare Pro — AI Proxy (Google Gemini 1.5 Flash)
//  POST /backend/api/ai.php
//  Body: { module: string, feature: string, payload: object }
// ═══════════════════════════════════════════════════════════

// Allow PHP errors to be caught as exceptions (don't show HTML errors)
set_error_handler(function($errno, $errstr) {
    throw new ErrorException($errstr, 0, $errno);
});

try {

session_start();
require_once __DIR__ . '/../config/db.php';

// ── CORS preflight ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

// ── Only accept POST ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'error'   => 'This endpoint only accepts POST requests. If you are opening this URL in a browser, that is a GET request — that is correct behavior. Call this from your dashboard JS code instead.',
        'hint'    => 'Use: fetch("backend/api/ai.php", { method: "POST", body: JSON.stringify({...}) })'
    ], 405);
}

// ── Parse input ───────────────────────────────────────────
$input   = getInput();
$feature = sanitize($input['feature'] ?? '');
$module  = sanitize($input['module']  ?? '');
$payload = $input['payload'] ?? [];

if (!$feature || !$module) {
    jsonResponse(['success' => false, 'error' => 'Missing required fields: module and feature.'], 400);
}

// ── Validate API key ─────────────────────────────────────
$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
if (empty($apiKey) || $apiKey === 'YOUR_API_KEY_HERE') {
    jsonResponse(['success' => false, 'error' => 'Gemini API key not set. Open backend/config/db.php and set GEMINI_API_KEY.']);
}

// ── Build prompt and call Gemini ──────────────────────────
$prompt    = buildPrompt($module, $feature, $payload);
$startTime = microtime(true);
$result    = callGemini($prompt);
$latencyMs = (int)((microtime(true) - $startTime) * 1000);

// ── Log to DB (non-fatal — never break the AI response) ──
try {
    $pdo = getDB();
    $pdo->prepare(
        "INSERT INTO ai_logs (module, feature, user_id, user_role, latency_ms, success, error_msg)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $module,
        $feature,
        $_SESSION['medicare_user_id'] ?? null,
        $_SESSION['medicare_role']    ?? 'guest',
        $latencyMs,
        $result['success'] ? 1 : 0,
        $result['success'] ? null : substr($result['error'] ?? '', 0, 500),
    ]);
} catch (Throwable $logErr) {
    // Logging failure never blocks the AI response
    error_log("MediCare AI log failed: " . $logErr->getMessage());
}

jsonResponse($result);

} catch (Throwable $e) {
    // Catch absolutely any PHP error and return it as JSON
    jsonResponse([
        'success' => false,
        'error'   => 'PHP error in ai.php: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ], 500);
}

// ═══════════════════════════════════════════════════════════
//  PROMPT BUILDER — one prompt per feature
// ═══════════════════════════════════════════════════════════
function buildPrompt(string $module, string $feature, array $payload): string {

    // For chat features, extract message before json_encode corrupts context
    $userMessage = $payload['message'] ?? 'Hello';
    $history     = is_array($payload['history'] ?? []) ? ($payload['history'] ?? []) : [];
    $data        = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Build conversation context for chat features
    $chatContext = '';
    if (!empty($history)) {
        foreach (array_slice($history, -6) as $h) {
            $role    = $h['role'] === 'user' ? 'User' : 'Assistant';
            $content = substr($h['content'] ?? '', 0, 300);
            $chatContext .= "{$role}: {$content}\n";
        }
    }

    $key = "{$module}:{$feature}";

    switch ($key) {

        // ══════════ ADMIN ══════════════════════════════════

        case 'admin:bed_forecast':
            return "You are a hospital capacity AI. Analyze bed occupancy data and forecast for next 8 hours.
Data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"forecast_summary\":\"Concise 1-2 sentence capacity forecast\",\"risk_level\":\"Low\",\"icu_prediction\":\"Stable\",\"general_prediction\":\"Stable\",\"emergency_prediction\":\"Rising\",\"recommended_actions\":[\"Action 1\",\"Action 2\",\"Action 3\"]}";

        case 'admin:staff_optimization':
            return "You are a hospital HR AI. Analyze workload and suggest rebalancing.
Data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"summary\":\"Staff situation summary\",\"overloaded_staff\":[\"Name - Dept (score%)\"],\"recommendations\":[\"Recommendation 1\",\"Recommendation 2\",\"Recommendation 3\"],\"shift_adjustment\":\"Specific shift change instruction\"}";

        case 'admin:billing_anomaly':
            return "You are a healthcare billing fraud AI. Find anomalies in billing data.
Data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"anomalies_detected\":2,\"flagged_items\":[\"Description of anomaly 1\",\"Description of anomaly 2\"],\"risk_score\":65,\"summary\":\"Overall fraud risk assessment\"}";

        case 'admin:analytics_summary':
            return "You are a hospital analytics AI. Create an executive KPI summary.
Data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"executive_summary\":\"2-3 sentences on hospital performance\",\"top_insights\":[\"Insight 1\",\"Insight 2\",\"Insight 3\"],\"trend_analysis\":\"Key trend description\",\"action_items\":[\"Priority action 1\",\"Priority action 2\"]}";

        case 'admin:insurance_verification':
            return "You are a medical insurance evaluation AI. Assess claim validity.
Data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"approval_probability\":72,\"risk_factors\":[\"Risk factor 1\",\"Risk factor 2\"],\"recommendation\":\"Approve or escalate for review\",\"notes\":\"Additional verification notes\"}";

        case 'admin:chat':
            return "You are MediCare Pro Admin AI assistant. Answer hospital management questions helpfully and concisely.
{$chatContext}User: {$userMessage}
Return ONLY a JSON object: {\"response\":\"Your helpful answer here\"}";

        // ══════════ DOCTOR ════════════════════════════════

        case 'doctor:diagnosis_support':
            return "You are a clinical AI for doctors. Analyze symptoms and return differential diagnoses.
Patient data: {$data}
CRITICAL: Respond with ONLY a raw JSON object. No markdown. No text before or after. No code fences.
The JSON must have EXACTLY these keys: likely_diagnoses (array of objects with name/probability/reasoning), recommended_tests (string array), red_flags (string array), clinical_notes (string).
Example format:
{\"likely_diagnoses\":[{\"name\":\"Hypertensive Crisis\",\"probability\":75,\"reasoning\":\"BP 140/90 with chest pain in hypertensive patient\"}],\"recommended_tests\":[\"ECG\",\"Troponin\"],\"red_flags\":[\"Severe chest pain\"],\"clinical_notes\":\"Urgent evaluation needed\"}";

        case 'doctor:drug_interaction':
            return "You are a pharmacology safety AI. Check drug-drug interactions for this prescription.
Medicines: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"interactions\":[{\"drugs\":[\"Drug A\",\"Drug B\"],\"severity\":\"High\",\"description\":\"Interaction mechanism and risk\"}],\"safe_to_prescribe\":true,\"warnings\":[\"Warning 1\",\"Warning 2\"]}";

        case 'doctor:soap_notes':
            return "You are a clinical documentation AI. Generate a professional SOAP note.
Consultation data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"subjective\":\"Patient's presenting complaints\",\"objective\":\"Examination findings and measurements\",\"assessment\":\"Clinical diagnosis and assessment\",\"plan\":\"Treatment plan, medications, follow-up\",\"summary\":\"One-line consultation summary\"}";

        case 'doctor:risk_stratification':
            return "You are a clinical risk stratification AI. Compute patient risk score.
Clinical data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"risk_level\":\"Moderate\",\"risk_score\":65,\"contributing_factors\":[\"Factor 1\",\"Factor 2\",\"Factor 3\"],\"monitoring_frequency\":\"Weekly\",\"alerts\":[\"Alert 1\",\"Alert 2\"]}";

        case 'doctor:prescription_assist':
            return "You are a clinical prescribing AI. Suggest appropriate medicines for the diagnosis.
Data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"medicines\":[{\"name\":\"Medicine Name\",\"dosage\":\"500mg\",\"frequency\":\"Twice daily\",\"duration\":\"7 days\",\"instructions\":\"Take after food\"}],\"contraindications\":[\"Contraindication 1\"],\"follow_up_days\":7,\"notes\":\"Prescribing notes and warnings\"}";

        case 'doctor:chat':
            return "You are a clinical AI copilot for doctors. Provide accurate, evidence-based medical answers.
{$chatContext}Doctor: {$userMessage}
Return ONLY a JSON object: {\"response\":\"Detailed clinical answer\",\"confidence\":\"High\"}";

        // ══════════ PATIENT ═══════════════════════════════

        case 'patient:symptom_checker':
            return "You are a friendly symptom assessment AI. Use simple language a patient can understand.
Symptoms: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"possible_conditions\":[{\"name\":\"Condition\",\"likelihood\":\"High\",\"description\":\"Simple explanation\"},{\"name\":\"Condition 2\",\"likelihood\":\"Medium\",\"description\":\"Explanation\"}],\"urgency_level\":\"See a doctor soon\",\"recommended_action\":\"What the patient should do next\",\"warning_signs\":[\"Warning 1\",\"Warning 2\"],\"disclaimer\":\"This is not a medical diagnosis. Please consult a qualified doctor.\"}";

        case 'patient:appointment_advice':
            return "You are a healthcare triage AI helping a patient choose the right doctor.
Patient concern: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"recommended_department\":\"Cardiology\",\"urgency\":\"Soon\",\"questions_to_ask_doctor\":[\"Question 1\",\"Question 2\"],\"preparation_tips\":[\"Tip 1\",\"Tip 2\"],\"expected_tests\":[\"Test 1\"]}";

        case 'patient:report_summary':
            return "You are a medical report AI. Explain lab results in simple, patient-friendly language.
Report: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"overall_status\":\"Some Concerns\",\"findings\":[{\"test\":\"Test Name\",\"value\":\"Value\",\"status\":\"elevated\",\"explanation\":\"Plain English explanation\"},{\"test\":\"Test 2\",\"value\":\"Value\",\"status\":\"normal\",\"explanation\":\"Plain English explanation\"}],\"lifestyle_tips\":[\"Tip 1\",\"Tip 2\"],\"should_see_doctor_urgently\":false,\"summary\":\"Overall summary in simple words\"}";

        case 'patient:medication_reminder':
            return "You are a medication schedule AI. Create a practical daily medicine routine.
Medicines: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"schedule\":[{\"medicine\":\"Medicine Name\",\"time\":\"8:00 AM\",\"instructions\":\"Take after breakfast\"},{\"medicine\":\"Medicine 2\",\"time\":\"9:00 PM\",\"instructions\":\"Take before bed\"}],\"missed_dose_guidance\":\"What to do if a dose is missed\",\"important_warnings\":[\"Important warning 1\"]}";

        case 'patient:health_insights':
            return "You are a personal health AI advisor. Give wellness insights based on patient data.
Patient data: {$data}
Return ONLY a JSON object, no explanation, no markdown:
{\"health_score\":72,\"risk_areas\":[\"Risk area 1\",\"Risk area 2\"],\"strengths\":[\"Health strength 1\"],\"recommendations\":[\"Recommendation 1\",\"Recommendation 2\",\"Recommendation 3\"],\"next_checkup\":\"In 3 months\"}";

        case 'patient:chat':
            return "You are a friendly patient health assistant. Use warm, simple, empathetic language. Never diagnose.
{$chatContext}Patient: {$userMessage}
Return ONLY a JSON object: {\"response\":\"Your kind, helpful response here\",\"should_see_doctor\":false,\"urgency\":\"Low\"}";

        default:
            return "You are MediCare Pro AI assistant. Help with: {$feature}\nContext: {$data}\nReturn ONLY valid JSON: {\"response\":\"Your helpful response\"}";
    }
}

// ═══════════════════════════════════════════════════════════
//  GEMINI API CALLER — auto-fallback + auto-discover
// ═══════════════════════════════════════════════════════════
function callGemini(string $prompt): array {

    // Models confirmed available for this API key (from ListModels response)
    $modelsToTry = [
        'gemini-2.5-flash',         // Best — latest, fast, generous quota
        'gemini-2.0-flash',         // Stable fallback
        'gemini-2.0-flash-lite',    // Lighter fallback
        'gemini-flash-latest',      // Alias
        'gemini-2.5-pro',           // Premium fallback
        'gemini-pro-latest',        // Legacy fallback
    ];

    $errors = [];

    foreach ($modelsToTry as $model) {
        $result = callGeminiModel($model, $prompt);
        if ($result['success']) {
            return $result;
        }
        $http = $result['http'] ?? 0;
        $errors[$model] = "HTTP {$http}: " . substr($result['error'] ?? '', 0, 80);
        // 404 = model not available, 429 = quota exceeded → try next
        if ($http === 404 || $http === 429) continue;
        // Auth error (401/403) — no point trying other models
        if ($http === 401 || $http === 403) return $result;
    }

    // All hardcoded models failed — try auto-discovering via ListModels
    $discovered = discoverWorkingModel($prompt);
    if ($discovered !== null) return $discovered;

    return [
        'success' => false,
        'error'   => 'No Gemini model available. Errors per model: ' . json_encode($errors) .
                     ' | Check quota at https://aistudio.google.com',
    ];
}

// Calls ListModels API to find a working model dynamically
function discoverWorkingModel(string $prompt): ?array {
    $apiKey = GEMINI_API_KEY;
    $url    = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}&pageSize=50";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$raw) return null;

    $resp = json_decode($raw, true);
    $models = $resp['models'] ?? [];

    foreach ($models as $m) {
        if (!in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) continue;
        $name = str_replace('models/', '', $m['name']);
        if (strpos($name, 'gemini') === false) continue;
        $result = callGeminiModel($name, $prompt);
        if ($result['success']) return $result;
    }
    return null;
}

function callGeminiModel(string $model, string $prompt): array {

    $apiKey = GEMINI_API_KEY;
    $url    = GEMINI_BASE_URL . "/{$model}:generateContent?key={$apiKey}";

    $body = json_encode([
        'contents' => [[
            'parts' => [['text' => $prompt]]
        ]],
        'generationConfig' => [
            'temperature'     => 0.25,
            'maxOutputTokens' => 2048,
            'topP'            => 0.8,
            'topK'            => 40,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
        ],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    $curlNo   = curl_errno($ch);
    curl_close($ch);

    if ($raw === false || $curlNo !== 0) {
        return ['success' => false, 'error' => "Network error: {$curlErr} (code {$curlNo})"];
    }

    $resp = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => "Non-JSON response: " . substr($raw, 0, 200)];
    }

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error'   => "Gemini API error {$httpCode}: " . ($resp['error']['message'] ?? 'Unknown'),
            'http'    => $httpCode,
        ];
    }

    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($text === null) {
        $reason = $resp['candidates'][0]['finishReason']
               ?? $resp['promptFeedback']['blockReason']
               ?? 'UNKNOWN';
        return ['success' => false, 'error' => "Empty response from Gemini. Reason: {$reason}"];
    }

    // Clean markdown fences
    $text = trim($text);
    $text = preg_replace('/^```(?:json)?\s*/im', '', $text);
    $text = preg_replace('/\s*```\s*$/im', '', $text);
    $text = trim($text);

    // Extract JSON if buried in prose
    if (!str_starts_with($text, '{') && !str_starts_with($text, '[')) {
        if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/m', $text, $m)) {
            $text = $m[1];
        }
    }

    $parsed = json_decode($text, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => true, 'data' => ['response' => $text]];
    }

    return ['success' => true, 'data' => $parsed, 'model_used' => $model];
}
