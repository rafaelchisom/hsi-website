ALTER TABLE news_articles DROP CONSTRAINT news_articles_status_check;
ALTER TABLE news_articles ADD CONSTRAINT news_articles_status_check CHECK (status IN ('draft','scheduled','published'));
