<?php
function initDb($dbPath = 'jobs.sqlite') {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id TEXT PRIMARY KEY,
            status TEXT,
            created_at TEXT,
            updated_at TEXT,
            payload TEXT
        )
    ");
    return $db;
}

function insertJob($data) {
    $pdo = initDb();

    $stmt = $pdo->prepare('INSERT INTO jobs (id, status, created_at, updated_at, payload) VALUES (:id, :status, :created_at, :updated_at, :payload)');

    $stmt->execute([
        ':id' => $data['id'],
        ':status' => $data['status'],
        ':created_at' => $data['created_at'],
        ':updated_at' => $data['updated_at'],
        ':payload' => json_encode($data['payload']) // Serialize the payload here
    ]);

    return [
        'status' => 'success',
        'message' => 'Job inserted successfully.',
        'job_id' => $data['id']
    ];
}

function updateJobStatus($db, $id, $status, $payload = null) {
    $stmt = $db->prepare("UPDATE jobs SET status = ?, updated_at = datetime('now'), payload = COALESCE(?, payload) WHERE id = ?");
    $stmt->execute([$status, $payload ? json_encode($payload) : null, $id]);
}

function getJob($db, $id) {
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllJobs($db, $statusList) {
    if ($statusList === '*') {
        $stmt = $db->query("SELECT * FROM jobs");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (is_string($statusList)) {
        $statusList = array_filter(array_map('trim', explode(',', $statusList)));
    }

    if (empty($statusList)) {
        $stmt = $db->query("SELECT * FROM jobs");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $placeholders = implode(',', array_fill(0, count($statusList), '?'));
    $stmt = $db->prepare("SELECT * FROM jobs WHERE status IN ($placeholders)");
    $stmt->execute($statusList);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

