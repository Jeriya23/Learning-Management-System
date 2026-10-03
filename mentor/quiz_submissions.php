<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('mentor');

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$quiz = $conn->query("SELECT q.*, c.class_name, c.mentor_id, c.id as class_id FROM quizzes q JOIN classes c ON c.id=q.class_id WHERE q.id=$quiz_id")->fetch_assoc();
if (!$quiz || $quiz['mentor_id'] != $_SESSION['user_id']) { die("Quiz not found."); }

// Handle grade override + feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sub_id = (int)$_POST['submission_id'];
    $score = (int)$_POST['score'];
    $feedback = trim($_POST['feedback'] ?? '');
    $stmt = $conn->prepare("UPDATE submissions SET score=?, status='evaluated', graded_at=NOW(), mentor_feedback=? WHERE id=? AND quiz_id=?");
    $stmt->bind_param("isii", $score, $feedback, $sub_id, $quiz_id);
    $stmt->execute();

    $stu = $conn->query("SELECT student_id FROM submissions WHERE id=$sub_id")->fetch_assoc();
    if ($stu) {
        push_notification($conn, $stu['student_id'], "Your quiz \"{$quiz['title']}\" was evaluated — score: $score/{$quiz['total_marks']}");
    }
    header("Location: quiz_submissions.php?quiz_id=$quiz_id");
    exit;
}

$subs = $conn->query("SELECT s.*, u.name, u.email, u.avatar_color FROM submissions s
    JOIN users u ON u.id=s.student_id WHERE s.quiz_id=$quiz_id ORDER BY s.submitted_at DESC");

$questions = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id");
$q_map = [];
while ($qq = $questions->fetch_assoc()) { $q_map[$qq['id']] = $qq; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Submissions — <?= h($quiz['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap">
  <a href="class.php?id=<?= $quiz['class_id'] ?>&tab=quizzes" style="font-size:13px;color:var(--muted);">← Back to <?= h($quiz['class_name']) ?></a>
  <h1 class="section-title" style="margin-top:8px;"><?= h($quiz['title']) ?> — Submissions</h1>
  <p class="section-sub">Total marks: <?= $quiz['total_marks'] ?> · Auto-graded on submit, override below if needed.</p>

  <?php if ($subs->num_rows === 0): ?>
    <div class="empty-state card"><div class="big-icon">📭</div>No submissions yet.</div>
  <?php endif; ?>

  <?php while ($s = $subs->fetch_assoc()): ?>
    <div class="card card-pad shadow" style="margin-bottom:14px;">
      <div class="flex" style="justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
        <div class="flex gap-16" style="align-items:center;">
          <span class="avatar" style="background:<?= h($s['avatar_color']) ?>"><?= h(strtoupper(substr($s['name'],0,1))) ?></span>
          <div>
            <div style="font-weight:700;"><?= h($s['name']) ?></div>
            <div style="font-size:12.5px;color:var(--muted);"><?= h($s['email']) ?> · submitted <?= date('d M, h:i A', strtotime($s['submitted_at'])) ?></div>
          </div>
        </div>
        <span class="badge <?= $s['status']==='evaluated' ? 'badge-done':'badge-pending' ?>">
          <?= $s['status']==='evaluated' ? 'Evaluated' : 'Pending review' ?>
        </span>
      </div>

      <details style="margin-top:14px;">
        <summary style="cursor:pointer;font-size:13.5px;color:var(--primary);font-weight:600;">View answers</summary>
        <?php
        $answers = $conn->query("SELECT * FROM answers WHERE submission_id={$s['id']}");
        while ($a = $answers->fetch_assoc()):
          $q = $q_map[$a['question_id']] ?? null;
          if (!$q) continue;
        ?>
          <div style="padding:10px 0;border-bottom:1px solid var(--border);font-size:13.5px;">
            <div style="font-weight:600;"><?= h($q['question_text']) ?></div>
            <div style="margin-top:4px;color:<?= $a['is_correct'] ? '#0f9d58' : '#e0483f' ?>;">
              Student chose: <?= $a['selected_option'] ? strtoupper($a['selected_option']).'. '.h($q['option_'.$a['selected_option']]) : '— not answered —' ?>
              <?= $a['is_correct'] ? '✓' : '✗' ?>
            </div>
            <?php if (!$a['is_correct']): ?>
              <div style="color:var(--muted);">Correct answer: <?= strtoupper($q['correct_option']) ?>. <?= h($q['option_'.$q['correct_option']]) ?></div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </details>

      <form method="POST" class="flex gap-16" style="margin-top:14px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
        <div class="form-group" style="margin-bottom:0;">
          <label>Score (out of <?= $quiz['total_marks'] ?>)</label>
          <input type="number" name="score" value="<?= $s['score'] ?>" min="0" max="<?= $quiz['total_marks'] ?>" required style="width:120px;">
        </div>
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px;">
          <label>Feedback (optional)</label>
          <input type="text" name="feedback" value="<?= h($s['mentor_feedback']) ?>" placeholder="Good job! Review chapter 3 again.">
        </div>
        <button class="btn btn-primary" type="submit"><?= $s['status']==='evaluated' ? 'Update' : 'Confirm evaluation' ?></button>
      </form>
    </div>
  <?php endwhile; ?>
</div>
</body>
</html>
