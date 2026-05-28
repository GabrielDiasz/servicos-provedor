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
        $service = new SgpService();
        $method = new ReflectionMethod($service, 'normalizarBusca');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, 'Jhon Cleiton Soares Cavalcante');

        $this->assertSame('JHONCLEITONSOARESCAVALCANTE', $resultado);
    }

    public function test_resolver_motivo_os_mapeia_os_principais_tipos(): void
    {
        $service = new SgpService();
        $method = new ReflectionMethod($service, 'resolverMotivoOs');
        $method->setAccessible(true);

        $ordem = new OrdemServico(['tipo_servico' => 'instalacao']);
        $html = '<select name="motivoos"><option value="7">Instalacao de KIT</option></select>';

        $resultado = $method->invoke($service, $ordem, $html);

        $this->assertSame('7', $resultado);
    }

    public function test_resolver_motivo_os_usa_corretiva_quando_nao_encontra_um_motivo_especifico(): void
    {
        $service = new SgpService();
        $method = new ReflectionMethod($service, 'resolverMotivoOs');
        $method->setAccessible(true);

        $ordem = new OrdemServico(['tipo_servico' => 'reparo']);
        $html = '<select name="motivoos"><option value="12">Corretiva</option></select>';

        $resultado = $method->invoke($service, $ordem, $html);

        $this->assertSame('12', $resultado);
    }

    public function test_resolver_tecnico_responsavel_mapeia_jhon_vanderley_e_teste_para_pablo(): void
    {
        $service = new SgpService();
        $labelMethod = new ReflectionMethod($service, 'resolverTecnicoResponsavelLabel');
        $labelMethod->setAccessible(true);
        $method = new ReflectionMethod($service, 'resolverTecnicoResponsavelSgp');
        $method->setAccessible(true);

        $ordemJhon = new OrdemServico();
        $ordemJhon->setRelation('tecnico', new Tecnico(['nome' => 'Jhon do Laravel']));

        $ordemVanderley = new OrdemServico();
        $ordemVanderley->setRelation('tecnico', new Tecnico(['nome' => 'Vanderley do Laravel']));

        $ordemTeste = new OrdemServico();
        $ordemTeste->setRelation('tecnico', new Tecnico(['nome' => 'Tecnico de Teste']));

        $html = '<select name="responsavel"><option value="21">Jonh cleiton soares cavalcante</option><option value="22">Vanderley</option><option value="28">Pablo Oliveira Bomfim</option></select>';

        $this->assertSame('Jonh cleiton soares cavalcante', $labelMethod->invoke($service, $ordemJhon));
        $this->assertSame('21', $method->invoke($service, $ordemJhon, $html));

        $this->assertSame('Vanderley', $labelMethod->invoke($service, $ordemVanderley));
        $this->assertSame('22', $method->invoke($service, $ordemVanderley, $html));

        $this->assertSame('Pablo Oliveira Bomfim', $labelMethod->invoke($service, $ordemTeste));
        $this->assertSame('28', $method->invoke($service, $ordemTeste, $html));
    }

    public function test_resolver_tecnico_responsavel_retorna_nulo_quando_nao_existe_correspondencia(): void
    {
        $service = new SgpService();
        $labelMethod = new ReflectionMethod($service, 'resolverTecnicoResponsavelLabel');
        $labelMethod->setAccessible(true);
        $method = new ReflectionMethod($service, 'resolverTecnicoResponsavelSgp');
        $method->setAccessible(true);

        $ordem = new OrdemServico();
        $ordem->setRelation('tecnico', new Tecnico(['nome' => 'Outro Tecnico']));

        $html = '<select name="responsavel"><option value="21">Jonh cleiton soares cavalcante</option><option value="22">Vanderley</option></select>';

        $this->assertNull($labelMethod->invoke($service, $ordem));
        $this->assertNull($method->invoke($service, $ordem, $html));
    }

    public function test_resolver_usuario_responsavel_faz_fallback_para_gabrieldias_quando_nao_encontra_correspondencia(): void
    {
        $service = new SgpService();
        $method = new ReflectionMethod($service, 'resolverUsuarioResponsavelSgp');
        $method->setAccessible(true);

        $html = '<select name="usuario_responsavel"><option value="42">gabrieldias</option><option value="77">outro.usuario</option></select>';

        $this->assertSame('42', $method->invoke($service, $html, 'Administrador'));
    }

    public function test_conteudo_ocorrencia_sgp_usa_o_nome_do_servico_quando_nao_ha_observacao(): void
    {
        $service = new SgpService();
        $method = new ReflectionMethod($service, 'conteudoOcorrenciaSgp');
        $method->setAccessible(true);

        $this->assertSame('INSTALAÇÃO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'instalacao', 'observacao' => null])));
        $this->assertSame('REATIVAÇÃO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'reativacao', 'observacao' => null])));
        $this->assertSame('MUDANÇA DE ENDEREÇO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'mudanca_endereco', 'observacao' => null])));
        $this->assertSame('DESCONECTADO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'desconectado', 'observacao' => null])));
        $this->assertSame('OSCILAÇÃO', $method->invoke($service, new OrdemServico(['tipo_servico' => 'reparo', 'observacao' => null])));
    }
}
