<?php

use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Warehouse::class)->nullable();
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(Supplier::class)->nullable();
            $table->integer('counter')->nullable();
            $table->string('number')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('date')->nullable();
            $table->string('type')->nullable(); // purchase_in, purchase_return, production_out, production_return
            $table->string('note')->nullable();
            $table->string('pic_field')->nullable();
            $table->string('status')->nullable(); //draft, approve
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
