<?php

require __DIR__ . '/config.php';


spl_autoload_register(function (string $class): void {
    foreach ([__DIR__ . '/lib/', __DIR__ . '/controllers/'] as $dir) {
        $file = $dir . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

Auth::init();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($uriPath, $scriptDir)) {
    $path = '/' . ltrim(substr($uriPath, strlen($scriptDir)), '/');
} else {
    // Reached via a root-level rewrite (e.g. /sitemap.xml -> api/sitemap.xml)
    // where REQUEST_URI still reflects the original, un-rewritten URL rather
    // than this script's own directory. Fall back to stripping the site
    // base path (the parent of this script's directory) instead.
    $siteBase = str_replace('\\', '/', dirname($scriptDir));
    $path = '/' . ltrim(substr($uriPath, strlen($siteBase)), '/');
}
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

$router = new Router();

AuthController::routes($router);
DashboardController::routes($router);
TeamMembersController::routes($router);
NewsController::routes($router);
CountriesController::routes($router);
DonationTiersController::routes($router);
ContactMethodsController::routes($router);
SdgGoalsController::routes($router);
PagesController::routes($router);
SiteSettingsController::routes($router);
MediaController::routes($router);
ContactSubmissionsController::routes($router);
NewsletterController::routes($router);
SitemapController::routes($router);
RevisionsController::routes($router);
DonationsController::routes($router);
TeamTiersController::routes($router);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (PDOException $e) {
    error_log('[DB error] ' . $e->getMessage());
    Response::error(APP_DEBUG ? 'Database error: ' . $e->getMessage() : 'A database error occurred', 500);
} catch (Throwable $e) {
    error_log('[Server error] ' . $e->getMessage());
    Response::error(APP_DEBUG ? 'Server error: ' . $e->getMessage() : 'A server error occurred', 500);
}
