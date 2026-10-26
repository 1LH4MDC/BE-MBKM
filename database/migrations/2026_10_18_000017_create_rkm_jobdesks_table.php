<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkm_jobdesks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkm_mata_kuliah_id')->constrained('rkm_mata_kuliahs')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->text('deskripsi');
            $table->enum('origin', ['master_mk', 'custom'])->default('master_mk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkm_jobdesks');
    }
};
