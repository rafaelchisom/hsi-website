CREATE TABLE login_attempts (
  id SERIAL PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  succeeded SMALLINT NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_email_time ON login_attempts (email, attempted_at);
