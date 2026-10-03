<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id={$_SESSION['user_id']}");
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id={$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Notifications — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap" style="max-width:640px;">
  <h1 class="section-title">Notifications</h1>
  <div class="card shadow">
    <?php if ($notifs->num_rows === 0): ?>
      <div class="empty-state"><div class="big-icon">🔔</div>Nothing here yet.</div>
    <?php endif; ?>
    <?php while ($n = $notifs->fetch_assoc()): ?>
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-size:13.5px;">
        <?= h($n['message']) ?>
        <div style="font-size:11.5px;color:var(--muted);margin-top:3px;"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>
