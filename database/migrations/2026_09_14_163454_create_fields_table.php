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
        Schema::create('fields', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('parent_id')->nullable()->index();
            $table->string('label');
            $table->string('type')->index();
            $table->string('sub_type')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('orm_only',10)->default('no');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
