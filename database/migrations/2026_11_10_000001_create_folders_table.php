<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('name', 255);
            $table->unsignedTinyInteger('depth')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'parent_id']);
            $table->unique(['user_id', 'parent_id', 'name', 'deleted_at'], 'folders_unique_name_per_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
