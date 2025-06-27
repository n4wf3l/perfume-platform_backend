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
        Schema::table('products', function (Blueprint $table) {
        $table->text('olfactive_notes')->nullable();
        $table->string('gender')->nullable(); // e.g. 'male', 'female', 'unisex'
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['olfactive_notes', 'gender']);
    });
    }
};
