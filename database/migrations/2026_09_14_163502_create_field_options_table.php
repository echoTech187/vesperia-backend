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
        Schema::create('field_options', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('field_id')->index();
            $table->string('label');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('field_id')
            ->references('id')
            ->on('fields')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('field_options', function (Blueprint $table) {
            $table->dropForeign(['field_id']);
        });
        Schema::dropIfExists('field_options');
    }
};
