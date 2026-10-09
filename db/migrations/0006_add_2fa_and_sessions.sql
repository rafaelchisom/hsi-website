ALTER TABLE users
  ADD COLUMN totp_secret VARCHAR(64) NULL,
  ADD COLUMN totp_enabled SMALLINT NOT NULL DEFAULT 0;

CREATE TABLE user_recovery_codes (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  code_hash VARCHAR(255) NOT NULL,
  used_at TIMESTAMP(0) NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

-- `id` (not the raw PHP session_id) is the identifier ever exposed to the
-- client for listing/revoking sessions — returning the actual session_id
-- would hand out a live session token that could be used to hijack it.
CREATE TABLE user_sessions (
  id SERIAL PRIMARY KEY,
  session_id VARCHAR(128) NOT NULL UNIQUE,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  last_active_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_user_sessions_user ON user_sessions (user_id);
