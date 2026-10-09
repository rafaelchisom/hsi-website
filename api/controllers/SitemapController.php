<?php

class SitemapController
{
    public static function routes(Router $r): void
    {
        $r->get('/sitemap.xml', [self::class, 'show']);
    }

    public static function show(): void
    {
        $pdo = Database::get();

        $pages = $pdo->query('SELECT slug, updated_at FROM pages')->fetchAll();
        $articles = $pdo->query("SELECT slug, updated_at FROM news_articles WHERE status = 'published'")->fetchAll();

        $entries = [];
        foreach ($pages as $page) {
            $entries[] = [
                'loc' => $page['slug'] === 'home' ? SITE_URL . '/' : SITE_URL . '/' . $page['slug'],
                'lastmod' => $page['updated_at'],
            ];
        }
        foreach ($articles as $article) {
            $entries[] = [
                'loc' => SITE_URL . '/news/' . $article['slug'],
                'lastmod' => $article['updated_at'],
            ];
        }

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        foreach ($entries as $entry) {
            $urlEl = $xml->addChild('url');
            $urlEl->addChild('loc', htmlspecialchars($entry['loc'], ENT_XML1));
            if ($entry['lastmod']) {
                $urlEl->addChild('lastmod', date('Y-m-d', strtotime($entry['lastmod'])));
            }
        }

        Response::xml($xml->asXML());
    }
}
