<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
            if (! Schema::hasColumn('pendaftaran_mbkms', 'link_logbook')) {
                $table->string('link_logbook', 500)->nullable()->after('link_wa_grup');
            }
            if (! Schema::hasColumn('pendaftaran_mbkms', 'link_wa_dpl')) {
                $table->string('link_wa_dpl', 500)->nullable()->after('link_logbook');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
            $cols = array_filter(
                ['link_logbook', 'link_wa_dpl'],
                fn ($c) => Schema::hasColumn('pendaftaran_mbkms', $c)
            );
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
