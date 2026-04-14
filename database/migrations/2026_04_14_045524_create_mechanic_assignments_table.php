<?php

use App\Enums\MechanicAssignmentStatus;
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
        Schema::create('mechanic_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('work_order_service_id')
                ->constrained('work_order_services')
                ->cascadeOnDelete();

            $table->foreignUuid('mechanic_id')
                ->constrained('users');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('status')->default(MechanicAssignmentStatus::ASSIGNED->value);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mechanic_assignments');
    }
};
