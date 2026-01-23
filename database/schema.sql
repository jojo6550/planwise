-- PlanWise Database Schema
-- Complete database structure for the Lesson Plan Builder application

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS planwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE planwise;

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 2,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);

-- Lesson plans table
CREATE TABLE IF NOT EXISTS lesson_plans (
    lesson_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(100),
    grade_level VARCHAR(50),
    duration INT, -- in minutes
    objectives TEXT,
    materials TEXT,
    procedures TEXT,
    assessment TEXT,
    notes TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Lesson sections table
CREATE TABLE IF NOT EXISTS lesson_sections (
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    section_type ENUM('introduction', 'main_activity', 'conclusion', 'assessment') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    duration INT, -- in minutes
    order_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lesson_plans(lesson_id) ON DELETE CASCADE
);

-- Files table
CREATE TABLE IF NOT EXISTS files (
    file_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    lesson_id INT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lesson_plans(lesson_id) ON DELETE SET NULL
);

-- QR codes table
CREATE TABLE IF NOT EXISTS qr_codes (
    qr_id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    qr_code_data TEXT NOT NULL,
    qr_image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lesson_plans(lesson_id) ON DELETE CASCADE
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reset_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_lesson_plans_user_id ON lesson_plans(user_id);
CREATE INDEX idx_lesson_plans_status ON lesson_plans(status);
CREATE INDEX idx_lesson_sections_lesson_id ON lesson_sections(lesson_id);
CREATE INDEX idx_files_user_id ON files(user_id);
CREATE INDEX idx_files_lesson_id ON files(lesson_id);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_password_resets_token ON password_resets(reset_token);
CREATE INDEX idx_password_resets_expires ON password_resets(expires_at);
