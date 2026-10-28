/*
 * MS Football Academy - service worker for the home-screen app.
 * It caches NOTHING: every page and every save goes to the server as normal, so staff always see
 * live data and attendance is never stored on the phone. Its only job is to make the site
 * installable and to show a friendly "No internet" page instead of the browser's error screen.
 */
self.addEventListener('install', function() {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});

var OFFLINE_PAGE = '<!doctype html><html lang="en"><head><meta charset="utf-8">' +
    '<meta name="viewport" content="width=device-width, initial-scale=1">' +
    '<title>No internet - MS Academy</title><style>' +
    'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#07142b;' +
    'font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#e6edf7;text-align:center;padding:24px;box-sizing:border-box}' +
    '.card{max-width:340px}h1{font-size:20px;margin:18px 0 8px;color:#fff}p{color:#9fb0c8;font-size:15px;line-height:1.5;margin:0 0 22px}' +
    'img{width:84px;height:84px;border-radius:50%;background:#fff;padding:10px;box-sizing:border-box;object-fit:contain}' +
    'button{background:#f5b301;color:#07142b;border:0;border-radius:10px;padding:12px 26px;font-size:16px;font-weight:600}' +
    '</style></head><body><div class="card">' +
    '<img src="/images/ms-logo.png" alt="" onerror="this.remove()">' +
    '<h1>No internet connection</h1>' +
    '<p>The academy app needs the internet to load and save. Check Wi-Fi or mobile data, then try again.</p>' +
    '<button onclick="location.reload()">Try again</button>' +
    '</div></body></html>';

self.addEventListener('fetch', function(event) {
    // only full page loads; everything else goes straight to the network as usual
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(function() {
            return new Response(OFFLINE_PAGE, {
                status: 503,
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            });
        })
    );
});
