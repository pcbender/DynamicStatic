<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__.'/config.php';

function weaver_private_key() {
  $pem = envr('WEAVER_JWT_PRIVATE_KEY');
  $key = openssl_pkey_get_private($pem);
  if (!$key) { throw new Exception('Invalid private key'); }
  return $pem;
}

function weaver_sign(array $claims, $ttl=null) {
  $now = time();
  $ttl = $ttl ?? intval(envr('WEAVER_JWT_TTL', 1800));
  $payload = array_merge([
    'iss' => weaver_issuer(),
    'aud' => 'weaver-api',
    'iat' => $now,
    'nbf' => $now - 5,
    'exp' => $now + $ttl,
  ], $claims);

  $hdr = ['kid'=>envr('WEAVER_JWT_KID'), 'typ'=>'JWT', 'alg'=>'RS256'];
  return JWT::encode($payload, weaver_private_key(), 'RS256', null, $hdr);
}

function jwk_from_private() {
  $pem = envr('WEAVER_JWT_PRIVATE_KEY');
  $res = openssl_pkey_get_private($pem);
  $det = openssl_pkey_get_details($res);
  $n = rtrim(strtr(base64_encode($det['rsa']['n']), '+/', '-_'), '=');
  $e = rtrim(strtr(base64_encode($det['rsa']['e']), '+/', '-_'), '=');
  return [
    'kty'=>'RSA','kid'=>envr('WEAVER_JWT_KID'),'use'=>'sig','alg'=>'RS256',
    'n'=>$n,'e'=>$e,
  ];
}
