<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_pengantars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat', 100)->unique();
            $table->date('tanggal_surat');
            $table->string('perihal', 200)->default('Permohonan Izin Magang MBKM');
            $table->string('tujuan_instansi', 200);
            $table->string('nama_penerima', 150)->nullable();
            $table->text('alamat_instansi');
            $table->string('kota_instansi', 100);
            $table->date('periode_magang_mulai')->nullable();
            $table->date('periode_magang_selesai')->nullable();
            $table->foreignId('pejabat_ttd_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_pengantars');
    }
};
