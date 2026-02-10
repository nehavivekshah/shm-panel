<?php
/**
 * .htaccess to Nginx Parser
 * Converts common Apache directives to Nginx format.
 */

function convert_htaccess_to_nginx($htaccess_content)
{
    if (empty($htaccess_content))
        return "";

    $lines = explode("\n", $htaccess_content);
    $nginx_config = "# Auto-generated from .htaccess\n\n";
    $rewrite_engine = false;
    $conditions = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0)
            continue;

        // RewriteEngine
        if (preg_match('/^RewriteEngine\s+(on|off)/i', $line, $matches)) {
            $rewrite_engine = strtolower($matches[1]) === 'on';
            continue;
        }

        // RewriteBase
        if (preg_match('/^RewriteBase\s+(.*)/i', $line, $matches)) {
            // Nginx usually doesn't need RewriteBase if rules are relative to root or within a location
            continue;
        }

        // RewriteCond
        if (preg_match('/^RewriteCond\s+(.*)\s+(.*)\s*(\[.*\])?/i', $line, $matches)) {
            $test = $matches[1];
            $cond = $matches[2];
            $flags = $matches[3] ?? '';

            // Map common conditions
            $test = str_replace('%{REQUEST_FILENAME}', '$request_filename', $test);
            $test = str_replace('%{HTTP_HOST}', '$http_host', $test);
            $test = str_replace('%{REQUEST_URI}', '$request_uri', $test);
            $test = str_replace('%{HTTPS}', '$https', $test);

            if ($cond === '!-f') {
                $conditions[] = "! -f $test";
            } elseif ($cond === '!-d') {
                $conditions[] = "! -d $test";
            } elseif (preg_match('/^\^?www\./i', $cond)) {
                $conditions[] = "$test ~* ^www\.";
            } else {
                // Fallback for simple patterns
                $conditions[] = "$test ~ " . str_replace('"', '', $cond);
            }
            continue;
        }

        // RewriteRule
        if (preg_match('/^RewriteRule\s+(.*)\s+(.*)\s*(\[.*\])?/i', $line, $matches)) {
            $pattern = $matches[1];
            $substitution = $matches[2];
            $flags = $matches[3] ?? '';

            $nginx_rule = "rewrite $pattern $substitution";

            // Map flags
            if (stripos($flags, 'L') !== false)
                $nginx_rule .= " last";
            elseif (stripos($flags, 'R') !== false)
                $nginx_rule .= " redirect";

            $nginx_rule .= ";";

            if (!empty($conditions)) {
                foreach ($conditions as $c) {
                    $nginx_config .= "if ($c) {\n    $nginx_rule\n}\n";
                }
                $conditions = []; // Reset for next rule
            } else {
                $nginx_config .= "$nginx_rule\n";
            }
            continue;
        }

        // ErrorDocument
        if (preg_match('/^ErrorDocument\s+(\d+)\s+(.*)/i', $line, $matches)) {
            $code = $matches[1];
            $path = $matches[2];
            $nginx_config .= "error_page $code $path;\n";
            continue;
        }

        // DirectoryIndex
        if (preg_match('/^DirectoryIndex\s+(.*)/i', $line, $matches)) {
            $nginx_config .= "index " . $matches[1] . ";\n";
            continue;
        }
    }

    return $nginx_config;
}

// If called via CLI for testing or single file use
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $file = $argv[1];
    if (file_exists($file)) {
        echo convert_htaccess_to_nginx(file_get_contents($file));
    }
}
