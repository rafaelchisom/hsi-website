CREATE TABLE revisions (
  id SERIAL PRIMARY KEY,
  resource_type VARCHAR(50) NOT NULL,
  resource_id INTEGER NOT NULL,
  data_json JSON NOT NULL,
  user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_resource ON revisions (resource_type, resource_id, created_at);

CREATE TABLE activity_log (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
  action VARCHAR(20) NOT NULL,
  resource_type VARCHAR(50) NOT NULL,
  resource_id INTEGER NULL,
  description VARCHAR(300) NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_activity_created ON activity_log (created_at);
