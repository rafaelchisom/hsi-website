<?php

/**
 * Classic honeypot spam check: public forms include a field real visitors
 * never see or fill in (hidden off-screen, not display:none — some bots skip
 * those specifically). Any value in it means the submitter is a bot filling
 * every field it finds, so the request is silently dropped as if it
 * succeeded — telling a bot "rejected" just teaches it to adapt.
 */
class Honeypot
{
    public static function isBot(array $data, string $field = 'website'): bool
    {
        return trim((string) ($data[$field] ?? '')) !== '';
    }
}
