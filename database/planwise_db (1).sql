CREATE DATABASE IF NOT EXISTS planwise_db;
USE planwise_db;

-- ----------------------------
-- roles
-- ----------------------------
CREATE TABLE roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ----------------------------
-- users
-- ----------------------------
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  profile_picture VARCHAR(255),
  profile_thumbnail VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_role (role_id),
  CONSTRAINT fk_user_role FOREIGN KEY (role_id)
    REFERENCES roles(role_id)
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- lesson_plans
-- ----------------------------
CREATE TABLE lesson_plans (
  lesson_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  subject VARCHAR(100) NOT NULL,
  grade_level VARCHAR(50),
  duration INT,
  objectives TEXT,
  materials TEXT,
  procedures TEXT,
  assessment TEXT,
  notes TEXT,
  status ENUM('draft','published','archived') DEFAULT 'draft',
  grade VARCHAR(50) NOT NULL,
  theme VARCHAR(150),
  attainment_target TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_lesson_user (user_id),
  CONSTRAINT fk_lesson_user FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- lesson_sections
-- ----------------------------
CREATE TABLE lesson_sections (
  section_id INT AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT NOT NULL,
  section_type VARCHAR(50) NOT NULL,
  content TEXT NOT NULL,
  INDEX idx_section_lesson (lesson_id),
  CONSTRAINT fk_section_lesson FOREIGN KEY (lesson_id)
    REFERENCES lesson_plans(lesson_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- files
-- ----------------------------
CREATE TABLE files (
  file_id INT AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_file_lesson (lesson_id),
  CONSTRAINT fk_files_lesson FOREIGN KEY (lesson_id)
    REFERENCES lesson_plans(lesson_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- qr_codes
-- ----------------------------
CREATE TABLE qr_codes (
  qr_id INT AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT NOT NULL UNIQUE,
  qr_path VARCHAR(255) NOT NULL,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_qr_lesson FOREIGN KEY (lesson_id)
    REFERENCES lesson_plans(lesson_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- password_resets
-- ----------------------------
CREATE TABLE password_resets (
  reset_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_resets_token (token),
  INDEX idx_password_resets_expires_at (expires_at),
  CONSTRAINT fk_password_user FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- activity_logs
-- ----------------------------
CREATE TABLE activity_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(255) NOT NULL,
  description TEXT,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_user (user_id),
  INDEX idx_activity_logs_action (action),
  INDEX idx_activity_logs_created_at (created_at),
  CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;