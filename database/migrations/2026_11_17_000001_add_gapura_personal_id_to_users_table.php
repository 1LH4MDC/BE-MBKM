<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'gapura_personal_id')) {
                $table->string('gapura_personal_id', 100)->nullable()->unique()->after('nim_nip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'gapura_personal_id')) {
                $table->dropUnique(['gapura_personal_id']);
                $table->dropColumn('gapura_personal_id');
            }
        });
    }
};
