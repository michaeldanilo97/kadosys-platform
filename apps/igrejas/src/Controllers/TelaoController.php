<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Session;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\ProjecaoEstado;
use Igrejas\Models\ProjecaoSessao;

/**
 * Tela publica do telao (projetor). Acesso direto por link com token, ou
 * por PIN de 6 digitos (tela de entrada em /telao) que redireciona pro
 * link com token - sem exigir login administrativo. O PIN e o mesmo
 * usado pelo preletor, ja que ambos dao acesso ao mesmo conteudo (o que
 * esta em projecao), so que o telao e so leitura.
 */
final class TelaoController extends Controller
{
    public function entrar(): void
    {
        echo $this->view('telao.telao-entrar', [
            'pageTitle' => 'Telão - KADOSYS Igrejas',
            'error' => Session::flash('telao_error'),
        ], 'telao');
    }

    public function autenticar(): void
    {
        $pin = trim((string) $this->request->input('pin', ''));
        $sessao = $pin !== '' ? ProjecaoSessao::findAtivaByPin($pin) : null;

        if (!$sessao) {
            Session::flash('telao_error', 'PIN inválido ou sessão de projeção encerrada.');
            $this->redirect('/telao');
        }

        $this->redirect('/telao/' . $sessao->token);
    }

    public function show(string $token): void
    {
        $sessao = ProjecaoSessao::findAtivaByToken($token);

        if (!$sessao) {
            echo $this->view('telao.encerrado', [
                'pageTitle' => 'Projeção encerrada - KADOSYS Igrejas',
            ], 'telao');

            return;
        }

        $configuracao = ConfiguracaoIgreja::atual();
        $estado = ProjecaoEstado::atual($sessao->id);

        echo $this->view('telao.show', [
            'pageTitle' => 'Telão - KADOSYS Igrejas',
            'token' => $token,
            'logoPath' => $configuracao->logoPath,
            'estadoInicial' => $estado?->paraJson(),
        ], 'telao');
    }
}
