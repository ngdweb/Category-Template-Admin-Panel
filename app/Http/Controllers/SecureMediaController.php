<?php

namespace App\Http\Controllers;

use App\Support\SecureMediaHelper;
use Illuminate\Http\Request;

class SecureMediaController extends Controller
{
    private const MIME_MAP = [
        'webp' => 'image/webp',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/x-icon',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
        'avi'  => 'video/x-msvideo',
        'wmv'  => 'video/x-ms-wmv',
        'mkv'  => 'video/x-matroska',
        'webm' => 'video/webm',
        'zip'  => 'application/zip',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'  => 'font/ttf',
        'otf'  => 'font/otf',
    ];

    public function stream(Request $request)
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            abort(403);
        }

        $relative = SecureMediaHelper::decrypt($token);
        if ($relative === null) {
            abort(403); // expired or tampered
        }

        // Normalise and block path traversal / anything outside upload/.
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if (!str_starts_with($relative, 'upload/') || str_contains($relative, '..')) {
            abort(403);
        }

        $real = realpath(public_path($relative));
        $base = realpath(public_path('upload'));
        if ($real === false || $base === false || !str_starts_with($real, $base)) {
            abort(404);
        }

        $ext  = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        $mime = self::MIME_MAP[$ext] ?? 'application/octet-stream';

        return response()->file($real, [
            'Content-Type'        => $mime,
            // Cache in THIS admin's private browser cache only (not shared/CDN). This keeps
            // display stable (no re-fetch storms on repaint) while the token URL still
            // expires and the real path stays hidden.
            'Cache-Control'       => 'private, max-age=600',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
