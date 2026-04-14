<?php

if (!function_exists('gbi_is_html_fragment')) {
    function gbi_is_html_fragment($text)
    {
        return preg_match('/<\s*\/?\s*[a-z][^>]*>/i', (string) $text) === 1;
    }
}

if (!function_exists('gbi_sanitize_renungan_html')) {
    function gbi_sanitize_renungan_html($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = str_replace(["\r\n", "\r"], "\n", $html);
        $html = preg_replace('#<\s*(/?)\s*div\b[^>]*>#i', '<$1p>', $html);

        $html = preg_replace('#<\s*(script|style|iframe|object|embed|svg|math|form|input|button|textarea|select|option|meta|link)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4>';
        $html = strip_tags($html, $allowed);

        $html = preg_replace_callback('/<(\/?)([a-z0-9]+)(?:\s[^>]*)?>/i', static function ($match) {
            $tag = strtolower($match[2]);
            $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'h2', 'h3', 'h4'];

            if (!in_array($tag, $allowedTags, true)) {
                return '';
            }

            if ($tag === 'br') {
                return '<br>';
            }

            return '<' . $match[1] . $tag . '>';
        }, $html);

        $html = preg_replace('/\n{3,}/', "\n\n", $html);
        $html = trim($html);

        $textOnly = strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $html));
        if (trim((string) $textOnly) === '') {
            return '';
        }

        return $html;
    }
}

if (!function_exists('gbi_render_renungan_body')) {
    function gbi_render_renungan_body($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        if (gbi_is_html_fragment($value)) {
            return gbi_sanitize_renungan_html($value);
        }

        return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('gbi_editor_initial_html')) {
    function gbi_editor_initial_html($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (gbi_is_html_fragment($value)) {
            return gbi_sanitize_renungan_html($value);
        }

        return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }
}
