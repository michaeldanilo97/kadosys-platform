<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\ComunicacaoAviso;

/**
 * Controller do modulo Comunicacao: avisos e comunicados centralizados
 * para membros e lideranca, com listagem com busca e paginacao, edicao
 * e remocao. O envio efetivo (e-mail/SMS/push) fica pra uma proxima
 * etapa - por enquanto e um mural centralizado dentro do painel.
 */
final class ComunicacaoController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = ComunicacaoAviso::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.comunicacao.index', [
            'pageTitle' => 'Comunicacao - KADOSYS Igrejas',
            'activeMenu' => 'comunicacao',
            'breadcrumb' => ['Dashboard', 'Comunicacao'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'avisos' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('comunicacao_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    /**
     * Pagina de detalhe de um aviso - abrir aqui marca automaticamente
     * como lido pro usuario logado (tira o indicador de "novo" na
     * lista da barra lateral, ver ComunicacaoAviso::paraSidebar()).
     */
    public function show(string $id): void
    {
        $aviso = ComunicacaoAviso::find((int) $id);

        if (!$aviso) {
            $this->renderNotFound();

            return;
        }

        $user = (new Auth($this->config))->user();

        if ($user !== null) {
            ComunicacaoAviso::marcarComoLido($aviso->id, $user->id);
        }

        echo $this->view('dashboard.comunicacao.show', [
            'pageTitle' => $aviso->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'comunicacao',
            'breadcrumb' => ['Dashboard', 'Comunicacao', $aviso->titulo],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'aviso' => $aviso,
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.comunicacao.form', [
            'pageTitle' => 'Novo aviso - KADOSYS Igrejas',
            'activeMenu' => 'comunicacao',
            'breadcrumb' => ['Dashboard', 'Comunicacao', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'aviso' => null,
            'old' => Session::flash('comunicacao_old') ?? [],
            'errors' => Session::flash('comunicacao_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('comunicacao_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/comunicacao/novo');
        }

        $data = $this->request->only(['titulo', 'conteudo', 'publico_alvo', 'status', 'data_publicacao']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('comunicacao_errors', $errors);
            Session::flash('comunicacao_old', $data);
            $this->redirect('/dashboard/comunicacao/novo');
        }

        ComunicacaoAviso::create($data);

        Session::flash('comunicacao_success', 'Aviso cadastrado com sucesso.');
        $this->redirect('/dashboard/comunicacao');
    }

    public function edit(string $id): void
    {
        $aviso = ComunicacaoAviso::find((int) $id);

        if (!$aviso) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.comunicacao.form', [
            'pageTitle' => 'Editar ' . $aviso->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'comunicacao',
            'breadcrumb' => ['Dashboard', 'Comunicacao', $aviso->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'aviso' => $aviso,
            'old' => Session::flash('comunicacao_old') ?? [],
            'errors' => Session::flash('comunicacao_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!ComunicacaoAviso::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('comunicacao_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect("/dashboard/comunicacao/{$id}/editar");
        }

        $data = $this->request->only(['titulo', 'conteudo', 'publico_alvo', 'status', 'data_publicacao']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('comunicacao_errors', $errors);
            Session::flash('comunicacao_old', $data);
            $this->redirect("/dashboard/comunicacao/{$id}/editar");
        }

        ComunicacaoAviso::update((int) $id, $data);

        Session::flash('comunicacao_success', 'Aviso atualizado com sucesso.');
        $this->redirect('/dashboard/comunicacao');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ComunicacaoAviso::delete((int) $id);
            Session::flash('comunicacao_success', 'Aviso removido com sucesso.');
        }

        $this->redirect('/dashboard/comunicacao');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['titulo'] ?? '')) === '') {
            $errors[] = 'Informe o titulo do aviso.';
        }

        if (trim((string) ($data['conteudo'] ?? '')) === '') {
            $errors[] = 'Informe o conteudo do aviso.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
            'activeMenu' => 'comunicacao',
            'breadcrumb' => ['Dashboard', 'Comunicacao', 'Nao encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
