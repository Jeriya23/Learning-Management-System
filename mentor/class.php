<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('mentor');

$class_id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM classes WHERE id=? AND mentor_id=?");
$stmt->bind_param("ii", $class_id, $_SESSION['user_id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
if (!$class) { die("Class not found or you don't have access."); }

// Handle posting an announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
    $msg = trim($_POST['announcement']);
    if ($msg) {
        $stmt = $conn->prepare("INSERT INTO announcements (class_id, mentor_id, message) VALUES (?,?,?)");
        $stmt->bind_param("iis", $class_id, $_SESSION['user_id'], $msg);
        $stmt->execute();
        // notify enrolled students
        $students = $conn->query("SELECT student_id FROM enrollments WHERE class_id=$class_id");
        while ($s = $students->fetch_assoc()) {
            push_notification($conn, $s['student_id'], "New announcement in {$class['class_name']}");
        }
    }
    header("Location: class.php?id=$class_id&tab=stream");
    exit;
}

// Handle adding material link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['material_title'])) {
    $title = trim($_POST['material_title']);
    $link = trim($_POST['material_link'] ?? '');
    if ($title) {
        $stmt = $conn->prepare("INSERT INTO materials (class_id, title, link_url) VALUES (?,?,?)");
        $stmt->bind_param("iss", $class_id, $title, $link);
        $stmt->execute();
    }
    header("Location: class.php?id=$class_id&tab=stream");
    exit;
}

$tab = $_GET['tab'] ?? 'stream';

$announcements = $conn->query("SELECT * FROM announcements WHERE class_id=$class_id ORDER BY created_at DESC");
$materials = $conn->query("SELECT * FROM materials WHERE class_id=$class_id ORDER BY uploaded_at DESC");

$students = $conn->query("SELECT u.id, u.name, u.email, u.avatar_color, e.joined_at,
    (SELECT COUNT(*) FROM submissions s JOIN quizzes q ON q.id=s.quiz_id WHERE q.class_id=$class_id AND s.student_id=u.id AND s.status='evaluated') as attempted,
    (SELECT AVG(s.score/s.total_marks*100) FROM submissions s JOIN quizzes q ON q.id=s.quiz_id WHERE q.class_id=$class_id AND s.student_id=u.id AND s.status='evaluated') as avg_pct
    FROM enrollments e JOIN users u ON u.id=e.student_id WHERE e.class_id=$class_id ORDER BY u.name");

$quizzes = $conn->query("SELECT q.*, 
    (SELECT COUNT(*) FROM submissions s WHERE s.quiz_id=q.id) as submitted_count,
    (SELECT COUNT(*) FROM submissions s WHERE s.quiz_id=q.id AND s.status='pending') as pending_count
    FROM quizzes q WHERE q.class_id=$class_id ORDER BY q.created_at DESC");
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
    <div style="margin-top:14px;display:flex;gap:10px;align-items:center;">
      <span style="background:#fff;color:#1f2430;font-weight:700;padding:6px 14px;border-radius:8px;font-size:14px;letter-spacing:.08em;">Code: <?= h($class['class_code']) ?></span>
      <span style="font-size:12.5px;opacity:.85;">Share this with students so they can join</span>
    </div>
  </div>

  <div class="tabs">
    <a class="tab <?= $tab==='stream'?'active':'' ?>" href="?id=<?= $class_id ?>&tab=stream">Stream</a>
    <a class="tab <?= $tab==='students'?'active':'' ?>" href="?id=<?= $class_id ?>&tab=students">Students (<?= $students->num_rows ?>)</a>
    <a class="tab <?= $tab==='quizzes'?'active':'' ?>" href="?id=<?= $class_id ?>&tab=quizzes">Quizzes (<?= $quizzes->num_rows ?>)</a>
  </div>

  <?php if ($tab === 'stream'): ?>
    <div class="two-col">
      <div class="card card-pad shadow">
        <form method="POST" style="margin-bottom:10px;">
          <textarea name="announcement" placeholder="Share something with your class..." required></textarea>
          <button class="btn btn-primary" style="margin-top:10px;" type="submit">📣 Post</button>
        </form>
        <hr style="border:none;border-top:1px solid var(--border);margin:14px 0;">
        <?php if ($announcements->num_rows === 0): ?>
          <p style="color:var(--muted);font-size:13.5px;">No announcements yet.</p>
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
        <form method="POST" style="margin-bottom:14px;">
          <div class="form-group"><input type="text" name="material_title" placeholder="Title, e.g. Week 1 Notes" required></div>
          <div class="form-group"><input type="text" name="material_link" placeholder="https:// link (optional)"></div>
          <button class="btn btn-outline btn-sm" type="submit">+ Add material</button>
        </form>
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

  <?php elseif ($tab === 'students'): ?>
    <div class="card shadow" style="overflow:hidden;">
      <?php if ($students->num_rows === 0): ?>
        <div class="empty-state"><div class="big-icon">🧑‍🎓</div>No students have joined yet. Share code <b><?= h($class['class_code']) ?></b>.</div>
      <?php else: ?>
      <table>
        <tr><th>Student</th><th>Email</th><th>Joined</th><th>Quizzes evaluated</th><th>Average score</th></tr>
        <?php while ($s = $students->fetch_assoc()): ?>
        <tr>
          <td style="display:flex;align-items:center;gap:8px;">
            <span class="avatar" style="width:28px;height:28px;font-size:11px;background:<?= h($s['avatar_color']) ?>"><?= h(strtoupper(substr($s['name'],0,1))) ?></span>
            <?= h($s['name']) ?>
          </td>
          <td><?= h($s['email']) ?></td>
          <td><?= date('d M Y', strtotime($s['joined_at'])) ?></td>
          <td><?= (int)$s['attempted'] ?></td>
          <td><?= $s['avg_pct'] !== null ? round($s['avg_pct'],1).'%' : '—' ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>

  <?php elseif ($tab === 'quizzes'): ?>
    <div class="flex" style="justify-content:flex-end;margin-bottom:16px;">
      <a href="create_quiz.php?class_id=<?= $class_id ?>" class="btn btn-primary">+ Create quiz</a>
    </div>
    <?php if ($quizzes->num_rows === 0): ?>
      <div class="empty-state card"><div class="big-icon">📝</div>No quizzes yet. Create one for your students.</div>
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
        <div class="flex gap-16" style="align-items:center;">
          <?php if ($q['pending_count'] > 0): ?>
            <span class="badge badge-pending"><?= $q['pending_count'] ?> to evaluate</span>
          <?php endif; ?>
          <span style="font-size:12.5px;color:var(--muted);"><?= $q['submitted_count'] ?> submitted</span>
          <a href="quiz_submissions.php?quiz_id=<?= $q['id'] ?>" class="btn btn-outline btn-sm">View submissions</a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>

</div>
</body>
</html>
