<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ngendev_images', function (Blueprint $table) {
            if (!Schema::hasColumn('ngendev_images', 'status')) {
                // 1 = On (shown in API), 0 = Off (hidden). Default on so existing images stay visible.
                $table->boolean('status')->default(1)->after('image_hint');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ngendev_images', function (Blueprint $table) {
            if (Schema::hasColumn('ngendev_images', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
