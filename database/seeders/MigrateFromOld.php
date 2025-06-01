<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateFromOld extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $icons = DB::connection('mysql_old')->table('icons')->get();
        foreach ($icons as $icon) {
            DB::table('icons')->insert([
                'id' => $icon->id,
                'name' => $icon->name,
                'class' => $icon->class,
                'created_at' => $icon->date,
                'updated_at' => $icon->date,
            ]);
        }
        $categories = DB::connection('mysql_old')->table('categories')->where('userId', 1)->get();
        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'id' => $category->id,
                'user_id' => $category->userId,
                'icon_id' => $category->iconId,
                'slug' => $category->slug,
                'name' => $category->name,
                'created_at' => $category->date,
                'updated_at' => $category->date,
            ]);
        }


        $recipes = DB::connection('mysql_old')->table('recipes')->where('userId', 1)->get();
        foreach ($recipes as $recipe) {
            $tags = DB::connection('mysql_old')->table('recipe_tags')->where('recipeId', $recipe->id)->get();
            $ingredients = DB::connection('mysql_old')->table('recipe_ingredient')->where('recipeId', $recipe->id)->get();
            $categories = DB::connection('mysql_old')->table('recipe_category')->where('recipeId', $recipe->id)->get();

            $recipeId = DB::table('recipes')->insertGetId([
                'id' => $recipe->id,
                'user_id' => $recipe->userId,
                'name' => $recipe->name,
                'description' => $recipe->description,
                'note' => $recipe->note,
                'public' => $recipe->public,
                'created_at' => $recipe->date,
                'updated_at' => $recipe->date,
            ]);

            foreach ($categories as $category) {
                DB::table('category_recipe')->insert([
                    'recipe_id' => $recipeId,
                    'category_id' => $category->categoryId,
                ]);
            }

            foreach ($tags as $tag) {
                DB::table('recipe_tags')->insert([
                    'recipe_id' => $recipeId,
                    'name' => $tag->name,
                ]);
            }

            foreach ($ingredients as $ingredient) {
                DB::table('recipe_ingredients')->insert([
                    'recipe_id' => $recipeId,
                    'name' => $ingredient->name,
                ]);
            }
        }
    }
}
