<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * APPLICATION table. The accepted application acts as the contract,
     * and each stage of the job tracker has its own timestamp.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs');
            $table->foreignId('user_id')->constrained('gig_workers', 'user_id');
            $table->text('message')->nullable();
            $table->enum('stage', [
                'applied', 'accepted', 'completed', 'confirmed', 'rated',
                'withdrawn', 'rejected', 'cancelled',
            ])->default('applied')->index();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rated_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->enum('approval_method', ['manual', 'automatic'])->nullable();

            $table->unique(['job_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
