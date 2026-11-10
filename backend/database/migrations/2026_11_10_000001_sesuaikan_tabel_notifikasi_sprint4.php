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
        Schema::table('notifikasi', function (Blueprint $table) {
            if (! Schema::hasColumn('notifikasi', 'jenis')) {
                $table->string('jenis', 50)
                    ->default('pengingat_event')
                    ->after('id_organisasi');
            }

            if (! Schema::hasColumn('notifikasi', 'sudah_baca')) {
                $table->boolean('sudah_baca')
                    ->default(false)
                    ->after('pesan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            if (Schema::hasColumn('notifikasi', 'sudah_baca')) {
                $table->dropColumn('sudah_baca');
            }

            if (Schema::hasColumn('notifikasi', 'jenis')) {
                $table->dropColumn('jenis');
            }
        });
    }
};
