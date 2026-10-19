-- Practical 8: MySQL Schema Design, ER Model, PDO Connectivity
-- Database Name: studenthub_db

CREATE DATABASE IF NOT EXISTS studenthub_db;
USE studenthub_db;

-- 1. Students Table
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    enrollment_no VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(15),
    course VARCHAR(50),
    year INT,
    gender ENUM('Male', 'Female', 'Other'),
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive', 'Deleted') DEFAULT 'Active', -- Practical 11 Advanced Extension (Soft Delete)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Events Table
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    event_date DATETIME NOT NULL,
    format ENUM('Online', 'Offline', 'Hybrid') NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Technical', -- Practical 12 Intermediate Extension
    event_type VARCHAR(50) NOT NULL,
    location VARCHAR(150),
    poster_image VARCHAR(255) NULL, -- Practical 12 File Upload
    status ENUM('Open', 'Closed') DEFAULT 'Open', -- Practical 12 Intermediate Extension
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Registrations Table (Mapping Students to Events)
CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (student_id, event_id)
);

-- Seed Data for Students (Practical 11: Minimum 20 Seeded Records)
INSERT INTO students (name, enrollment_no, email, mobile, course, year, gender, password_hash, status) VALUES
('John Doe', 'EN202601', 'john@example.com', '9876543210', 'BTech', 3, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Jane Smith', 'EN202602', 'jane@example.com', '9876543211', 'MCA', 2, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Alex Johnson', 'EN202603', 'alex@example.com', '9876543212', 'BTech', 1, 'Other', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Rohan Patel', 'EN202604', 'rohan.patel@example.com', '9876543213', 'BTech', 2, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Priya Sharma', 'EN202605', 'priya.sharma@example.com', '9876543214', 'MCA', 1, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Aarav Mehta', 'EN202606', 'aarav.mehta@example.com', '9876543215', 'BCA', 3, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Ananya Desai', 'EN202607', 'ananya.desai@example.com', '9876543216', 'BTech', 4, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Dev Joshi', 'EN202608', 'dev.joshi@example.com', '9876543217', 'MTech', 1, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Diya Shah', 'EN202609', 'diya.shah@example.com', '9876543218', 'BCA', 2, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Karan Verma', 'EN202610', 'karan.verma@example.com', '9876543219', 'BTech', 2, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Sneha Nair', 'EN202611', 'sneha.nair@example.com', '9876543220', 'MCA', 2, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Vikram Rathore', 'EN202612', 'vikram.rathore@example.com', '9876543221', 'BTech', 3, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Tanvi Trivedi', 'EN202613', 'tanvi.trivedi@example.com', '9876543222', 'BCA', 1, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Manish Kumar', 'EN202614', 'manish.kumar@example.com', '9876543223', 'MTech', 2, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Inactive'),
('Kavita Rao', 'EN202615', 'kavita.rao@example.com', '9876543224', 'BTech', 1, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Siddharth Bhatt', 'EN202616', 'siddharth.bhatt@example.com', '9876543225', 'MCA', 1, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Meera Iyer', 'EN202617', 'meera.iyer@example.com', '9876543226', 'BTech', 4, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Aditya Dave', 'EN202618', 'aditya.dave@example.com', '9876543227', 'BCA', 3, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Ritu Chauhan', 'EN202619', 'ritu.chauhan@example.com', '9876543228', 'BTech', 2, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Yash Solanki', 'EN202620', 'yash.solanki@example.com', '9876543229', 'MCA', 2, 'Male', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active'),
('Pooja Kulkarni', 'EN202621', 'pooja.kulkarni@example.com', '9876543230', 'BTech', 3, 'Female', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'Active');

-- Seed Data for Events (Practical 12: Events with Categories, Status and Posters)
INSERT INTO events (title, description, event_date, format, category, event_type, location, poster_image, status) VALUES
('Coder''s Arcade Hackathon', 'Annual 24-hour hackathon with competitive programming tracks.', '2026-08-18 10:00:00', 'Offline', 'Technical', 'Hackathon', 'Main Auditorium', 'image/20150412_HACKATHON-slide-ECXQ-superJumbo.webp', 'Open'),
('Supernova Cultural Fest', 'Grand university youth festival featuring music, dance, and drama.', '2026-08-25 09:00:00', 'Hybrid', 'Cultural', 'Festival', 'University Amphitheatre', 'image/cultural-fest.png', 'Open'),
('Annual Sports Meet 2026', 'Inter-department athletics, badminton, football, and cricket matches.', '2026-09-15 09:30:00', 'Offline', 'Sports', 'Tournament', 'Sports Complex Ground', 'image/annaul_sports_meet.jpeg', 'Open'),
('AI & Cloud Innovation Summit', 'Keynote talks and technical presentations from industry experts.', '2026-10-05 11:00:00', 'Online', 'Technical', 'Seminar', 'Zoom Hall 1', NULL, 'Open'),
('Modern Web Dev Workshop', 'Deep dive into fullstack web application development.', '2026-10-25 14:00:00', 'Hybrid', 'Academic', 'Workshop', 'Lab 4 & Online', NULL, 'Closed');

-- Seed Data for Registrations
INSERT INTO registrations (student_id, event_id) VALUES
(1, 1),
(1, 2),
(2, 1),
(3, 3);

-- 4. Users Table (Authentication & RBAC for Practical 10 & 14)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Remember-Me Tokens Table (Practical 10 Advanced Extension)
CREATE TABLE IF NOT EXISTS remember_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector VARCHAR(32) NOT NULL UNIQUE,
    hashed_validator VARCHAR(64) NOT NULL,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Seed Data for Users
-- Password for admin is 'Admin@123', password for students is 'Student@123'
INSERT INTO users (name, email, password_hash, role) VALUES
('System Administrator', 'admin@studenthub.com', '$2y$10$KwS5dgdrViN3oyNH2NW9x.oS9mP8B/1QIDd6aSS3Dq8l/t8Xz.07y', 'admin'),
('John Doe', 'john@example.com', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'student'),
('Jane Smith', 'jane@example.com', '$2y$10$Hymn2.421JNmTX22HxfXrO8AHmcmKIagcibbM0Cf3l7Cgn/XdhpdW', 'student')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Advanced Extension: Stored Procedure for Registering a Student to an Event safely
DELIMITER //
CREATE PROCEDURE RegisterStudentForEvent(
    IN p_student_id INT,
    IN p_event_id INT
)
BEGIN
    -- Check if registration already exists to avoid duplicate entries
    IF NOT EXISTS (SELECT 1 FROM registrations WHERE student_id = p_student_id AND event_id = p_event_id) THEN
        INSERT INTO registrations (student_id, event_id) VALUES (p_student_id, p_event_id);
    END IF;
END //
DELIMITER ;

-- 6. Audit Logs Table (Practical 12 Advanced Extension & Practical 14)
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_name VARCHAR(100) NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

