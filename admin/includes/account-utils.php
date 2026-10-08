<?php
if (!function_exists('gbi_admin_base_url')) {
    function gbi_admin_base_url()
    {
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $adminPos = strpos($scriptPath, '/admin/');
        $baseUrl = $adminPos !== false
            ? substr($scriptPath, 0, $adminPos)
            : rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
        if ($baseUrl === '/') {
            $baseUrl = '';
        }

        return $baseUrl;
    }
}

if (!function_exists('gbi_admin_escape')) {
    function gbi_admin_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('gbi_admin_normalize_text')) {
    function gbi_admin_normalize_text($value)
    {
        return trim((string) $value);
    }
}

if (!function_exists('gbi_admin_verify_password')) {
    function gbi_admin_verify_password($plainPassword, $storedPassword)
    {
        return password_verify($plainPassword, $storedPassword);
    }
}