<?php

declare(strict_types=1);

use App\Http\Controllers\ReceiptController;
use App\Livewire\AddRecipe;
use App\Livewire\Category\Categories;
use App\Livewire\EditRecipe;
use App\Livewire\Login;
use App\Livewire\Recipes;
use App\Livewire\SearchRecipe;
use App\Livewire\Shopping\ShoppingList;
use App\Livewire\SingleRecipe;
use App\Livewire\Storage;
use App\Livewire\Tag\Tags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/login', Login::class)->name('login');
Route::get('logout', function (): RedirectResponse {
    \auth()->logout();

    return \redirect()->route('login');
})->name('logout');
Route::get('/', Recipes::class)->middleware('auth')->name('index');
Route::get('/recipe/add', AddRecipe::class)->middleware('auth')->name('add');
Route::get('recipe/search', SearchRecipe::class)->middleware('auth')->name('search');

Route::get('/recipe/{id}', SingleRecipe::class)->middleware('auth')->name('single');
Route::get('/recipe/{id}/edit', EditRecipe::class)->middleware('auth')->name('edit');

Route::get('category/{slug}', Categories::class)->middleware('auth')->name('category');

Route::get('tag/{tag}', Tags::class)->middleware('auth')->name('tag');

Route::get('shopping/list', ShoppingList::class)->middleware('auth')->name('shopping.list');

Route::get('settings/categories', App\Livewire\Settings\Categories::class)->middleware('auth')->name('settings.categories');

// Route::get('debug', function () {
//     \broadcast(new App\Events\ShoppingList(App\Models\User::find(1), 'Test', ['Test']));
// })->middleware('auth')->name('profile');

Route::get('/storage', Storage::class)->name('storage')->middleware('auth');

Route::get('receipts', App\Livewire\Receipts\Index::class)->middleware('auth')->name('receipts.index');
Route::get('receipts/create', App\Livewire\Receipts\Create::class)->middleware('auth')->name('receipts.create');
Route::get('receipts/{receipt}', App\Livewire\Receipts\Show::class)->middleware('auth')->name('receipts.show');
Route::get('receipts/{receipt}/edit', App\Livewire\Receipts\Edit::class)->middleware('auth')->name('receipts.edit');
// Route::resource('receipts', ReceiptController::class);

Route::get('/receipts/image/{receipt}', function (App\Models\Receipt $receipt): Illuminate\Http\Response {
    if (empty($receipt->file_path) || !\Storage::disk('wasabi')->exists($receipt->file_path)) {
        \abort(404);
    }
    $mime = \Storage::disk('wasabi')->mimeType($receipt->file_path) ?: 'application/octet-stream';
    $content = \Storage::disk('wasabi')->get($receipt->file_path);

    return \response($content, 200)->header('Content-Type', $mime);
})->name('receipts.image')->middleware(['auth']);
