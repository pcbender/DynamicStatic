<?php
require_once __DIR__ . "/../bootstrap.php";
function initDb($dbPath = __DIR__ . '/jobs.sqlite'): PDO {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS jobs (
        id TEXT PRIMARY KEY,
        status TEXT,
        created_at TEXT,
        updated_at TEXT,
        payload TEXT,
        created_by_sub TEXT,
        created_by_email TEXT
    )");
    $cols = $db->query('PRAGMA table_info(jobs)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('created_by_sub', $cols, true)) {
        $db->exec('ALTER TABLE jobs ADD COLUMN created_by_sub TEXT');
    }
    if (!in_array('created_by_email', $cols, true)) {
        $db->exec('ALTER TABLE jobs ADD COLUMN created_by_email TEXT');
    }
    return $db;
}

function insertJob(array $input): array {
    $db = initDb();
    $id = $input['id'] ?? bin2hex(random_bytes(8));
    $status = $input['status'] ?? 'pending';
    $payload = $input['payload'] ?? [];
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $created_by_sub = $input['created_by_sub'] ?? null;
    $created_by_email = $input['created_by_email'] ?? null;
    $stmt = $db->prepare('INSERT INTO jobs (id, status, created_at, updated_at, payload, created_by_sub, created_by_email) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $status, $now, $now, json_encode($payload), $created_by_sub, $created_by_email]);
    return [
        'id' => $id,
        'status' => $status,
        'created_at' => $now,
        'updated_at' => $now,
        'payload' => $payload,
        'created_by_sub' => $created_by_sub,
        'created_by_email' => $created_by_email
    ];
}

function getJob(PDO $db, string $id): ?array {
    $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function updateJobStatus(PDO $db, string $id, string $status, ?array $payloadPatch = null): void {
    $payloadJson = null;
    if ($payloadPatch !== null) {
        $existing = getJob($db, $id);
        $current = $existing ? json_decode($existing['payload'], true) : [];
        $merged = array_replace_recursive($current, $payloadPatch);
        $payloadJson = json_encode($merged);
    }
    $stmt = $db->prepare("UPDATE jobs SET status = ?, updated_at = ?, payload = COALESCE(?, payload) WHERE id = ?");
    $stmt->execute([$status, gmdate('Y-m-d\TH:i:s\Z'), $payloadJson, $id]);
}

function getAllJobs(PDO $db, $statusCsvOrArray): array {
    if ($statusCsvOrArray === '*' || $statusCsvOrArray === null) {
        $stmt = $db->query('SELECT * FROM jobs');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if (is_string($statusCsvOrArray)) {
        $statusCsvOrArray = array_filter(array_map('trim', explode(',', $statusCsvOrArray)));
    }
    if (empty($statusCsvOrArray)) {
        $stmt = $db->query('SELECT * FROM jobs');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $placeholders = implode(',', array_fill(0, count($statusCsvOrArray), '?'));
    $stmt = $db->prepare("SELECT * FROM jobs WHERE status IN ($placeholders)");
    $stmt->execute($statusCsvOrArray);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
