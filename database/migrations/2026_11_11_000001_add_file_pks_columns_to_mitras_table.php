<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            if (! Schema::hasColumn('mitras', 'file_pks')) {
                $table->text('file_pks')->nullable()->after('nomor_pks');
            }
            if (! Schema::hasColumn('mitras', 'file_pks_name')) {
                $table->string('file_pks_name', 255)->nullable()->after('file_pks');
            }
            if (! Schema::hasColumn('mitras', 'file_pks_size')) {
                $table->unsignedBigInteger('file_pks_size')->nullable()->after('file_pks_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            $cols = array_filter(
                ['file_pks', 'file_pks_name', 'file_pks_size'],
                fn ($c) => Schema::hasColumn('mitras', $c)
            );
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
