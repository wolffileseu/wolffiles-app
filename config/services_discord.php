<?php
// Add to config/services.php - merge into existing file

return [
    // ... existing services ...

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    ],
];
