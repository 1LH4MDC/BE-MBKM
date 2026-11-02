<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuisioner_pertanyaans', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 30);
            $table->unsignedInteger('nomor_urut')->default(1);
            $table->text('pertanyaan');
            $table->string('tipe_input', 30)->default('likert_5');
            $table->json('opsi_pilihan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kuisioner_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_mbkm_id')->nullable()->constrained('pendaftaran_mbkms')->nullOnDelete();
            $table->foreignId('mahasiswa_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dpl_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mitra_id')->nullable()->constrained('mitras')->nullOnDelete();
            $table->string('nama_mitra_snapshot')->nullable();
            $table->json('jawaban_dpl')->nullable();
            $table->json('jawaban_mitra')->nullable();
            $table->decimal('rata_rata_dpl', 4, 2)->default(0.00);
            $table->decimal('rata_rata_mitra', 4, 2)->default(0.00);
            $table->json('kompensasi_tipe')->nullable();
            $table->string('kompensasi_nominal')->nullable();
            $table->text('saran_masukan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuisioner_jawabans');
        Schema::dropIfExists('kuisioner_pertanyaans');
    }
};
