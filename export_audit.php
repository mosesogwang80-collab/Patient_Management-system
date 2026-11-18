<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','ceo','medical_director'])) {
    header("Location: login.php"); exit;
}
require 'db.php';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=audit_export_' . date('Ymd_His') . '.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID','User ID','Action','Detail','IP','Created At']);
$res = $conn->query("SELECT id, user_id, action, detail, ip, created_at FROM audit_logs ORDER BY created_at DESC");
if ($res) { while($r = $res->fetch_assoc()) { fputcsv($out, [$r['id'],$r['user_id'],$r['action'],$r['detail'],$r['ip'],$r['created_at']]); } }
fclose($out); exit;
?>