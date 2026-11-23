<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('reason', ['scam', 'spam', 'misleading_details', 'inappropriate_content', 'other']);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['job_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
