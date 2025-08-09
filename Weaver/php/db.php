<?php
function initDb($dbPath = 'jobs.sqlite'): PDO {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS jobs (
        id TEXT PRIMARY KEY,
        status TEXT,
        created_at TEXT,
        updated_at TEXT,
        payload TEXT
    )");
    return $db;
}

function insertJob(array $input): array {
    $db = initDb();
    $id = $input['id'] ?? bin2hex(random_bytes(8));
    $status = $input['status'] ?? 'pending';
    $payload = $input['payload'] ?? [];
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $stmt = $db->prepare('INSERT INTO jobs (id, status, created_at, updated_at, payload) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$id, $status, $now, $now, json_encode($payload)]);
    return ['id' => $id, 'status' => $status, 'created_at' => $now, 'updated_at' => $now, 'payload' => $payload];
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
