CREATE TABLE rate_limits (
  id SERIAL PRIMARY KEY,
  bucket VARCHAR(50) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_bucket_ip_time ON rate_limits (bucket, ip_address, created_at);
