<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

try {
    $db = getDB();
    $stmt = $db->query("SELECT value FROM site_settings WHERE setting_key = 'site_url'");
    $row  = $stmt->fetch();
    $base = rtrim($row['value'] ?? 'https://healthsystemsinitiative.org', '/');
} catch (Exception $e) {
    $base = 'https://healthsystemsinitiative.org';
}

$now = date('Y-m-d');

$static = [
    ['loc' => $base . '/',                                'priority' => '1.0', 'freq' => 'weekly'],
    ['loc' => $base . '/#/about',                         'priority' => '0.8', 'freq' => 'monthly'],
    ['loc' => $base . '/#/our-approach',                  'priority' => '0.8', 'freq' => 'monthly'],
    ['loc' => $base . '/#/projects',                      'priority' => '0.9', 'freq' => 'weekly'],
    ['loc' => $base . '/#/projects/nicu-network-nigeria', 'priority' => '0.9', 'freq' => 'monthly'],
    ['loc' => $base . '/#/team',                          'priority' => '0.7', 'freq' => 'monthly'],
    ['loc' => $base . '/#/news',                          'priority' => '0.8', 'freq' => 'daily'],
    ['loc' => $base . '/#/get-involved',                  'priority' => '0.9', 'freq' => 'monthly'],
    ['loc' => $base . '/#/contact',                       'priority' => '0.7', 'freq' => 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($static as $url) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
    echo "    <lastmod>{$now}</lastmod>\n";
    echo "    <changefreq>{$url['freq']}</changefreq>\n";
    echo "    <priority>{$url['priority']}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
