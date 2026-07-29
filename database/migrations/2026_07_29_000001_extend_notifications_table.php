<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la tabla de notificaciones para dar soporte al sistema completo:
 *
 *  - channel pasa de enum('email','sms','whatsapp') a string, para poder
 *    guardar también notificaciones internas de la app (channel = 'app').
 *  - type: categoría del evento (nueva_cita, cita_cancelada, etc.), útil
 *    para mostrar el ícono/color correcto en el frontend.
 *  - read_at: marca de tiempo de lectura; null = no leída.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Antes era un enum; lo volvemos string para admitir 'app'.
            $table->string('channel', 20)->default('app')->change();

            $table->string('type', 40)->nullable()->after('channel');
            $table->timestamp('read_at')->nullable()->after('status');

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read_at']);
            $table->dropColumn(['type', 'read_at']);
            $table->enum('channel', ['email', 'sms', 'whatsapp'])->change();
        });
    }
};
