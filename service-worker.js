self.addEventListener('push', function(event) {
    const options = {
        body: event.data.text(),
        icon: 'logo (1) text.png',
        badge: 'logo (1) text.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        }
    };

    event.waitUntil(
        self.registration.showNotification('New Message - LokPixPC', options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/lokpixpc/inbox.php')
    );
});
