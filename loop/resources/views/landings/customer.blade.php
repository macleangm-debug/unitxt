<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loop for customers — Your points, your shops</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<header class="loop-shell flex items-center justify-between py-6">
    <a href="{{ route('home') }}" class="flex items-center gap-3">
        <x-loop-logo class="h-10 w-10" />
        <span class="font-display text-2xl font-semibold">Loop</span>
    </a>
    <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">See campaigns</a>
</header>

<main class="loop-shell py-12">
    <div class="mx-auto max-w-lg text-center animate-fade-up">
        <x-loop-logo class="mx-auto h-14 w-14" />
        <h1 class="mt-6 font-display text-4xl font-semibold">Your loyalty lives on your phone.</h1>
        <p class="mt-4 text-ink-muted">Enter your number to see points across coffee shops, restaurants, fashion, and more. Rewards are applied when you buy — in store or on a phone order.</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('customer.login') }}" class="loop-btn-mint">Enter phone number</a>
            <a href="{{ route('discover') }}" class="loop-btn-ghost">Browse shops first</a>
        </div>
    </div>
</main>
</body>
</html>
