<?php
// Live AI-generated movie/show recommendations, proxied through your own server.
//
// WHY THIS FILE EXISTS: browsers can't call api.anthropic.com directly — there's no way to
// attach an API key without exposing it to every visitor, and Anthropic's API doesn't allow
// direct cross-origin browser requests anyway. Routing through PHP solves both: the key stays
// server-side, and server-to-server calls aren't subject to browser CORS restrictions.
//
// SETUP:
// 1. Get an API key from https://console.anthropic.com (requires billing/credits on the account —
//    this is a paid API; Sonnet costs a small fraction of a cent per recommendation).
// 2. Paste that key below where it says YOUR_ANTHROPIC_API_KEY_HERE.
// 3. Upload this file into the SAME folder as index.html and api.php.

header('Content-Type: application/json');

$SECRET = 'b8m3vFA_1oWjk00x--fTuoJEJyhJM65o'; // must match API_KEY in index.html
$ANTHROPIC_API_KEY = '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';

function fail($code, $msg, $detail = null) {
    http_response_code($code);
    echo json_encode(['error' => $msg, 'detail' => $detail]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'method not allowed');
}

if ($ANTHROPIC_API_KEY === 'YOUR_ANTHROPIC_API_KEY_HERE') {
    fail(500, 'server not configured — add your Anthropic API key to recommend.php');
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['key']) || !hash_equals($SECRET, $body['key'])) {
    fail(403, 'forbidden');
}

$prompt = $body['prompt'] ?? '';
if (!$prompt) {
    fail(400, 'missing prompt');
}

$payload = json_encode([
    'model' => 'claude-sonnet-5',
    'max_tokens' => 1536,
    'thinking' => ['type' => 'disabled'],
    'messages' => [
        ['role' => 'user', 'content' => $prompt]
    ]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    fail(502, 'could not reach Anthropic API', $curlError);
}

http_response_code($httpCode);
echo $response;
