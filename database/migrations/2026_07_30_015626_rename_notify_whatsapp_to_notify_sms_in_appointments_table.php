<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se reemplazó la confirmación de citas por WhatsApp por SMS (vía Brevo),
 * así que la columna que indica si el cliente quiere que se le avise pasa
 * de "notify_whatsapp" a "notify_sms". Se hace con una migración nueva (no
 * editando la migración original) para no romper bases de datos que ya
 * corrieron la migración de `appointments`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('notify_whatsapp', 'notify_sms');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('notify_sms', 'notify_whatsapp');
        });
    }
};
