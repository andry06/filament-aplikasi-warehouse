<?php

namespace App\Models;

use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameDetail extends Model
{
    protected $fillable = ['transaction_detail_id', 'system_stock', 'actual_stock', 'diff_stock'];

    public $timestamps = false;

    /**
     * Get the transaction detail that the stock opname detail belongs to.
     *
     * @return BelongsTo
     */
    public function transactionDetail(): BelongsTo
    {
        return $this->belongsTo(TransactionDetail::class);
    }
}
