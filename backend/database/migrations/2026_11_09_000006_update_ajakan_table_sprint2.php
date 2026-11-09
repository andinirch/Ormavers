<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajakan', function (Blueprint $table) {
            if (! Schema::hasColumn('ajakan', 'djawab_pada')) {
                $table->timestamp('djawab_pada')->nullable()->after('alasan_penolakan');
            }

            $table->enum('status', ['menunggu', 'diterima', 'ditolak', 'dibatalkan'])
                ->default('menunggu')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('ajakan', function (Blueprint $table) {
            if (Schema::hasColumn('ajakan', 'djawab_pada')) {
                $table->dropColumn('djawab_pada');
            }

            $table->enum('status', ['menunggu', 'diterima', 'ditolak'])
                ->default('menunggu')
                ->change();
        });
    }
};
