<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('mentor');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['class_name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $color = $_POST['cover_color'] ?? '#3b6ef0';

    if (!$name) {
        $error = "Class name is required.";
    } else {
        // Generate a unique class code
        do {
            $code = gen_class_code(6);
            $check = $conn->prepare("SELECT id FROM classes WHERE class_code=?");
            $check->bind_param("s", $code);
            $check->execute();
        } while ($check->get_result()->num_rows > 0);

        $stmt = $conn->prepare("INSERT INTO classes (mentor_id, class_name, subject, section, description, class_code, cover_color) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $_SESSION['user_id'], $name, $subject, $section, $desc, $code, $color);
        if ($stmt->execute()) {
            header("Location: class.php?id=" . $conn->insert_id . "&new=1");
            exit;
        } else {
            $error = "Could not create class. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Create class — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap" style="max-width:560px;">
  <h1 class="section-title">Create a new class</h1>
  <p class="section-sub">A unique join code will be generated automatically — share it with your students.</p>
  <div class="card card-pad shadow">
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Class name *</label>
        <input type="text" name="class_name" placeholder="e.g. Web Development 101" required>
      </div>
      <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" placeholder="e.g. Computer Science">
      </div>
      <div class="form-group">
        <label>Section</label>
        <input type="text" name="section" placeholder="e.g. Batch A">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" placeholder="What is this class about?"></textarea>
      </div>
      <div class="form-group">
        <label>Banner color</label>
        <div style="display:flex;gap:10px;">
          <?php foreach (['#3b6ef0','#0f9d58','#f4a937','#e0483f','#8e44ad','#16a085'] as $c): ?>
            <label style="cursor:pointer;">
              <input type="radio" name="cover_color" value="<?= $c ?>" style="display:none;" <?= $c==='#3b6ef0'?'checked':'' ?> onclick="document.querySelectorAll('.swatch').forEach(s=>s.style.outline='none');this.nextElementSibling.style.outline='3px solid #1f2430'">
              <span class="swatch" style="display:inline-block;width:30px;height:30px;border-radius:8px;background:<?= $c ?>;<?= $c==='#3b6ef0'?'outline:3px solid #1f2430;':'' ?>"></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Create class</button>
    </form>
  </div>
</div>
</body>
</html>
