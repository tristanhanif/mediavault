<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('media_tag', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['media_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_tag');
        Schema::dropIfExists('tags');
    }
};
