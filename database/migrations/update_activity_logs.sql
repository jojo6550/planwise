-- Activity Logging Schema Update
-- Adds missing columns to activity_logs table

ALTER TABLE activity_logs 
ADD COLUMN description TEXT DEFAULT NULL AFTER action,
ADD COLUMN user_agent VARCHAR(255) DEFAULT NULL AFTER ip_address;

-- Create index for better query performance
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

