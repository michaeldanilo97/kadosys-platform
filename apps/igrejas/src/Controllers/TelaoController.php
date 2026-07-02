<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\ProjecaoEstado;
use Igrejas\Models\ProjecaoSessao;

/**
 * Tela publica do telao (projetor). Acesso direto por link com token,
 * sem exigir login administrativo - o link e aberto uma vez pelo
 * operador no computador ligado ao projetor.
 */
final class TelaoController extends Controller
{
    public function show(string $token): void
    {
        $sessao = ProjecaoSessao::findAtivaByToken($token);

        if (!$sessao) {
            echo $this->view('telao.encerrado', [
                'pageTitle' => 'Projecao encerrada - KADOSYS Igrejas',
            ], 'telao');

            return;
        }

        $configuracao = ConfiguracaoIgreja::atual();
        $estado = ProjecaoEstado::atual($sessao->id);

        echo $this->view('telao.show', [
            'pageTitle' => 'Telao - KADOSYS Igrejas',
            'token' => $token,
            'logoPath' => $configuracao->logoPath,
            'estadoInicial' => $estado?->paraJson(),
        ], 'telao');
    }
}
