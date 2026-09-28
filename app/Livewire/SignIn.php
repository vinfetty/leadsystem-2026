<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\Demo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Sign in')]
class SignIn extends Component
{
    public const MAX_ATTEMPTS = 5;

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function signIn(): void
    {
        $this->validate();
        $this->ensureIsNotLockedOut();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            $this->reset('password');

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(route('leads.index'));
    }

    /**
     * In demo mode a visitor may step straight into one of the seeded accounts.
     */
    public function signInAs(string $account): void
    {
        abort_unless(Demo::enabled() && array_key_exists($account, Demo::ACCOUNTS), 404);

        $user = User::query()->where('email', Demo::ACCOUNTS[$account])->first();

        if ($user === null) {
            throw ValidationException::withMessages(['demo' => 'The demo accounts have not been created yet.']);
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('leads.index');
    }

    public function render(): View
    {
        return view('livewire.sign-in');
    }

    private function ensureIsNotLockedOut(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
