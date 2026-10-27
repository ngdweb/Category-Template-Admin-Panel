/**
 * Admin media protection engine.
 *  - Converts protected (/secure-media) images into <canvas> so there is no <img>
 *    element to drag, open, or "Save image as".
 *  - Hardens <video> elements: no native download button, no picture-in-picture,
 *    no remote playback, right-click and drag blocked.
 *  - Re-applies to content added later by AJAX (MutationObserver).
 *
 * Note: this raises the bar against casual inspection/scraping. It cannot stop a
 * logged-in admin using DevTools or a screen recorder — no web tech can.
 */
(function () {
    'use strict';

    function isSecure(src) {
        return typeof src === 'string' && src.indexOf('/secure-media') !== -1;
    }

    // ---- Images -> canvas ------------------------------------------------
    function canvasify(img) {
        if (!img || img.dataset.secured === '1') return;
        var src = img.getAttribute('src') || '';
        if (!isSecure(src)) return;
        img.dataset.secured = '1';

        var loader = new Image();
        // Same-origin request (cookies sent automatically). Do NOT set crossOrigin —
        // it would force a CORS check the same-origin endpoint doesn't answer, and
        // same-origin images never taint the canvas anyway.
        loader.onload = function () {
            try {
                var canvas = document.createElement('canvas');
                canvas.width = loader.naturalWidth || img.naturalWidth || 1;
                canvas.height = loader.naturalHeight || img.naturalHeight || 1;
                canvas.className = img.className;
                if (img.getAttribute('style')) canvas.setAttribute('style', img.getAttribute('style'));
                if (img.getAttribute('width')) canvas.style.width = img.getAttribute('width') + 'px';
                if (img.getAttribute('height')) canvas.style.height = img.getAttribute('height') + 'px';
                var alt = img.getAttribute('alt');
                if (alt) canvas.setAttribute('aria-label', alt);
                canvas.setAttribute('draggable', 'false');
                canvas.getContext('2d').drawImage(loader, 0, 0);
                if (img.parentNode) img.parentNode.replaceChild(canvas, img);
            } catch (e) {
                /* tainted canvas or other issue: leave the <img> as-is */
            }
        };
        loader.onerror = function () { /* leave the <img> as-is */ };
        loader.src = src;
    }

    // ---- Videos: harden --------------------------------------------------
    function hardenVideo(video) {
        if (!video || video.dataset.secured === '1') return;
        video.dataset.secured = '1';
        try {
            video.setAttribute('controlsList', 'nodownload noplaybackrate noremoteplayback');
            video.disablePictureInPicture = true;
            video.setAttribute('disablePictureInPicture', '');
            video.setAttribute('disableRemotePlayback', '');
            video.setAttribute('draggable', 'false');
            video.addEventListener('contextmenu', blockEvent);
            video.addEventListener('dragstart', blockEvent);
        } catch (e) { /* ignore */ }
    }

    function blockEvent(e) { e.preventDefault(); return false; }

    // ---- Sweep -----------------------------------------------------------
    function sweep(root) {
        root = root || document;
        var imgs = root.querySelectorAll ? root.querySelectorAll('img[src*="/secure-media"]') : [];
        for (var i = 0; i < imgs.length; i++) canvasify(imgs[i]);
        var vids = root.querySelectorAll ? root.querySelectorAll('video') : [];
        for (var j = 0; j < vids.length; j++) hardenVideo(vids[j]);
    }

    // Block right-click / drag on protected media globally (cosmetic hardening).
    document.addEventListener('contextmenu', function (e) {
        var t = e.target;
        if (t && (t.tagName === 'CANVAS' || t.tagName === 'VIDEO' ||
            (t.tagName === 'IMG' && isSecure(t.getAttribute('src'))))) {
            e.preventDefault();
        }
    });
    document.addEventListener('dragstart', function (e) {
        var t = e.target;
        if (t && (t.tagName === 'CANVAS' || t.tagName === 'VIDEO' ||
            (t.tagName === 'IMG' && isSecure(t.getAttribute('src'))))) {
            e.preventDefault();
        }
    });

    function start() {
        sweep(document);
        var mo = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    var node = added[j];
                    if (node.nodeType !== 1) continue;
                    if (node.tagName === 'IMG' && isSecure(node.getAttribute('src'))) canvasify(node);
                    else if (node.tagName === 'VIDEO') hardenVideo(node);
                    else sweep(node);
                }
            }
        });
        mo.observe(document.documentElement, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
