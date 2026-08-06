<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Set up your business</h1>
        <p class="mt-1 text-ink-muted">Tell Loop about your brand so you can add shops and campaigns.</p>
    </x-slot>

    <form method="POST" action="{{ route('business.store') }}" class="loop-panel max-w-xl space-y-4 p-6 animate-fade-up">
        @csrf
        <div>
            <label class="loop-label" for="name">Business name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="loop-input" required>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <label class="loop-label" for="category">Category</label>
            <input id="category" name="category" value="{{ old('category') }}" class="loop-input" placeholder="Cafe, retail, salon…">
        </div>
        <div>
            <label class="loop-label" for="description">Description</label>
            <textarea id="description" name="description" rows="4" class="loop-input">{{ old('description') }}</textarea>
        </div>
        <button class="loop-btn">Create business</button>
    </form>
</x-app-layout>
