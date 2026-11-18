<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['cashier','admin','ceo','medical_director'])) {
    header("Location: login.php"); exit;
}
require 'db.php';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bills_export_' . date('Ymd_His') . '.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['Bill ID','Patient','Amount','Description','Status','Created At']);
$res = $conn->query("SELECT b.id, p.name AS patient_name, b.amount, b.description, b.status, b.created_at FROM medical_bills b JOIN patients p ON b.patient_id=p.id ORDER BY b.created_at DESC");
if ($res) { while($r = $res->fetch_assoc()) { fputcsv($out, [$r['id'],$r['patient_name'],$r['amount'],$r['description'],$r['status'],$r['created_at']]); } }
fclose($out); exit;
?>