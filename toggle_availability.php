<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
include 'db.php';
$uid = $_SESSION['user_id'];
$conn->query("UPDATE users SET available = 1 - available WHERE id=$uid");
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
