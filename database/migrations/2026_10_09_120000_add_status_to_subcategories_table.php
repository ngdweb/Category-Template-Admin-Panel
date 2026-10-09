<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('subcategories', 'status')) {
                // 1 = Published (shown in API), 0 = Draft. Default published so existing data stays visible.
                $table->boolean('status')->default(1)->after('trending');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('subcategories', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
