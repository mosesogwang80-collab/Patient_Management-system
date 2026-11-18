<?php
// includes/functions.php
require_once __DIR__ . '/db.php';


function find_or_create_patient($name, $email, $phone){
global $pdo;
$stmt = $pdo->prepare('SELECT * FROM patients WHERE phone = ? LIMIT 1');
$stmt->execute([$phone]);
$p = $stmt->fetch();
if($p) return $p;


$stmt = $pdo->prepare('INSERT INTO patients (name,email,phone) VALUES (?,?,?)');
$stmt->execute([$name, $email, $phone]);
$id = $pdo->lastInsertId();
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
return $stmt->fetch();
}


function queue_notification($to_phone, $message, $channel = 'sms', $meta = null){
global $pdo;
$stmt = $pdo->prepare('INSERT INTO notifications (to_phone, message, channel, status, meta) VALUES (?,?,?,"queued",?)');
$stmt->execute([$to_phone, $message, $channel, $meta ? json_encode($meta) : null]);
}


function send_sms($to, $message){
// IMPORTANT: Replace this with your SMS provider API call.
// Example (generic HTTP API using cURL):
$api_url = 'https://api.example-sms.com/send';
$api_key = getenv('SMS_API_KEY') ?: 'YOUR_API_KEY';


$payload = [
'to' => $to,
'message' => $message,
'api_key' => $api_key
];


$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);


return $res !== false && !$err;
}


function log_action($user_id, $action, $detail = null){
global $pdo;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, detail, ip) VALUES (?,?,?,?)');
$stmt->execute([$user_id, $action, $detail, $ip]);
}
