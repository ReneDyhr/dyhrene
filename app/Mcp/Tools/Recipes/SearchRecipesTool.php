<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Models\Recipe;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'recipe_search')]
#[Description(value: 'Search recipes using weighted matching across name, description, note, ingredients, tags, and categories.')]
#[IsReadOnly(value: true)]
class SearchRecipesTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{query: string, category_ids?: list<int>, tags?: list<string>, limit?: int, offset?: int} $validated */
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:200',
            'category_ids' => 'sometimes|array|max:50',
            'category_ids.*' => 'integer|min:1',
            'tags' => 'sometimes|array|max:50',
            'tags.*' => 'string|min:1|max:100',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0|max:10000',
        ]);

        $queryText = \trim($validated['query']);
        $tokens = $this->tokenize($queryText);

        if ($tokens === []) {
            return Response::structured([
                'recipes' => [],
                'meta' => [
                    'query' => $queryText,
                    'tokens' => [],
                    'total' => 0,
                    'limit' => $validated['limit'] ?? 25,
                    'offset' => $validated['offset'] ?? 0,
                ],
            ]);
        }

        $categoryIds = \array_values(\array_unique($validated['category_ids'] ?? []));
        $tags = RecipeToolSupport::sanitizeCollection($validated['tags'] ?? []);
        $limit = $validated['limit'] ?? 25;
        $offset = $validated['offset'] ?? 0;

        if (!RecipeToolSupport::userOwnsAllCategories($userId, $categoryIds)) {
            return Response::error('One or more category_ids do not belong to the authenticated user.');
        }

        $baseQuery = Recipe::forAuthUser()
            ->with(['categories:id,name,slug', 'ingredients:id,recipe_id,name', 'tags:id,recipe_id,name'])
            ->when($categoryIds !== [], function (Builder $builder) use ($categoryIds): void {
                $builder->whereHas('categories', static function (Builder $categoryQuery) use ($categoryIds): void {
                    $categoryQuery->whereIn('categories.id', $categoryIds);
                });
            })
            ->when($tags !== [], function (Builder $builder) use ($tags): void {
                $builder->whereHas('tags', static function (Builder $tagQuery) use ($tags): void {
                    foreach ($tags as $index => $tag) {
                        if ($index === 0) {
                            $tagQuery->whereRaw('LOWER(recipe_tags.name) = ?', [\mb_strtolower($tag)]);
                        } else {
                            $tagQuery->orWhereRaw('LOWER(recipe_tags.name) = ?', [\mb_strtolower($tag)]);
                        }
                    }
                });
            })
            ->where(function (Builder $builder) use ($queryText, $tokens): void {
                $builder->where('name', 'LIKE', '%' . $queryText . '%')
                    ->orWhere('description', 'LIKE', '%' . $queryText . '%')
                    ->orWhere('note', 'LIKE', '%' . $queryText . '%')
                    ->orWhereHas('ingredients', static function (Builder $ingredientQuery) use ($tokens): void {
                        foreach ($tokens as $token) {
                            $ingredientQuery->orWhere('name', 'LIKE', '%' . $token . '%');
                        }
                    })
                    ->orWhereHas('tags', static function (Builder $tagQuery) use ($tokens): void {
                        foreach ($tokens as $token) {
                            $tagQuery->orWhere('name', 'LIKE', '%' . $token . '%');
                        }
                    })
                    ->orWhereHas('categories', static function (Builder $categoryQuery) use ($tokens): void {
                        foreach ($tokens as $token) {
                            $categoryQuery->orWhere('name', 'LIKE', '%' . $token . '%');
                        }
                    });
            });

        $candidates = $baseQuery->limit(300)->get();

        $scored = $candidates->map(function (Recipe $recipe) use ($queryText, $tokens): array {
            $score = $this->scoreRecipe($recipe, $queryText, $tokens);

            return [
                'score' => $score,
                'recipe' => $recipe,
            ];
        })->filter(static fn(array $row): bool => $row['score'] > 0)
            ->sort(function (array $left, array $right): int {
                if ($left['score'] !== $right['score']) {
                    return $right['score'] <=> $left['score'];
                }

                /** @var Recipe $leftRecipe */
                $leftRecipe = $left['recipe'];
                /** @var Recipe $rightRecipe */
                $rightRecipe = $right['recipe'];

                return $rightRecipe->id <=> $leftRecipe->id;
            })
            ->values();

        $total = $scored->count();
        $slice = $scored->slice($offset, $limit)->values();

        return Response::structured([
            'recipes' => $slice->map(static function (array $row): array {
                /** @var Recipe $recipe */
                $recipe = $row['recipe'];

                return [
                    ...RecipeToolSupport::summarizeRecipe($recipe),
                    'score' => $row['score'],
                ];
            })->all(),
            'meta' => [
                'query' => $queryText,
                'tokens' => $tokens,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $query): array
    {
        $query = \mb_strtolower($query);
        $query = (string) \preg_replace('/[^\\pL\\pN\\s]+/u', ' ', $query);
        /** @var array<int, string> $parts */
        $parts = \preg_split('/\\s+/u', $query, -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = \array_values(\array_unique($parts));

        return \array_values(\array_filter($parts, static fn(string $token): bool => \mb_strlen($token) >= 2));
    }

    /**
     * @param  list<string> $tokens
     */
    private function scoreRecipe(Recipe $recipe, string $query, array $tokens): int
    {
        $score = 0;
        $queryLc = \mb_strtolower($query);
        $name = \mb_strtolower($recipe->name);
        $description = \mb_strtolower($recipe->description);
        $note = \mb_strtolower($recipe->note);
        $ingredients = \mb_strtolower($recipe->ingredients->pluck('name')->implode(' '));
        $tags = \mb_strtolower($recipe->tags->pluck('name')->implode(' '));
        $categories = \mb_strtolower($recipe->categories->pluck('name')->implode(' '));

        if ($name === $queryLc) {
            $score += 120;
        } elseif (\str_starts_with($name, $queryLc)) {
            $score += 80;
        } elseif (\str_contains($name, $queryLc)) {
            $score += 60;
        }

        if (\str_contains($description, $queryLc)) {
            $score += 30;
        }

        if (\str_contains($note, $queryLc)) {
            $score += 20;
        }

        foreach ($tokens as $token) {
            if (\str_contains($name, $token)) {
                $score += 24;
            }
            if (\str_contains($description, $token)) {
                $score += 12;
            }
            if (\str_contains($note, $token)) {
                $score += 8;
            }
            if (\str_contains($ingredients, $token)) {
                $score += 14;
            }
            if (\str_contains($tags, $token)) {
                $score += 14;
            }
            if (\str_contains($categories, $token)) {
                $score += 10;
            }
        }

        return $score;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required()->description('Search phrase.'),
            'category_ids' => $schema->array()->items($schema->integer())->description('Optional category filter applied before scoring.'),
            'tags' => $schema->array()->items($schema->string())->description('Optional tag filter applied before scoring.'),
            'limit' => $schema->integer()->description('Optional page size (default 25, max 100).'),
            'offset' => $schema->integer()->description('Optional page offset (default 0).'),
        ];
    }
}
