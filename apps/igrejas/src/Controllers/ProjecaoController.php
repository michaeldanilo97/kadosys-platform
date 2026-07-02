<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\BibliaLivro;
use Igrejas\Models\BibliaVersao;
use Igrejas\Models\BibliaVersiculo;
use Igrejas\Models\ProjecaoEstado;
use Igrejas\Models\ProjecaoSessao;

/**
 * Painel de controle do operador (notebook do culto): inicia/encerra a
 * sessao de projecao e exibe o link do telao + PIN do preletor. Os
 * controles de biblia/video propriamente ditos falam diretamente com o
 * ProjecaoEstadoController (endpoints publicos por token), via JS.
 */
final class ProjecaoController extends Controller
{
    public function index(): void
    {
        $sessao = ProjecaoSessao::ativa();
        $estado = $sessao ? ProjecaoEstado::atual($sessao->id) : null;

        echo $this->view('dashboard.projecao.index', [
            'pageTitle' => 'Projecao - KADOSYS Igrejas',
            'activeMenu' => 'projecao',
            'breadcrumb' => ['Dashboard', 'Projecao'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'sessao' => $sessao,
            'estado' => $estado,
            'livros' => BibliaLivro::all(),
            'versoes' => BibliaVersao::todas(),
            'bibliaImportada' => BibliaVersiculo::textoImportado(),
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function iniciar(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $userId = (new Auth($this->config))->user()?->id;
            ProjecaoSessao::iniciar($userId);
        }

        $this->redirect('/dashboard/projecao');
    }

    public function encerrar(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $sessao = ProjecaoSessao::ativa();

            if ($sessao) {
                ProjecaoSessao::encerrar($sessao->id);
            }
        }

        $this->redirect('/dashboard/projecao');
    }
}
