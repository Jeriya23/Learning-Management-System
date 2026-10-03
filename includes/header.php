<?php
// Expects $conn and session already loaded by includes/auth.php
$initials = '';
foreach (explode(' ', $_SESSION['name'] ?? '?') as $p) { $initials .= strtoupper($p[0] ?? ''); }
$initials = substr($initials, 0, 2);
$unread = unread_notif_count($conn, $_SESSION['user_id']);
$home_link = ($_SESSION['role'] === 'mentor') ? '/lms/mentor/dashboard.php' : '/lms/student/dashboard.php';
?>
<div class="topbar">
  <a href="<?= $home_link ?>" class="brand"><span class="logo-dot"></span> EduSphere</a>
  <div class="topbar-right">
    <a href="<?= ($_SESSION['role']==='mentor') ? '/lms/mentor/notifications.php' : '/lms/student/notifications.php' ?>" class="notif-bell">
      🔔<?php if ($unread > 0): ?><span class="notif-badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <div class="avatar" style="background:<?= h($_SESSION['avatar_color'] ?? '#3b6ef0') ?>"><?= h($initials) ?></div>
    <span style="font-size:13.5px;font-weight:600;"><?= h($_SESSION['name']) ?> <span style="color:#9aa0ab;font-weight:400;">(<?= h($_SESSION['role']) ?>)</span></span>
    <a href="/lms/logout.php" class="logout-link">Log out</a>
  </div>
</div>
