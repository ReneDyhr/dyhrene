@auth
    <div class="family-account" data-mobile-account>
        <span class="family-account-name">{{ \auth()->user()?->name }}</span>
        <a href="{{ \route('logout') }}" class="family-account-logout" aria-label="Log ud">Log ud</a>
    </div>
@endauth
