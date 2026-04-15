<?php

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
        Schema::table('mechanic_assignments', function (Blueprint $table) {
            // Add complaint_service_id as nullable FK after work_order_service_id
            // This allows mechanic_assignments to serve both work_order_services AND complaint_services
            $table->foreignUuid('complaint_service_id')
                ->nullable()
                ->constrained('complaint_services')
                ->cascadeOnDelete()
                ->after('work_order_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mechanic_assignments', function (Blueprint $table) {
            $table->dropForeign(['complaint_service_id']);
            $table->dropColumn('complaint_service_id');
        });
    }
};
