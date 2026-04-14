<?php

use App\Enums\ServiceItemStatus;
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
        Schema::create('work_order_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained();
            $table->decimal('price', 15, 2);
            $table->string('status')->default(ServiceItemStatus::PENDING->value);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_services');
    }
};
