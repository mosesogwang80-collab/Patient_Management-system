<?php
// Run once to reset all lost passwords to: password123
include 'db.php';
$pwd = 'password123';
$conn->query("UPDATE users SET password='$pwd'");
$conn->query("UPDATE patients SET password='$pwd'");
echo "Passwords reset to: $pwd";

