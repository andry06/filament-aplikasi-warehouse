<?php

use App\Http\Controllers\PrintController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware([Authenticate::class])->group(function () {
    Route::get('print/good-receives/{transaction}', [PrintController::class, 'printGoodReceive'])
        ->name('print.good-receives');
});
