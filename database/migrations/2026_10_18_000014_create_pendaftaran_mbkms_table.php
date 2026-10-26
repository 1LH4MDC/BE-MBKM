<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran_mbkms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nim', 30)->index();
            $table->string('nama_lengkap', 150);
            $table->foreignId('program_studi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->foreignId('mitra_id')->nullable()->constrained('mitras')->nullOnDelete();
            $table->string('mitra_magang_text', 150);
            $table->string('posisi_magang', 100);
            $table->boolean('is_custom_role')->default(false);
            $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();

            $table->string('transkrip_file_name');
            $table->string('transkrip_file_path');
            $table->string('transkrip_file_size', 50);
            $table->string('loa_file_name');
            $table->string('loa_file_path');
            $table->string('loa_file_size', 50);

            $table->enum('status', ['Menunggu Verifikasi', 'Terverifikasi', 'Perbaikan', 'Ditolak'])
                ->default('Menunggu Verifikasi');
            $table->text('catatan')->nullable();
            $table->string('link_wa_grup')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->boolean('is_plotted_pic')->default(false);
            $table->foreignId('pic_verifikator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_plotted_dpl_penguji')->default(false);
            $table->foreignId('dpl_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('penguji_semhas_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_mbkms');
    }
};
