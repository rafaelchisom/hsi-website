-- DHAF CMS schema (PostgreSQL / Supabase port of the original MySQL schema).
-- Conventions used throughout the migrations:
--   * MySQL TINYINT(1) flags   -> SMALLINT 0/1 (the PHP code compares with = 1)
--   * MySQL ENUM               -> VARCHAR + CHECK
--   * ON UPDATE CURRENT_TIMESTAMP -> set_updated_at() trigger
--   * TIMESTAMP(0)             -> whole seconds, matching what MySQL returned

CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP(0);
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'editor' CHECK (role IN ('admin','editor')),
  is_active SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE media (
  id SERIAL PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size_bytes INTEGER NOT NULL,
  alt_text VARCHAR(255) NULL,
  uploaded_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER site_settings_updated_at BEFORE UPDATE ON site_settings FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE pages (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  meta_description VARCHAR(300) NULL,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER pages_updated_at BEFORE UPDATE ON pages FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE page_sections (
  id SERIAL PRIMARY KEY,
  page_id INTEGER NOT NULL REFERENCES pages(id) ON DELETE CASCADE,
  section_key VARCHAR(100) NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  heading VARCHAR(300) NULL,
  body_html TEXT NULL,
  data_json JSON NULL,
  image_id INTEGER NULL REFERENCES media(id) ON DELETE SET NULL,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_page_section UNIQUE (page_id, section_key)
);
CREATE TRIGGER page_sections_updated_at BEFORE UPDATE ON page_sections FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE team_members (
  id SERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  role VARCHAR(150) NOT NULL,
  bio TEXT NULL,
  photo_id INTEGER NULL REFERENCES media(id) ON DELETE SET NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_published SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER team_members_updated_at BEFORE UPDATE ON team_members FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE news_articles (
  id SERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  category VARCHAR(100) NOT NULL,
  summary VARCHAR(500) NULL,
  lede TEXT NULL,
  body_html TEXT NULL,
  author VARCHAR(150) NULL DEFAULT 'Syncura Initiative',
  featured_image_id INTEGER NULL REFERENCES media(id) ON DELETE SET NULL,
  icon_type VARCHAR(50) NULL,
  background_gradient VARCHAR(30) NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'draft',
  publish_date DATE NULL,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT news_articles_status_check CHECK (status IN ('draft','published'))
);
CREATE INDEX idx_status_date ON news_articles (status, publish_date);
CREATE INDEX idx_category ON news_articles (category);
CREATE TRIGGER news_articles_updated_at BEFORE UPDATE ON news_articles FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE countries (
  id SERIAL PRIMARY KEY,
  iso_code CHAR(2) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  programme_name VARCHAR(150) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'planned' CHECK (status IN ('active','planned')),
  sort_order INTEGER NOT NULL DEFAULT 0,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER countries_updated_at BEFORE UPDATE ON countries FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE donation_tiers (
  id SERIAL PRIMARY KEY,
  tier_name VARCHAR(100) NOT NULL,
  usd_amount VARCHAR(20) NULL,
  ngn_amount VARCHAR(20) NULL,
  description VARCHAR(300) NULL,
  is_featured SMALLINT NOT NULL DEFAULT 0,
  button_url VARCHAR(255) NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER donation_tiers_updated_at BEFORE UPDATE ON donation_tiers FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE contact_methods (
  id SERIAL PRIMARY KEY,
  label VARCHAR(100) NOT NULL,
  icon_type VARCHAR(50) NOT NULL,
  contact_value VARCHAR(255) NOT NULL,
  link_type VARCHAR(10) NOT NULL DEFAULT 'mailto' CHECK (link_type IN ('mailto','plain')),
  sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE sdg_goals (
  id SERIAL PRIMARY KEY,
  sdg_number SMALLINT NOT NULL,
  target_label VARCHAR(100) NULL,
  alignment_type VARCHAR(10) NOT NULL DEFAULT 'supporting' CHECK (alignment_type IN ('primary','supporting')),
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(50) NULL,
  color_class VARCHAR(30) NULL,
  sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE contact_submissions (
  id SERIAL PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  organisation VARCHAR(150) NULL,
  enquiry_type VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_subscribers (
  id SERIAL PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  subscribed_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);
