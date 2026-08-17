<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        @csrf

        <div>
            <h1 class="font-display text-2xl font-semibold text-ink">Welcome back</h1>
            <p class="mt-1 text-sm text-ink-muted">Log in to manage campaigns or check in for points.</p>
        </div>

        <div>
            <label class="loop-label" for="email">Email</label>
            <input id="email" class="loop-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="off" data-lpignore="true" data-1p-ignore="true" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label class="loop-label" for="password">Password</label>
            <input id="password" class="loop-input" type="password" name="password" required autocomplete="off" data-lpignore="true" data-1p-ignore="true" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-ink-muted">
                <input id="remember_me" type="checkbox" class="rounded border-ink/20 text-mint focus:ring-mint" name="remember">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm text-ink-muted underline hover:text-ink" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm text-ink-muted underline hover:text-ink" href="{{ route('register') }}">Create account</a>
            <button class="loop-btn">Log in</button>
        </div>
    </form>
</x-guest-layout>
