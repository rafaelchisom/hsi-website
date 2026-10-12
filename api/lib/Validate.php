<?php

class Validate
{
    public static function requireFields(array $data, array $fields): void
    {
        $missing = [];
        foreach ($fields as $f) {
            if (!array_key_exists($f, $data) || $data[$f] === null || $data[$f] === '') {
                $missing[] = $f;
            }
        }
        if ($missing) {
            Response::error('Missing required field(s): ' . implode(', ', $missing), 422);
        }
    }

    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Response::error('Invalid JSON body', 400);
        }
        // The flag columns are SMALLINT 0/1 (MySQL TINYINT(1) originally). The
        // admin sends JSON true/false for is_active, is_published, is_featured;
        // MySQL accepted those, but PDO binds false as '' which PostgreSQL
        // rejects. Only top-level values: nested ones (e.g. a page section's
        // data_json) are stored as JSON and must keep their real booleans.
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $data[$key] = $value ? 1 : 0;
            }
        }
        return $data;
    }
}
