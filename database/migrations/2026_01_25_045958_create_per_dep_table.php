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
        Schema::create('per_dep', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_persona');
            $table->unsignedBigInteger('id_depa');
            $table->unsignedBigInteger('id_rol');
            $table->boolean('residente')->default(false);
            $table->string('codigo', 10)->nullable();
            $table->date('fecha_inicio')->useCurrent();
            $table->date('fecha_fin')->nullable();
            $table->foreign('id_persona')->references('id')->on('personas')->onDelete('cascade');
            $table->foreign('id_depa')->references('id')->on('departamentos')->onDelete('cascade');
            $table->foreign('id_rol')->references('id')->on('roles')->onDelete('restrict');
            $table->index('id_persona');
            $table->index('id_depa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('per_dep');
    }
};
