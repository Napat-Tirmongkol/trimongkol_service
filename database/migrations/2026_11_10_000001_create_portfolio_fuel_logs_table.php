<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_fuel_logs')) {
            Schema::create('portfolio_fuel_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->date('refill_date');
                $table->string('vehicle_name', 100)->default('มอเตอร์ไซค์');
                $table->decimal('odometer', 10, 2)->nullable();
                $table->decimal('distance', 10, 2)->nullable();
                $table->decimal('liters', 8, 2);
                $table->decimal('price_per_liter', 8, 2);
                $table->decimal('total_price', 10, 2);
                $table->string('fuel_type', 50)->default('แก๊สโซฮอล์ 95');
                $table->string('gas_station', 50)->default('ปตท.');
                $table->boolean('is_full_tank')->default(true);
                $table->decimal('efficiency_kml', 8, 2)->nullable();
                $table->decimal('cost_per_km', 8, 2)->nullable();
                $table->unsignedBigInteger('ledger_entry_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('refill_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_fuel_logs');
    }
};
