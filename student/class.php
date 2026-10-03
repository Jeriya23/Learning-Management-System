<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');
$sid = $_SESSION['user_id'];

$class_id = (int)($_GET['id'] ?? 0);
$check = $conn->prepare("SELECT * FROM classes c JOIN enrollments e ON e.class_id=c.id WHERE c.id=? AND e.student_id=?");
$check->bind_param("ii", $class_id, $sid);
$check->execute();
$class = $check->get_result()->fetch_assoc();
if (!$class) { die("You are not enrolled in this class."); }

$tab = $_GET['tab'] ?? 'stream';

$announcements = $conn->query("SELECT * FROM announcements WHERE class_id=$class_id ORDER BY created_at DESC");
$materials = $conn->query("SELECT * FROM materials WHERE class_id=$class_id ORDER BY uploaded_at DESC");

$quizzes = $conn->query("SELECT q.*, s.id as sub_id, s.status as sub_status, s.score as sub_score
    FROM quizzes q LEFT JOIN submissions s ON s.quiz_id=q.id AND s.student_id=$sid
    WHERE q.class_id=$class_id ORDER BY q.created_at DESC");

$leaderboard = $conn->query("SELECT u.name, u.avatar_color, AVG(s.score/s.total_marks*100) avg_pct, COUNT(s.id) attempts
    FROM enrollments e JOIN users u ON u.id=e.student_id
    LEFT JOIN submissions s ON s.student_id=u.id AND s.status='evaluated'
        AND s.quiz_id IN (SELECT id FROM quizzes WHERE class_id=$class_id)
    WHERE e.class_id=$class_id GROUP BY u.id HAVING attempts > 0 ORDER BY avg_pct DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title><?= h($class['class_name']) ?> — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap">

  <div class="class-banner card" style="background:<?= h($class['cover_color']) ?>;border-radius:14px;padding:26px;margin-bottom:22px;">
    <h1 style="margin:0;font-size:26px;">📘 <?= h($class['class_name']) ?></h1>
    <p style="margin:6px 0 0;"><?= h($class['subject']) ?> <?= $class['section'] ? '· '.h($class['section']) : '' ?></p>
  </div>

  <div class="tabs">
    <a class="tab <?= $tab==='stream'?'active':'' ?>" href="?id=<?= $class_id ?>&tab=stream">Stream</a>
    <a class="tab <?= $tab==='quizzes'?'active':'' ?>" href="?id=<?= $class_id ?>&tab=quizzes">Quizzes (<?= $quizzes->num_rows ?>)</a>
    <a class="tab <?= $tab==='leaderboard'?'active':'' ?>" href="?id=<?= $class_id ?>&tab=leaderboard">🏆 Leaderboard</a>
  </div>

  <?php if ($tab === 'stream'): ?>
    <div class="two-col">
      <div class="card card-pad shadow">
        <h3 style="margin-top:0;font-size:15px;">📣 Announcements</h3>
        <?php if ($announcements->num_rows === 0): ?>
          <p style="color:var(--muted);font-size:13.5px;">Nothing posted yet.</p>
        <?php endif; ?>
        <?php while ($a = $announcements->fetch_assoc()): ?>
          <div class="stream-post">
            <div class="pavatar" style="background:<?= h($class['cover_color']) ?>">🧑‍🏫</div>
            <div>
              <div><?= nl2br(h($a['message'])) ?></div>
              <div class="ptime"><?= date('d M Y, h:i A', strtotime($a['created_at'])) ?></div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
      <div class="card card-pad shadow">
        <h3 style="margin-top:0;font-size:15px;">📎 Materials</h3>
        <?php if ($materials->num_rows === 0): ?>
          <p style="color:var(--muted);font-size:13px;">Nothing shared yet.</p>
        <?php endif; ?>
        <?php while ($m = $materials->fetch_assoc()): ?>
          <div style="padding:9px 0;border-bottom:1px solid var(--border);font-size:13.5px;">
            📄 <?= $m['link_url'] ? '<a href="'.h($m['link_url']).'" target="_blank" style="color:var(--primary);font-weight:600;">'.h($m['title']).'</a>' : h($m['title']) ?>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

  <?php elseif ($tab === 'quizzes'): ?>
    <?php if ($quizzes->num_rows === 0): ?>
      <div class="empty-state card"><div class="big-icon">📝</div>No quizzes assigned yet.</div>
    <?php endif; ?>
    <?php while ($q = $quizzes->fetch_assoc()): ?>
      <div class="card card-pad shadow" style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <div>
          <div style="font-weight:700;font-size:16px;"><?= h($q['title']) ?></div>
          <div style="color:var(--muted);font-size:13px;margin-top:4px;">
            ⏱ <?= $q['duration_minutes'] ?> min · 🎯 <?= $q['total_marks'] ?> marks
            <?php if ($q['due_date']): ?> · 📅 Due <?= date('d M, h:i A', strtotime($q['due_date'])) ?><?php endif; ?>
          </div>
        </div>
        <div>
          <?php if (!$q['sub_id']): ?>
            <a href="take_quiz.php?quiz_id=<?= $q['id'] ?>" class="btn btn-primary btn-sm">Attempt now</a>
          <?php elseif ($q['sub_status'] === 'pending'): ?>
            <span class="badge badge-pending">Awaiting evaluation</span>
          <?php else: ?>
            <a href="quiz_result.php?quiz_id=<?= $q['id'] ?>" class="btn btn-outline btn-sm">View result (<?= $q['sub_score'] ?>/<?= $q['total_marks'] ?>)</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>

  <?php elseif ($tab === 'leaderboard'): ?>
    <div class="card shadow" style="overflow:hidden;">
      <?php if ($leaderboard->num_rows === 0): ?>
        <div class="empty-state"><div class="big-icon">🏆</div>No graded quizzes yet — be the first to top the board!</div>
      <?php else: ?>
      <table>
        <tr><th>Rank</th><th>Student</th><th>Avg score</th><th>Quizzes graded</th></tr>
        <?php $rank = 1; while ($l = $leaderboard->fetch_assoc()): ?>
        <tr>
          <td><?= $rank==1?'🥇':($rank==2?'🥈':($rank==3?'🥉':$rank)) ?></td>
          <td style="display:flex;align-items:center;gap:8px;">
            <span class="avatar" style="width:28px;height:28px;font-size:11px;background:<?= h($l['avatar_color']) ?>"><?= h(strtoupper(substr($l['name'],0,1))) ?></span>
            <?= h($l['name']) ?>
          </td>
          <td><?= round($l['avg_pct'],1) ?>%</td>
          <td><?= $l['attempts'] ?></td>
        </tr>
        <?php $rank++; endwhile; ?>
      </table>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
