<?php

namespace App\Models;

use App\Models\Item;
use App\Models\ItemVariant;
use App\Models\Transaction;
use App\Models\StockHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    protected $fillable = ['transaction_id', 'item_id', 'item_variant_id', 'qty', 'unit', 'price', 'note'];

    protected $casts = [
        'is_purchase_in' => 'boolean'
    ];

    /**
     * Get the item that the transaction detail belongs to.
     *
     * @return BelongsTo
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

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
     * Get the stock history that the transaction detail belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function stockHistory(): HasOne
    {
        return $this->hasOne(StockHistory::class);
    }
}
