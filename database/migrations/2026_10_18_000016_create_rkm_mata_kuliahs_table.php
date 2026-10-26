<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkm_mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkm_id')->constrained('rkms')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->string('kode_mk', 20);
            $table->string('nama_mk', 150);
            $table->unsignedTinyInteger('sks');
            $table->unsignedSmallInteger('durasi_jam');
            $table->text('kompetensi');

            $table->enum('status_mk', ['aktif', 'dropped'])->default('aktif');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkm_mata_kuliahs');
    }
};
