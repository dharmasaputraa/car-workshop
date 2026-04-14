<?php

use App\Enums\WorkOrderStatus;
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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('car_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users');
            $table->string('status')->default(WorkOrderStatus::DRAFT->value);
            $table->text('diagnosis_notes')->nullable();
            $table->date('estimated_completion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
