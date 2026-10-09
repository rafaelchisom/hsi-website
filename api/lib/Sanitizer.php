<?php

/**
 * Whitelist HTML sanitizer for admin-authored rich text (Tiptap output).
 * Strips anything outside the allowed tag/attribute set so a compromised or
 * malicious editor account can't stash a stored XSS payload in body_html.
 */
class Sanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'u', 's',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'a', 'code', 'pre', 'span', 'img',
    ];

    private const ALLOWED_ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ];

    // Tags whose content (not just the tag) is unsafe and must be dropped entirely.
    private const STRIP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg'];

    public static function html(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div id="__sanitize_root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $doc->getElementById('__sanitize_root');
        if (!$root) {
            return '';
        }

        self::cleanChildren($doc, $root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    private static function cleanChildren(DOMDocument $doc, DOMNode $node): void
    {
        $toRemove = [];

        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $toRemove[] = $child;
                continue;
            }

            if ($child instanceof DOMText) {
                continue;
            }

            if (!($child instanceof DOMElement)) {
                $toRemove[] = $child;
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::STRIP_WITH_CONTENT, true)) {
                $toRemove[] = $child;
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown/disallowed tag: unwrap it, keep its (sanitized) children.
                self::cleanChildren($doc, $child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $toRemove[] = $child;
                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::cleanChildren($doc, $child);
        }

        foreach ($toRemove as $r) {
            $node->removeChild($r);
        }
    }

    private static function sanitizeAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRS[$tag] ?? [];
        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $name = strtolower($attr->name);
            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            if (in_array($name, ['href', 'src'], true) && self::isUnsafeUrl($attr->value)) {
                $el->removeAttribute($attr->name);
            }
        }
    }

    private static function isUnsafeUrl(string $value): bool
    {
        $value = trim($value);
        return (bool) preg_match('/^\s*(javascript|data|vbscript):/i', $value);
    }
}
