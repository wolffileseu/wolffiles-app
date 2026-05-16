<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Wolffiles Embed')</title>
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors *;">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body {
            margin: 0; padding: 0;
            background: #0b0f17;
            color: #f3f4f6;
            height: 100%; width: 100%;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }
        body.embed-mode-player { background: #000; overflow: hidden; }
        body.embed-mode-card   { background: #0b0f17; overflow: auto; }

        /* PLAYER MODE */
        .embed-player-wrap {
            width: 100%; height: 100vh;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .embed-player-wrap .plyr {
            width: 100%; height: 100%;
            border-radius: 0 !important;
        }

        /* CARD MODE */
        .embed-card {
            width: 100%; min-height: 100vh;
            display: flex; flex-direction: column;
            background: linear-gradient(180deg, #111827 0%, #0b0f17 100%);
        }
        .embed-card-image {
            position: relative;
            width: 100%; aspect-ratio: 16 / 9;
            background: #1f2937 center/cover no-repeat;
            border-bottom: 1px solid rgba(245, 158, 11, 0.15);
            overflow: hidden;
        }
        .embed-card-image .placeholder {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: rgba(245, 158, 11, 0.5);
            font-size: clamp(48px, 10vw, 96px);
        }
        .embed-card-image .type-badge {
            position: absolute; top: 8px; left: 8px;
            background: rgba(0, 0, 0, 0.65);
            color: #f59e0b;
            padding: 4px 10px; border-radius: 4px;
            font-size: 11px; font-weight: 600;
            backdrop-filter: blur(8px);
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .embed-card-body {
            padding: 14px 16px;
            flex: 1;
            display: flex; flex-direction: column;
            gap: 8px;
        }
        .embed-card-title {
            font-size: 16px; font-weight: 600;
            color: #f9fafb; margin: 0;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .embed-card-meta {
            font-size: 12px; color: #9ca3af;
            display: flex; flex-wrap: wrap;
            gap: 4px 10px;
        }
        .embed-card-meta .sep { color: #4b5563; }
        .embed-card-stats {
            display: flex; flex-wrap: wrap;
            gap: 8px 14px;
            font-size: 12px; color: #d1d5db;
        }
        .embed-card-stats span { display: inline-flex; align-items: center; gap: 4px; }
        .embed-card-stats .star { color: #f59e0b; }
        .embed-card-actions {
            margin-top: auto;
            display: flex; justify-content: space-between;
            align-items: center; gap: 8px;
            padding-top: 8px;
        }
        .embed-card-cta {
            background: #f59e0b; color: #111827;
            padding: 8px 14px; border-radius: 4px;
            font-size: 13px; font-weight: 600;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: background 0.15s;
        }
        .embed-card-cta:hover { background: #fbbf24; }
        .embed-card-cta.secondary {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .embed-card-cta.secondary:hover { background: rgba(245, 158, 11, 0.25); }

        .embed-brand {
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            text-decoration: none;
            transition: color 0.2s;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .embed-brand:hover { color: #f59e0b; }

        .embed-mode-player .embed-brand {
            position: absolute; top: 8px; right: 12px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.5);
            padding: 4px 10px; border-radius: 4px;
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="embed-mode-@yield('mode', 'card')">
    @yield('content')
</body>
</html>
