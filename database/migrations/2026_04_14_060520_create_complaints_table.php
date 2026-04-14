<?php

use App\Enums\ComplaintStatus;
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
        Schema::create('complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->text('description');
            $table->string('status')->default(ComplaintStatus::PENDING->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
