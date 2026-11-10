<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portofolio', function (Blueprint $table) {
            $table->unsignedBigInteger('id_event')->nullable()->after('id_organisasi');
            $table->foreign('id_event')
                ->references('id')
                ->on('event')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('portofolio', function (Blueprint $table) {
            $table->dropForeign(['id_event']);
            $table->dropColumn('id_event');
        });
    }
};
