<?php

namespace App\Models;

use App\Models\Item;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemVariant extends Model
{
    protected $fillable = ['item_id', 'color', 'price'];

    /**
     * Get the item that owns the ItemDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    /**
    * Get the item that the item variant belongs to.
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }


    /**
     * Get all of the transaction details for the item variant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Get all of the stocks for the item variant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get all of the stock histories for the item variant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }


    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }
}
