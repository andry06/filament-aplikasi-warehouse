<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = ['code', 'name', 'unit', 'category'];

    /**
     * Get all of the comments for the Item
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ItemVariants(): HasMany
    {
        return $this->hasMany(ItemVariant::class);
    }
}
