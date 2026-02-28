-- PlanWise Database Schema
-- CS334 Applied Web Programming
-- Created: 2026-02-28

CREATE DATABASE IF NOT EXISTS planwise_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE planwise_db;

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT(11) NOT NULL AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (role_id)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT IGNORE INTO roles (role_id, role_name) VALUES 
(1, 'Admin'),
(2, 'Teacher');

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT(11) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    INDEX idx_email (email),
    INDEX idx_role_id (role_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson Plans Table
CREATE TABLE IF NOT EXISTS lesson_plans (
    lesson_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(100),
    grade_level VARCHAR(50),
    duration INT(11),
    objectives TEXT,
    materials TEXT,
    procedures TEXT,
    assessment TEXT,
    notes TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    grade VARCHAR(50),
    theme VARCHAR(150),
    attainment_target TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (lesson_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson Sections Table
CREATE TABLE IF NOT EXISTS lesson_sections (
    section_id INT(11) NOT NULL AUTO_INCREMENT,
    lesson_id INT(11) NOT NULL,
    section_type VARCHAR(50),
    title VARCHAR(255),
    content TEXT,
    duration INT(11),
    order_position INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (section_id),
    INDEX idx_lesson_id (lesson_id),
    FOREIGN KEY (lesson_id) REFERENCES lesson_plans(lesson_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files Table
CREATE TABLE IF NOT EXISTS files (
    file_id INT(11) NOT NULL AUTO_INCREMENT,
    lesson_id INT(11),
    user_id INT(11),
    original_name VARCHAR(255),
    file_name VARCHAR(255),
    file_type VARCHAR(50),
    file_size INT(11),
    file_path VARCHAR(255),
    thumbnail_path VARCHAR(255),
    uploaded_by INT(11),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (file_id),
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (lesson_id) REFERENCES lesson_plans(lesson_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- QR Codes Table
CREATE TABLE IF NOT EXISTS qr_codes (
    qr_id INT(11) NOT NULL AUTO_INCREMENT,
    lesson_id INT(11) NOT NULL UNIQUE,
    qr_path VARCHAR(255),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (qr_id),
    FOREIGN KEY (lesson_id) REFERENCES lesson_plans(lesson_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    action VARCHAR(255),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reset_id),
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Set character set for all tables
ALTER DATABASE planwise_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
