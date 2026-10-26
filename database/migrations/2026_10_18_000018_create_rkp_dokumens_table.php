<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkp_dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkm_id')->unique()->constrained('rkms')->cascadeOnDelete();
            $table->foreignId('pendaftaran_mbkm_id')->constrained('pendaftaran_mbkms')->cascadeOnDelete();
            $table->string('nomor_dokumen', 100)->unique();
            $table->string('judul', 200);
            $table->string('durasi_pelaksanaan', 50)->default('4 bulan');
            $table->string('semester_tahun_akademik', 100)->default('5 / 2026/2027 Ganjil');
            $table->string('prasyarat_sks', 50)->default('80 SKS');
            $table->string('nama_dpl', 150)->nullable();
            $table->string('nip_dpl', 30)->nullable();
            $table->string('nama_kaprodi', 150)->default('Priza Pandunata, S.Kom., M.Sc.');
            $table->string('nip_kaprodi', 30)->default('198301312015041001');

            $table->json('snapshot_data');
            $table->enum('status', ['Draf', 'Disahkan'])->default('Draf');
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disahkan_at')->nullable();
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkp_dokumens');
    }
};
