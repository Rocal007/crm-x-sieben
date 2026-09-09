<?php
/**
 * Normalizes a string for comparison by making it lowercase, trimming whitespace,
 * and removing special characters.
 *
 * @param string $str The input string.
 * @return string The normalized string.
 */
function normalize_string($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    // Replace en-dash, em-dash, and minus sign with a standard hyphen
    $str = preg_replace('/[\x{2013}\x{2014}\x{2212}]/u', '-', $str);
    // Remove registered trademark symbol and replace slashes with spaces
    $str = str_replace(['®', '/'], ['', ' '], $str);
    // Collapse multiple whitespace characters into a single space
    $str = preg_replace('/\s+/', ' ', $str);
    // Remove all characters except lowercase letters, numbers, hyphens, and spaces
    return preg_replace('/[^a-z0-9\- ]/', '', $str);
}

/**
 * Normalizes HTML for transactional and CRM outgoing emails.
 *
 * Ensures all image sources (src) and hyperlinks (href) use full, valid, canonical HTTPS URLs
 * pointing to the live production server (https://x-sieben.at), eliminating relative paths
 * (/wp-content/...), local test domains, redirecting domains (www.), and destructive
 * attributes added by cookie consent plugins (e.g. consent-original-src-_).
 *
 * @param string $body Raw or semi-processed HTML content.
 * @return string Fully normalized, mail-client-ready HTML string.
 */
function crm_prepare_email_html_for_sending($body) {
    if (empty($body) || !is_string($body)) {
        return '';
    }

    $canonical_host = 'https://x-sieben.at';

    // 0. Strip TinyMCE editor artifacts globally before attribute parsing
    $body = preg_replace('/\s*data-mce-[a-z0-9_-]+=["\'][^"\']*["\']/i', '', $body);

    // 1. Convert WordPress emoji smiley images back to their text emoji (e.g. 🧪)
    // to prevent broken image boxes or cookie banner interception on smilies
    $body = preg_replace_callback('/<img\s+[^>]*class=["\'][^"\']*wp-smiley[^"\']*["\'][^>]*\/?>/i', function ($matches) {
        if (preg_match('/alt=["\']([^"\']+)["\']/i', $matches[0], $alt)) {
            return $alt[1];
        }
        return '';
    }, $body);

    // 2. Restore src from cookie banner consent attributes (e.g. Real Cookie Banner, Cookiebot)
    $body = preg_replace_callback('/<img([^>]+)>/i', function ($matches) {
        $tag = $matches[0];
        if (preg_match('/consent-original-src-_=["\']([^"\']+)["\']/i', $tag, $m)) {
            $orig_src = $m[1];
            $tag = preg_replace('/\s*consent-[a-z0-9_-]+=["\'][^"\']*["\']/i', '', $tag);
            if (preg_match('/\ssrc=["\'][^"\']*["\']/i', $tag)) {
                $tag = preg_replace('/\ssrc=["\'][^"\']*["\']/i', ' src="' . esc_url($orig_src) . '"', $tag);
            } else {
                $tag = preg_replace('/<img/i', '<img src="' . esc_url($orig_src) . '"', $tag);
            }
        }
        return $tag;
    }, $body);

    // 3. Normalize all <img> src attributes to guaranteed public HTTPS
    $body = preg_replace_callback('/<img(\s+[^>]*?)\bsrc=["\']([^"\']+)["\']([^>]*)>/i', function ($matches) use ($canonical_host) {
        $before = $matches[1];
        $src    = trim($matches[2]);
        $after  = $matches[3];

        // Skip data: URIs or cid: if already present
        if (strpos($src, 'data:') === 0 || strpos($src, 'cid:') === 0) {
            return $matches[0];
        }

        // Clean local development hosts
        $src = preg_replace('#^https?://(www\.)?x-sieben\.(test|local|dev)/#i', $canonical_host . '/', $src);
        $src = preg_replace('#^https?://localhost(:[0-9]+)?/#i', $canonical_host . '/', $src);

        // Strip www. from x-sieben.at to avoid 301 redirects in mail proxies
        $src = preg_replace('#^https?://www\.x-sieben\.at/#i', $canonical_host . '/', $src);

        // Force HTTPS for x-sieben.at
        $src = preg_replace('#^http://x-sieben\.at/#i', 'https://x-sieben.at/', $src);

        // Expand root-relative or theme-relative upload paths
        if (preg_match('#^/wp-content/(.+)$#i', $src, $m)) {
            $src = $canonical_host . '/wp-content/' . $m[1];
        } elseif (preg_match('#^wp-content/(.+)$#i', $src, $m)) {
            $src = $canonical_host . '/wp-content/' . $m[1];
        } elseif (preg_match('#^\.\./wp-content/(.+)$#i', $src, $m)) {
            $src = $canonical_host . '/wp-content/' . $m[1];
        }

        // Clean up accidental double slashes in paths
        $src = preg_replace('#([^:])//+#', '$1/', $src);

        // Ensure border="0" is present for legacy email clients
        if (stripos($before, 'border=') === false && stripos($after, 'border=') === false) {
            $after .= ' border="0"';
        }

        return '<img' . $before . ' src="' . esc_url($src) . '"' . $after . '>';
    }, $body);

    // 4. Normalize all <a href="..."> links to guaranteed absolute HTTPS
    $body = preg_replace_callback('/<a(\s+[^>]*?)\bhref=["\']([^"\']+)["\']([^>]*)>/i', function ($matches) use ($canonical_host) {
        $before = $matches[1];
        $href   = trim($matches[2]);
        $after  = $matches[3];

        if (preg_match('/^(mailto:|tel:|#|javascript:)/i', $href)) {
            return $matches[0];
        }


        // Fix damaged relative URLs from older database filters
        if (preg_match('#^http://(weiterbildung|experte|blog|datenschutzerklaerung|kontakt|agb)(/.*)?$#i', $href, $m)) {
            $href = $canonical_host . '/' . $m[1] . ($m[2] ?? '');
        }

        // Clean dev hosts & www
        $href = preg_replace('#^https?://(www\.)?x-sieben\.(test|local|dev)/#i', $canonical_host . '/', $href);
        $href = preg_replace('#^https?://localhost(:[0-9]+)?/#i', $canonical_host . '/', $href);
        $href = preg_replace('#^https?://www\.x-sieben\.at/#i', $canonical_host . '/', $href);
        $href = preg_replace('#^http://x-sieben\.at/#i', 'https://x-sieben.at/', $href);

        // Expand root-relative paths
        if (strpos($href, '/') === 0 && strpos($href, '//') !== 0) {
            $href = $canonical_host . $href;
        }

        return '<a' . $before . ' href="' . esc_url($href) . '"' . $after . '>';
    }, $body);

    return $body;
}

/**
 * Erzeugt eine saubere, dezente Trennlinie für TCPDF ohne schwarze Block-Artefakte.
 *
 * @param string $color Hex-Farbcode (Standard: #cbd5e1 - dezentes Grau)
 * @param int $padding_top Abstand nach oben in pt
 * @param int $padding_bottom Abstand nach unten in pt
 * @return string HTML-Trennlinie
 */
function crm_pdf_divider(string $color = '#cbd5e1', int $padding_top = 4, int $padding_bottom = 4): string {
    return '<table cellspacing="0" cellpadding="0" style="width: 100%; margin-top: ' . intval($padding_top) . 'pt; margin-bottom: ' . intval($padding_bottom) . 'pt;"><tr><td style="border-bottom: 1px solid ' . esc_attr($color) . '; height: 1px; font-size: 1pt;">&nbsp;</td></tr></table>';
}