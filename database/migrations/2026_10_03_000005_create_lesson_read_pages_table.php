<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_read_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->unsignedInteger('total_pages');
            $table->timestamps();
            $table->unique(['lesson_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_read_pages');
    }
};
