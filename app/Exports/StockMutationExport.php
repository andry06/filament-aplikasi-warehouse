<?php

namespace App\Exports;


use Carbon\Carbon;
use App\Models\ItemVariant;
use App\Models\StockMutation;
use App\Exports\StockMutationSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockMutationExport implements WithMultipleSheets
{
    protected $groupStockMutations;

    public function __construct()
    {
        $this->groupStockMutations = $this->getStockMutations()->groupBy('category');
    }

    public function sheets(): array
    {
        foreach ($this->groupStockMutations as $category => $stockMutations) {
            $sheets[] = new StockMutationSheetExport($category, $stockMutations);
        }

        return $sheets;
    }

    /**
     * Get stock mutations from session filters
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStockMutations()
    {
        $sessionFilters = session('filter_stock_mutation');
        $dates = $sessionFilters['dates'];
        $warehouseId = $sessionFilters['warehouse_id'];

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
                ->orderBy('items.category', 'asc')
                ->get();
    }

}
