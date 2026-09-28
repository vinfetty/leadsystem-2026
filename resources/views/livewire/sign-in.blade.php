<div>
    <div class="mb-6 flex items-center justify-center gap-2.5 text-lg font-semibold tracking-tight">
        <span class="grid size-8 place-items-center rounded-md bg-brand-700 text-xs text-white" aria-hidden="true">LS</span>
        {{ config('app.name') }}
    </div>

    <form wire:submit="signIn" class="space-y-4 rounded-xl border border-stone-200 bg-white p-6 shadow-xs">
        <h1 class="text-lg font-semibold tracking-tight">Sign in</h1>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                class="field" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                class="field" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <p id="password-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input wire:model="remember" type="checkbox" class="size-4 rounded border-stone-300 accent-brand-700">
            Keep me signed in
        </label>

        <button type="submit" class="button w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="signIn">Sign in</span>
            <span wire:loading wire:target="signIn">Signing in…</span>
        </button>
    </form>

    @env('local')
        <div class="mt-4 rounded-xl border border-dashed border-stone-300 px-5 py-4 text-sm text-stone-600">
            <p class="font-medium text-stone-900">Demo accounts</p>
            <p class="mt-0.5">Shown only while the app runs locally, after seeding.</p>

            <dl class="mt-3 space-y-1">
                <div class="flex justify-between gap-4">
                    <dt>Admin</dt>
                    <dd class="font-medium text-stone-900">admin@example.com</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Processor</dt>
                    <dd class="font-medium text-stone-900">processor@example.com</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Password for both</dt>
                    <dd class="font-medium text-stone-900">password</dd>
                </div>
            </dl>
        </div>
    @endenv
</div>
