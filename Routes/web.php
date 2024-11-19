<?php

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

use Modules\Base\Models\ConfigModel;

Route::prefix('admin')->group(function () {
    Route::get('config/list', fn() => view('lte::components.pages.config.config_list_page'))
        ->name('admin.configs');
    Route::get('config/{config?}', fn(ConfigModel $config) => view('lte::components.pages.config.config_form_page', compact('config')))
        ->name('admin.config');
});
