<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');
$sid = $_SESSION['user_id'];

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$quiz = $conn->query("SELECT q.*, c.class_name, c.id as class_id, c.mentor_id FROM quizzes q JOIN classes c ON c.id=q.class_id WHERE q.id=$quiz_id")->fetch_assoc();
if (!$quiz) { die("Quiz not found."); }

// verify enrollment
$enroll = $conn->query("SELECT id FROM enrollments WHERE class_id={$quiz['class_id']} AND student_id=$sid");
if ($enroll->num_rows === 0) { die("You are not enrolled in this class."); }

// already submitted?
$existing = $conn->query("SELECT id FROM submissions WHERE quiz_id=$quiz_id AND student_id=$sid");
if ($existing->num_rows > 0) {
    header("Location: quiz_result.php?quiz_id=$quiz_id");
    exit;
}

$questions = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id");
$q_list = [];
while ($row = $questions->fetch_assoc()) { $q_list[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO submissions (quiz_id, student_id, score, total_marks, status) VALUES (?,?,0,?,'pending')");
    $stmt->bind_param("iii", $quiz_id, $sid, $quiz['total_marks']);
    $stmt->execute();
    $sub_id = $conn->insert_id;

    $score = 0;
    $astmt = $conn->prepare("INSERT INTO answers (submission_id, question_id, selected_option, is_correct) VALUES (?,?,?,?)");
    foreach ($q_list as $q) {
        $selected = $_POST['answer_' . $q['id']] ?? null;
        $is_correct = ($selected !== null && $selected === $q['correct_option']) ? 1 : 0;
        if ($is_correct) $score += (int)$q['marks'];
        $astmt->bind_param("iisi", $sub_id, $q['id'], $selected, $is_correct);
        $astmt->execute();
    }
    $upd = $conn->prepare("UPDATE submissions SET score=? WHERE id=?");
    $upd->bind_param("ii", $score, $sub_id);
    $upd->execute();

    push_notification($conn, $quiz['mentor_id'], "{$_SESSION['name']} submitted \"{$quiz['title']}\" — awaiting your evaluation");

    header("Location: quiz_result.php?quiz_id=$quiz_id&just_submitted=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title><?= h($quiz['title']) ?> — Quiz</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap" style="max-width:720px;">

  <div class="quiz-timer">
    <span>⏱ Time remaining</span>
    <span id="timerDisplay"><?= sprintf('%02d:00', $quiz['duration_minutes']) ?></span>
  </div>

  <h1 class="section-title"><?= h($quiz['title']) ?></h1>
  <p class="section-sub"><?= h($quiz['description']) ?> — <?= count($q_list) ?> questions · <?= $quiz['total_marks'] ?> marks total</p>

  <form method="POST" id="quizForm">
    <?php foreach ($q_list as $i => $q): ?>
      <div class="card card-pad shadow q-block" style="margin-bottom:14px;">
        <div class="q-text">Q<?= $i+1 ?>. <?= h($q['question_text']) ?> <span style="color:var(--muted);font-weight:400;">(<?= $q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?>)</span></div>
        <?php foreach (['a','b','c','d'] as $opt): ?>
          <label class="opt-row">
            <input type="radio" name="answer_<?= $q['id'] ?>" value="<?= $opt ?>" required>
            <?= strtoupper($opt) ?>. <?= h($q['option_'.$opt]) ?>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <div class="flex" style="justify-content:flex-end;">
      <button class="btn btn-primary" type="submit" id="submitBtn">Submit quiz</button>
    </div>
  </form>
</div>

<script>
  let secondsLeft = <?= (int)$quiz['duration_minutes'] * 60 ?>;
  const display = document.getElementById('timerDisplay');
  const form = document.getElementById('quizForm');
  let autoSubmitted = false;

  function tick(){
    if (secondsLeft <= 0) {
      if (!autoSubmitted) { autoSubmitted = true; form.submit(); }
      return;
    }
    secondsLeft--;
    const m = Math.floor(secondsLeft/60), s = secondsLeft%60;
    display.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    if (secondsLeft <= 30) display.style.color = '#e0483f';
  }
  setInterval(tick, 1000);

  form.addEventListener('submit', () => {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = 'Submitting...';
  });
</script>
</body>
</html>
