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

use Illuminate\Notifications\DatabaseNotification;
use Modules\Base\Models\ConfigModel;
use Modules\DvUi\View\BaseBladeComponent;

Route::prefix('admin/')->middleware(['auth', 'verified'])->group(function () {
    Route::get('config/list', fn () => view('lte::components.pages.config.config_list_page'))
        ->name('admin.configs');
    Route::get('config/{config?}', fn (ConfigModel $config) => view('lte::components.pages.config.config_form_page', compact('config')))
        ->name('admin.config');

    Route::get('notifications', function () {
        if (BaseBladeComponent::published('base.page.notification.notification-list-page')) {
            return view('components.base.page.notification.notification-list-page');
        }

        return view('base::components.page.notification.notification-list-page');
    })
        ->name('my-notifications');

    Route::get('notification/{notification}', function (DatabaseNotification $notification) {
        if (BaseBladeComponent::published('base.page.notification.notification-view-page')) {
            return view('components.base.page.notification.notification-view-page', compact('notification'));
        }

        return view('base::components.page.notification.notification-view-page', compact('notification'));
    })->name('notification');
});
