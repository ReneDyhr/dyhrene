<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Login extends Component
{
    #[Validate(attribute: 'required|email')]
    public string $email = '';

    #[Validate(rule: 'required')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): ?Redirector
    {
        if (\auth()->check()) {
            return $this->redirect(\route('index'));
        }

        return null;
    }

    public function render(): View
    {
        return \view('livewire.login', ['title' => 'Login', 'bodyClass' => 'login-page']);
    }

    public function login(): ?Redirector
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $intendedUrl = \session()->pull('url.intended', \route('index'));

            return $this->redirect($intendedUrl);
        }

        $this->addError('email', 'These credentials do not match our records.');

        return null;
    }
}
