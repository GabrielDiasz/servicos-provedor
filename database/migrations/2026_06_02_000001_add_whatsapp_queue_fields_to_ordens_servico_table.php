<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('whatsapp_send_status')->nullable()->after('sgp_sync_error');
            $table->text('whatsapp_send_error')->nullable()->after('whatsapp_send_status');
            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_send_error');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_send_status',
                'whatsapp_send_error',
                'whatsapp_sent_at',
            ]);
        });
    }
};
