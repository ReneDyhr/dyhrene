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
Route::get('receipts/mass-edit-items', App\Livewire\Receipts\MassEditItems::class)->middleware('auth')->name('receipts.mass-edit-items');
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

// 3D Printing Dashboard
Route::get('/printing', App\Livewire\Printing\Index::class)->middleware('auth')->name('printing.index');

// Print Customers CRUD
Route::get('/print-customers', App\Livewire\PrintCustomers\Index::class)->middleware('auth')->name('print-customers.index');
Route::get('/print-customers/create', App\Livewire\PrintCustomers\Create::class)->middleware('auth')->name('print-customers.create');
Route::post('/print-customers', [App\Livewire\PrintCustomers\Create::class, 'save'])->middleware('auth')->name('print-customers.store');
Route::get('/print-customers/{customer}/edit', App\Livewire\PrintCustomers\Edit::class)->middleware('auth')->name('print-customers.edit');
Route::put('/print-customers/{customer}', [App\Livewire\PrintCustomers\Edit::class, 'save'])->middleware('auth')->name('print-customers.update');
Route::delete('/print-customers/{customer}', [App\Livewire\PrintCustomers\Index::class, 'delete'])->middleware('auth')->name('print-customers.destroy');

// Print Material Types CRUD
Route::get('/print-material-types', App\Livewire\PrintMaterialTypes\Index::class)->middleware('auth')->name('print-material-types.index');
Route::get('/print-material-types/create', App\Livewire\PrintMaterialTypes\Create::class)->middleware('auth')->name('print-material-types.create');
Route::post('/print-material-types', [App\Livewire\PrintMaterialTypes\Create::class, 'save'])->middleware('auth')->name('print-material-types.store');
Route::get('/print-material-types/{materialType}/edit', App\Livewire\PrintMaterialTypes\Edit::class)->middleware('auth')->name('print-material-types.edit');
Route::put('/print-material-types/{materialType}', [App\Livewire\PrintMaterialTypes\Edit::class, 'save'])->middleware('auth')->name('print-material-types.update');
Route::delete('/print-material-types/{materialType}', [App\Livewire\PrintMaterialTypes\Index::class, 'delete'])->middleware('auth')->name('print-material-types.destroy');

// Print Materials CRUD
Route::get('/print-materials', App\Livewire\PrintMaterials\Index::class)->middleware('auth')->name('print-materials.index');
Route::get('/print-materials/create', App\Livewire\PrintMaterials\Create::class)->middleware('auth')->name('print-materials.create');
Route::post('/print-materials', [App\Livewire\PrintMaterials\Create::class, 'save'])->middleware('auth')->name('print-materials.store');
Route::get('/print-materials/{material}/edit', App\Livewire\PrintMaterials\Edit::class)->middleware('auth')->name('print-materials.edit');
Route::put('/print-materials/{material}', [App\Livewire\PrintMaterials\Edit::class, 'save'])->middleware('auth')->name('print-materials.update');
Route::delete('/print-materials/{material}', [App\Livewire\PrintMaterials\Index::class, 'delete'])->middleware('auth')->name('print-materials.destroy');

// Print Settings
Route::get('/print-settings', App\Livewire\PrintSettings\Edit::class)->middleware('auth')->name('print-settings.edit');
Route::put('/print-settings', [App\Livewire\PrintSettings\Edit::class, 'save'])->middleware('auth')->name('print-settings.update');

// Print Jobs CRUD
Route::get('/print-jobs', App\Livewire\PrintJobs\Index::class)->middleware('auth')->name('print-jobs.index');
Route::get('/print-jobs/create', App\Livewire\PrintJobs\Create::class)->middleware('auth')->name('print-jobs.create');
Route::post('/print-jobs', [App\Livewire\PrintJobs\Create::class, 'save'])->middleware('auth')->name('print-jobs.store');
Route::get('/print-jobs/{printJob}', App\Livewire\PrintJobs\Show::class)->middleware('auth')->name('print-jobs.show');
Route::get('/print-jobs/{printJob}/edit', App\Livewire\PrintJobs\Edit::class)->middleware('auth')->name('print-jobs.edit');
Route::put('/print-jobs/{printJob}', [App\Livewire\PrintJobs\Edit::class, 'save'])->middleware('auth')->name('print-jobs.update');
Route::delete('/print-jobs/{printJob}', [App\Livewire\PrintJobs\Index::class, 'delete'])->middleware('auth')->name('print-jobs.destroy');
