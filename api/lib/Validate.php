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
        return $data;
    }
}
