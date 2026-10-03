<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    header("Location: " . (current_role() === 'mentor' ? 'mentor/dashboard.php' : 'student/dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EduSphere LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  body{background:linear-gradient(135deg,#3b6ef0 0%, #6f86f4 55%, #0f9d58 100%);min-height:100vh;}
  .hero{max-width:920px;margin:0 auto;padding:90px 24px 60px;text-align:center;color:#fff;}
  .hero h1{font-size:42px;font-weight:800;margin-bottom:14px;}
  .hero p{font-size:16px;opacity:.92;max-width:560px;margin:0 auto 32px;}
  .hero-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;}
  .hero-actions .btn{padding:13px 28px;font-size:15px;}
  .feature-grid{max-width:960px;margin:40px auto 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;padding:0 24px 60px;}
  .feature{background:rgba(255,255,255,0.12);backdrop-filter:blur(4px);border-radius:14px;padding:20px;color:#fff;}
  .feature .ico{font-size:26px;margin-bottom:8px;}
  .feature h4{margin:0 0 6px;font-size:15px;}
  .feature p{margin:0;font-size:13px;opacity:.85;}
</style>
</head>
<body>
  <div class="hero">
    <div style="display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.15);padding:8px 16px;border-radius:30px;margin-bottom:24px;font-size:13px;font-weight:600;">
      🎓 Learning Management System
    </div>
    <h1>EduSphere</h1>
    <p>Mentors create classes and quizzes. Students join with a single class code, attempt quizzes, and track every mark on a live dashboard.</p>
    <div class="hero-actions">
      <a href="register.php" class="btn btn-primary">Get started — it's free</a>
      <a href="login.php" class="btn btn-outline" style="background:#fff;">I already have an account</a>
    </div>
  </div>
  <div class="feature-grid">
    <div class="feature"><div class="ico">🏷️</div><h4>Unique class codes</h4><p>Every mentor gets an auto-generated code students use to join instantly.</p></div>
    <div class="feature"><div class="ico">📝</div><h4>Quizzes & auto-grading</h4><p>Create MCQ quizzes with deadlines; objective answers are graded instantly.</p></div>
    <div class="feature"><div class="ico">📊</div><h4>Visual dashboards</h4><p>Score trends, class averages and completion charts for both roles.</p></div>
    <div class="feature"><div class="ico">📣</div><h4>Class stream</h4><p>Mentors post announcements & share materials students see in real time.</p></div>
    <div class="feature"><div class="ico">🔔</div><h4>Notifications</h4><p>Students get notified of new quizzes; mentors get notified of submissions.</p></div>
    <div class="feature"><div class="ico">🏆</div><h4>Leaderboard</h4><p>Friendly per-class leaderboard ranked by average quiz score.</p></div>
  </div>
</body>
</html>
