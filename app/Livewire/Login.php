<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate(attribute: 'required|email')]
    public string $email = '';

    #[Validate(rule: 'required')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): ?RedirectResponse
    {
        if (\auth()->check()) {
            return \redirect()->intended(\route('index'));
        }

        return null;
    }

    public function render(): View
    {
        return \view('livewire.login', ['title' => 'Login', 'bodyClass' => 'login-page']);
    }

    public function login(): ?RedirectResponse
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        if (\auth()->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            return \redirect()->intended(\route('index'));
        }

        $this->addError('email', 'These credentials do not match our records.');

        return null;
    }
}
