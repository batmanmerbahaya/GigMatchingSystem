<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications');
            $table->text('note');
            $table->enum('status', ['pending', 'approved', 'changes_requested'])->default('pending');
            $table->timestamp('submitted_at')->useCurrent();

            $table->index(['status', 'submitted_at']);
        });

        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions');
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_files');
        Schema::dropIfExists('submissions');
    }
};
