<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('mentor');

$class_id = (int)($_GET['class_id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM classes WHERE id=? AND mentor_id=?");
$stmt->bind_param("ii", $class_id, $_SESSION['user_id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
if (!$class) { die("Class not found."); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 15);
    $due = $_POST['due_date'] ?? null;
    $qtext = $_POST['q_text'] ?? [];
    $opt_a = $_POST['opt_a'] ?? [];
    $opt_b = $_POST['opt_b'] ?? [];
    $opt_c = $_POST['opt_c'] ?? [];
    $opt_d = $_POST['opt_d'] ?? [];
    $correct = $_POST['correct'] ?? [];
    $marks = $_POST['q_marks'] ?? [];

    if (!$title || count($qtext) === 0) {
        $error = "Add a title and at least one question.";
    } else {
        $total_marks = array_sum(array_map('intval', $marks));
        $due_sql = $due ? date('Y-m-d H:i:s', strtotime($due)) : null;

        $stmt = $conn->prepare("INSERT INTO quizzes (class_id, title, description, duration_minutes, total_marks, due_date) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issiis", $class_id, $title, $desc, $duration, $total_marks, $due_sql);
        $stmt->execute();
        $quiz_id = $conn->insert_id;

        $qstmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks) VALUES (?,?,?,?,?,?,?,?)");
        for ($i = 0; $i < count($qtext); $i++) {
            if (trim($qtext[$i]) === '') continue;
            $m = (int)($marks[$i] ?? 1);
            $c = $correct[$i] ?? 'a';
            $qstmt->bind_param("issssssi", $quiz_id, $qtext[$i], $opt_a[$i], $opt_b[$i], $opt_c[$i], $opt_d[$i], $c, $m);
            $qstmt->execute();
        }

        // notify students
        $students = $conn->query("SELECT student_id FROM enrollments WHERE class_id=$class_id");
        while ($s = $students->fetch_assoc()) {
            push_notification($conn, $s['student_id'], "New quiz \"$title\" assigned in {$class['class_name']}");
        }

        header("Location: class.php?id=$class_id&tab=quizzes");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Create quiz — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap" style="max-width:760px;">
  <h1 class="section-title">Create quiz for <?= h($class['class_name']) ?></h1>
  <p class="section-sub">Add multiple-choice questions. Objective answers will be auto-graded the moment a student submits.</p>

  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

  <form method="POST" id="quizForm">
    <div class="card card-pad shadow" style="margin-bottom:18px;">
      <div class="form-group"><label>Quiz title *</label><input type="text" name="title" required placeholder="e.g. Chapter 3 — Loops"></div>
      <div class="form-group"><label>Description</label><textarea name="description" placeholder="Optional instructions for students"></textarea></div>
      <div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" value="15" min="1" required></div>
        <div class="form-group"><label>Due date (optional)</label><input type="datetime-local" name="due_date"></div>
      </div>
    </div>

    <div id="questionsWrap"></div>

    <button type="button" class="btn btn-outline" id="addQBtn" style="margin-bottom:18px;">+ Add question</button>
    <div class="flex" style="justify-content:flex-end;gap:10px;">
      <a href="class.php?id=<?= $class_id ?>&tab=quizzes" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary" type="submit">Publish quiz</button>
    </div>
  </form>
</div>

<template id="qTemplate">
  <div class="card card-pad shadow q-edit-block" style="margin-bottom:14px;">
    <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:10px;">
      <strong class="q-num">Question</strong>
      <button type="button" class="btn btn-red btn-sm remove-q">Remove</button>
    </div>
    <div class="form-group"><input type="text" name="q_text[]" placeholder="Question text" required></div>
    <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px;">
      <div class="form-group"><input type="text" name="opt_a[]" placeholder="Option A" required></div>
      <div class="form-group"><input type="text" name="opt_b[]" placeholder="Option B" required></div>
      <div class="form-group"><input type="text" name="opt_c[]" placeholder="Option C" required></div>
      <div class="form-group"><input type="text" name="opt_d[]" placeholder="Option D" required></div>
    </div>
    <div class="grid" style="grid-template-columns:2fr 1fr;gap:10px;">
      <div class="form-group">
        <label>Correct option</label>
        <select name="correct[]">
          <option value="a">Option A</option>
          <option value="b">Option B</option>
          <option value="c">Option C</option>
          <option value="d">Option D</option>
        </select>
      </div>
      <div class="form-group"><label>Marks</label><input type="number" name="q_marks[]" value="1" min="1" required></div>
    </div>
  </div>
</template>

<script>
  const wrap = document.getElementById('questionsWrap');
  const tpl = document.getElementById('qTemplate');
  let qCount = 0;

  function addQuestion(){
    qCount++;
    const node = tpl.content.cloneNode(true);
    node.querySelector('.q-num').textContent = 'Question ' + qCount;
    wrap.appendChild(node);
    renumber();
  }
  function renumber(){
    document.querySelectorAll('.q-edit-block .q-num').forEach((el,i)=> el.textContent = 'Question ' + (i+1));
  }
  document.getElementById('addQBtn').addEventListener('click', addQuestion);
  wrap.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-q')) {
      e.target.closest('.q-edit-block').remove();
      renumber();
    }
  });
  addQuestion(); // start with one question
  document.getElementById('quizForm').addEventListener('submit', (e) => {
    if (wrap.children.length === 0) {
      e.preventDefault();
      alert('Add at least one question.');
    }
  });
</script>
</body>
</html>
