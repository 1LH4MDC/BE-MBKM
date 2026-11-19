<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimbingan_mata_kuliah_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bimbingan_magang_id')->constrained('bimbingan_magangs')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();
            $table->string('kode_mk', 50);
            $table->string('nama_mk', 255);
            $table->unsignedTinyInteger('sks');
            $table->enum('status', ['Belum Terlaksana', 'Sebagian Terlaksana', 'Terlaksana'])->default('Belum Terlaksana');
            $table->timestamps();

            $table->index(['bimbingan_magang_id', 'kode_mk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimbingan_mata_kuliah_statuses');
    }
};
