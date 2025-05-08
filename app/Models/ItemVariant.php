<?php

namespace App\Models;

use App\Models\Item;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemVariant extends Model
{
    protected $fillable = ['item_id', 'color'];

    /**
     * Get the item that owns the ItemDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
