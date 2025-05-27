<?php

namespace App\Filament\Resources\StockMutationResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use App\Models\ItemVariant;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Session;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StockMutationResource;

class ListStockMutations extends ListRecords
{
    protected static string $resource = StockMutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTableQuery(): ?Builder
    {
        $filters = $this->tableFilters;

        // untuk handle agar tidak memberatkan sistem
        if($filters == null) {
            return ItemVariant::limit(1);
        }

        $warehouseId = $filters['warehouse_id']['value'] ?? null;
        $dates = $filters['date']['date'] ?? null;
        $categories = $filters['category']['values'] ?? null;

        Session::put('filter_stock_mutation', [
            'warehouse_id' => $warehouseId,
            'dates' => $dates,
        ]);

        $subQueryBeginStock = StockMutation::selectRaw('item_variant_id,
                sum(qty_in) - sum(qty_out) as begin_stock')
            ->where('warehouse_id', $warehouseId)
            ->when($dates, function ($query, $dates) {
                [$start, $end] = explode(' - ', $dates);
                $startDate = Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                $query->whereDate('date', '<', $startDate);
            })
            ->groupBy('warehouse_id', 'item_variant_id');

        $subQueryTransaction = StockMutation::selectRaw('item_variant_id,
                sum(qty_in) as total_qty_in, sum(qty_out) as total_qty_out')
            ->where('warehouse_id', $warehouseId)
            ->when($dates, function ($query, $dates) {
                [$start, $end] = explode(' - ', $dates);
                $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                return $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('warehouse_id', 'item_variant_id');

        return ItemVariant::select('item_variants.*', 'items.code', 'items.name', 'items.category', 'items.unit')
                ->selectRaw('IFNULL(Gbs.begin_stock, 0) as begin_stock, IFNULL(Gt.total_qty_in, 0) as total_qty_in,
                    IFNULL(Gt.total_qty_out, 0) as total_qty_out,
                    (IFNULL(Gbs.begin_stock, 0) + IFNULL(Gt.total_qty_in, 0) - IFNULL(Gt.total_qty_out, 0) ) as ending_stock')
                ->leftJoin('items', 'item_variants.item_id', '=', 'items.id')
                ->leftJoinSub($subQueryBeginStock, 'Gbs', 'Gbs.item_variant_id', '=', 'item_variants.id')
                ->leftJoinSub($subQueryTransaction, 'Gt', 'Gt.item_variant_id', '=', 'item_variants.id')
                ->when($categories, function ($query) use ($categories) {
                    return $query->whereIn('items.category', $categories);
                });
    }
}
