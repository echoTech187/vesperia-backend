<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->onDelete('cascade');
            $table->string('field_id')->index();
            $table->jsonb('raw_answer')->nullable();
            $table->text('text_value')->nullable();
            $table->decimal('numeric_value',18,2)->nullable()->index();
            $table->date('date_value')->nullable()->index();
            $table->jsonb('supporting_files')->nullable();
            $table->timestamps();

            $table->foreign('field_id')
            ->references('id')
            ->on('fields')
            ->onDelete('cascade');

            $table->index(['submission_id', 'field_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
