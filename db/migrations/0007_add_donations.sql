CREATE TABLE donations (
  id SERIAL PRIMARY KEY,
  provider VARCHAR(30) NOT NULL DEFAULT 'paypal',
  provider_order_id VARCHAR(100) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  frequency VARCHAR(20) NOT NULL DEFAULT 'once',
  amount NUMERIC(12,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'USD',
  donor_name VARCHAR(150) NULL,
  donor_email VARCHAR(190) NULL,
  dedication TEXT NULL,
  raw_response TEXT NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uniq_provider_order UNIQUE (provider, provider_order_id)
);
CREATE INDEX idx_donations_status ON donations (status);
CREATE INDEX idx_donations_created ON donations (created_at);
CREATE TRIGGER donations_updated_at BEFORE UPDATE ON donations FOR EACH ROW EXECUTE FUNCTION set_updated_at();
