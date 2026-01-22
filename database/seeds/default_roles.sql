-- Seed data for default roles
-- Insert default roles into the roles table

INSERT INTO roles (role_name, description) VALUES
('Admin', 'Administrator with full system access'),
('Teacher', 'Teacher with access to lesson plan management')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;
