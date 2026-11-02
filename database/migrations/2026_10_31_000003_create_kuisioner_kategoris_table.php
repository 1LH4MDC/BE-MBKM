<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kuisioner_kategoris')) {
            Schema::create('kuisioner_kategoris', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('slug')->unique();
                $table->string('target_evaluasi', 30)->default('dpl');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('urutan')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('kuisioner_pertanyaans') && ! Schema::hasColumn('kuisioner_pertanyaans', 'kategori_id')) {
            Schema::table('kuisioner_pertanyaans', function (Blueprint $table) {
                $table->foreignId('kategori_id')->nullable()->after('id')->constrained('kuisioner_kategoris')->nullOnDelete();
            });
        }

        $now = now();
        $categories = [
            [
                'nama' => 'Evaluasi DPL',
                'slug' => 'dpl',
                'target_evaluasi' => 'dpl',
                'is_active' => true,
                'urutan' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Evaluasi Mitra',
                'slug' => 'mitra',
                'target_evaluasi' => 'mitra',
                'is_active' => true,
                'urutan' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Saran Pengembangan',
                'slug' => 'saran',
                'target_evaluasi' => 'umum',
                'is_active' => true,
                'urutan' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($categories as $cat) {
            $existing = DB::table('kuisioner_kategoris')->where('slug', $cat['slug'])->first();
            if (! $existing) {
                $id = DB::table('kuisioner_kategoris')->insertGetId($cat);
            } else {
                $id = $existing->id;
            }

            if ($cat['slug'] === 'saran') {
                DB::table('kuisioner_pertanyaans')
                    ->whereIn('kategori', ['umum', 'saran'])
                    ->where('tipe_input', 'textarea')
                    ->update(['kategori_id' => $id, 'kategori' => 'saran']);
            } else {
                DB::table('kuisioner_pertanyaans')
                    ->where('kategori', $cat['slug'])
                    ->update(['kategori_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kuisioner_pertanyaans', 'kategori_id')) {
            Schema::table('kuisioner_pertanyaans', function (Blueprint $table) {
                $table->dropForeign(['kategori_id']);
                $table->dropColumn('kategori_id');
            });
        }

        Schema::dropIfExists('kuisioner_kategoris');
    }
};
