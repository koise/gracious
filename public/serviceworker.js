var staticCacheName = "pwa-v" + new Date().getTime();
var filesToCache = [
    '/offline',
    '/css/app.css',
    '/js/app.js'
    // Removed problematic icon files
];

// Cache on install
self.addEventListener("install", event => {
    this.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName)
            .then(cache => {
                // Use a more robust caching strategy that doesn't fail if a single file is missing
                return Promise.allSettled(
                    filesToCache.map(url => 
                        cache.add(url).catch(error => {
                            console.error('Failed to cache:', url, error);
                            return null; // Continue caching other files
                        })
                    )
                );
            })
    );
});

// Clear cache on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => (cacheName.startsWith("pwa-")))
                    .filter(cacheName => (cacheName !== staticCacheName))
                    .map(cacheName => caches.delete(cacheName))
            );
        })
    );
});

// Serve from Cache
self.addEventListener("fetch", event => {
    // Skip caching for QR images
    if (event.request.url.includes('qr_images')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                return response || fetch(event.request)
                    .catch(() => {
                        return caches.match('offline');
                    });
            })
            .catch(() => {
                return caches.match('offline');
            })
    );
});