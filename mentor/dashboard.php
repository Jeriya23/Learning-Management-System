<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('mentor');
$mentor_id = $_SESSION['user_id'];

// Stat counts
$classes = $conn->query("SELECT c.*, 
    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id=c.id) as student_count,
    (SELECT COUNT(*) FROM quizzes q WHERE q.class_id=c.id) as quiz_count
    FROM classes c WHERE c.mentor_id=$mentor_id ORDER BY c.created_at DESC");

$total_classes = $classes->num_rows;

$stmt = $conn->query("SELECT COUNT(DISTINCT e.student_id) c FROM enrollments e 
    JOIN classes cl ON cl.id=e.class_id WHERE cl.mentor_id=$mentor_id");
$total_students = $stmt->fetch_assoc()['c'];

$stmt = $conn->query("SELECT COUNT(*) c FROM quizzes q JOIN classes cl ON cl.id=q.class_id WHERE cl.mentor_id=$mentor_id");
$total_quizzes = $stmt->fetch_assoc()['c'];

$stmt = $conn->query("SELECT COUNT(*) c FROM submissions s JOIN quizzes q ON q.id=s.quiz_id JOIN classes cl ON cl.id=q.class_id 
    WHERE cl.mentor_id=$mentor_id AND s.status='pending'");
$pending_eval = $stmt->fetch_assoc()['c'];

// Chart data: average score per class
$chart_q = $conn->query("SELECT cl.class_name, AVG(s.score/s.total_marks*100) as avg_pct
    FROM classes cl LEFT JOIN quizzes q ON q.class_id=cl.id LEFT JOIN submissions s ON s.quiz_id=q.id AND s.status='evaluated'
    WHERE cl.mentor_id=$mentor_id GROUP BY cl.id");
$chart_labels = []; $chart_data = [];
while ($row = $chart_q->fetch_assoc()) {
    $chart_labels[] = $row['class_name'];
    $chart_data[] = round($row['avg_pct'] ?? 0, 1);
}

// Chart 2: submissions over last 7 days
$trend_q = $conn->query("SELECT DATE(s.submitted_at) d, COUNT(*) c FROM submissions s
    JOIN quizzes q ON q.id=s.quiz_id JOIN classes cl ON cl.id=q.class_id
    WHERE cl.mentor_id=$mentor_id AND s.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(s.submitted_at) ORDER BY d");
$trend_labels = []; $trend_data = [];
while ($row = $trend_q->fetch_assoc()) { $trend_labels[] = $row['d']; $trend_data[] = (int)$row['c']; }

$classes->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Mentor Dashboard — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap">

  <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:6px;">
    <h1 class="section-title">Welcome back, <?= h(explode(' ', $_SESSION['name'])[0]) ?> 👋</h1>
    <a href="create_class.php" class="btn btn-primary">+ Create class</a>
  </div>
  <p class="section-sub">Here's how all your classes are doing.</p>

  <div class="stat-row">
    <div class="stat-card"><div class="num"><?= $total_classes ?></div><div class="lbl">Classes</div></div>
    <div class="stat-card"><div class="num"><?= $total_students ?></div><div class="lbl">Total students</div></div>
    <div class="stat-card"><div class="num"><?= $total_quizzes ?></div><div class="lbl">Quizzes created</div></div>
    <div class="stat-card"><div class="num" style="color:<?= $pending_eval>0?'#e0483f':'#0f9d58' ?>"><?= $pending_eval ?></div><div class="lbl">Pending evaluations</div></div>
  </div>

  <div class="two-col" style="margin-bottom:32px;">
    <div class="chart-card">
      <h3 style="margin-top:0;font-size:15px;">Average score by class</h3>
      <canvas id="avgChart" height="180"></canvas>
    </div>
    <div class="chart-card">
      <h3 style="margin-top:0;font-size:15px;">Submissions — last 7 days</h3>
      <canvas id="trendChart" height="180"></canvas>
    </div>
  </div>

  <h2 class="section-title">Your classes</h2>
  <?php if ($total_classes === 0): ?>
    <div class="empty-state card">
      <div class="big-icon">🏫</div>
      <p>You haven't created a class yet.</p>
      <a href="create_class.php" class="btn btn-primary">Create your first class</a>
    </div>
  <?php else: ?>
  <div class="class-grid">
    <?php while ($c = $classes->fetch_assoc()): ?>
      <a href="class.php?id=<?= $c['id'] ?>" class="class-card">
        <div class="class-banner" style="background:<?= h($c['cover_color']) ?>">
          <h3><?= h($c['class_name']) ?></h3>
          <p><?= h($c['subject'] ?: 'General') ?> <?= $c['section'] ? '· '.h($c['section']) : '' ?></p>
          <div class="code-chip"><?= h($c['class_code']) ?></div>
        </div>
        <div class="class-body">
          <?= h(mb_strimwidth($c['description'] ?: 'No description yet.', 0, 70, '…')) ?>
          <div class="meta-row">
            <span>👥 <?= $c['student_count'] ?> students</span>
            <span>📝 <?= $c['quiz_count'] ?> quizzes</span>
          </div>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>

</div>
<script>
new Chart(document.getElementById('avgChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{ label:'Avg %', data: <?= json_encode($chart_data) ?>, backgroundColor:'#3b6ef0', borderRadius:6 }]
  },
  options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,max:100}} }
});
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($trend_labels) ?>,
    datasets: [{ label:'Submissions', data: <?= json_encode($trend_data) ?>, borderColor:'#0f9d58', backgroundColor:'rgba(15,157,88,0.15)', fill:true, tension:.35 }]
  },
  options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
});
</script>
</body>
</html>
