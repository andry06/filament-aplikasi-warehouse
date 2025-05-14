<?php

namespace App\Models;

use App\Models\Warehouse;
use App\Models\ItemVariant;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistory extends Model
{
    protected $fillable = [
        'warehouse_id',
        'transaction_detail_id',
        'item_variant_id',
        'date',
        'begin_stock',
        'qty',
        'ending_stock',
        'movement_type'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    /**
     * Get the warehouse that owns the StockHistory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the transaction detail that owns the StockHistory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionDetail(): BelongsTo
    {
        return $this->belongsTo(TransactionDetail::class);
    }

    /**
     * Get the item variant that owns the StockHistory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function ItemVariant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class);
    }
}
