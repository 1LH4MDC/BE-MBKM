<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimbingan_magangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_mbkm_id')->constrained('pendaftaran_mbkms')->cascadeOnDelete();
            $table->foreignId('dpl_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('periode_akademik_id')->nullable()->constrained('periode_akademiks')->nullOnDelete();
            $table->string('tahun_ajaran', 50)->nullable();
            $table->unsignedInteger('bimbingan_ke');
            $table->date('tanggal_bimbingan');
            $table->text('catatan_bimbingan');
            $table->string('foto_bukti_path', 500)->nullable();
            $table->timestamps();

            $table->unique(['pendaftaran_mbkm_id', 'bimbingan_ke'], 'unique_pendaftaran_bimbingan_ke');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimbingan_magangs');
    }
};
