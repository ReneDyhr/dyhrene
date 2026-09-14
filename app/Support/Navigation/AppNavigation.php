<?php

declare(strict_types=1);

namespace App\Support\Navigation;

use App\Enums\AppArea;
use Illuminate\Support\Facades\Route;

final class AppNavigation
{
    /**
     * @return list<array{area: AppArea, href: string, icon: string, isCurrent: bool, label: string}>
     */
    public function items(?AppArea $currentArea = null): array
    {
        $currentArea ??= $this->areaForRoute(Route::currentRouteName());

        return \array_map(
            fn(AppArea $area): array => [
                'area' => $area,
                'href' => \route($this->entryRouteFor($area)),
                'icon' => $area->icon(),
                'isCurrent' => $area === $currentArea,
                'label' => $area->label(),
            ],
            AppArea::cases(),
        );
    }

    public function homeHref(): string
    {
        return \route($this->entryRouteFor(AppArea::Overview));
    }

    public function areaForRoute(?string $routeName): AppArea
    {
        if ($routeName === null) {
            return AppArea::Overview;
        }

        return match (true) {
            $this->matches($routeName, ['add', 'category', 'edit', 'search', 'settings.categories', 'single', 'storage', 'tag'], ['shopping.']) => AppArea::Kitchen,
            $this->matches($routeName, [], ['nature.', 'observations.', 'species.', 'wild-edibles.']) => AppArea::Nature,
            $this->matches($routeName, [], ['print-customers.', 'print-jobs.', 'print-material-types.', 'print-materials.', 'print-settings.', 'printing.']) => AppArea::Workshop,
            $this->matches($routeName, [], ['inventory.', 'receipts.']) => AppArea::Household,
            $this->matches($routeName, ['settings.mcp'], ['mail.']) => AppArea::Family,
            default => AppArea::Overview,
        };
    }

    private function entryRouteFor(AppArea $area): string
    {
        return match ($area) {
            AppArea::Overview => 'index',
            AppArea::Kitchen => 'shopping.list',
            AppArea::Nature => 'nature.dashboard',
            AppArea::Workshop => 'printing.index',
            AppArea::Household => 'receipts.index',
            AppArea::Family => 'mail.inbox',
        };
    }

    /**
     * @param list<string> $routeNames
     * @param list<string> $routeNamespaces
     */
    private function matches(string $routeName, array $routeNames, array $routeNamespaces): bool
    {
        if (\in_array($routeName, $routeNames, true)) {
            return true;
        }

        foreach ($routeNamespaces as $routeNamespace) {
            if (\str_ends_with($routeNamespace, '.') && \str_starts_with($routeName, $routeNamespace)) {
                return true;
            }
        }

        return false;
    }
}
