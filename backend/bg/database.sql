CREATE DATABASE IF NOT EXISTS `edu_institution`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `edu_institution`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `grades`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `lessons`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(160) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `subject_name` VARCHAR(120) NULL,
    `bio` TEXT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'teacher', 'parent', 'student') NOT NULL DEFAULT 'parent',
    `code` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(160) NOT NULL UNIQUE,
    `code` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_code` VARCHAR(20) NOT NULL UNIQUE,
    `first_name` VARCHAR(90) NOT NULL,
    `last_name` VARCHAR(90) NOT NULL,
    `birth_date` DATE NOT NULL,
    `gender` ENUM('male', 'female') NOT NULL,
    `class_name` VARCHAR(60) NOT NULL,
    `level` VARCHAR(60) NOT NULL,
    `email` VARCHAR(160) NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `address` VARCHAR(255) NULL,
    `guardian_name` VARCHAR(120) NOT NULL,
    `guardian_phone` VARCHAR(30) NOT NULL,
    `guardian_email` VARCHAR(160) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `subject_name` VARCHAR(120) NOT NULL,
    `continuous_score` DECIMAL(5,2) NOT NULL,
    `exam_score` DECIMAL(5,2) NOT NULL,
    `coefficient` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    `semester` VARCHAR(20) NOT NULL DEFAULT 'S1',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_grades_student` (`student_id`),
    CONSTRAINT `fk_grades_student`
        FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `absence_date` DATE NOT NULL,
    `session_label` VARCHAR(50) NOT NULL,
    `absence_subject` VARCHAR(120) NULL,
    `justified` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_attendance_student_date` (`student_id`, `absence_date`),
    CONSTRAINT `fk_attendance_student`
        FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(160) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `subject` VARCHAR(160) NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'in_progress', 'closed') NOT NULL DEFAULT 'new',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lessons` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `lesson_type` ENUM('pdf', 'video', 'image') NOT NULL,
    `level` VARCHAR(80) NULL,
    `subject_name` VARCHAR(120) NULL,
    `content_url` VARCHAR(500) NOT NULL,
    `thumbnail_url` VARCHAR(500) NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by_role` VARCHAR(30) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_lesson_type` (`lesson_type`),
    INDEX `idx_lesson_level` (`level`),
    INDEX `idx_lesson_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('Admin Sagim', 'admin@kawkab-ouloum.ma', '0690032824', '$2y$10$i9jUN.S8sO19G0b7DON81uxQ/WTi1X9QUMPFthbCOSAxJyKESxW6y', 'admin', 'active'),
('Parent Demo', 'parent.demo@kawkab-ouloum.ma', '0689032824', '$2y$10$7fEdFO9dOSmcDMrhXy4IpO.alRPGMFi8JCOYgoLUqCf.OqW25IMde', 'parent', 'active'),
('Yassine Alaoui', 'yassine.alaoui@kawkab-ouloum.ma', '0690032824', '$2y$10$siWWohnqSEjM.9iz4OGnfergrXSIudF4eCovhpktrFcGs4BQ6PwtO', 'student', 'active'),
('Salma Idrissi', 'salma.idrissi@kawkab-ouloum.ma', '0661122334', '$2y$10$siWWohnqSEjM.9iz4OGnfergrXSIudF4eCovhpktrFcGs4BQ6PwtO', 'student', 'active'),
('Omar Bennis', 'omar.bennis@kawkab-ouloum.ma', '0672233445', '$2y$10$siWWohnqSEjM.9iz4OGnfergrXSIudF4eCovhpktrFcGs4BQ6PwtO', 'student', 'active');

INSERT INTO `users` (`full_name`, `email`, `phone`, `subject_name`, `password`, `role`, `code`, `status`) VALUES
('Teacher Demo', 'teacher@kawkab-ouloum.ma', '0650012345', 'Mathématiques', '$2y$10$siWWohnqSEjM.9iz4OGnfergrXSIudF4eCovhpktrFcGs4BQ6PwtO', 'teacher', '$2y$10$b1hwi8pvFW/8wi7/MHkgEeiDqD0ocIcbm/HpxpWhA5a8SeGi5Gi/W', 'active');

INSERT INTO `admins` (`full_name`, `email`, `code`, `status`) VALUES
('Super Admin', 'admin@kawkab-ouloum.ma', '$2y$10$b1hwi8pvFW/8wi7/MHkgEeiDqD0ocIcbm/HpxpWhA5a8SeGi5Gi/W', 'active');

INSERT INTO `students` (
    `student_code`,
    `first_name`,
    `last_name`,
    `birth_date`,
    `gender`,
    `class_name`,
    `level`,
    `email`,
    `phone`,
    `address`,
    `guardian_name`,
    `guardian_phone`,
    `guardian_email`
) VALUES
(
    'G142026001',
    'Yassine',
    'Alaoui',
    '2008-04-16',
    'male',
    '3AC-1',
    'Baccalauréat',
    'yassine.alaoui@kawkab-ouloum.ma',
    '0650032824',
    'Daoudiate, Marrakech',
    'Karim Alaoui',
    '0650032824',
    'karim.alaoui@gmail.com'
),
(
    'G152026002',
    'Salma',
    'Idrissi',
    '2009-09-22',
    'female',
    '2AC-3',
    'Collège',
    'salma.idrissi@kawkab-ouloum.ma',
    '0661122334',
    'Guéliz, Marrakech',
    'Amina Idrissi',
    '0661122334',
    'amina.idrissi@gmail.com'
),
(
    'G162026003',
    'Omar',
    'Bennis',
    '2010-01-08',
    'male',
    '1AC-2',
    'Lycée',
    'omar.bennis@kawkab-ouloum.ma',
    '0672233445',
    'Targa, Marrakech',
    'Rachid Bennis',
    '0672233445',
    'rachid.bennis@gmail.com'
);

INSERT INTO `grades` (`student_id`, `subject_name`, `continuous_score`, `exam_score`, `coefficient`, `semester`) VALUES
(1, 'Mathématiques', 15.50, 16.00, 2.00, 'S1'),
(1, 'Physique-Chimie', 14.75, 15.50, 2.00, 'S2'),
(1, 'Français', 13.50, 14.25, 1.00, 'S2'),
(1, 'Arabe', 16.25, 15.75, 1.00, 'S1'),
(1, 'Anglais', 14.00, 15.25, 1.00, 'S2'),
(1, 'Sciences de la Vie et de la Terre', 15.00, 14.50, 1.00, 'S1'),
(2, 'Mathématiques', 13.00, 14.00, 2.00, 'S1'),
(2, 'Français', 15.00, 14.50, 1.00, 'S2'),
(2, 'Anglais', 16.00, 15.50, 1.00, 'S1'),
(2, 'Français', 13.50, 14.25, 1.00, 'S1'),
(2, 'Arabe', 16.25, 15.75, 1.00, 'S1'),
(2, 'Anglais', 14.00, 15.25, 1.00, 'S2'),
(3, 'Mathématiques', 11.50, 12.00, 2.00, 'S1'),
(3, 'Arabe', 13.50, 13.00, 1.00, 'S1'),
(3, 'Français', 12.50, 13.25, 1.00, 'S1');
(3, 'Français', 13.50, 14.25, 1.00, 'S2'),
(3, 'Arabe', 16.25, 15.75, 1.00, 'S1'),
(3, 'Anglais', 14.00, 15.25, 1.00, 'S1'),

INSERT INTO `attendance` (`student_id`, `absence_date`, `session_label`, `absence_subject`, `justified`, `notes`) VALUES
(1, '2026-01-11', 'Matin', 'Mathématiques', 1, 'Certificat médical'),
(1, '2026-01-23', 'Après-midi', 'Français', 0, 'Retard non justifié'),
(1, '2026-02-03', 'Matin', 'Physique-Chimie', 0, 'Absence sans justificatif'),
(1, '2026-02-10', 'Après-midi', 'SVT', 1, 'Rendez-vous médical'),
(2, '2026-02-05', 'Matin', 'Arabe', 0, 'Absence sans justificatif'),
(2, '2026-02-12', 'Après-midi', 'Anglais', 1, 'Maladie'),
(3, '2026-01-29', 'Matin', 'Histoire-Géographie', 1, 'Certificat médical');
(3, '2026-10-11', 'Matin', 'Mathématiques', 1, 'Certificat médical'),
(3, '2026-05-23', 'Après-midi', 'Français', 0, 'Retard non justifié'),

INSERT INTO `lessons` (`title`, `description`, `lesson_type`, `level`, `subject_name`, `content_url`, `thumbnail_url`, `is_published`, `created_by_role`) VALUES
('درس تمهيدي في الرياضيات', 'فيديو قصير لشرح أساسيات الجبر للمبتدئين.', 'video', 'Collège', 'Mathématiques', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1000&q=80', 1, 'director'),
('ملخص قواعد اللغة الفرنسية', 'ملف PDF منظم لأهم القواعد مع تمارين تطبيقية.', 'pdf', 'Lycée', 'Français', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=1000&q=80', 1, 'secretary'),
('بطاقات مصورة - العلوم', 'صور توضيحية حول أجزاء الخلية والأنسجة.', 'image', 'Baccalauréat', 'SVT', 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1400&q=80', 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1000&q=80', 1, 'director');

CREATE TABLE `gallery_photos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NULL,
    `description` TEXT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `thumbnail_path` VARCHAR(500) NULL,
    `category` ENUM('trips', 'events', 'ceremonies', 'activities', 'other') NOT NULL DEFAULT 'other',
    `location_label` VARCHAR(100) NULL,
    `upload_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `uploaded_by` VARCHAR(50) NULL,
    INDEX `idx_gallery_category` (`category`),
    INDEX `idx_gallery_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `excerpt` VARCHAR(300) NULL,
    `post_type` ENUM('blog', 'event', 'announcement') NOT NULL DEFAULT 'blog',
    `category` VARCHAR(80) NULL,
    `featured_image` VARCHAR(500) NULL,
    `author_id` INT UNSIGNED NULL,
    `author_name` VARCHAR(120) NOT NULL,
    `published_at` DATETIME NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_blog_type` (`post_type`),
    INDEX `idx_blog_published` (`is_published`),
    INDEX `idx_blog_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `registration_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_code` VARCHAR(20) NOT NULL UNIQUE,
    `first_name` VARCHAR(90) NOT NULL,
    `last_name` VARCHAR(90) NOT NULL,
    `birth_date` DATE NOT NULL,
    `gender` ENUM('male', 'female') NOT NULL,
    `class_name` VARCHAR(60) NOT NULL,
    `level` VARCHAR(60) NOT NULL,
    `email` VARCHAR(160) NULL,
    `phone` VARCHAR(30) NULL,
    `address` VARCHAR(255) NULL,
    `guardian_name` VARCHAR(120) NOT NULL,
    `guardian_phone` VARCHAR(30) NOT NULL,
    `guardian_email` VARCHAR(160) NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    `reviewed_by` INT UNSIGNED NULL,
    INDEX `idx_registration_status` (`status`),
    INDEX `idx_registration_code` (`student_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    user_type ENUM('admin', 'teacher') NOT NULL,
    	token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token_used (is_used),
    INDEX idx_expires (expires_at),
    UNIQUE KEY uniq_email_user (email, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

