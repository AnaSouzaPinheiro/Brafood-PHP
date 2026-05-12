<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/UpdatingRecipeModel.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../model/ReceitaModel.php';
require_once __DIR__ . '/../service/ReceitaDiffService.php';
require_once __DIR__ . '/../controller/UpdatingRecipeCtl.php';

class UpdatingRecipeCtlTest extends TestCase
{
    private function makeCtl(
        UpdatingRecipeModel $updating,
        ReceitaModel        $receita,
        ReceitaDiffService  $diff
    ): UpdatingRecipeCtl {
        $ctl = new UpdatingRecipeCtl(null);

        $r1 = new ReflectionProperty(UpdatingRecipeCtl::class, 'model');
        $r1->setAccessible(true);
        $r1->setValue($ctl, $updating);

        $r2 = new ReflectionProperty(UpdatingRecipeCtl::class, 'receitaModel');
        $r2->setAccessible(true);
        $r2->setValue($ctl, $receita);

        $r3 = new ReflectionProperty(UpdatingRecipeCtl::class, 'diffService');
        $r3->setAccessible(true);
        $r3->setValue($ctl, $diff);

        return $ctl;
    }

    private function dadosMinimos(int $id = 1): array
    {
        return [
            'id'          => $id,
            'id_usuario'  => 1,
            'titulo'      => 'Bolo editado',
            'resumo'      => 'Resumo',
            'dificuldade' => 'Fácil',
            'tempo_hora'  => 0,
            'tempo_min'   => 30,
            'porcoes'     => 8,
            'tipo_porcao' => 'fatia',
            'ingredientes'=> [],
            'etapas'      => [],
        ];
    }

    public function testEditarReceitaComSucesso(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $receita  = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn(null);
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $diff = $this->createMock(ReceitaDiffService::class);

        $ctl = $this->makeCtl($updating, $receita, $diff);

        $resultado = $ctl->editarReceita($this->dadosMinimos(), []);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Receita editada com sucesso!', $resultado['message']);
        $this->assertEquals(1, $resultado['id']);
    }

    public function testEditarReceitaDelegaDiffAoService(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $receita  = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn('Bolos');
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $diff = $this->createMock(ReceitaDiffService::class);
        $diff->expects($this->once())->method('diffSubcategoria');
        $diff->expects($this->once())->method('diffIngredientes');
        $diff->expects($this->once())->method('diffEtapas');

        $ctl = $this->makeCtl($updating, $receita, $diff);

        $ctl->editarReceita($this->dadosMinimos(), []);
    }

    public function testEditarReceitaLancaExcecaoQuandoUpdateFalha(): void
    {
        $this->expectException(Exception::class);

        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->method('updateReceita')->willThrowException(new Exception('Erro'));

        $receita = $this->createMock(ReceitaModel::class);
        $diff    = $this->createMock(ReceitaDiffService::class);

        $this->makeCtl($updating, $receita, $diff)
             ->editarReceita($this->dadosMinimos(), []);
    }

    public function testEditarReceitaPassaImagensCorretamente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())
                 ->method('updateReceita')
                 ->with(
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     $this->anything(),
                     'img1.jpg',
                     'img2.jpg',
                     null
                 );

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn(null);
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $diff = $this->createMock(ReceitaDiffService::class);

        $this->makeCtl($updating, $receita, $diff)
             ->editarReceita($this->dadosMinimos(), ['imagem1' => 'img1.jpg', 'imagem2' => 'img2.jpg']);
    }
}
