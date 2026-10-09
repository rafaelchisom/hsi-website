<?php
/**
 * Serves the public single-page app's shell (public/index.html) with the
 * page-specific <title>, meta description, canonical, Open Graph and Twitter
 * tags filled in from the database on every request, and a real HTTP 404 for
 * URLs that don't exist.
 *
 * Why: the shell is the same file for every URL, and link-preview bots
 * (Facebook, WhatsApp, Slack, LinkedIn, X) and crawlers read the raw HTML
 * without running JavaScript. Injecting the tags here means each page and
 * article shares correctly and always reflects the latest admin edits — with
 * no browser or build step needed on the server.
 *
 * The injected tags carry data-default="true"; the React app strips those on
 * load and re-renders the same values itself (see app-public/src/main.jsx),
 * so there are never duplicates.
 */

require __DIR__ . '/api/config.php';
require __DIR__ . '/api/lib/Database.php';
require __DIR__ . '/api/lib/Uploads.php';

const SEO_DEFAULT_TITLE = 'Digital Healthcare Access Foundation | Strengthening Health Systems. Saving Lives.';
const SEO_DEFAULT_DESCRIPTION = 'Digital Healthcare Access Foundation strengthens health systems through effective, equitable, and sustainable digital technologies.';
const SEO_STATIC_PAGES = ['our-approach', 'our-team', 'nicu-network', 'news', 'get-involved', 'donate'];

function seo_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function seo_plain(?string $html, int $max = 200): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));
    return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
}

$shell = @file_get_contents(__DIR__ . '/public/index.html');
if ($shell === false) {
    http_response_code(500);
    exit('The site has not been installed correctly (public/index.html is missing).');
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (SITE_PATH !== '' && strpos($path, SITE_PATH) === 0) {
    $path = substr($path, strlen(SITE_PATH));
}
$path = '/' . trim($path, '/');

$status = 200;
$title = SEO_DEFAULT_TITLE;
$description = SEO_DEFAULT_DESCRIPTION;
$image = null;
$type = 'website';
$siteName = 'Digital Healthcare Access Foundation';

try {
    $pdo = Database::get();
    $settings = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    if (!empty($settings['site_title'])) {
        $siteName = $settings['site_title'];
    }
    if (!empty($settings['og_image_url'])) {
        $image = $settings['og_image_url'];
    }

    $pageSlug = null;
    $articleSlug = null;
    if ($path === '/') {
        $pageSlug = 'home';
    } elseif (in_array(ltrim($path, '/'), SEO_STATIC_PAGES, true)) {
        $pageSlug = ltrim($path, '/');
    } elseif (preg_match('#^/news/([a-z0-9-]+)$#i', $path, $m)) {
        $articleSlug = $m[1];
    } else {
        $status = 404;
    }

    if ($pageSlug !== null) {
        $stmt = $pdo->prepare('SELECT title, meta_description FROM pages WHERE slug = ?');
        $stmt->execute([$pageSlug]);
        if ($row = $stmt->fetch()) {
            $title = $row['title'] ?: $title;
            $description = $row['meta_description'] ?: $description;
        }
    } elseif ($articleSlug !== null) {
        $stmt = $pdo->prepare("SELECT n.title, n.summary, n.lede, m.filename AS image FROM news_articles n
            LEFT JOIN media m ON m.id = n.featured_image_id
            WHERE n.slug = ? AND (n.status = 'published' OR (n.status = 'scheduled' AND n.publish_date <= CURRENT_DATE))");
        $stmt->execute([$articleSlug]);
        if ($row = $stmt->fetch()) {
            $title = $row['title'];
            $description = seo_plain($row['summary'] ?: $row['lede']) ?: $description;
            $type = 'article';
            if ($row['image']) {
                $image = Uploads::url($row['image']);
            }
        } else {
            $status = 404;
        }
    }
} catch (Throwable $e) {
    error_log('[seo.php] ' . $e->getMessage());
    // Fall through with the defaults: a database hiccup must not take the page down.
}

if ($image === null) {
    $image = SITE_PATH . '/assets/DHAF_h_primary.png';
}
$origin = preg_replace('#^(https?://[^/]+).*$#', '$1', SITE_URL);
$imageUrl = preg_match('#^https?://#', $image) ? $image : $origin . $image;
$canonical = SITE_URL . ($path === '/' ? '/' : $path);

$tags = [
    '<title data-default="true">' . seo_h($title) . '</title>',
    '<meta data-default="true" name="description" content="' . seo_h($description) . '" />',
    '<link data-default="true" rel="canonical" href="' . seo_h($canonical) . '" />',
    '<meta data-default="true" property="og:type" content="' . $type . '" />',
    '<meta data-default="true" property="og:site_name" content="' . seo_h($siteName) . '" />',
    '<meta data-default="true" property="og:title" content="' . seo_h($title) . '" />',
    '<meta data-default="true" property="og:description" content="' . seo_h($description) . '" />',
    '<meta data-default="true" property="og:url" content="' . seo_h($canonical) . '" />',
    '<meta data-default="true" property="og:image" content="' . seo_h($imageUrl) . '" />',
    '<meta data-default="true" name="twitter:card" content="summary_large_image" />',
    '<meta data-default="true" name="twitter:title" content="' . seo_h($title) . '" />',
    '<meta data-default="true" name="twitter:description" content="' . seo_h($description) . '" />',
    '<meta data-default="true" name="twitter:image" content="' . seo_h($imageUrl) . '" />',
];
if ($status === 404) {
    $tags[] = '<meta data-default="true" name="robots" content="noindex, nofollow" />';
}

// Replace the shell's generic defaults with this page's tags.
$shell = preg_replace('#<title data-default="true">.*?</title>#s', '', $shell);
$shell = preg_replace('#<meta data-default="true"[^>]*>#', '', $shell);
$shell = preg_replace('#</head>#', "    " . implode("\n    ", $tags) . "\n  </head>", $shell, 1);
$shell = str_replace(
    '<div id="root"></div>',
    '<div id="root"></div>' . "\n    <noscript><h1>" . seo_h($title) . '</h1><p>' . seo_h($description) . '</p></noscript>',
    $shell
);

http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
echo $shell;
