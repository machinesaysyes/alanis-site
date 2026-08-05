<?php
// vault89 storage — a tiny JSON-file-backed store so data syncs across devices.
// Upload this file into the SAME folder as index.html (public_html/vault89/api.php).
//
// Supports multiple named "buckets" (e.g. ratings feedback, the AI-refreshed analysis text)
// so different kinds of data don't collide. The original "feedback" bucket keeps its original
// filename for backward compatibility with data already saved before buckets existed.

header('Content-Type: application/json');

// Must match the API_KEY constant in index.html exactly.
$SECRET = 'b8m3vFA_1oWjk00x--fTuoJEJyhJM65o';

$method = $_SERVER['REQUEST_METHOD'];

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function bucketFile($bucket) {
    $bucket = preg_replace('/[^a-z0-9_]/i', '', (string)$bucket);
    if ($bucket === '' || $bucket === 'feedback') {
        return __DIR__ . '/vault89_data.json'; // original filename, unchanged
    }
    return __DIR__ . '/vault89_data_' . $bucket . '.json';
}

if ($method === 'GET') {
    $key = $_GET['key'] ?? '';
    if (!hash_equals($SECRET, $key)) {
        fail(403, 'forbidden');
    }
    $dataFile = bucketFile($_GET['bucket'] ?? 'feedback');
    if (file_exists($dataFile)) {
        readfile($dataFile);
    } else {
        echo json_encode(new stdClass());
    }
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!$body || !isset($body['key']) || !hash_equals($SECRET, $body['key'])) {
        fail(403, 'forbidden');
    }

    $dataFile = bucketFile($body['bucket'] ?? 'feedback');
    $data = $body['data'] ?? new stdClass();
    $json = json_encode($data);

    if ($json === false) {
        fail(400, 'invalid json');
    }

    $written = @file_put_contents($dataFile, $json, LOCK_EX);
    if ($written === false) {
        fail(500, 'could not write file — check folder permissions');
    }

    echo json_encode(['status' => 'ok']);
    exit;
}

fail(405, 'method not allowed');

