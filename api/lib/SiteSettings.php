<?php

/**
 * Read-side helper for backend code that needs a specific site_settings value
 * (e.g. email config). Admin read/write of the full set still goes through
 * SiteSettingsController's GET/PUT /site-settings — this is just a
 * per-request-cached lookup for server-side use.
 */
class SiteSettings
{
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            self::$cache = [];
            $rows = Database::get()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        }
        $value = self::$cache[$key] ?? null;
        return ($value !== null && $value !== '') ? $value : $default;
    }
}
