<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('barber_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->dateTime('date_time');
            $table->unsignedSmallInteger('duration'); // copiado del servicio al agendar
            $table->enum('status', ['pendiente', 'confirmada', 'pospuesta', 'completada', 'cancelada'])
                ->default('pendiente');
            $table->boolean('pay_online')->default(false);
            $table->boolean('notify_whatsapp')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
