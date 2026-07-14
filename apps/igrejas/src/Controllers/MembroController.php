<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Culto;
use Igrejas\Models\Grupo;
use Igrejas\Models\Membro;
use Igrejas\Models\MembroDocumento;
use Igrejas\Models\Ministerio;
use Igrejas\Models\User;

/**
 * Controller do modulo Membros: primeiro modulo de negocio da v2
 * (cadastro, listagem com busca/paginacao, edicao e remocao), com a
 * tela de perfil individual (show) reunindo dados, ministerios/grupos,
 * participacoes em cultos e documentos.
 */
final class MembroController extends Controller
{
    private const PER_PAGE = 15;

    /** Diretorio base dos documentos anexados ao membro - mesmo motivo
     * do UPLOAD_DIR de Playbacks (subpasta por tenant, docroot
     * compartilhado entre subdominios). */
    private const UPLOAD_DIR = 'uploads/membros';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO_DOCUMENTO = 10 * 1024 * 1024;

    private const FIELDS = [
        'nome', 'email', 'telefone', 'data_nascimento', 'genero', 'estado_civil',
        'cpf', 'rg', 'naturalidade', 'endereco', 'cep', 'logradouro', 'numero',
        'complemento', 'bairro', 'cidade', 'estado', 'data_membresia',
        'status', 'observacoes',
    ];

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Membro::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.membros.index', [
            'pageTitle' => 'Membros - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membros' => $result['items'],
            'acessoPorMembroId' => User::cargosPorMembroIds(array_map(static fn (Membro $m) => $m->id, $result['items'])),
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('membro_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    /**
     * Perfil do membro - aberto ao clicar no nome na listagem. Reune
     * dados pessoais/endereco (editaveis nesta mesma tela, sem
     * redirecionar pro form separado), ministerios/grupos, cultos que
     * participou e documentos anexados.
     */
    public function show(string $id): void
    {
        $membro = Membro::find((int) $id);

        if (!$membro) {
            $this->renderNotFound();

            return;
        }

        $ministerios = Ministerio::doMembro($membro->id);
        $grupos = Grupo::doMembro($membro->id);
        $participacoes = Culto::doMembro($membro->id);

        echo $this->view('dashboard.membros.show', [
            'pageTitle' => $membro->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros', $membro->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'membro' => $membro,
            'fotoAcesso' => User::cargosPorMembroIds([$membro->id])[$membro->id]['fotoPath'] ?? null,
            'ministerios' => $ministerios,
            'grupos' => $grupos,
            'participacoes' => $participacoes,
            'documentos' => MembroDocumento::doMembro($membro->id),
            'success' => Session::flash('membro_success'),
            'errors' => Session::flash('membro_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.membros.form', [
            'pageTitle' => 'Novo membro - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'old' => Session::flash('membro_old') ?? [],
            'errors' => Session::flash('membro_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('membro_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/membros/novo');
        }

        $data = $this->request->only(self::FIELDS);
        $errors = $this->validate($data);

        $criarAcesso = !empty($this->request->input('criar_acesso'));
        $senha = (string) $this->request->input('senha', '');
        $senhaConfirmacao = (string) $this->request->input('senha_confirmacao', '');
        $musico = !empty($this->request->input('musico'));
        $liderLouvor = !empty($this->request->input('lider_louvor'));

        if ($criarAcesso) {
            $errors = [...$errors, ...$this->validateAcesso($data, $senha, $senhaConfirmacao)];
        }

        if ($errors !== []) {
            Session::flash('membro_errors', $errors);
            Session::flash('membro_old', $data + [
                'criar_acesso' => $criarAcesso,
                'musico' => $musico,
                'lider_louvor' => $liderLouvor,
            ]);
            $this->redirect('/dashboard/membros/novo');
        }

        $membroId = Membro::create($data);

        if ($criarAcesso) {
            $userId = User::create([
                'name' => trim((string) $data['nome']),
                'email' => trim((string) $data['email']),
                'password' => $senha,
                'role' => User::ROLE_USUARIO,
                'musico' => $musico,
                'lider_louvor' => $liderLouvor,
                'cargo' => $musico ? User::CARGO_MUSICO : User::CARGO_MEMBRO,
            ]);
            User::aplicarPerfilPadrao($userId);
            User::findById($userId)->vincularMembro($membroId);
        }

        Session::flash('membro_success', 'Membro cadastrado com sucesso.' . ($criarAcesso ? ' Acesso ao sistema criado.' : ''));
        $this->redirect('/dashboard/membros');
    }

    public function update(string $id): void
    {
        if (!Membro::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('membro_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        $data = $this->request->only(self::FIELDS);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('membro_errors', $errors);
            $this->redirect("/dashboard/membros/{$id}");
        }

        Membro::update((int) $id, $data);

        Session::flash('membro_success', 'Dados atualizados com sucesso.');
        $this->redirect("/dashboard/membros/{$id}");
    }

    /**
     * Upload de um documento anexado ao membro (aba "Documentos").
     */
    public function documentoStore(string $id): void
    {
        $membro = Membro::find((int) $id);

        if (!$membro) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('membro_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        $arquivo = $this->request->file('documento');

        if ($arquivo === null || $arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('membro_errors', ['Selecione um arquivo para enviar.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO_DOCUMENTO) {
            Session::flash('membro_errors', ['O arquivo deve ter no máximo 10MB.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('membro_errors', ['Formato inválido. Envie PDF, JPG, PNG ou WEBP.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('membro_errors', ['Não foi possível salvar o arquivo no servidor.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('membro_errors', ['Não foi possível salvar o arquivo no servidor.']);
            $this->redirect("/dashboard/membros/{$id}");
        }

        $nomeOriginal = trim((string) $this->request->input('nome', ''));

        MembroDocumento::create(
            membroId: (int) $id,
            nome: $nomeOriginal !== '' ? $nomeOriginal : (string) $arquivo['name'],
            arquivoPath: self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo,
            tamanhoBytes: $arquivo['size'],
        );

        Session::flash('membro_success', 'Documento enviado com sucesso.');
        $this->redirect("/dashboard/membros/{$id}");
    }

    public function documentoDestroy(string $id, string $documentoId): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $documento = MembroDocumento::find((int) $documentoId);

            if ($documento && $documento->membroId === (int) $id) {
                $caminhoCompleto = dirname(__DIR__, 2) . '/public/' . $documento->arquivoPath;

                if (is_file($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }

                MembroDocumento::delete($documento->id);
            }

            Session::flash('membro_success', 'Documento removido com sucesso.');
        }

        $this->redirect("/dashboard/membros/{$id}");
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Membro::delete((int) $id);
            Session::flash('membro_success', 'Membro removido com sucesso.');
        }

        $this->redirect('/dashboard/membros');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome do membro.';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        }

        $status = $data['status'] ?? 'ativo';
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $errors[] = 'Status inválido.';
        }

        return $errors;
    }

    /**
     * Validacao extra so quando o admin marca "criar acesso ao
     * sistema" - o e-mail (ja preenchido acima nos dados do membro)
     * vira o login, entao precisa ser obrigatorio e unico tanto entre
     * membros quanto entre usuarios (ver User::create()).
     *
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validateAcesso(array $data, string $senha, string $senhaConfirmacao): array
    {
        $errors = [];

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            $errors[] = 'Informe o e-mail do membro pra criar o acesso - ele vira o login.';
        } elseif (User::emailEmUso($email)) {
            $errors[] = 'Já existe um usuário com esse e-mail.';
        }

        if (mb_strlen($senha) < 8) {
            $errors[] = 'A senha do acesso precisa ter pelo menos 8 caracteres.';
        } elseif ($senha !== $senhaConfirmacao) {
            $errors[] = 'A confirmação de senha do acesso não confere.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'membros',
            'breadcrumb' => ['Dashboard', 'Membros', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }

    private function tenantSlugOuCentral(): string
    {
        return TenantResolver::atual()?->slug ?? 'central';
    }

    private function diretorioTenant(): string
    {
        return dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral();
    }
}
