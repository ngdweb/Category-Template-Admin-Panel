<?php

namespace App\Http\Middleware;

use App\Support\SecureMediaHelper;
use Closure;
use Illuminate\Http\Request;

/**
 * Rewrites every /upload/... media URL in an admin response (HTML or AJAX JSON)
 * into an opaque, expiring /secure-media token URL, so the real file path never
 * appears in the page, DOM, view-source, or network response.
 */
class ProtectAdminMediaUrls
{
    // Generous TTLs so a token never expires mid-view (access is already gated by the
    // admin session on the /secure-media route; the token mainly hides the real path).
    private const SHORT_TTL_SECONDS = 86400; // images (24 h)
    private const LONG_TTL_SECONDS  = 86400; // click-to-open / streamed types (24 h)

    private const LONG_LIVED_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'mkv', 'webm'];

    // Only protect inline IMAGES and VIDEOS. Downloadable types (zip) and fonts are left
    // alone so they keep their real filename/behaviour (tokenizing a zip would rename the
    // download to "secure-media.zip").
    private const URL_PATTERN = '~(?:https?://[^/"\'\s()<>]+)?/upload/[^"\'()<>]+?\.(?:webp|png|jpe?g|gif|svg|bmp|ico|mp4|mov|avi|wmv|mkv|webm)~i';

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // AJAX JSON responses (e.g. table reloads) — Content-Type is set immediately.
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $content = $response->getContent();
            // Raw JSON escapes slashes (\/upload\/), so match loosely on "upload".
            if (is_string($content) && $content !== '' && stripos($content, 'upload') !== false) {
                $data = $response->getData(true);
                if (is_array($data)) {
                    array_walk_recursive($data, function (&$val) {
                        if (is_string($val) && stripos($val, '/upload/') !== false) {
                            $val = $this->rewrite($val);
                        }
                    });
                    $response->setData($data);
                }
            }
            return $response;
        }

        // HTML view responses. NOTE: Laravel sets the Content-Type header during
        // Response::prepare(), which runs AFTER middleware — so here it is usually
        // empty for a view. Treat empty or text/html content as HTML. Binary/file
        // responses return non-string content and are skipped.
        if (method_exists($response, 'getContent') && method_exists($response, 'setContent')) {
            $content = $response->getContent();
            if (is_string($content) && $content !== '' && stripos($content, '/upload/') !== false) {
                $ct = (string) $response->headers->get('Content-Type', '');
                if ($ct === '' || str_contains($ct, 'text/html')) {
                    $response->setContent($this->rewrite($content));
                }
            }
        }

        return $response;
    }

    private function rewrite(string $content): string
    {
        $result = preg_replace_callback(self::URL_PATTERN, function ($m) {
            $url = $m[0];

            $pos = stripos($url, '/upload/');
            if ($pos === false) {
                return $url;
            }

            // public-relative path. Decode HTML entities first (Blade escapes '&' to
            // '&amp;' in src/href), then URL-encoding (%20 etc.) → real characters.
            $relative = rawurldecode(html_entity_decode(substr($url, $pos + 1), ENT_QUOTES | ENT_HTML5));

            if (!is_file(public_path($relative))) {
                return $url; // not a real file — leave it untouched
            }

            $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
            $ttl = in_array($ext, self::LONG_LIVED_EXTENSIONS, true)
                ? self::LONG_TTL_SECONDS
                : self::SHORT_TTL_SECONDS;

            $token = SecureMediaHelper::encrypt($relative, $ttl);

            return '/secure-media?token=' . rawurlencode($token);
        }, $content);

        return $result ?? $content;
    }
}
