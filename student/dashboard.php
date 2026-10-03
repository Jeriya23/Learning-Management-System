<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');
$sid = $_SESSION['user_id'];

$classes = $conn->query("SELECT c.*, 
    (SELECT COUNT(*) FROM quizzes q WHERE q.class_id=c.id) as quiz_count
    FROM classes c JOIN enrollments e ON e.class_id=c.id WHERE e.student_id=$sid ORDER BY e.joined_at DESC");
$total_classes = $classes->num_rows;

$pending_q = $conn->query("SELECT COUNT(*) c FROM quizzes q
    JOIN enrollments e ON e.class_id=q.class_id
    LEFT JOIN submissions s ON s.quiz_id=q.id AND s.student_id=$sid
    WHERE e.student_id=$sid AND s.id IS NULL");
$pending_quizzes = $pending_q->fetch_assoc()['c'];

$done_q = $conn->query("SELECT COUNT(*) c FROM submissions WHERE student_id=$sid AND status='evaluated'");
$done_quizzes = $done_q->fetch_assoc()['c'];

$avg_q = $conn->query("SELECT AVG(score/total_marks*100) a FROM submissions WHERE student_id=$sid AND status='evaluated'");
$avg_score = $avg_q->fetch_assoc()['a'];

$awaiting_q = $conn->query("SELECT COUNT(*) c FROM submissions WHERE student_id=$sid AND status='pending'");
$awaiting = $awaiting_q->fetch_assoc()['c'];

// Score trend chart
$trend = $conn->query("SELECT q.title, s.score, s.total_marks, s.graded_at FROM submissions s JOIN quizzes q ON q.id=s.quiz_id
    WHERE s.student_id=$sid AND s.status='evaluated' ORDER BY s.graded_at ASC");
$trend_labels = []; $trend_data = [];
while ($t = $trend->fetch_assoc()) {
    $trend_labels[] = $t['title'];
    $trend_data[] = round($t['score']/$t['total_marks']*100, 1);
}

// Per-class average chart
$class_avg = $conn->query("SELECT c.class_name, AVG(s.score/s.total_marks*100) a FROM classes c
    JOIN quizzes q ON q.class_id=c.id JOIN submissions s ON s.quiz_id=q.id
    WHERE s.student_id=$sid AND s.status='evaluated' GROUP BY c.id");
$class_labels = []; $class_data = [];
while ($c = $class_avg->fetch_assoc()) { $class_labels[] = $c['class_name']; $class_data[] = round($c['a'],1); }

$classes->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Student Dashboard — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap">

  <div class="flex" style="justify-content:space-between;align-items:center;">
    <h1 class="section-title">Hey <?= h(explode(' ', $_SESSION['name'])[0]) ?>, ready to learn? 🚀</h1>
    <a href="join_class.php" class="btn btn-primary">+ Join class</a>
  </div>
  <p class="section-sub">Track your classes, quizzes and grades here.</p>

  <div class="stat-row">
    <div class="stat-card"><div class="num"><?= $total_classes ?></div><div class="lbl">Classes joined</div></div>
    <div class="stat-card"><div class="num" style="color:#e0483f"><?= $pending_quizzes ?></div><div class="lbl">Quizzes to attempt</div></div>
    <div class="stat-card"><div class="num" style="color:#f4a937"><?= $awaiting ?></div><div class="lbl">Awaiting evaluation</div></div>
    <div class="stat-card"><div class="num" style="color:#0f9d58"><?= $avg_score!==null?round($avg_score,1).'%':'—' ?></div><div class="lbl">Average score (<?= $done_quizzes ?> graded)</div></div>
  </div>

  <div class="two-col" style="margin-bottom:32px;">
    <div class="chart-card">
      <h3 style="margin-top:0;font-size:15px;">Score trend across quizzes</h3>
      <canvas id="trendChart" height="180"></canvas>
    </div>
    <div class="chart-card">
      <h3 style="margin-top:0;font-size:15px;">Average score per class</h3>
      <canvas id="classChart" height="180"></canvas>
    </div>
  </div>

  <h2 class="section-title">Your classes</h2>
  <?php if ($total_classes === 0): ?>
    <div class="empty-state card">
      <div class="big-icon">🎒</div>
      <p>You haven't joined a class yet. Ask your mentor for the class code.</p>
      <a href="join_class.php" class="btn btn-primary">Join a class</a>
    </div>
  <?php else: ?>
  <div class="class-grid">
    <?php while ($c = $classes->fetch_assoc()): ?>
      <a href="class.php?id=<?= $c['id'] ?>" class="class-card">
        <div class="class-banner" style="background:<?= h($c['cover_color']) ?>">
          <h3><?= h($c['class_name']) ?></h3>
          <p><?= h($c['subject'] ?: 'General') ?> <?= $c['section'] ? '· '.h($c['section']) : '' ?></p>
        </div>
        <div class="class-body">
          <?= h(mb_strimwidth($c['description'] ?: 'No description yet.', 0, 70, '…')) ?>
          <div class="meta-row"><span>📝 <?= $c['quiz_count'] ?> quizzes</span></div>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: { labels: <?= json_encode($trend_labels) ?>,
    datasets: [{ label:'Score %', data: <?= json_encode($trend_data) ?>, borderColor:'#3b6ef0', backgroundColor:'rgba(59,110,240,0.15)', fill:true, tension:.35 }] },
  options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,max:100}} }
});
new Chart(document.getElementById('classChart'), {
  type: 'bar',
  data: { labels: <?= json_encode($class_labels) ?>,
    datasets: [{ label:'Avg %', data: <?= json_encode($class_data) ?>, backgroundColor:'#0f9d58', borderRadius:6 }] },
  options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,max:100}} }
});
</script>
</body>
</html>
