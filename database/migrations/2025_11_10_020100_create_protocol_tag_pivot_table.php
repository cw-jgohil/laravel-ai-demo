<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('protocol_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['protocol_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_tag');
    }
};


