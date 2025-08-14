<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_api_key.php';
if (env('WEAVER_SESSION_JWT_SECRET')) { require_once __DIR__ . '/session.php'; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

$db = initDb();
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['status'])) { bad_request('Missing status'); }

$limit = isset($data['limit']) ? (int)$data['limit'] : 50;
if ($limit < 1) $limit = 1; if ($limit > 200) $limit = 200;
$offset = isset($data['offset']) ? (int)$data['offset'] : 0; if ($offset < 0) $offset = 0;

$jobs = getAllJobs($db, $data['status']);
// Sort newest first by created_at if present
usort($jobs, function($a, $b) {
    $ta = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
    $tb = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
    return $tb <=> $ta;
});

// Optional session filtering: if a session bearer is provided and valid, restrict to that job id
if (function_exists('requireSession') && env('WEAVER_SESSION_JWT_SECRET')) {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($hdr) {
        try {
            $sess = requireSession();
            if (!empty($sess['job_id'])) {
                $jobs = array_values(array_filter($jobs, fn($j) => $j['id'] === $sess['job_id']));
            }
        } catch (Throwable $e) {
            // Ignore session errors for listing (still return API-key authorized list)
        }
    }
}
$total = count($jobs);
$jobs = array_slice($jobs, $offset, $limit);
json_out([
    'items' => $jobs,
    'pagination' => [
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]
]);
