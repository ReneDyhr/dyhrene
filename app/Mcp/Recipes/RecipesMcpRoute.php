<?php

declare(strict_types=1);

namespace App\Mcp\Recipes;

final class RecipesMcpRoute
{
    public const PATH = 'mcp/recipes';

    public static function endpointUrl(): string
    {
        return \url('/' . self::PATH);
    }
}
