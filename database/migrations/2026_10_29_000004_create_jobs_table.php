<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JOB table. Category is an ENUM on the job (decided 29 Oct 2026),
     * so there is no CATEGORY table.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('contract_entities', 'user_id');
            $table->enum('category', [
                'food_and_beverage', 'retail', 'event', 'delivery', 'tutoring',
                'cleaning', 'design_digital', 'data_entry_admin', 'other',
            ])->index();
            $table->foreignId('location_id')->constrained('locations');
            $table->string('title', 120);
            $table->text('description');
            $table->decimal('pay_rate', 8, 2)->index();
            $table->enum('pay_type', ['hourly', 'daily', 'fixed']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'in_progress', 'hidden', 'closed'])->default('open');
            $table->timestamps();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
