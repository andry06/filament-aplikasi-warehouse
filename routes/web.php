<?php

use App\Models\StockMutation;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ExportController;
use Filament\Http\Middleware\Authenticate;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware([Authenticate::class])->group(function () {
    Route::get('print/good-receives/{transaction}', [PrintController::class, 'printGoodReceive'])->name('print.good-receives');
    Route::get('print/purchase-returns/{transaction}', [PrintController::class, 'printPurchaseReturn'])->name('print.purchase-returns');
    Route::get('print/production-allocations/{transaction}', [PrintController::class, 'printProductionAllocation'])->name('print.production-allocations');
    Route::get('print/production-returns/{transaction}', [PrintController::class, 'printProductionReturn'])->name('print.production-returns');
    Route::get('print/stock-opnames/{transaction}', [PrintController::class, 'printStockOpname'])->name('print.stock-opnames');

    Route::get('/export/stock-mutations', [ExportController::class, 'exportStockMutations'])->name('export.stock-mutations');
});
