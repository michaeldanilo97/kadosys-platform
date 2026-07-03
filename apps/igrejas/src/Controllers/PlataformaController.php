<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\CpanelUapiClient;
use Igrejas\Core\Desprovisionador;
use Igrejas\Core\Middleware\PlataformaAuthMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\PlataformaAviso;
use Igrejas\Models\Tenant;

/**
 * Painel administrativo da plataforma (dono do sistema) - lista e
 * exclui igrejas provisionadas automaticamente. Autenticacao propria
 * (uma unica chave mestra, ver config/plataforma.php), separada do
 * login normal de cada igreja.
 */
final class PlataformaController extends Controller
{
    public function entrar(): void
    {
        if (Session::get(PlataformaAuthMiddleware::SESSION_KEY) === true) {
            $this->redirect('/plataforma/igrejas');
        }

        echo $this->view('plataforma.entrar', [
            'pageTitle' => 'Painel da Plataforma - KADOSYS',
            'csrf' => Csrf::field(),
            'error' => Session::flash('plataforma_login_error'),
        ], 'auth');
    }

    public function autenticar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('plataforma_login_error', 'Sessao expirada. Tente novamente.');
            $this->redirect('/plataforma/entrar');
        }

        $chave = (string) $this->request->input('chave', '');
        $config = require dirname(__DIR__, 2) . '/config/plataforma.php';

        if ($config['senha_hash'] === '' || !password_verify($chave, $config['senha_hash'])) {
            Session::flash('plataforma_login_error', 'Chave invalida.');
            $this->redirect('/plataforma/entrar');
        }

        Session::regenerate();
        Session::set(PlataformaAuthMiddleware::SESSION_KEY, true);

        $this->redirect('/plataforma/igrejas');
    }

    public function sair(): void
    {
        Session::remove(PlataformaAuthMiddleware::SESSION_KEY);
        $this->redirect('/plataforma/entrar');
    }

    public function igrejas(): void
    {
        echo $this->view('plataforma.igrejas', [
            'pageTitle' => 'Igrejas - Painel da Plataforma',
            'activeMenu' => 'igrejas',
            'igrejas' => Tenant::listarTodas(),
            'success' => Session::flash('plataforma_success'),
            'errors' => Session::flash('plataforma_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'plataforma');
    }

    /**
     * Exclui uma igreja de vez: banco de dados, usuario do banco e
     * subdominio no cPanel, alem do registro central - ver
     * Igrejas\Core\Desprovisionador. Acao irreversivel de proposito
     * sem "lixeira"/soft-delete, entao a confirmacao na tela (ver
     * plataforma/igrejas.php) precisa ser bem clara.
     */
    public function excluirIgreja(string $id): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('plataforma_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/plataforma/igrejas');
        }

        $tenant = Tenant::buscarPorId((int) $id);

        if ($tenant === null) {
            Session::flash('plataforma_errors', ['Igreja nao encontrada.']);
            $this->redirect('/plataforma/igrejas');
        }

        $resultado = (new Desprovisionador(new CpanelUapiClient()))->excluir($tenant);

        if ($resultado['erros'] === []) {
            Session::flash('plataforma_success', array_merge(
                ['"' . $tenant->nomeIgreja . '" foi excluida (banco de dados e usuario removidos do cPanel, registro removido do sistema).'],
                $resultado['avisos']
            ));
        } else {
            Session::flash('plataforma_errors', array_merge(
                ['"' . $tenant->nomeIgreja . '" foi removida do sistema, mas algumas etapas no cPanel falharam - confira manualmente:'],
                $resultado['erros'],
                $resultado['avisos']
            ));
        }

        $this->redirect('/plataforma/igrejas');
    }

    /**
     * Avisos do dono da plataforma pra todas as igrejas cadastradas
     * (ver PlataformaAviso) - mostrados no sino de notificacoes de
     * cada uma delas.
     */
    public function avisos(): void
    {
        echo $this->view('plataforma.avisos', [
            'pageTitle' => 'Avisos - Painel da Plataforma',
            'activeMenu' => 'avisos',
            'avisoAtivo' => PlataformaAviso::ativo(),
            'historico' => PlataformaAviso::todos(),
            'success' => Session::flash('plataforma_success'),
            'errors' => Session::flash('plataforma_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'plataforma');
    }

    public function publicarAviso(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('plataforma_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/plataforma/avisos');
        }

        $mensagem = trim((string) $this->request->input('mensagem', ''));

        if ($mensagem === '') {
            Session::flash('plataforma_errors', ['Escreva a mensagem do aviso.']);
            $this->redirect('/plataforma/avisos');
        }

        PlataformaAviso::publicar($mensagem);

        Session::flash('plataforma_success', ['Aviso publicado - vai aparecer no sino de notificacoes de todas as igrejas.']);
        $this->redirect('/plataforma/avisos');
    }

    public function encerrarAviso(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            PlataformaAviso::encerrar((int) $id);
            Session::flash('plataforma_success', ['Aviso encerrado.']);
        }

        $this->redirect('/plataforma/avisos');
    }
}
