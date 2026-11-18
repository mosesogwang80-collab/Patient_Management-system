<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['pharmacy','admin','ceo','medical_director','doctor'])) {
    header("Location: login.php"); exit;
}
require 'db.php';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=prescriptions_export_' . date('Ymd_His') . '.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['Rx ID','Patient','Doctor','Drug','Dosage','Status','Issued At','Dispensed At']);
$res = $conn->query("SELECT pr.id, p.name AS patient_name, u.name AS doctor_name, pr.drug_name, pr.dosage, pr.status, pr.issued_at, pr.dispensed_at FROM prescriptions pr JOIN patients p ON pr.patient_id=p.id LEFT JOIN users u ON pr.doctor_id=u.id ORDER BY pr.issued_at DESC");
if ($res) { while($r = $res->fetch_assoc()) { fputcsv($out, [$r['id'],$r['patient_name'],$r['doctor_name'],$r['drug_name'],$r['dosage'],$r['status'],$r['issued_at'],$r['dispensed_at']]); } }
fclose($out); exit;
?>