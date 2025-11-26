<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hangman_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');  // İpucu / Soru
            $table->string('answer');    // Kelime / Cevap
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hangman_questions');
    }
};
