<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('sgp_cliente_link')->nullable()->after('cliente_telefone');
            $table->unsignedBigInteger('sgp_cliente_id')->nullable()->after('sgp_cliente_link');
            $table->unsignedBigInteger('sgp_contrato_id')->nullable()->after('sgp_cliente_id');
            $table->string('sgp_cpf_cnpj')->nullable()->after('sgp_contrato_id');
            $table->date('sgp_data_nascimento')->nullable()->after('sgp_cpf_cnpj');
            $table->string('sgp_plano')->nullable()->after('sgp_data_nascimento');
            $table->unsignedInteger('sgp_vencimento')->nullable()->after('sgp_plano');
            $table->string('sgp_pppoe_login')->nullable()->after('sgp_vencimento');
            $table->string('sgp_pppoe_senha')->nullable()->after('sgp_pppoe_login');
            $table->string('sgp_endereco')->nullable()->after('sgp_pppoe_senha');
            $table->json('sgp_dados')->nullable()->after('sgp_endereco');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn([
                'sgp_cliente_link',
                'sgp_cliente_id',
                'sgp_contrato_id',
                'sgp_cpf_cnpj',
                'sgp_data_nascimento',
                'sgp_plano',
                'sgp_vencimento',
                'sgp_pppoe_login',
                'sgp_pppoe_senha',
                'sgp_endereco',
                'sgp_dados',
            ]);
        });
    }
};
