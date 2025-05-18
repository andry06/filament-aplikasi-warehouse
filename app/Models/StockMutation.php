<?php

namespace App\Models;

use App\Models\ItemVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMutation extends Model
{
    protected $fillable = ['warehouse_id', 'item_variant_id', 'date', 'begin_stock', 'qty_in', 'qty_out', 'ending_stock'];

    /**
     * Get the item variant associated with the StockMutation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ItemVariant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class);
    }
}
