-- Rebrand: Syncura Initiative -> Digital Healthcare Access Foundation (DHAF).
-- Updates the one DB-level default that hardcoded the old org name (news_articles.author);
-- all other branding lives in site_settings/content and was updated via direct data edits.
ALTER TABLE news_articles ALTER COLUMN author SET DEFAULT 'Digital Healthcare Access Foundation';
