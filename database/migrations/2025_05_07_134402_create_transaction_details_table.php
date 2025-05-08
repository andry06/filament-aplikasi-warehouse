<?php

use App\Models\ItemVariant;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Transaction::class)->nullable();
            $table->foreignIdFor(ItemVariant::class)->nullable();
            $table->decimal('qty', 8, 2)->nullable();
            $table->decimal('qty_used', 8, 2)->nullable();
            $table->string('unit')->nullable();
            $table->bigInteger('price')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
