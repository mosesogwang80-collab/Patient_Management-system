<?php
session_start();
require 'db.php';
 
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient'){
    header("Location: login.php");
    exit();
}
 
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $patient_id = $_SESSION['user_id'];
    $appointment_time = $_POST['appointment_time'];
    $note = $_POST['note'];

    // Auto-allocate doctor by issue keywords
    $issue = strtolower($note);
    $specialty = 'General Medicine';
    if(strpos($issue,'heart')!==false) $specialty='Cardiology';
    if(strpos($issue,'tooth')!==false || strpos($issue,'dental')!==false) $specialty='Dental';
    if(strpos($issue,'bone')!==false || strpos($issue,'joint')!==false) $specialty='Orthopedics';
    if(strpos($issue,'child')!==false) $specialty='Pediatrics';
    if(strpos($issue,'brain')!==false || strpos($issue,'nerve')!==false) $specialty='Neurology';

    $stmt = $conn->prepare("SELECT id FROM users WHERE role='doctor' AND specialty LIKE ? AND status='active' AND available=1 LIMIT 1");
    $like = "%$specialty%";
    $stmt->bind_param("s",$like);
    $stmt->execute();
    $res = $stmt->get_result();
    $doc = $res->fetch_assoc();
    $doctor_id = $doc ? $doc['id'] : null;

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, issue, requested_datetime, patient_note, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $note, $appointment_time, $note);
    
    if($stmt->execute()){
        if($doctor_id){
            $stmt2 = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
            $subj = "New appointment request";
            $body = "A patient has requested an appointment: $note";
            $stmt2->bind_param("iiss", $patient_id, $doctor_id, $subj, $body);
            $stmt2->execute();
        }
        header("Location: patient_dashboard.php?success=Appointment+booked");
        exit();
    } else {
        header("Location: patient_dashboard.php?error=Failed+to+book");
        exit();
    }
}else{
    header("Location: patient_dashboard.php");
    exit();
}
