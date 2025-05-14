<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'warehouse_id',
        'user_id',
        'supplier_id',
        'counter',
        'number',
        'reference_number',
        'date',
        'type',
        'note',
        'pic_field',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the warehouse that the transaction belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user that created the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the supplier that the transaction belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }


    /**
     * Get all of the transaction details for the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function getPurchaseItemUsedAttribute(): bool
    {
        return $this->transactionDetails()->where('qty_used', '>', 0)->exists();
    }


}
