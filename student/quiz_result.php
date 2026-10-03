<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');
$sid = $_SESSION['user_id'];

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$quiz = $conn->query("SELECT q.*, c.class_name, c.id as class_id FROM quizzes q JOIN classes c ON c.id=q.class_id WHERE q.id=$quiz_id")->fetch_assoc();
if (!$quiz) { die("Quiz not found."); }

$sub = $conn->query("SELECT * FROM submissions WHERE quiz_id=$quiz_id AND student_id=$sid")->fetch_assoc();
if (!$sub) { die("You haven't attempted this quiz yet."); }

$questions = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id");
$q_map = [];
while ($q = $questions->fetch_assoc()) { $q_map[$q['id']] = $q; }

$answers = $conn->query("SELECT * FROM answers WHERE submission_id={$sub['id']}");
$just = isset($_GET['just_submitted']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Result — <?= h($quiz['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap" style="max-width:720px;">
  <a href="class.php?id=<?= $quiz['class_id'] ?>&tab=quizzes" style="font-size:13px;color:var(--muted);">← Back to <?= h($quiz['class_name']) ?></a>

  <?php if ($just): ?>
    <div class="alert alert-success" style="margin-top:16px;">🎉 Quiz submitted! Your mentor will confirm the final evaluation soon.</div>
  <?php endif; ?>

  <div class="card card-pad shadow" style="margin-top:10px;margin-bottom:20px;text-align:center;">
    <?php if ($sub['status'] === 'pending'): ?>
      <div style="font-size:34px;">⏳</div>
      <h2 style="margin:8px 0 4px;">Awaiting evaluation</h2>
      <p style="color:var(--muted);">Auto-graded score: <?= $sub['score'] ?>/<?= $sub['total_marks'] ?> — your mentor will confirm this shortly.</p>
    <?php else: ?>
      <div style="font-size:34px;"><?= ($sub['score']/$sub['total_marks']) >= 0.5 ? '🎉' : '📘' ?></div>
      <h2 style="margin:8px 0 4px;"><?= $sub['score'] ?> / <?= $sub['total_marks'] ?></h2>
      <p style="color:var(--muted);">Score: <?= round($sub['score']/$sub['total_marks']*100,1) ?>%</p>
      <?php if ($sub['mentor_feedback']): ?>
        <div style="margin-top:14px;background:#eef2ff;border-radius:10px;padding:12px;font-size:13.5px;">
          💬 <strong>Mentor feedback:</strong> <?= h($sub['mentor_feedback']) ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <h3 class="section-title" style="font-size:17px;">Review your answers</h3>
  <?php while ($a = $answers->fetch_assoc()):
    $q = $q_map[$a['question_id']] ?? null;
    if (!$q) continue;
  ?>
    <div class="card card-pad shadow" style="margin-bottom:12px;">
      <div class="q-text"><?= h($q['question_text']) ?></div>
      <div style="font-size:13.5px;color:<?= $a['is_correct']?'#0f9d58':'#e0483f' ?>;font-weight:600;">
        Your answer: <?= $a['selected_option'] ? strtoupper($a['selected_option']).'. '.h($q['option_'.$a['selected_option']]) : 'Not answered' ?> <?= $a['is_correct']?'✓':'✗' ?>
      </div>
      <?php if (!$a['is_correct']): ?>
        <div style="font-size:13px;color:var(--muted);margin-top:4px;">Correct answer: <?= strtoupper($q['correct_option']) ?>. <?= h($q['option_'.$q['correct_option']]) ?></div>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
</div>
</body>
</html>
