<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\KidsCheckin;
use Igrejas\Models\KidsCrianca;
use Igrejas\Models\KidsTurma;
use Igrejas\Models\Membro;

/**
 * Controller do modulo KADOSYS Kids > Crianças: cadastro, listagem com
 * busca/paginacao, perfil individual (dados de segurança + histórico
 * de check-in + gamificação) e foto - mesma estrutura do
 * MembroController.
 */
final class KidsCriancaController extends Controller
{
    private const PER_PAGE = 15;

    /** Mesmo motivo do UPLOAD_DIR de Membros/Perfil (subpasta por tenant). */
    private const UPLOAD_DIR = 'uploads/kids';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024;

    private const FIELDS = [
        'nome', 'data_nascimento', 'genero', 'turma_id', 'responsavel_membro_id',
        'responsavel_nome', 'responsavel_telefone', 'autorizados_retirada',
        'alergias', 'observacoes_medicas', 'observacoes', 'status',
    ];

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = KidsCrianca::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.kids.criancas.index', [
            'pageTitle' => 'Crianças Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Crianças'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'criancas' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('kids_crianca_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function show(string $id): void
    {
        $crianca = KidsCrianca::find((int) $id);

        if (!$crianca) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.kids.criancas.show', [
            'pageTitle' => $crianca->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Crianças', $crianca->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'crianca' => $crianca,
            'checkinAberto' => KidsCheckin::criancaComCheckinAbertoHoje($crianca->id),
            'historicoCheckins' => KidsCheckin::doCrianca($crianca->id),
            'success' => Session::flash('kids_crianca_success'),
            'errors' => Session::flash('kids_crianca_errors') ?? [],
            'pinGerado' => Session::flash('kids_crianca_pin_gerado'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.kids.criancas.form', [
            'pageTitle' => 'Nova criança - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Crianças', 'Nova'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'crianca' => null,
            'turmasAtivas' => KidsTurma::allActive(),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('kids_crianca_old') ?? [],
            'errors' => Session::flash('kids_crianca_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_crianca_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/kids/criancas/novo');
        }

        $data = $this->request->only(self::FIELDS);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('kids_crianca_errors', $errors);
            Session::flash('kids_crianca_old', $data);
            $this->redirect('/dashboard/kids/criancas/novo');
        }

        $id = KidsCrianca::create($data);

        $erroFoto = $this->tratarUploadFoto($id);
        if ($erroFoto !== null) {
            Session::flash('kids_crianca_errors', [$erroFoto]);
        }

        Session::flash('kids_crianca_success', 'Criança cadastrada com sucesso.');
        $this->redirect("/dashboard/kids/criancas/{$id}");
    }

    public function update(string $id): void
    {
        if (!KidsCrianca::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_crianca_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/kids/criancas/{$id}/editar");
        }

        $data = $this->request->only(self::FIELDS);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('kids_crianca_errors', $errors);
            Session::flash('kids_crianca_old', $data);
            $this->redirect("/dashboard/kids/criancas/{$id}/editar");
        }

        KidsCrianca::update((int) $id, $data);

        $erroFoto = $this->tratarUploadFoto((int) $id);
        if ($erroFoto !== null) {
            Session::flash('kids_crianca_errors', [$erroFoto]);
        }

        Session::flash('kids_crianca_success', 'Dados atualizados com sucesso.');
        $this->redirect("/dashboard/kids/criancas/{$id}");
    }

    public function edit(string $id): void
    {
        $crianca = KidsCrianca::find((int) $id);

        if (!$crianca) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.kids.criancas.form', [
            'pageTitle' => 'Editar ' . $crianca->nome . ' - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Crianças', $crianca->nome],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'crianca' => $crianca,
            'turmasAtivas' => KidsTurma::allActive(),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('kids_crianca_old') ?? [],
            'errors' => Session::flash('kids_crianca_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            KidsCrianca::delete((int) $id);
            Session::flash('kids_crianca_success', 'Criança removida com sucesso.');
        }

        $this->redirect('/dashboard/kids/criancas');
    }

    /**
     * Gera (ou renova) o PIN de login da criança na Biblioteca - só
     * funciona se já houver um responsável vinculado (ver
     * KidsCrianca::gerarESalvarPin()), que serve como o consentimento
     * mínimo antes de liberar esse acesso independente.
     */
    public function gerarPin(string $id): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_crianca_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/kids/criancas/{$id}");
        }

        $pin = KidsCrianca::gerarESalvarPin((int) $id);

        if ($pin === null) {
            Session::flash('kids_crianca_errors', ['Vincule um responsável (Membro) antes de gerar o PIN da criança.']);
        } else {
            Session::flash('kids_crianca_pin_gerado', $pin);
            Session::flash('kids_crianca_success', 'PIN gerado com sucesso. Entregue este código ao responsável.');
        }

        $this->redirect("/dashboard/kids/criancas/{$id}");
    }

    public function removerPin(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            KidsCrianca::removerPin((int) $id);
            Session::flash('kids_crianca_success', 'PIN removido. A criança não conseguirá mais entrar sozinha até um novo PIN ser gerado.');
        }

        $this->redirect("/dashboard/kids/criancas/{$id}");
    }

    private function tratarUploadFoto(int $criancaId): ?string
    {
        $arquivo = $this->request->file('foto');

        if ($arquivo === null || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio da foto. Tente novamente.';
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO_FOTO) {
            return 'A foto deve ter no máximo 5MB.';
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            return 'Formato inválido. Envie JPG, PNG ou WEBP.';
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            return 'Não foi possível salvar a foto no servidor.';
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return 'Não foi possível salvar a foto no servidor.';
        }

        KidsCrianca::atualizarFoto($criancaId, self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo);

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['nome'] ?? '')) === '') {
            $errors[] = 'Informe o nome da criança.';
        }

        $turmaId = trim((string) ($data['turma_id'] ?? ''));
        if ($turmaId !== '' && KidsTurma::find((int) $turmaId) === null) {
            $errors[] = 'Turma selecionada inválida.';
        }

        $responsavelMembroId = trim((string) ($data['responsavel_membro_id'] ?? ''));
        if ($responsavelMembroId !== '' && Membro::find((int) $responsavelMembroId) === null) {
            $errors[] = 'Responsável selecionado inválido.';
        }

        if ($responsavelMembroId === '' && trim((string) ($data['responsavel_nome'] ?? '')) === '') {
            $errors[] = 'Informe o responsável pela criança (selecione um membro ou digite o nome).';
        }

        $status = $data['status'] ?? 'ativo';
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $errors[] = 'Status inválido.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Crianças', 'Não encontrado'],
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
