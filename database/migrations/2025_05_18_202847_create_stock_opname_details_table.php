<?php

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
        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TransactionDetail::class)->nullable();
            $table->decimal('system_stock', 12, 2)->nullable();
            $table->decimal('actual_stock', 12, 2)->nullable();
            $table->decimal('diff_stock', 12, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_details');
    }
};
