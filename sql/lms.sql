-- ============================================
-- LMS Database Schema (Google Classroom inspired)
-- Import this in phpMyAdmin (Import tab) or run via MySQL CLI:
--   mysql -u root -p < lms.sql
-- ============================================

CREATE DATABASE IF NOT EXISTS lms_db;
USE lms_db;

-- 1. USERS (mentors + students share table, differentiated by role)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('mentor','student') NOT NULL,
    avatar_color VARCHAR(7) DEFAULT '#4f46e5',
    bio VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. CLASSES (created by mentors, each has a unique join code)
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    class_name VARCHAR(150) NOT NULL,
    subject VARCHAR(100) DEFAULT NULL,
    section VARCHAR(50) DEFAULT NULL,
    description TEXT,
    class_code VARCHAR(10) NOT NULL UNIQUE,
    cover_color VARCHAR(7) DEFAULT '#4f46e5',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. ENROLLMENTS (students join classes using class_code)
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. ANNOUNCEMENTS / CLASS STREAM POSTS
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    mentor_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. MATERIALS (resources / notes / links shared by mentor)
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- 6. QUIZZES (assigned by mentor to a class)
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    duration_minutes INT DEFAULT 15,
    total_marks INT DEFAULT 0,
    due_date DATETIME DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- 7. QUESTIONS (MCQ questions belonging to a quiz)
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('a','b','c','d') NOT NULL,
    marks INT DEFAULT 1,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- 8. SUBMISSIONS (one row per student attempt of a quiz)
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT DEFAULT 0,
    total_marks INT DEFAULT 0,
    status ENUM('pending','evaluated') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL DEFAULT NULL,
    mentor_feedback VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY unique_submission (quiz_id, student_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 9. ANSWERS (each answer a student picked, used for auto-grading + review)
CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option ENUM('a','b','c','d') DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- 10. NOTIFICATIONS (extra feature: alerts for students & mentors)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_class_mentor ON classes(mentor_id);
CREATE INDEX idx_enroll_student ON enrollments(student_id);
CREATE INDEX idx_quiz_class ON quizzes(class_id);
CREATE INDEX idx_sub_student ON submissions(student_id);
