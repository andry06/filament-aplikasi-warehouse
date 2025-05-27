<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name', 'date', 'has_allocation', 'is_completed', 'material_cost'];

    /**
     * Get all transactions for the project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTotalMaterialCostAttribute()
    {
        $result = $this->transactions()
            ->selectRaw("SUM(IF(transactions.type = 'production_allocation', (transaction_details.price * transaction_details.qty), 0)) as material_cost_allocation")
            ->selectRaw("SUM(IF(transactions.type = 'production_return', (transaction_details.price * transaction_details.qty), 0)) as material_cost_return")
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('status', 'approve')
            ->groupBy('transactions.project_id')
            ->first();
        return $result ? $result->material_cost_allocation - $result->material_cost_return : 0;

    }

}
