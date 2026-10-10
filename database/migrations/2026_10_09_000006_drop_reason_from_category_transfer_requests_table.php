<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_transfer_requests')) {
            return;
        }

        if (! Schema::hasColumn('category_transfer_requests', 'reason')) {
            return;
        }

        Schema::table('category_transfer_requests', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('category_transfer_requests')) {
            return;
        }

        if (Schema::hasColumn('category_transfer_requests', 'reason')) {
            return;
        }

        Schema::table('category_transfer_requests', function (Blueprint $table) {
            $table->text('reason')->nullable();
        });
    }
};
