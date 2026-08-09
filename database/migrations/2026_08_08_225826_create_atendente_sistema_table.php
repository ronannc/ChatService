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
        Schema::create('atendente_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atendente_id')->constrained('atendentes');
            $table->string('sistema_id');
            $table->foreign('sistema_id')->references('codigo')->on('sistemas');
            $table->timestamps();

            $table->unique(['atendente_id', 'sistema_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atendente_sistema');
    }
};
