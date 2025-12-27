<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fix Laravel 12 issue: Ensure console commands get Laravel instance when resolved from container
        $this->app->resolving(\Illuminate\Console\Command::class, function (\Illuminate\Console\Command $command): void {
            // getLaravel() can return null, but PHPStan sees it as always non-null due to type inference
            // This is a defensive check that may be optimized away but is safe to keep
            $laravel = $command->getLaravel();
            // @phpstan-ignore-next-line
            if ($laravel === null) {
                $command->setLaravel($this->app);
            }
        });

        UrlGenerator::macro(
            'alternateHasCorrectSignature',
            function (Request $request, bool $absolute = true, array $ignoreQuery = []): bool {
                $ignoreQuery[] = 'signature';

                $absoluteUrl = \url($request->path());
                $url = $absolute ? $absoluteUrl : '/' . $request->path();

                $queryString = \collect(\explode('&', $request
                    ->server->getString('QUERY_STRING')))
                    ->reject(fn(string $parameter) => \in_array(\Str::before($parameter, '='), $ignoreQuery))
                    ->join('&');

                $original = \rtrim($url . '?' . $queryString, '?');
                $appKeyRaw = \config('app.key', '');
                $appKey = \is_string($appKeyRaw) ? $appKeyRaw : '';
                $signature = \hash_hmac('sha256', $original, $appKey);

                return \hash_equals($signature, (string) $request->string('signature', ''));
            },
        );
        UrlGenerator::macro('alternateHasValidSignature', function (Request $request, bool $absolute = true, array $ignoreQuery = []): bool {
            return URL::alternateHasCorrectSignature($request, $absolute, $ignoreQuery)
                && URL::signatureHasNotExpired($request);
        });
        Request::macro('hasValidSignature', function (bool $absolute = true, array $ignoreQuery = []): mixed {
            return URL::alternateHasValidSignature($this, $absolute, $ignoreQuery);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') === true) {
            URL::forceScheme('https');
        }
    }
}
