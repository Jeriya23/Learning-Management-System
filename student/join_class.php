<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['class_code'] ?? ''));
    $stmt = $conn->prepare("SELECT * FROM classes WHERE class_code=?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $class = $stmt->get_result()->fetch_assoc();

    if (!$class) {
        $error = "No class found with that code. Double check with your mentor.";
    } else {
        $check = $conn->prepare("SELECT id FROM enrollments WHERE class_id=? AND student_id=?");
        $check->bind_param("ii", $class['id'], $_SESSION['user_id']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "You're already enrolled in this class.";
        } else {
            $stmt = $conn->prepare("INSERT INTO enrollments (class_id, student_id) VALUES (?,?)");
            $stmt->bind_param("ii", $class['id'], $_SESSION['user_id']);
            $stmt->execute();
            push_notification($conn, $class['mentor_id'], "{$_SESSION['name']} joined your class \"{$class['class_name']}\"");
            header("Location: class.php?id=" . $class['id'] . "&joined=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Join class — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap" style="max-width:460px;">
  <h1 class="section-title">Join a class</h1>
  <p class="section-sub">Ask your mentor for the class code and enter it below.</p>
  <div class="card card-pad shadow">
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Class code</label>
        <input type="text" name="class_code" placeholder="e.g. AB3X9Q" maxlength="10" style="text-transform:uppercase;letter-spacing:.1em;text-align:center;font-weight:700;" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Join class</button>
    </form>
  </div>
</div>
</body>
</html>
