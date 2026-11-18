<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
if(!is_logged_in() || current_user()['role'] !== 'admin'){
header('Location: /login.php'); exit;
}
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Manage Users</title></head>
<body>
<h1>Users</h1>
<table border="1" cellpadding="10">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th></tr>
<?php foreach($users as $u): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= htmlspecialchars($u['name']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= htmlspecialchars($u['phone']) ?></td>
<td><?= $u['role'] ?></td>
<td><?= $u['status'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
