<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->string('public_id', 500);
            $table->string('resource_type', 20)->index();
            $table->string('mime_type', 120);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'folder_id']);
            $table->index('created_at');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
