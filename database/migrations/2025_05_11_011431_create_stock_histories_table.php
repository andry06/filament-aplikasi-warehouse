<?php

use App\Models\Warehouse;
use App\Models\ItemVariant;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Warehouse::class)->nullable();
            $table->foreignIdFor(TransactionDetail::class)->nullable();
            $table->foreignIdFor(ItemVariant::class)->nullable();
            $table->date('date')->nullable();
            $table->decimal('begin_stock', 12, 2)->nullable();
            $table->decimal('qty')->nullable();
            $table->decimal('ending_stock', 12, 2)->nullable();
            $table->string('movement_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
