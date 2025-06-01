@section('title', $title)
<div class="login-page">
    <div class="login-box">
        <div class="login-box-body">
            <p class="login-box-msg">Log in to get started!</p>
            <form wire:submit="login">
                <div class="form-group">
                    <input type="email" wire:model="email" class="form-control" tabindex="1" placeholder="Email">
                </div>
                <div class="form-group">
                    <input type="password" wire:model="password" class="form-control" tabindex="2" placeholder="Password">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" wire:model="remember" tabindex="3"> Remember Me
                    </label>
                </div>
                @error('email')
                    <span class="alert-danger">{{ $message }}</span>
                @enderror
                <div class="col-8">
                    <a href="/create" tabindex="4">Create Account</a><br>
                </div>
                <div class="col-8">
                    <a href="/forgot" tabindex="5">Forgot your password?</a><br>
                </div>
                <div class="col-4">
                    <button type="submit" tabindex="3" class="full-width btn btn-primary btn-block btn-flat" style="margin-top:-16px;">Login</button>
                </div>
                <div class="clear"></div>
            </form>
        </div>
    </div>
</div>