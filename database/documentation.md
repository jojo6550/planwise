# PlanWise Database Documentation

## Overview

The `planwise_db` is a MySQL database designed for a lesson planning system called PlanWise. It supports user management, role-based access control, lesson plan creation and management, file attachments, QR code generation, activity logging, and password reset functionality. The database uses InnoDB engine with UTF-8 character set for international support.

### Key Features
- **User Management**: Supports multiple user roles (Admin, Teacher) with authentication
- **Lesson Planning**: Comprehensive lesson plan creation with sections, materials, objectives, etc.
- **File Management**: Attachment support for lesson plans
- **QR Code Integration**: QR code generation for lesson plans
- **Activity Logging**: Tracks user actions for audit purposes
- **Security**: Password reset functionality and secure password hashing

## Database Schema

### Tables Overview

| Table | Purpose | Primary Key |
|-------|---------|-------------|
| `users` | Stores user account information | `user_id` |
| `roles` | Defines user roles (Admin, Teacher) | `role_id` |
| `lesson_plans` | Main lesson plan data | `lesson_id` |
| `lesson_sections` | Detailed sections within lesson plans | `section_id` |
| `files` | File attachments for lesson plans | `file_id` |
| `qr_codes` | QR code data for lesson plans | `qr_id` |
| `activity_logs` | User activity tracking | `log_id` |
| `password_resets` | Password reset tokens | `reset_id` |

### Detailed Table Descriptions

#### users
Stores user account information and authentication details.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `user_id` | int(11) | NO | AUTO_INCREMENT | Unique user identifier |
| `first_name` | varchar(100) | NO | - | User's first name |
| `last_name` | varchar(100) | NO | - | User's last name |
| `email` | varchar(150) | NO | - | User's email address (unique) |
| `password_hash` | varchar(255) | NO | - | Hashed password |
| `role_id` | int(11) | NO | - | Foreign key to roles table |
| `status` | enum('active','inactive') | YES | 'active' | Account status |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Account creation timestamp |

#### roles
Defines available user roles in the system.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `role_id` | int(11) | NO | AUTO_INCREMENT | Unique role identifier |
| `role_name` | varchar(50) | NO | - | Role name (unique) |

#### lesson_plans
Core table containing lesson plan information.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `lesson_id` | int(11) | NO | AUTO_INCREMENT | Unique lesson identifier |
| `user_id` | int(11) | NO | - | Foreign key to users table (creator) |
| `title` | varchar(255) | NO | - | Lesson plan title |
| `subject` | varchar(100) | NO | - | Subject area |
| `grade_level` | varchar(50) | YES | NULL | Target grade level |
| `duration` | int(11) | YES | NULL | Lesson duration in minutes |
| `objectives` | text | YES | NULL | Learning objectives |
| `materials` | text | YES | NULL | Required materials |
| `procedures` | text | YES | NULL | Lesson procedures |
| `assessment` | text | YES | NULL | Assessment methods |
| `notes` | text | YES | NULL | Additional notes |
| `status` | enum('draft','published','archived') | YES | 'draft' | Publication status |
| `grade` | varchar(50) | NO | - | Grade level (seems redundant with grade_level) |
| `theme` | varchar(150) | YES | NULL | Lesson theme |
| `attainment_target` | text | YES | NULL | Learning targets |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | timestamp | YES | NULL | Last update timestamp |

#### lesson_sections
Stores detailed sections within lesson plans (e.g., introduction, main activity, conclusion).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `section_id` | int(11) | NO | AUTO_INCREMENT | Unique section identifier |
| `lesson_id` | int(11) | NO | - | Foreign key to lesson_plans table |
| `section_type` | varchar(50) | NO | - | Type of section (e.g., 'introduction') |
| `content` | text | NO | - | Section content |

#### files
Manages file attachments for lesson plans.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `file_id` | int(11) | NO | AUTO_INCREMENT | Unique file identifier |
| `lesson_id` | int(11) | NO | - | Foreign key to lesson_plans table |
| `file_type` | varchar(50) | NO | - | File type (e.g., 'pdf', 'docx') |
| `file_path` | varchar(255) | NO | - | File storage path |
| `uploaded_at` | timestamp | NO | CURRENT_TIMESTAMP | Upload timestamp |

#### qr_codes
Stores QR code information for lesson plans.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `qr_id` | int(11) | NO | AUTO_INCREMENT | Unique QR code identifier |
| `lesson_id` | int(11) | NO | - | Foreign key to lesson_plans table (unique) |
| `qr_path` | varchar(255) | NO | - | QR code file path |
| `generated_at` | timestamp | NO | CURRENT_TIMESTAMP | Generation timestamp |

#### activity_logs
Tracks user activities for auditing purposes.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `log_id` | int(11) | NO | AUTO_INCREMENT | Unique log entry identifier |
| `user_id` | int(11) | NO | - | Foreign key to users table |
| `action` | varchar(255) | NO | - | Description of the action performed |
| `ip_address` | varchar(45) | YES | NULL | IP address of the user |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Log timestamp |

#### password_resets
Manages password reset tokens.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `reset_id` | int(11) | NO | AUTO_INCREMENT | Unique reset identifier |
| `user_id` | int(11) | NO | - | Foreign key to users table |
| `token` | varchar(255) | NO | - | Reset token |
| `expires_at` | datetime | NO | - | Token expiration time |

## Entity-Relationship Diagram

```
users (1) ────┬─── (many) lesson_plans
              │
              └─── (many) activity_logs
              │
              └─── (many) password_resets

roles (1) ──── (many) users

lesson_plans (1) ────┬─── (many) lesson_sections
                     │
                     ├─── (many) files
                     │
                     └─── (1) qr_codes
```

### Relationships Summary
- **users → lesson_plans**: One-to-many (a user can create multiple lesson plans)
- **users → activity_logs**: One-to-many (a user can have multiple activity logs)
- **users → password_resets**: One-to-many (a user can have multiple reset requests)
- **roles → users**: One-to-many (a role can be assigned to multiple users)
- **lesson_plans → lesson_sections**: One-to-many (a lesson can have multiple sections)
- **lesson_plans → files**: One-to-many (a lesson can have multiple file attachments)
- **lesson_plans → qr_codes**: One-to-one (each lesson can have one QR code)

## Indexes and Constraints

### Indexes
- **users**: PRIMARY KEY on `user_id`, UNIQUE on `email`, INDEX on `role_id` (idx_user_role)
- **roles**: PRIMARY KEY on `role_id`, UNIQUE on `role_name`
- **lesson_plans**: PRIMARY KEY on `lesson_id`, INDEX on `user_id` (idx_lesson_user)
- **lesson_sections**: PRIMARY KEY on `section_id`, INDEX on `lesson_id` (idx_section_lesson)
- **files**: PRIMARY KEY on `file_id`, INDEX on `lesson_id` (idx_file_lesson)
- **qr_codes**: PRIMARY KEY on `qr_id`, UNIQUE on `lesson_id`
- **activity_logs**: PRIMARY KEY on `log_id`, INDEX on `user_id` (idx_log_user)
- **password_resets**: PRIMARY KEY on `reset_id`, INDEX on `user_id`

### Foreign Key Constraints
All foreign keys use CASCADE on DELETE and UPDATE for referential integrity:
- `activity_logs.user_id` → `users.user_id`
- `files.lesson_id` → `lesson_plans.lesson_id`
- `lesson_plans.user_id` → `users.user_id`
- `lesson_sections.lesson_id` → `lesson_plans.lesson_id`
- `password_resets.user_id` → `users.user_id`
- `qr_codes.lesson_id` → `lesson_plans.lesson_id`
- `users.role_id` → `roles.role_id`

### Auto-Increment Settings
- `users.user_id`: Starts at 7 (next available after sample data)
- `roles.role_id`: Starts at 3
- `lesson_plans.lesson_id`: Starts at 4
- Other tables start at 1

## Case Studies

### Case Study 1: User Registration and Role Assignment

**Scenario**: A new teacher joins the school and needs to be registered in the system.

**SQL Operations**:

```sql
-- Insert new role if needed (though Admin and Teacher already exist)
INSERT INTO roles (role_name) VALUES ('Teacher');

-- Register new user
INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
VALUES ('John', 'Doe', 'john.doe@school.edu', '$2y$10$hashedpasswordhere', 2, 'active');

-- Log the registration activity
INSERT INTO activity_logs (user_id, action, ip_address)
VALUES (LAST_INSERT_ID(), 'User registration completed', '192.168.1.100');
```

### Case Study 2: Creating a Complete Lesson Plan

**Scenario**: Teacher creates a comprehensive mathematics lesson plan with multiple sections and attachments.

**SQL Operations**:

```sql
-- Create the main lesson plan
INSERT INTO lesson_plans (
    user_id, title, subject, grade_level, duration, objectives,
    materials, procedures, assessment, notes, status, grade, theme
) VALUES (
    6, 'Introduction to Quadratic Equations', 'Mathematics', 'Grade 9',
    90, 'Students will understand quadratic equations and their solutions',
    'Whiteboard, calculators, worksheets', 'Introduction, guided practice, independent work',
    'Exit ticket quiz', 'Review homework next class', 'draft', '9', 'Algebra Fundamentals'
);

-- Add lesson sections
INSERT INTO lesson_sections (lesson_id, section_type, content) VALUES
(LAST_INSERT_ID(), 'introduction', 'Warm-up: Review linear equations'),
(LAST_INSERT_ID(), 'main_activity', 'Teach quadratic formula derivation'),
(LAST_INSERT_ID(), 'conclusion', 'Practice problems and Q&A');

-- Attach a file (worksheet)
INSERT INTO files (lesson_id, file_type, file_path)
VALUES (LAST_INSERT_ID(), 'pdf', '/uploads/lesson-plans/quadratic-worksheet.pdf');

-- Generate QR code for the lesson
INSERT INTO qr_codes (lesson_id, qr_path)
VALUES (LAST_INSERT_ID(), '/public/qr/lesson_4.png');

-- Log the creation activity
INSERT INTO activity_logs (user_id, action, ip_address)
VALUES (6, 'Created lesson plan: Introduction to Quadratic Equations', '192.168.1.100');
```

### Case Study 3: Retrieving User Dashboard Data

**Scenario**: Display a teacher's dashboard showing their lesson plans and recent activities.

**SQL Queries**:

```sql
-- Get user's lesson plans with counts
SELECT
    lp.lesson_id,
    lp.title,
    lp.subject,
    lp.status,
    lp.created_at,
    COUNT(ls.section_id) as section_count,
    COUNT(f.file_id) as file_count
FROM lesson_plans lp
LEFT JOIN lesson_sections ls ON lp.lesson_id = ls.lesson_id
LEFT JOIN files f ON lp.lesson_id = f.lesson_id
WHERE lp.user_id = 6
GROUP BY lp.lesson_id, lp.title, lp.subject, lp.status, lp.created_at
ORDER BY lp.created_at DESC;

-- Get recent activity logs
SELECT action, ip_address, created_at
FROM activity_logs
WHERE user_id = 6
ORDER BY created_at DESC
LIMIT 10;
```

### Case Study 4: Password Reset Process

**Scenario**: User requests a password reset and completes the process.

**SQL Operations**:

```sql
-- Generate reset token
INSERT INTO password_resets (user_id, token, expires_at)
VALUES (6, 'secure-random-token-here', DATE_ADD(NOW(), INTERVAL 1 HOUR));

-- Verify token and reset password (when user completes reset)
UPDATE users
SET password_hash = '$2y$10$newhashedpassword'
WHERE user_id = (
    SELECT user_id FROM password_resets
    WHERE token = 'secure-random-token-here'
    AND expires_at > NOW()
);

-- Clean up used token
DELETE FROM password_resets
WHERE token = 'secure-random-token-here';

-- Log the password reset
INSERT INTO activity_logs (user_id, action, ip_address)
VALUES (6, 'Password reset completed', '192.168.1.100');
```

### Case Study 5: Administrative Reporting

**Scenario**: Admin needs to generate reports on system usage and lesson plan statistics.

**SQL Queries**:

```sql
-- User statistics by role
SELECT
    r.role_name,
    COUNT(u.user_id) as user_count,
    COUNT(lp.lesson_id) as lesson_count
FROM roles r
LEFT JOIN users u ON r.role_id = u.role_id
LEFT JOIN lesson_plans lp ON u.user_id = lp.user_id
GROUP BY r.role_id, r.role_name;

-- Lesson plan status distribution
SELECT
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM lesson_plans), 2) as percentage
FROM lesson_plans
GROUP BY status;

-- Recent system activity
SELECT
    u.first_name,
    u.last_name,
    al.action,
    al.created_at
FROM activity_logs al
JOIN users u ON al.user_id = u.user_id
ORDER BY al.created_at DESC
LIMIT 50;

-- File storage usage by type
SELECT
    file_type,
    COUNT(*) as file_count,
    SUM(OCTET_LENGTH(file_path)) as total_size_bytes
FROM files
GROUP BY file_type;
```

### Case Study 6: Archiving Old Lesson Plans

**Scenario**: System maintenance task to archive lesson plans older than 2 years.

**SQL Operations**:

```sql
-- Update old lesson plans to archived status
UPDATE lesson_plans
SET status = 'archived', updated_at = NOW()
WHERE status = 'published'
AND created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);

-- Log the archiving operation (assuming run by admin user_id = 1)
INSERT INTO activity_logs (user_id, action, ip_address)
SELECT 1, CONCAT('Archived ', COUNT(*), ' old lesson plans'), '127.0.0.1'
FROM lesson_plans
WHERE status = 'archived'
AND updated_at = (
    SELECT MAX(updated_at) FROM lesson_plans WHERE status = 'archived'
);
```

## Sample Data

The database includes sample data for testing:

- **Roles**: Admin (role_id: 1), Teacher (role_id: 2)
- **Users**: One teacher account (Jamin Johnson, josiah.johnson6550@gmail.com)
- **Lesson Plans**: Two sample plans (one archived "Test Lesson Plan", one draft "Lesson1")

## Best Practices

1. **Security**: Always use prepared statements to prevent SQL injection
2. **Performance**: Use indexes effectively for common query patterns
3. **Data Integrity**: Leverage foreign key constraints for referential integrity
4. **Auditing**: Log all significant user actions for compliance
5. **Backup**: Regular database backups are crucial for lesson plan data
6. **Normalization**: The schema is well-normalized to reduce redundancy

## Maintenance Notes

- Regularly clean up expired password reset tokens
- Monitor activity logs for unusual patterns
- Archive old lesson plans to improve performance
- Ensure file paths in `files` and `qr_codes` tables remain valid
- Update `updated_at` timestamps on lesson plan modifications
