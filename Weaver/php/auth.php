<?php
function base64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function require_oauth(): array {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if (!$auth || !preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $jwt = $m[1];
    $parts = explode('.', $jwt);
    if (count($parts) < 2) {
        return ['sub' => 'unknown'];
    }
    $payload = json_decode(base64url_decode($parts[1]), true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $iss = getenv('WEAVER_ALLOWED_ISS');
    if ($iss && ($payload['iss'] ?? null) !== $iss) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $aud = getenv('WEAVER_ALLOWED_AUD');
    if ($aud) {
        $payloadAud = $payload['aud'] ?? null;
        if (is_array($payloadAud)) {
            if (!in_array($aud, $payloadAud, true)) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
        } elseif ($payloadAud !== $aud) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
    if (!isset($payload['sub'])) {
        $payload['sub'] = 'unknown';
    }
    return $payload;
}
