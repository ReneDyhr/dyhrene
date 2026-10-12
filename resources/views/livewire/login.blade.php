@section('title', $title)
<div class="login-page">
    <div class="login-box">
        <div class="login-box-body">
            <p class="login-box-msg">Log ind for at fortsætte</p>
            <form wire:submit="login">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" wire:model="email" class="form-control" autocomplete="username" placeholder="E-mail">
                </div>
                <div class="form-group">
                    <label for="password">Adgangskode</label>
                    <input id="password" type="password" wire:model="password" class="form-control" autocomplete="current-password" placeholder="Adgangskode">
                </div>
                <div class="form-group">
                    <label for="remember">
                        <input id="remember" type="checkbox" wire:model="remember"> Husk mig
                    </label>
                </div>
                @error('email')
                    <span class="alert-danger" role="alert">{{ $message }}</span>
                @enderror
                <div class="col-8">
                    <a href="/create">Opret konto</a><br>
                </div>
                <div class="col-8">
                    <a href="/forgot">Glemt adgangskode?</a><br>
                </div>
                <div class="col-4">
                    <button type="submit" class="full-width btn btn-primary btn-block btn-flat" style="margin-top:-16px;">Log ind</button>
                </div>
                <div class="clear"></div>
            </form>
        </div>
    </div>
</div>
