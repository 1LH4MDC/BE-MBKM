<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitras', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('nomor_pks', 100)->nullable();
            $table->text('alamat');
            $table->string('kontak', 150);
            $table->string('email', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitras');
    }
};
