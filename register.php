<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (!$name || !$email || !$password) {
        $error = "Please fill all fields.";
    } elseif (!in_array($role, ['mentor','student'])) {
        $error = "Invalid role.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "That email is already registered.";
        } else {
            $colors = ['#3b6ef0','#0f9d58','#f4a937','#e0483f','#8e44ad','#16a085'];
            $color = $colors[array_rand($colors)];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, avatar_color) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $hash, $role, $color);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['avatar_color'] = $color;
                header("Location: " . ($role === 'mentor' ? 'mentor/dashboard.php' : 'student/dashboard.php'));
                exit;
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Sign up — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrap">
  <div class="auth-card">
    <h1>Create your account</h1>
    <p class="sub">Join EduSphere as a mentor or a student</p>

    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

    <form method="POST">
      <div class="role-toggle">
        <label><input type="radio" name="role" value="student" checked onclick="this.form.role.value='student'"><span>🎓 Student</span></label>
        <label><input type="radio" name="role" value="mentor"><span>🧑‍🏫 Mentor</span></label>
      </div>

      <div class="form-group">
        <label>Full name</label>
        <input type="text" name="name" placeholder="Jane Doe" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Create account</button>
    </form>
    <div class="auth-foot">Already have an account? <a href="login.php">Log in</a></div>
  </div>
<script>
  // keep radio "checked" state syncing visually (handled by CSS via :checked)
</script>
</body>
</html>
