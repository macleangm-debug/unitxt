<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <h1 class="font-display text-2xl font-semibold text-ink">Join Loop</h1>
            <p class="mt-1 text-sm text-ink-muted">Create an account as a business or a customer.</p>
        </div>

        <div>
            <label class="loop-label" for="name">Name</label>
            <input id="name" class="loop-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label class="loop-label" for="email">Email</label>
            <input id="email" class="loop-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label class="loop-label">I am joining as</label>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="cursor-pointer rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft">
                    <input type="radio" name="role" value="business" class="sr-only" @checked(old('role', 'business') === 'business')>
                    <span class="font-semibold text-ink">Business</span>
                    <span class="mt-1 block text-xs text-ink-muted">Run shops & campaigns</span>
                </label>
                <label class="cursor-pointer rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft">
                    <input type="radio" name="role" value="customer" class="sr-only" @checked(old('role') === 'customer')>
                    <span class="font-semibold text-ink">Customer</span>
                    <span class="mt-1 block text-xs text-ink-muted">Earn points on visits</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div>
            <label class="loop-label" for="password">Password</label>
            <input id="password" class="loop-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label class="loop-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" class="loop-input" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm text-ink-muted underline hover:text-ink" href="{{ route('login') }}">Already registered?</a>
            <button class="loop-btn">Create account</button>
        </div>
    </form>
</x-guest-layout>
