<?php

declare(strict_types=1);

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Picking;
use App\Models\WildEdible;
use App\Models\WildEdiblePhoto;
use App\Policies\PickingPolicy;
use App\Policies\WildEdiblePhotoPolicy;
use App\Policies\WildEdiblePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        WildEdible::class => WildEdiblePolicy::class,
        WildEdiblePhoto::class => WildEdiblePhotoPolicy::class,
        Picking::class => PickingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {}
}
