<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Make work_order_service_id nullable to allow mechanic_assignments
     * to serve both work_order_services AND complaint_services.
     */
    public function up(): void
    {
        Schema::table('mechanic_assignments', function (Blueprint $table) {
            $table->uuid('work_order_service_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mechanic_assignments', function (Blueprint $table) {
            $table->uuid('work_order_service_id')->nullable(false)->change();
        });
    }
};
