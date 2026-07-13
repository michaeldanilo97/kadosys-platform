<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Louvor;
use Igrejas\Models\LouvorTomHistorico;
use Igrejas\Models\Playback;

/**
 * Controller do modulo Louvores: letras, cifras e tons dos louvores do
 * ministerio de louvor, com historico de mudancas de tom - so acessivel
 * por 'admin' ou usuarios marcados como musico (ver
 * User::MODULOS_SOMENTE_MUSICO / AuthMiddleware::bloquearSePermissaoNegada).
 */
final class LouvorController extends Controller
{
    private const PER_PAGE = 15;

    private const UPLOAD_DIR = 'uploads/louvores';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'application/pdf' => 'pdf',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO = 8 * 1024 * 1024;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Louvor::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.louvores.index', [
            'pageTitle' => 'Louvores - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'louvores' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('louvor_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function show(string $id): void
    {
        $louvor = Louvor::find((int) $id);

        if (!$louvor) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.louvores.show', [
            'pageTitle' => $louvor->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', $louvor->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'louvor' => $louvor,
            'historico' => LouvorTomHistorico::doLouvor($louvor->id),
        ], 'dashboard');
    }

    /**
     * Tela cheia sem distração (letra/cifra grandes, auto-scroll,
     * exportar PDF) - pensada pra ser aberta numa segunda tela (tablet
     * do músico, monitor no palco), sem o menu/sidebar do painel.
     */
    public function telaCheia(string $id): void
    {
        $louvor = Louvor::find((int) $id);

        if (!$louvor) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.louvores.tela-cheia', [
            'pageTitle' => $louvor->titulo . ' - KADOSYS Igrejas',
            'louvor' => $louvor,
        ], 'tela-cheia');
    }

    public function create(): void
    {
        echo $this->view('dashboard.louvores.form', [
            'pageTitle' => 'Novo louvor - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'louvor' => null,
            'playbacks' => Playback::listaParaSelect(),
            'old' => Session::flash('louvor_old') ?? [],
            'errors' => Session::flash('louvor_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('louvor_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/louvores/novo');
        }

        $data = $this->request->only(['titulo', 'letra', 'tom_atual', 'andamento_bpm', 'cifra', 'playback_id']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('louvor_errors', $errors);
            Session::flash('louvor_old', $data);
            $this->redirect('/dashboard/louvores/novo');
        }

        $anexo = $this->processarAnexoEnviado('/dashboard/louvores/novo', $data);

        if ($anexo !== null) {
            $data['anexo_path'] = $anexo['path'];
            $data['anexo_nome_original'] = $anexo['nome'];
        }

        $userId = (new Auth($this->config))->user()?->id;
        Louvor::create($data, $userId);

        Session::flash('louvor_success', 'Louvor cadastrado com sucesso.');
        $this->redirect('/dashboard/louvores');
    }

    public function edit(string $id): void
    {
        $louvor = Louvor::find((int) $id);

        if (!$louvor) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.louvores.form', [
            'pageTitle' => 'Editar ' . $louvor->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', $louvor->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'louvor' => $louvor,
            'playbacks' => Playback::listaParaSelect(),
            'old' => Session::flash('louvor_old') ?? [],
            'errors' => Session::flash('louvor_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $louvor = Louvor::find((int) $id);

        if (!$louvor) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('louvor_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/louvores/{$id}/editar");
        }

        $data = $this->request->only(['titulo', 'letra', 'tom_atual', 'andamento_bpm', 'tom_observacao', 'cifra', 'playback_id', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('louvor_errors', $errors);
            Session::flash('louvor_old', $data);
            $this->redirect("/dashboard/louvores/{$id}/editar");
        }

        $anexo = $this->processarAnexoEnviado("/dashboard/louvores/{$id}/editar", $data);

        if ($anexo !== null) {
            $this->removerArquivoAnexo($louvor);
            $data['anexo_path'] = $anexo['path'];
            $data['anexo_nome_original'] = $anexo['nome'];
        } elseif (!empty($this->request->input('remover_anexo'))) {
            $this->removerArquivoAnexo($louvor);
            $data['anexo_path'] = null;
            $data['anexo_nome_original'] = null;
        } else {
            $data['anexo_path'] = $louvor->anexoPath;
            $data['anexo_nome_original'] = $louvor->anexoNomeOriginal;
        }

        $userId = (new Auth($this->config))->user()?->id;
        Louvor::update((int) $id, $data, $userId);

        Session::flash('louvor_success', 'Louvor atualizado com sucesso.');
        $this->redirect('/dashboard/louvores');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $louvor = Louvor::find((int) $id);

            if ($louvor) {
                $this->removerArquivoAnexo($louvor);
                Louvor::delete((int) $id);
            }

            Session::flash('louvor_success', 'Louvor removido com sucesso.');
        }

        $this->redirect('/dashboard/louvores');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['titulo'] ?? '')) === '') {
            $errors[] = 'Informe o título do louvor.';
        }

        return $errors;
    }

    /**
     * Trata o upload opcional do anexo (PDF/imagem da cifra) - retorna
     * null quando nenhum arquivo foi enviado (nao e erro, o anexo e
     * opcional), ou o path/nome salvo quando o upload deu certo.
     * Redireciona (interrompendo a execucao, ver Controller::redirect())
     * se um arquivo foi enviado mas e invalido.
     *
     * @param array<string, mixed> $data
     * @return array{path: string, nome: string}|null
     */
    private function processarAnexoEnviado(string $redirectPath, array $data): ?array
    {
        $arquivo = $this->request->file('anexo');

        if ($arquivo === null) {
            return null;
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('louvor_errors', ['Falha no envio do anexo. Tente novamente.']);
            Session::flash('louvor_old', $data);
            $this->redirect($redirectPath);
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            Session::flash('louvor_errors', ['O anexo deve ter no máximo 8MB.']);
            Session::flash('louvor_old', $data);
            $this->redirect($redirectPath);
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('louvor_errors', ['Formato inválido. Envie PDF, PNG, JPG ou WEBP.']);
            Session::flash('louvor_old', $data);
            $this->redirect($redirectPath);
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('louvor_errors', ['Não foi possível salvar o anexo no servidor.']);
            Session::flash('louvor_old', $data);
            $this->redirect($redirectPath);
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('louvor_errors', ['Não foi possível salvar o anexo no servidor.']);
            Session::flash('louvor_old', $data);
            $this->redirect($redirectPath);
        }

        return [
            'path' => self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo,
            'nome' => (string) ($arquivo['name'] ?? $nomeArquivo),
        ];
    }

    private function removerArquivoAnexo(Louvor $louvor): void
    {
        if ($louvor->anexoPath === null) {
            return;
        }

        $caminhoCompleto = dirname(__DIR__, 2) . '/public/' . $louvor->anexoPath;

        if (is_file($caminhoCompleto)) {
            unlink($caminhoCompleto);
        }
    }

    private function tenantSlugOuCentral(): string
    {
        return TenantResolver::atual()?->slug ?? 'central';
    }

    private function diretorioTenant(): string
    {
        return dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral();
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
