<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error | Wolffiles.eu</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center px-4">
        <h1 class="text-9xl font-bold text-red-500/30">500</h1>
        <h2 class="text-3xl font-bold text-white mt-4">Server Error</h2>
        <p class="text-gray-400 mt-4 max-w-md mx-auto">
            Something went wrong on our end. The server took a critical hit. We're working on fixing it.
        </p>
        <div class="mt-8">
            <a href="{{ url('/') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
