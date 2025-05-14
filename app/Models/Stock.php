<?php

namespace App\Models;

use App\Models\ItemVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = ['warehouse_id', 'item_variant_id', 'stock'];

    /**
     * Get the item variant associated with the TransactionDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ItemVariant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class);
    }

    /**
     * Get the warehouse that the stock belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

}
