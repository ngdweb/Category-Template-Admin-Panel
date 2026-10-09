<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\NgendevImageController;
use App\Http\Controllers\NgendevCategoryController;
use App\Http\Controllers\AiImageBabyPhotoSettingController;
use App\Http\Controllers\AiImageNgdSettingController;
use App\Http\Controllers\AiImageCategoryController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\FilterAiImageController;
use App\Http\Controllers\FilterAiImageCategoryController;
use App\Http\Controllers\FilterAiImageSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FirebaseProjectController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    return redirect()->back()->with('success', 'Cache cleared successfully!');
})->name('clear.cache');

Route::get('/', [AdminController::class, 'index'])->name('login');
Route::post('/login', [AdminController::class, 'login'])->name('admin-auth');

Route::middleware(['admin_auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/select/{group}', [DashboardController::class, 'selectGroup'])->name('dashboard.selectGroup');
    Route::get('/dashboard/clear-group', [DashboardController::class, 'clearGroup'])->name('dashboard.clearGroup');
    Route::get('/dashboard/module/{module}', [DashboardController::class, 'module'])->name('dashboard.module');

    Route::get('/apiList', function () {
        return view('apiList');
    });

    Route::post('/logout', [AdminController::class, 'logout'])->name('auth.logout');

    // Maintenance Routes
    Route::post('/clear-cache', [SystemController::class, 'clearCache'])->name('system.clearCache');

    Route::prefix('subcategories')
        ->name('subcategories.')
        ->group(function () {
            Route::get('/', [SubcategoryController::class, 'index'])->name('index');
            Route::get('form/{id?}', [SubcategoryController::class, 'form'])->name('form');
            Route::post('save/{id?}', [SubcategoryController::class, 'save'])->name('save');
            Route::get('subcategories/{id}/add-details', [SubcategoryController::class, 'addDetailsForm'])->name('addDetailsForm');
            Route::post('subcategories/{id}/save-details', [SubcategoryController::class, 'saveDetails'])->name('saveDetails');
            Route::get('image/delete/{subcategoryId}/{file}', [SubcategoryController::class, 'deleteImage'])->name('deleteImage');
            Route::post('update-status', [SubcategoryController::class, 'updateStatus'])->name('updateStatus');
            Route::get('{id}', [SubcategoryController::class, 'show'])->name('show');
            Route::delete('{id}', [SubcategoryController::class, 'destroy'])->name('destroy');
        });

    // Ngendev Images Routes
    Route::prefix('ngendev')
        ->name('ngendev.')
        ->group(function () {
            Route::get('/images', [NgendevImageController::class, 'index'])->name('images.index');
            Route::post('/images', [NgendevImageController::class, 'store'])->name('images.store');
            Route::get('/images/create', [NgendevImageController::class, 'create'])->name('images.create');
            Route::get('/images/{id}/edit', [NgendevImageController::class, 'edit'])->name('images.edit');
            Route::put('/images/{id}', [NgendevImageController::class, 'update'])->name('images.update');
            Route::delete('/images/{id}', [NgendevImageController::class, 'destroy'])->name('images.destroy');
            Route::get('/images-indexing', [NgendevImageController::class, 'indexing'])->name('images.indexing');
            Route::post('/images-update-order', [NgendevImageController::class, 'updateOrder'])->name('images.updateOrder');
            Route::get('/images-name-change-stats', [NgendevImageController::class, 'categoryNameChangeStats'])->name('images.nameChangeStats');
            Route::post('/images-bulk-name-change', [NgendevImageController::class, 'bulkToggleNameChange'])->name('images.bulkNameChange');
            Route::post('/images-update-status', [NgendevImageController::class, 'updateStatus'])->name('images.updateStatus');
        });

    // Ngendev Categories Routes
    Route::prefix('ngendev')
        ->name('ngendev.')
        ->group(function () {
            Route::get('/categories', [NgendevCategoryController::class, 'index'])->name('categories.index');
            Route::get('/categories-indexing', [NgendevCategoryController::class, 'indexing'])->name('categories.indexing');
            Route::post('/categories-update-order', [NgendevCategoryController::class, 'updateOrder'])->name('categories.updateOrder');
            Route::get('/categories/create', [NgendevCategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [NgendevCategoryController::class, 'store'])->name('categories.store');
            Route::get('/categories/{id}/edit', [NgendevCategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/categories/{id}', [NgendevCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{id}', [NgendevCategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('/categories/update-status', [NgendevCategoryController::class, 'updateStatus'])->name('categories.updateStatus');
            Route::post('/categories/update-type', [NgendevCategoryController::class, 'updateType'])->name('categories.updateType');
            Route::post('/categories/update-couple-status', [NgendevCategoryController::class, 'updateCoupleStatus'])->name('categories.updateCoupleStatus');
        });

    // Filter AI Image Images Routes
    Route::prefix('filter-ai-image')
        ->name('filter-ai-image.')
        ->group(function () {
            Route::get('/images', [FilterAiImageController::class, 'index'])->name('images.index');
            Route::post('/images', [FilterAiImageController::class, 'store'])->name('images.store');
            Route::get('/images-indexing', [FilterAiImageController::class, 'indexing'])->name('images.indexing');
            Route::post('/images-update-order', [FilterAiImageController::class, 'updateOrder'])->name('images.updateOrder');
            Route::put('/images/{id}', [FilterAiImageController::class, 'update'])->name('images.update');
            Route::delete('/images/{id}', [FilterAiImageController::class, 'destroy'])->name('images.destroy');
        });

    // Filter AI Image Categories Routes
    Route::prefix('filter-ai-image')
        ->name('filter-ai-image.')
        ->group(function () {
            Route::get('/categories', [FilterAiImageCategoryController::class, 'index'])->name('categories.index');
            Route::get('/categories-indexing', [FilterAiImageCategoryController::class, 'indexing'])->name('categories.indexing');
            Route::post('/categories-update-order', [FilterAiImageCategoryController::class, 'updateOrder'])->name('categories.updateOrder');
            Route::get('/categories/create', [FilterAiImageCategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [FilterAiImageCategoryController::class, 'store'])->name('categories.store');
            Route::get('/categories/{id}/edit', [FilterAiImageCategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/categories/{id}', [FilterAiImageCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{id}', [FilterAiImageCategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('/categories/update-status', [FilterAiImageCategoryController::class, 'updateStatus'])->name('categories.updateStatus');
        });


    /*
|--------------------------------------------------------------------------
| AI Image Baby Photo Setting Routes
|--------------------------------------------------------------------------
*/
    Route::prefix('ai-image-baby-photo-setting')
        ->name('ai-image-baby-photo-setting.')
        ->group(function () {
            Route::get('/', [AiImageBabyPhotoSettingController::class, 'index'])->name('index');
            Route::get('/create', [AiImageBabyPhotoSettingController::class, 'create'])->name('create');
            Route::post('/', [AiImageBabyPhotoSettingController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AiImageBabyPhotoSettingController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AiImageBabyPhotoSettingController::class, 'update'])->name('update');
            Route::delete('/{id}', [AiImageBabyPhotoSettingController::class, 'destroy'])->name('destroy');
        });

    /*
|--------------------------------------------------------------------------
| AI Image NGD Setting Routes
|--------------------------------------------------------------------------
*/
    Route::prefix('ai-image-ngd-setting')
        ->name('ai-image-ngd-setting.')
        ->group(function () {
            Route::get('/', [AiImageNgdSettingController::class, 'index'])->name('index');
            Route::post('/', [AiImageNgdSettingController::class, 'store'])->name('store');
            Route::put('/{id}', [AiImageNgdSettingController::class, 'update'])->name('update');
            Route::delete('/{id}', [AiImageNgdSettingController::class, 'destroy'])->name('destroy');
        });
    Route::post('/ai-image-categories/toggle-status', [AiImageCategoryController::class, 'toggleStatus'])->name('ai-image-categories.toggle-status');

    /*
|--------------------------------------------------------------------------
| AI Video NGD Setting Routes
|--------------------------------------------------------------------------
*/
    Route::prefix('ai-video-ngd-setting')
        ->name('ai-video-ngd-setting.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\AiVideoNgdSettingController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\AiVideoNgdSettingController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\AiVideoNgdSettingController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\AiVideoNgdSettingController::class, 'destroy'])->name('destroy');
        });

    // Ngendev Video Categories Routes
    Route::prefix('ngendev')
        ->name('ngendev-video-categories.')
        ->group(function () {
            Route::get('/video-categories', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'index'])->name('index');
            Route::get('/video-categories-indexing', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'indexing'])->name('indexing');
            Route::post('/video-categories-update-order', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'updateOrder'])->name('updateOrder');
            Route::get('/video-categories/create', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'create'])->name('create');
            Route::post('/video-categories', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'store'])->name('store');
            Route::get('/video-categories/{id}/edit', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'edit'])->name('edit');
            Route::put('/video-categories/{id}', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'update'])->name('update');
            Route::delete('/video-categories/{id}', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/video-categories/update-status', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'updateStatus'])->name('updateStatus');
            Route::post('/video-categories/update-type', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'updateType'])->name('updateType');
            Route::post('/video-categories/update-couple-status', [\App\Http\Controllers\NgendevVideoCategoryController::class, 'updateCoupleStatus'])->name('updateCoupleStatus');
        });

    // Ngendev Videos Routes
    Route::prefix('ngendev')
        ->name('ngendev-videos.')
        ->group(function () {
            Route::get('/videos', [\App\Http\Controllers\NgendevVideoController::class, 'index'])->name('index');
            Route::post('/videos', [\App\Http\Controllers\NgendevVideoController::class, 'store'])->name('store');
            Route::get('/videos/create', [\App\Http\Controllers\NgendevVideoController::class, 'create'])->name('create');
            Route::get('/videos/{id}/edit', [\App\Http\Controllers\NgendevVideoController::class, 'edit'])->name('edit');
            Route::put('/videos/{id}', [\App\Http\Controllers\NgendevVideoController::class, 'update'])->name('update');
            Route::delete('/videos/{id}', [\App\Http\Controllers\NgendevVideoController::class, 'destroy'])->name('destroy');
            Route::get('/videos-indexing', [\App\Http\Controllers\NgendevVideoController::class, 'indexing'])->name('indexing');
            Route::post('/videos-update-order', [\App\Http\Controllers\NgendevVideoController::class, 'updateOrder'])->name('updateOrder');
            Route::get('/videos-name-change-stats', [\App\Http\Controllers\NgendevVideoController::class, 'categoryNameChangeStats'])->name('nameChangeStats');
            Route::post('/videos-bulk-name-change', [\App\Http\Controllers\NgendevVideoController::class, 'bulkToggleNameChange'])->name('bulkNameChange');
            Route::post('/videos-update-status', [\App\Http\Controllers\NgendevVideoController::class, 'updateStatus'])->name('updateStatus');
        });

    /*
    |--------------------------------------------------------------------------
    | AI Baby Video Module Routes
    |--------------------------------------------------------------------------
    */
    // Category Management
    Route::prefix('ai-baby-video')
        ->name('ai-baby-video.')
        ->group(function () {
            // Categories
            Route::get('/categories', [App\Http\Controllers\AiVideoCategoryController::class, 'index'])->name('categories.index');
            Route::get('/categories/indexing', [App\Http\Controllers\AiVideoCategoryController::class, 'indexing'])->name('categories.indexing');
            Route::post('/categories/update-order', [App\Http\Controllers\AiVideoCategoryController::class, 'updateOrder'])->name('categories.updateOrder');
            Route::get('/categories/create', [App\Http\Controllers\AiVideoCategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [App\Http\Controllers\AiVideoCategoryController::class, 'store'])->name('categories.store');
            Route::get('/categories/{id}/edit', [App\Http\Controllers\AiVideoCategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/categories/{id}', [App\Http\Controllers\AiVideoCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{id}', [App\Http\Controllers\AiVideoCategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('/categories/update-status', [App\Http\Controllers\AiVideoCategoryController::class, 'toggleStatus'])->name('categories.updateStatus');

            // Videos (Subcategories used as Video Items)
            Route::get('/videos', [App\Http\Controllers\AiBabyVideoController::class, 'index'])->name('videos.index');
            Route::get('/videos/create', [App\Http\Controllers\AiBabyVideoController::class, 'create'])->name('videos.create');
            Route::post('/videos', [App\Http\Controllers\AiBabyVideoController::class, 'store'])->name('videos.store');
            Route::get('/videos/{id}/edit', [App\Http\Controllers\AiBabyVideoController::class, 'edit'])->name('videos.edit');
            Route::put('/videos/{id}', [App\Http\Controllers\AiBabyVideoController::class, 'update'])->name('videos.update');
            Route::delete('/videos/{id}', [App\Http\Controllers\AiBabyVideoController::class, 'destroy'])->name('videos.destroy');
        });

    Route::prefix('ai-baby-video-module-setting')
        ->name('ai-baby-video-module-setting.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\AiBabyVideoModuleSettingController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\AiBabyVideoModuleSettingController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\AiBabyVideoModuleSettingController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\AiBabyVideoModuleSettingController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\AiBabyVideoModuleSettingController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\AiBabyVideoModuleSettingController::class, 'destroy'])->name('destroy');
        });
    Route::post('/ai-video-categories/toggle-status', [\App\Http\Controllers\AiVideoCategoryController::class, 'toggleStatus'])->name('ai-video-categories.toggle-status');
    /*
    |--------------------------------------------------------------------------
    | Lips Sync Module Routes
    |--------------------------------------------------------------------------
    */
    // Lips Sync Categories — URL: /ai/lips-sync-categories
    Route::prefix('ai/lips-sync-categories')->name('lips-sync.categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LipsSyncCategoryController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\LipsSyncCategoryController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\LipsSyncCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\LipsSyncCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\LipsSyncCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\LipsSyncCategoryController::class, 'destroy'])->name('destroy');
    });

    // Lips Sync (Items) — URL: /ai/lips-sync
    Route::prefix('ai/lips-sync')->name('lips-sync.items.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LipsSyncController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\LipsSyncController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\LipsSyncController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\LipsSyncController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\LipsSyncController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\LipsSyncController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Dynamic Photo Frame Module Routes
    |--------------------------------------------------------------------------
    */
    // Dynamic Photo Frame Categories — URL: /dynamic-photo-frame/categories
    Route::prefix('dynamic-photo-frame/categories')->name('dynamic-photo-frame.categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DynamicPhotoFrameCategoryController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\DynamicPhotoFrameCategoryController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\DynamicPhotoFrameCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\DynamicPhotoFrameCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\DynamicPhotoFrameCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\DynamicPhotoFrameCategoryController::class, 'destroy'])->name('destroy');
    });

    // Dynamic Photo Frames — URL: /dynamic-photo-frame/frames
    Route::prefix('dynamic-photo-frame/frames')->name('dynamic-photo-frame.frames.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DynamicPhotoFrameController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\DynamicPhotoFrameController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\DynamicPhotoFrameController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\DynamicPhotoFrameController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\DynamicPhotoFrameController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\DynamicPhotoFrameController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Baby AI Home Screen Slider Module Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('baby-ai-home-slider')->name('baby-ai-home-slider.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-status', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'toggleStatus'])->name('toggleStatus');
        Route::get('/indexing', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'indexing'])->name('indexing');
        Route::post('/update-order', [\App\Http\Controllers\BabyAiHomeSliderController::class, 'updateOrder'])->name('updateOrder');
    });

    /*
    |--------------------------------------------------------------------------
    | Sticker Module Routes
    |--------------------------------------------------------------------------
    */
    // Sticker Categories — URL: /sticker/categories
    Route::prefix('sticker/categories')->name('sticker.categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StickerCategoryController::class, 'index'])->name('index');
        Route::get('/indexing', [\App\Http\Controllers\StickerCategoryController::class, 'indexing'])->name('indexing');
        Route::post('/update-order', [\App\Http\Controllers\StickerCategoryController::class, 'updateOrder'])->name('updateOrder');
        Route::post('/toggle-status', [\App\Http\Controllers\StickerCategoryController::class, 'toggleStatus'])->name('toggleStatus');
        Route::post('/toggle-premium', [\App\Http\Controllers\StickerCategoryController::class, 'togglePremium'])->name('togglePremium');
        Route::get('/create', [\App\Http\Controllers\StickerCategoryController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\StickerCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\StickerCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\StickerCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\StickerCategoryController::class, 'destroy'])->name('destroy');
    });

    // Stickers — URL: /sticker/stickers
    Route::prefix('sticker/stickers')->name('sticker.stickers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StickerController::class, 'index'])->name('index');
        Route::post('/update-order', [\App\Http\Controllers\StickerController::class, 'updateOrder'])->name('updateOrder');
        Route::get('/create', [\App\Http\Controllers\StickerController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\StickerController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\StickerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\StickerController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\StickerController::class, 'destroy'])->name('destroy');
    });

    // Single sticker image deletion (used by edit form). Body: { category_id, image }
    Route::post('/sticker/sticker-images/delete', [\App\Http\Controllers\StickerController::class, 'destroyOne'])->name('sticker.stickers.destroyOne');

    /*
    |--------------------------------------------------------------------------
    | Font Module Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('fonts')->name('fonts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FontController::class, 'index'])->name('index');
        Route::post('/toggle-premium', [\App\Http\Controllers\FontController::class, 'togglePremium'])->name('togglePremium');
        Route::get('/create', [\App\Http\Controllers\FontController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FontController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\FontController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\FontController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\FontController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Filter Module Routes
    |--------------------------------------------------------------------------
    */
    // Filter Categories — URL: /filter/categories
    Route::prefix('filter/categories')->name('filter.categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FilterCategoryController::class, 'index'])->name('index');
        Route::get('/indexing', [\App\Http\Controllers\FilterCategoryController::class, 'indexing'])->name('indexing');
        Route::post('/update-order', [\App\Http\Controllers\FilterCategoryController::class, 'updateOrder'])->name('updateOrder');
        Route::post('/toggle-status', [\App\Http\Controllers\FilterCategoryController::class, 'toggleStatus'])->name('toggleStatus');
        Route::get('/create', [\App\Http\Controllers\FilterCategoryController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FilterCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\FilterCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\FilterCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\FilterCategoryController::class, 'destroy'])->name('destroy');
    });

    // Filters — URL: /filter/filters
    Route::prefix('filter/filters')->name('filter.filters.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FilterController::class, 'index'])->name('index');
        Route::get('/import', [\App\Http\Controllers\FilterController::class, 'importForm'])->name('importForm');
        Route::post('/import', [\App\Http\Controllers\FilterController::class, 'importCsv'])->name('importCsv');
        Route::post('/toggle-premium', [\App\Http\Controllers\FilterController::class, 'togglePremium'])->name('togglePremium');
        Route::get('/create', [\App\Http\Controllers\FilterController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FilterController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\FilterController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\FilterController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\FilterController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Doodle Module Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('doodles')->name('doodles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DoodleController::class, 'index'])->name('index');
        Route::get('/indexing', [\App\Http\Controllers\DoodleController::class, 'indexing'])->name('indexing');
        Route::post('/update-order', [\App\Http\Controllers\DoodleController::class, 'updateOrder'])->name('updateOrder');
        Route::post('/toggle-premium', [\App\Http\Controllers\DoodleController::class, 'togglePremium'])->name('togglePremium');
        Route::get('/create', [\App\Http\Controllers\DoodleController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\DoodleController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\DoodleController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\DoodleController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\DoodleController::class, 'destroy'])->name('destroy');
    });

    // Top Slider Module
    Route::prefix('top-slider')->name('top-slider.')->group(function () {
        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\TopSliderCategoryController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\TopSliderCategoryController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\TopSliderCategoryController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\TopSliderCategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\TopSliderCategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\TopSliderCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/update-topslider-status', [\App\Http\Controllers\TopSliderCategoryController::class, 'toggleTopSliderStatus'])->name('updateTopSliderStatus');
            Route::get('/indexing', [\App\Http\Controllers\TopSliderCategoryController::class, 'indexing'])->name('indexing');
            Route::post('/update-order', [\App\Http\Controllers\TopSliderCategoryController::class, 'updateOrder'])->name('updateOrder');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Notify Module Routes
    |--------------------------------------------------------------------------
    | Shared notification system. One controller drives the list, the global
    | broadcast, per-category sends (registered in NotificationController::$modules)
    | and default-delay settings. Every push fans out to all Firebase projects.
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/settings', [NotificationController::class, 'settings'])->name('settings');
        Route::post('/settings', [NotificationController::class, 'saveSettings'])->name('settings.save');
        Route::get('/global', [NotificationController::class, 'globalForm'])->name('global');
        Route::post('/global', [NotificationController::class, 'globalStore'])->name('global.store');
        Route::get('/test', [NotificationController::class, 'testForm'])->name('test');
        Route::post('/test', [NotificationController::class, 'testSend'])->name('test.send');
        Route::post('/test/lookup', [NotificationController::class, 'testLookup'])->name('test.lookup');
        Route::post('/upload-image', [NotificationController::class, 'uploadImage'])->name('uploadImage');
        Route::get('/{id}/show', [NotificationController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{module}/{id}/form', [NotificationController::class, 'form'])->name('form');
        Route::post('/{module}/{id}', [NotificationController::class, 'store'])->name('store');
    });

    // Firebase Projects (manage FCM credentials for every app this panel serves)
    Route::prefix('firebase-projects')->name('firebase-projects.')->group(function () {
        Route::get('/', [FirebaseProjectController::class, 'index'])->name('index');
        Route::get('/create', [FirebaseProjectController::class, 'create'])->name('create');
        Route::post('/', [FirebaseProjectController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [FirebaseProjectController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FirebaseProjectController::class, 'update'])->name('update');
        Route::delete('/{id}', [FirebaseProjectController::class, 'destroy'])->name('destroy');
    });
});
