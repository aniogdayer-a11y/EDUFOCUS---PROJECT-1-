<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('completion_key');
            $table->date('study_date');
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['user_id', 'completion_key']);
            $table->index(['user_id', 'study_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
