<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id,name,password,role,avatar_color FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar_color'] = $user['avatar_color'];
            header("Location: " . ($user['role'] === 'mentor' ? 'mentor/dashboard.php' : 'student/dashboard.php'));
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Log in — EduSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrap">
  <div class="auth-card">
    <h1>Welcome back</h1>
    <p class="sub">Log in to your EduSphere account</p>

    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Log in</button>
    </form>
    <div class="auth-foot">New here? <a href="register.php">Create an account</a></div>
  </div>
</body>
</html>
