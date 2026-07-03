<?php

namespace App\Services;

use Illuminate\Support\Str;

class UpgradeMessageService
{
    public function montarMensagem(string $nomeCliente): string
    {
        $saudacao = $this->saudacaoAtual();
        $nomeCliente = $this->limparNomeCliente($nomeCliente);

        return "{$saudacao}, aqui é Gabriel da GPR Fibra.\n\nFalo com {$nomeCliente}?";
    }

    public function saudacaoAtual(): string
    {
        $hora = now()->hour;

        return match (true) {
            $hora >= 5 && $hora < 12 => 'Bom dia',
            $hora >= 12 && $hora < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
    }

    private function limparNomeCliente(string $nomeCliente): string
    {
        $nomeCliente = preg_replace('/\d+/', '', $nomeCliente) ?? $nomeCliente;

        return Str::squish($nomeCliente);
    }
}
