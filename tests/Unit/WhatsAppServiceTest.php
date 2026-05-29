<?php

namespace Tests\Unit;

use App\Models\OrdemServico;
use App\Services\WhatsAppService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WhatsAppServiceTest extends TestCase
{
    public function test_velocidade_kbps_para_plano_50m(): void
    {
        $service = new WhatsAppService;
        $method = new ReflectionMethod($service, 'velocidadeKbps');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, '50M');

        $this->assertSame('52200', $resultado);
    }

    public function test_telefone_principal_prefere_o_numero_salvo_na_os(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'cliente_telefone' => '(73) 99999-9999',
            'sgp_dados' => [
                'contatos' => [
                    'celulares' => ['(73) 98888-8888'],
                    'telefones' => ['(73) 97777-7777'],
                ],
            ],
        ]);

        $method = new ReflectionMethod($service, 'telefonePrincipal');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $ordem);

        $this->assertSame('73999999999', $resultado);
    }

    public function test_mensagem_nao_duplica_observacao_quando_ela_repete_o_tipo_do_servico(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'troca_senha',
            'observacao' => 'troca de senha',
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('TROCA DE SENHA'."\n", $mensagens[0]);
        $this->assertStringNotContainsString(' - troca de senha', $mensagens[0]);
    }

    public function test_mensagem_mantem_observacao_quando_ela_traz_informacao_diferente(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'troca_senha',
            'observacao' => 'cliente pediu nova senha',
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringContainsString('TROCA DE SENHA - cliente pediu nova senha', $mensagens[0]);
    }

    public function test_instalacao_sem_observacao_usa_o_nome_do_servico_na_primeira_linha(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'instalacao',
            'observacao' => null,
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('INSTALAÇÃO'."\n", $mensagens[0]);
        $this->assertStringNotContainsString(' - ', $mensagens[0]);
    }

    public function test_reativacao_sem_observacao_usa_o_nome_do_servico_na_primeira_linha(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'reativacao',
            'observacao' => null,
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('REATIVAÇÃO'."\n", $mensagens[0]);
        $this->assertStringNotContainsString(' - ', $mensagens[0]);
    }

    public function test_desconectado_sem_observacao_usa_o_nome_do_servico_na_primeira_linha(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'desconectado',
            'observacao' => null,
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('DESCONECTADO'."\n", $mensagens[0]);
        $this->assertStringNotContainsString(' - ', $mensagens[0]);
    }

    public function test_mudanca_de_endereco_sem_observacao_usa_o_nome_do_servico_na_primeira_linha(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'mudanca_endereco',
            'observacao' => null,
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                        'onu' => [
                            'splitter' => [
                                'nome' => 'CA 13',
                                'porta' => '8',
                            ],
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('MUDANÇA DE ENDEREÇO'."\n", $mensagens[0]);
        $this->assertStringNotContainsString(' - ', $mensagens[0]);
        $this->assertTrue(in_array('CTO: CA 13 Porta: 8', $mensagens, true));
    }

    public function test_reparo_sem_observacao_usa_oscilacao_na_primeira_linha(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'reparo',
            'observacao' => null,
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('REPARO - OSCILAÇÃO'."\n", $mensagens[0]);
    }

    public function test_troca_de_senha_sem_observacao_usa_o_nome_do_servico_na_primeira_linha(): void
    {
        $service = new WhatsAppService;

        $ordem = new OrdemServico([
            'tipo_servico' => 'troca_senha',
            'observacao' => null,
            'cliente_telefone' => '73998638320',
            'sgp_pppoe_login' => '2020',
            'sgp_pppoe_senha' => 'senha',
            'sgp_dados' => [
                'contratos' => [[
                    'servicos' => [[
                        'endereco' => [
                            'logradouro' => 'RUA DURVAL SOUZA FILHO',
                            'numero' => '19',
                            'bairro' => 'TAPERA',
                        ],
                    ]],
                ]],
            ],
        ]);

        $method = new ReflectionMethod($service, 'mensagensOrdemServico');
        $method->setAccessible(true);

        $mensagens = $method->invoke($service, $ordem);

        $this->assertStringStartsWith('TROCA DE SENHA'."\n", $mensagens[0]);
        $this->assertStringNotContainsString(' - ', $mensagens[0]);
    }
}
