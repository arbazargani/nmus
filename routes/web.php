<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    // return view('app.home');
    return view('app.mangene.index');
})->name('home');

Route::get('/notif/{title}', [\App\Http\Controllers\MasterController::class, 'dispatchNotif'])->name('notif');


Route::get('/menubar', function () {
    return view('app.mangene.index');
    // return view('app.mangene.menubar');

})->name('menubar');

Route::prefix('app')->group(function() {
    Route::get('close', function () { \Native\Laravel\Facades\Window::close('main'); })->name('app.close');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
