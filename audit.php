<?php
function audit_log($conn, $user_id, $action, $detail = null) {
    if (!$conn) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($user_id === null || $user_id === '') {
        $uid = null;
    } else {
        $uid = intval($user_id);
    }
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, detail, ip) VALUES (?, ?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("isss", $uid, $action, $detail, $ip);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
?>