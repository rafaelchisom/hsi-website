-- HSI Full Database Schema
-- Run in MAMP phpMyAdmin: http://localhost:8888/phpMyAdmin

-- ─── Content Blocks (all editable page text) ────────────────────────────────
CREATE TABLE IF NOT EXISTS content_blocks (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page       VARCHAR(100)  NOT NULL,
  block_key  VARCHAR(200)  NOT NULL,
  value      LONGTEXT,
  updated_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_page_key (page, block_key)
) ENGINE=InnoDB;

-- ─── Team Members ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS team_members (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(200)  NOT NULL,
  role       VARCHAR(200)  NOT NULL,
  bio        TEXT,
  photo_url  VARCHAR(500),
  sort_order SMALLINT      NOT NULL DEFAULT 0,
  published  TINYINT(1)    NOT NULL DEFAULT 1,
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── News Articles ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS news_articles (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(500)  NOT NULL,
  author     VARCHAR(200),
  excerpt    TEXT,
  body       LONGTEXT,
  image_url  VARCHAR(500),
  category   VARCHAR(100),
  published  TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order SMALLINT      NOT NULL DEFAULT 0,
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Contact Messages ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(200)  NOT NULL,
  email      VARCHAR(255)  NOT NULL,
  org        VARCHAR(200),
  type       VARCHAR(100),
  message    TEXT          NOT NULL,
  read_at    DATETIME,
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Donations ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS donations (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100)  NOT NULL,
  last_name  VARCHAR(100),
  email      VARCHAR(255)  NOT NULL,
  amount     DECIMAL(10,2) NOT NULL,
  frequency  ENUM('one-time','monthly') NOT NULL DEFAULT 'one-time',
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Newsletter Subscribers ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(255) NOT NULL UNIQUE,
  subscribed_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Admin Users ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Login Attempts (Brute Force Protection) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS login_attempts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address    VARCHAR(45) NOT NULL,
  attempts      INT UNSIGNED NOT NULL DEFAULT 1,
  last_attempt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ip (ip_address)
) ENGINE=InnoDB;

-- ─── Seed default content ────────────────────────────────────────────────────
INSERT IGNORE INTO content_blocks (page, block_key, value) VALUES
-- Homepage
('home','hero_headline','Building Health Systems. Saving Lives.'),
('home','hero_sub','Advancing equitable and people-centred health systems through effective digital technologies.'),
('home','hero_image','https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1600&q=80'),
('home','problem_headline','The challenge we\'re built to address'),
('home','problem_body','Health systems across sub-Saharan Africa operate under considerable pressure — shortages of trained healthcare professionals, insufficient financing, fragmented service delivery, and unequal access to quality care.\n\nDigital health technologies can strengthen communication, support clinical decision-making, improve referral pathways, and generate timely information for planning. But the presence of technology does not automatically improve health outcomes. Solutions are too often fragmented, narrowly focused, or poorly integrated with existing systems.\n\nHSI exists to change that — building digital technologies designed as part of the broader health system, not apart from it.'),
('home','cta_headline','Partner With Us to Strengthen Health Systems'),
('home','cta_body','HSI works collaboratively with healthcare professionals, communities, institutions, and funders to develop digital solutions that are clinically relevant, contextually appropriate, and built to last. Join us.'),
-- About
('about','who_headline','Health Systems Initiative'),
('about','who_body','Health Systems Initiative (HSI) is a non-profit organisation committed to strengthening health systems through effective, equitable, and sustainable digital technologies.\n\nHSI focuses on the shared digital infrastructure that allows health facilities to function as connected systems rather than isolated units. Our work is designed to improve visibility, communication, accountability, and coordination across facilities.\n\nWe work collaboratively with healthcare professionals, communities, health facilities, researchers, public institutions, and other partners to develop solutions that are clinically relevant, culturally appropriate, evidence-informed, and responsive to local contexts.\n\nHSI is based in Nigeria and is designed to contribute to health systems strengthening across Africa and other low- and middle-income settings.'),
('about','vision','Equitable, connected, and people-centred health systems in which effective digital technologies improve access to timely, high-quality healthcare for everyone.'),
('about','mission','To strengthen health systems through digital technologies that improve coordination, communication, access, data use, and the delivery of equitable, high-quality healthcare.'),
('about','who_image','https://images.unsplash.com/photo-1504813184591-a197d62a677e?w=700&q=80'),
-- NICU Network
('nicu','problem_headline','Nigeria\'s neonatal crisis demands better coordination'),
('nicu','problem_body','Nigeria continues to face a substantial burden of neonatal mortality. The 2024 Nigeria Demographic and Health Survey estimates that 41 newborns die during the first month of life for every 1,000 live births — considerably higher than the global rate of 17.2 per 1,000.\n\nFor newborns who require specialist care, timely referral can be critical. Yet referral pathways are often fragmented across facilities, with limited visibility into where neonatal intensive care capacity is available.\n\nThe challenge is not only the availability of care — it is the ability to identify and reach it quickly, especially for families navigating an emergency.'),
('nicu','what_headline','What NICU Network does'),
('nicu','what_body','NICU Network is a digital platform that helps clinical teams across participating hospitals coordinate neonatal referrals more quickly and reliably.\n\nIt gives hospitals a shared, real-time view of available neonatal care capacity, so that when a newborn needs specialist care, the right facility can be identified and the transfer arranged without delay.\n\nThe platform is designed to work in real clinical conditions and to support, not disrupt, the way care teams already work.'),
('nicu','pilot_body','NICU Network is launching as a pilot in the Federal Capital Territory of Abuja in 2026, working with a group of anchor hospitals that provide neonatal intensive care.\n\nThe pilot is being developed in close partnership with the clinical teams who will use it, and will lay the groundwork for wider expansion.'),
('nicu','hero_image','https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=1600&q=80'),
-- Contact
('contact','general_email','info@healthsystemsinitiative.org'),
('contact','press_email','press@healthsystemsinitiative.org'),
('contact','partnerships_email','partnerships@healthsystemsinitiative.org'),
('contact','careers_email','careers@healthsystemsinitiative.org'),
('contact','address','Nigeria (full address to be confirmed upon CAC registration)'),
-- Settings
('settings','seo_title','Health Systems Initiative | Building Health Systems. Saving Lives.'),
('settings','seo_description','HSI is a non-profit strengthening health systems through effective, equitable, and sustainable digital technologies — improving coordination, communication, and access to quality healthcare.'),
('settings','seo_keywords','health systems strengthening, digital health Africa, healthcare coordination Nigeria, health equity, digital health non-profit, interoperability, health information systems'),
('settings','og_image',''),
('settings','site_url','https://healthsystemsinitiative.org'),
('settings','ga_id',''),
('settings','cookie_tool',''),
('settings','cookie_script',''),
('settings','info_email','info@healthsystemsinitiative.org'),
('settings','form_recipient',''),
('settings','smtp_host',''),
('settings','smtp_port','465'),
('settings','smtp_user',''),
('settings','smtp_pass',''),
('settings','smtp_from',''),
('settings','linkedin',''),
('settings','twitter',''),
('settings','facebook',''),
('settings','youtube',''),
('settings','social_other',''),
('settings','donorbox_url',''),
('settings','paystack_url','');

-- Seed team members
INSERT IGNORE INTO team_members (name, role, bio, sort_order) VALUES
('Dr. Jason Ayomide Quist','Founder & Executive Director',NULL,1),
('Rafael Ariguzo','IT Lead',NULL,2),
('Dr. MaryJane Nweje','Programme Co-Lead',NULL,3),
('Dr. Samuel Abimbola Kolapo','Programme Co-Lead',NULL,4),
('Dr. Nehemiah Mfon','Clinical Lead',NULL,5),
('Dr. Amen Nwosu','Research & Evaluation Co-Lead',NULL,6),
('Dr. Angel Anjorin','Evaluation & Grants Lead',NULL,7),
('Dr. Aisha Garba','Coordination Team Lead',NULL,8);

-- Seed launch news article
INSERT IGNORE INTO news_articles (title, author, excerpt, body, category, sort_order) VALUES
('Introducing Health Systems Initiative',
 'Dr. Jason Ayomide Quist, Founder & Executive Director',
 'Health Systems Initiative was founded on a clear premise: that digital technologies can and should strengthen health systems — but only when they are designed as part of those systems, not apart from them.',
 'Health Systems Initiative was founded on a clear premise: that digital technologies can and should strengthen health systems — but only when they are designed as part of those systems, not apart from them. Today, we are proud to share who we are, what we are building, and why it matters.',
 'Announcement', 1);

-- Where We Work map countries (JSON array stored as a single block)
INSERT IGNORE INTO content_blocks (page, block_key, value) VALUES
('map','countries','[{"code":"NGA","name":"Nigeria","note":"Headquarters & NICU Network pilot"},{"code":"GHA","name":"Ghana","note":"Advisory engagement"},{"code":"KEN","name":"Kenya","note":"Partnerships in development"},{"code":"ZAF","name":"South Africa","note":"Research collaboration"}]'),
('map','section_headline','Where We Work'),
('map','section_body','HSI works across sub-Saharan Africa, partnering with healthcare facilities, governments, and communities to strengthen health systems through effective digital technologies.');
