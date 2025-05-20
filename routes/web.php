<?php

use App\Http\Controllers\PrintController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware([Authenticate::class])->group(function () {
    Route::get('print/good-receives/{transaction}', [PrintController::class, 'printGoodReceive'])->name('print.good-receives');
    Route::get('print/purchase-returns/{transaction}', [PrintController::class, 'printPurchaseReturn'])->name('print.purchase-returns');
    Route::get('print/production-allocations/{transaction}', [PrintController::class, 'printProductionAllocation'])->name('print.production-allocations');
    Route::get('print/production-returns/{transaction}', [PrintController::class, 'printProductionReturn'])->name('print.production-returns');
    Route::get('print/stock-opnames/{transaction}', [PrintController::class, 'printStockOpname'])->name('print.stock-opnames');
});
