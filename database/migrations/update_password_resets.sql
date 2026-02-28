-- Password Resets Table Schema Update
-- Adds missing created_at column to password_resets table

ALTER TABLE password_resets 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER expires_at;

-- Add index for better query performance
CREATE INDEX idx_password_resets_token ON password_resets(token);
CREATE INDEX idx_password_resets_expires_at ON password_resets(expires_at);
