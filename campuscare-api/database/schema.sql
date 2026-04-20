CREATE DATABASE IF NOT EXISTS campuscare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campuscare;

CREATE TABLE IF NOT EXISTS hostels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mentor', 'warden', 'iro', 'national', 'international') NOT NULL,
    roll_number VARCHAR(50) DEFAULT NULL UNIQUE,
    gender VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    hostel_id INT UNSIGNED DEFAULT NULL,
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_hostel FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS hostel_wardens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT UNSIGNED NOT NULL,
    warden_id INT UNSIGNED NOT NULL,
    UNIQUE KEY unique_hostel_id (hostel_id),
    UNIQUE KEY unique_warden_id (warden_id),
    CONSTRAINT fk_hostel_wardens_hostel FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE,
    CONSTRAINT fk_hostel_wardens_warden FOREIGN KEY (warden_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mentor_students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_mentor_students_mentor FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_students_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS iro_students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    iro_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_iro_students_iro FOREIGN KEY (iro_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_iro_students_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    route_to ENUM('mentor', 'warden', 'iro', 'admin') NOT NULL
);

CREATE TABLE IF NOT EXISTS complaints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    assigned_to INT UNSIGNED NOT NULL,
    status ENUM('submitted', 'in_progress', 'resolved', 'closed', 'escalated') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_complaints_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_complaints_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_complaints_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS complaint_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_complaint_comments_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    CONSTRAINT fk_complaint_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_hostel_id ON users(hostel_id);
CREATE INDEX idx_categories_route_to ON categories(route_to);
CREATE INDEX idx_complaints_student_id ON complaints(student_id);
CREATE INDEX idx_complaints_assigned_to ON complaints(assigned_to);
CREATE INDEX idx_complaints_status ON complaints(status);
CREATE INDEX idx_complaint_comments_complaint_id ON complaint_comments(complaint_id);

INSERT INTO hostels (hostel_name)
VALUES
    ('Boys Hostel A'),
    ('Boys Hostel B'),
    ('Girls Hostel A')
ON DUPLICATE KEY UPDATE hostel_name = VALUES(hostel_name);

INSERT INTO categories (name, route_to)
VALUES
    ('Hostel Issue', 'warden'),
    ('Educational Issue', 'mentor'),
    ('Harassment', 'mentor'),
    ('IRO Support', 'iro'),
    ('General Administration', 'admin')
ON DUPLICATE KEY UPDATE route_to = VALUES(route_to);

INSERT INTO users (name, email, password, role, roll_number, gender, phone, hostel_id, status)
VALUES
    ('System Admin', 'admin@campuscare.local', '$2y$10$4L4h3rJ3MsR8I97AAX9YuuoYvQ2x5G0vPLXnwI75PwQBGzb62LF8q', 'admin', NULL, NULL, '9999999999', NULL, 'active')
ON DUPLICATE KEY UPDATE email = VALUES(email);
