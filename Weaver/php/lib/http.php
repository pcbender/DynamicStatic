<?php
function json_out($data, $code=200){
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data, JSON_UNESCAPED_SLASHES);
  exit;
}
function bad_request($msg){ json_out(['error'=>'invalid_request','error_description'=>$msg],400); }
function unauthorized($msg){ json_out(['error'=>'unauthorized_client','error_description'=>$msg],401); }
function server_error($msg){ json_out(['error'=>'server_error','error_description'=>$msg],500); }
function require_post(){ if($_SERVER['REQUEST_METHOD']!=='POST') bad_request('POST required'); }
