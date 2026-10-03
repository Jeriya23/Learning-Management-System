# EduSphere — Learning Management System
A Google Classroom–inspired LMS built with **HTML, CSS, JavaScript, PHP & MySQL**, made to run on **XAMPP** and edited in **VS Code**.

## ✨ Features
- Separate **Mentor** and **Student** signup/login (role-based)
- Mentors create classes → get a **unique auto-generated class code**
- Students **join a class** using that code
- Mentors create **MCQ quizzes** (dynamic question builder, due dates, timed)
- Students **attempt quizzes** with a live countdown timer
- **Auto-grading** of objective answers the moment a student submits
- Mentor **evaluation/override** screen (confirm score + leave feedback) — score only appears on the student dashboard once the mentor confirms
- **Dashboards with Chart.js graphs**: average score by class, submission trends, per-student score trend, leaderboard
- Class **stream** (announcements) + **materials** sharing
- **Notifications** bell for both roles (new quiz, new submission, evaluation done, student joined)
- Per-class **leaderboard**
- 10 normalized MySQL tables (users, classes, enrollments, quizzes, questions, submissions, answers, announcements, materials, notifications)

## 🗂️ Folder structure
```
lms/
├── config/db.php          → MySQL connection settings
├── includes/auth.php      → session + auth helper functions
├── includes/header.php    → shared topbar (logo, avatar, notifications, logout)
├── assets/css/style.css   → all styling
├── sql/lms.sql            → database schema (import this!)
├── index.php, login.php, register.php, logout.php
├── mentor/                → dashboard, create_class, class, create_quiz, quiz_submissions, notifications
└── student/                → dashboard, join_class, class, take_quiz, quiz_result, notifications
```

## 🚀 Setup (XAMPP + VS Code)

1. **Install XAMPP** (https://www.apachefriends.org) if you haven't already.
2. Copy the entire `lms` folder into your XAMPP `htdocs` directory, so the path is:
   - Windows: `C:\xampp\htdocs\lms`
   - macOS: `/Applications/XAMPP/htdocs/lms`
   - Linux: `/opt/lampp/htdocs/lms`
3. **Start XAMPP Control Panel** → start **Apache** and **MySQL**.
4. Open **phpMyAdmin** (`http://localhost/phpmyadmin`):
   - Click **Import** → choose the file `lms/sql/lms.sql` → click **Go**.
   - This creates the `lms_db` database with all 10 tables.
5. (Optional) If your MySQL `root` user has a password, open `lms/config/db.php` in VS Code and update:
   ```php
   define('DB_PASS', 'your_password_here');
   ```
6. Open the project folder in **VS Code** (`File → Open Folder → lms`) to browse/edit code.
7. In your browser, go to:
   ```
   http://localhost/lms/
   ```
8. Click **Get started** → register once as a **Mentor** and once as a **Student** (use two different emails, e.g. in separate browser tabs/incognito) to try the full flow:
   - As mentor: create a class → copy the class code → create a quiz with a few MCQ questions.
   - As student: join the class using the code → attempt the quiz before the timer runs out.
   - As mentor: open the quiz's **submissions** page → confirm/override the score → add feedback.
   - As student: refresh the dashboard → see the score, chart, and feedback appear.

## 🔐 Notes
- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- All SQL queries use prepared statements to prevent SQL injection.
- The header include uses absolute paths like `/lms/...` — if you rename the folder from `lms`, update those links in `includes/header.php`, `login.php`'s redirects, etc., or place it at the `htdocs` root and remove the `/lms` prefix.
- This is a learning/demo project — for production use you'd want CSRF tokens, file-upload validation (the "materials" feature currently accepts links; you can extend it to real file uploads using the `assets/uploads/` folder which is already created), and email verification.

## 🧩 Extending it further
Ideas you can build on top of this foundation:
- Real file uploads for materials/assignments (the `assets/uploads` folder is ready)
- Subjective/long-answer questions graded entirely by the mentor
- Class-wide quiz analytics (question-level difficulty: % of students who got each question wrong)
- Dark mode toggle
- Export grades to CSV/PDF
