-- Activity Logs Table Schema Update
-- Adds missing description and user_agent columns to activity_logs table

ALTER TABLE activity_logs 
ADD COLUMN description TEXT AFTER action,
ADD COLUMN user_agent VARCHAR(255) AFTER ip_address;
