<?php

namespace Tests\Unit;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Services\SgpService;
use ReflectionMethod;
use Tests\TestCase;

class SgpServiceTest extends TestCase
{
    public function test_normalizar_busca_remove_espacos_e_acentos(): void
    {
        $service = new SgpService;
        $method = new ReflectionMethod($service, 'normalizarBusca');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, 'Jhon Cleiton Soares Cavalcante');

        $this->assertSame('JHONCLEITONSOARESCAVALCANTE', $resultado);
    }

    public function test_resolver_motivo_os_mapeia_os_principais_tipos(): void
    {
        $service = new SgpService;
        $method = new ReflectionMethod($service, 'resolverMotivoOs');
        $method->setAccessible(true);

        $ordem = new OrdemServico(['tipo_servico' => 'instalacao']);
        $html = '<select name="motivoos"><option value="7">Instalacao de KIT</option></select>';

        $resultado = $method->invoke($service, $ordem, $html);

        $this->assertSame('7', $resultado);
    }

    public function test_resolver_motivo_os_usa_corretiva_quando_nao_encontra_um_motivo_especifico(): void
    {
        $service = new SgpService;
        $method = new ReflectionMethod($service, 'resolverMotivoOs');
        $method->setAccessible(true);

        $ordem = new OrdemServico(['tipo_servico' => 'reparo']);
        $html = '<select name="motivoos"><option value="12">Corretiva</option></select>';

        $resultado = $method->invoke($service, $ordem, $html);

        $this->assertSame('12', $resultado);
    }

    public function test_resolver_tecnico_responsavel_mapeia_substrings_configuradas_para_opcoes_no_sgp(): void
    {
        config()->set('services.sgp.tecnico_responsavel_map', [
            'alpha' => 'Responsavel A',
            'beta' => 'Responsavel B',
            'teste' => 'Responsavel C',
        ]);

        $service = new SgpService;
        $labelMethod = new ReflectionMethod($service, 'resolverTecnicoResponsavelLabel');
        $labelMethod->setAccessible(true);
        $method = new ReflectionMethod($service, 'resolverTecnicoResponsavelSgp');
        $method->setAccessible(true);

        $ordemAlpha = new OrdemServico;
        $ordemAlpha->setRelation('tecnico', new Tecnico(['nome' => 'Tecnico Alpha']));

        $ordemBeta = new OrdemServico;
        $ordemBeta->setRelation('tecnico', new Tecnico(['nome' => 'Tecnico Beta']));

        $ordemTeste = new OrdemServico;
        $ordemTeste->setRelation('tecnico', new Tecnico(['nome' => 'Tecnico de Teste']));

        $html = '<select name="responsavel"><option value="21">Responsavel A</option><option value="22">Responsavel B</option><option value="28">Responsavel C</option></select>';

        $this->assertSame('Responsavel A', $labelMethod->invoke($service, $ordemAlpha));
        $this->assertSame('21', $method->invoke($service, $ordemAlpha, $html));

        $this->assertSame('Responsavel B', $labelMethod->invoke($service, $ordemBeta));
        $this->assertSame('22', $method->invoke($service, $ordemBeta, $html));

        $this->assertSame('Responsavel C', $labelMethod->invoke($service, $ordemTeste));
        $this->assertSame('28', $method->invoke($service, $ordemTeste, $html));
    }

    public function test_resolver_tecnico_responsavel_usa_o_responsavel_padrao_quando_nao_existe_correspondencia(): void
    {
        config()->set('services.sgp.default_responsavel', 'Responsavel Padrao');

        $service = new SgpService;
        $labelMethod = new ReflectionMethod($service, 'resolverTecnicoResponsavelLabel');
        $labelMethod->setAccessible(true);
        $method = new ReflectionMethod($service, 'resolverTecnicoResponsavelSgp');
        $method->setAccessible(true);

        $ordem = new OrdemServico;
        $ordem->setRelation('tecnico', new Tecnico(['nome' => 'Outro Tecnico']));

        $html = '<select name="responsavel"><option value="21">Responsavel A</option><option value="22">Responsavel B</option></select>';

        $this->assertSame('Responsavel Padrao', $labelMethod->invoke($service, $ordem));
        $this->assertSame('21', $method->invoke($service, $ordem, $html));
    }

    public function test_resolver_usuario_responsavel_usa_o_responsavel_padrao_configurado_quando_nao_encontra_correspondencia(): void
    {
        config()->set('services.sgp.default_responsavel', 'usuario.padrao');

        $service = new SgpService;
        $method = new ReflectionMethod($service, 'resolverUsuarioResponsavelSgp');
        $method->setAccessible(true);

        $html = '<select name="usuario_responsavel"><option value="42">usuario.padrao</option><option value="77">outro.usuario</option></select>';

        $this->assertSame('42', $method->invoke($service, $html, 'Administrador'));
    }

    public function test_resolver_usuario_responsavel_usa_o_mapeamento_do_atendente_logado(): void
    {
        config()->set('services.sgp.responsavel_usuario_map', [
            [
                'matchers' => ['Pablo Bomfim', 'pablo@gpr.local'],
                'responsaveis' => ['Pablo', 'Pablo Bomfim'],
            ],
            [
                'matchers' => ['Paulo Henrique', 'paulo@gpr.local'],
                'responsaveis' => ['paulo', 'Paulo Henrique'],
            ],
        ]);

        $service = new SgpService;
        $method = new ReflectionMethod($service, 'resolverUsuarioResponsavelSgp');
        $method->setAccessible(true);

        $html = '<select name="usuario_responsavel"><option value="42">Pablo</option><option value="77">paulo</option><option value="88">Outro</option></select>';

        $this->assertSame('42', $method->invoke($service, $html, 'Pablo Bomfim', 'pablo@gpr.local'));
        $this->assertSame('77', $method->invoke($service, $html, 'Paulo Henrique', 'paulo@gpr.local'));
    }

    public function test_conteudo_ocorrencia_sgp_usa_o_nome_do_servico_quando_nao_ha_observacao(): void
    {
        $service = new SgpService;
        $method = new ReflectionMethod($service, 'conteudoOcorrenciaSgp');
        $method->setAccessible(true);

        $this->assertSame('INSTALAÇÃO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'instalacao', 'observacao' => null])));
        $this->assertSame('REATIVAÇÃO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'reativacao', 'observacao' => null])));
        $this->assertSame('MUDANÇA DE ENDEREÇO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'mudanca_endereco', 'observacao' => null])));
        $this->assertSame('TROCA DE SENHA', $method->invoke($service, new OrdemServico(['tipo_servico' => 'troca_senha', 'observacao' => null])));
        $this->assertSame('DESCONECTADO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'desconectado', 'observacao' => null])));
        $this->assertSame('OSCILAÇÃO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'reparo', 'observacao' => null])));
    }
}
