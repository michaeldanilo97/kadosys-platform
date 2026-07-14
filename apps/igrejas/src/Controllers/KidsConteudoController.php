<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\KidsConteudo;

/**
 * Controller do CMS de conteudo do KADOSYS Kids (Kids > Conteúdos):
 * cadastro do que a propria igreja produz (video do pastor, quiz,
 * desafio da semana etc.) - o conteudo oficial da KADOSYS (origem
 * "kadosys", semeado em database/install.sql) aparece na mesma lista
 * mas e somente leitura aqui (ver KidsConteudo::editavel()).
 */
final class KidsConteudoController extends Controller
{
    private const PER_PAGE = 15;

    private const UPLOAD_DIR = 'uploads/kids-conteudos';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO_CAPA = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO_MIDIA = [
        'application/pdf' => 'pdf',
        'audio/mpeg' => 'mp3',
        'video/mp4' => 'mp4',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO = 20 * 1024 * 1024;

    private const FIELDS = [
        'tipo', 'titulo', 'descricao', 'categoria', 'tema', 'livro_biblico', 'personagem',
        'dificuldade', 'idade_min', 'idade_max', 'duracao_minutos', 'xp_recompensa',
        'moedas_recompensa', 'texto_conteudo', 'midia_url', 'status',
    ];

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));
        $tipo = trim((string) $this->request->input('tipo', ''));
        $origem = trim((string) $this->request->input('origem', ''));

        $result = KidsConteudo::paginate($page, self::PER_PAGE, $search, $tipo, $origem);

        echo $this->view('dashboard.kids.conteudos.index', [
            'pageTitle' => 'Conteúdos Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Conteúdos'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'conteudos' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'tipoFiltro' => $tipo,
            'origemFiltro' => $origem,
            'success' => Session::flash('kids_conteudo_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.kids.conteudos.form', [
            'pageTitle' => 'Novo conteúdo Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Conteúdos', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'conteudo' => null,
            'old' => Session::flash('kids_conteudo_old') ?? [],
            'errors' => Session::flash('kids_conteudo_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_conteudo_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/kids/conteudos/novo');
        }

        $data = $this->request->only(self::FIELDS);
        $data['quiz_perguntas'] = $this->extrairQuizPerguntas();
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('kids_conteudo_errors', $errors);
            Session::flash('kids_conteudo_old', $data);
            $this->redirect('/dashboard/kids/conteudos/novo');
        }

        $user = (new Auth($this->config))->user();
        $data['criado_por'] = $user?->id;

        $id = KidsConteudo::create($data);

        $erro = $this->tratarUploads($id);
        if ($erro !== null) {
            Session::flash('kids_conteudo_errors', [$erro]);
        }

        Session::flash('kids_conteudo_success', 'Conteúdo cadastrado com sucesso.');
        $this->redirect('/dashboard/kids/conteudos');
    }

    public function edit(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);

        if (!$conteudo || !$conteudo->editavel()) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.kids.conteudos.form', [
            'pageTitle' => 'Editar ' . $conteudo->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Conteúdos', $conteudo->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'conteudo' => $conteudo,
            'old' => Session::flash('kids_conteudo_old') ?? [],
            'errors' => Session::flash('kids_conteudo_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);

        if (!$conteudo || !$conteudo->editavel()) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_conteudo_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/kids/conteudos/{$id}/editar");
        }

        $data = $this->request->only(self::FIELDS);
        $data['quiz_perguntas'] = $this->extrairQuizPerguntas();
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('kids_conteudo_errors', $errors);
            Session::flash('kids_conteudo_old', $data);
            $this->redirect("/dashboard/kids/conteudos/{$id}/editar");
        }

        KidsConteudo::update((int) $id, $data);

        $erro = $this->tratarUploads((int) $id);
        if ($erro !== null) {
            Session::flash('kids_conteudo_errors', [$erro]);
        }

        Session::flash('kids_conteudo_success', 'Conteúdo atualizado com sucesso.');
        $this->redirect('/dashboard/kids/conteudos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            KidsConteudo::delete((int) $id);
            Session::flash('kids_conteudo_success', 'Conteúdo removido com sucesso.');
        }

        $this->redirect('/dashboard/kids/conteudos');
    }

    /**
     * Monta o array de perguntas do quiz a partir dos campos
     * "quiz_pergunta[]", "quiz_alternativas[]" (uma linha por
     * pergunta, alternativas separadas por "|") e "quiz_correta[]"
     * (indice da alternativa certa) enviados pelo form.
     *
     * @return array<int, array{pergunta: string, alternativas: array<int, string>, correta: int}>
     */
    private function extrairQuizPerguntas(): array
    {
        $perguntas = (array) $this->request->input('quiz_pergunta', []);
        $alternativas = (array) $this->request->input('quiz_alternativas', []);
        $corretas = (array) $this->request->input('quiz_correta', []);

        $resultado = [];
        foreach ($perguntas as $i => $pergunta) {
            $pergunta = trim((string) $pergunta);
            if ($pergunta === '') {
                continue;
            }

            $listaAlternativas = array_values(array_filter(array_map(
                'trim',
                explode('|', (string) ($alternativas[$i] ?? ''))
            ), static fn (string $alt) => $alt !== ''));

            if ($listaAlternativas === []) {
                continue;
            }

            $resultado[] = [
                'pergunta' => $pergunta,
                'alternativas' => $listaAlternativas,
                'correta' => max(0, min(count($listaAlternativas) - 1, (int) ($corretas[$i] ?? 0))),
            ];
        }

        return $resultado;
    }

    private function tratarUploads(int $conteudoId): ?string
    {
        $capa = $this->request->file('capa');
        if ($capa !== null && $capa['error'] !== UPLOAD_ERR_NO_FILE) {
            $erro = $this->salvarArquivo($conteudoId, $capa, self::MIME_PARA_EXTENSAO_CAPA, 'capa');
            if ($erro !== null) {
                return $erro;
            }
        }

        $midia = $this->request->file('midia');
        if ($midia !== null && $midia['error'] !== UPLOAD_ERR_NO_FILE) {
            $erro = $this->salvarArquivo($conteudoId, $midia, self::MIME_PARA_EXTENSAO_MIDIA, 'midia');
            if ($erro !== null) {
                return $erro;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $mimeMap
     */
    private function salvarArquivo(int $conteudoId, array $arquivo, array $mimeMap, string $campo): ?string
    {
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio do arquivo. Tente novamente.';
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            return 'O arquivo deve ter no máximo 20MB.';
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = $mimeMap[$mime] ?? null;

        if ($extensao === null) {
            return 'Formato de arquivo não suportado.';
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            return 'Não foi possível salvar o arquivo no servidor.';
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return 'Não foi possível salvar o arquivo no servidor.';
        }

        $caminhoRelativo = self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo;

        if ($campo === 'capa') {
            KidsConteudo::atualizarCapa($conteudoId, $caminhoRelativo);
        } else {
            KidsConteudo::atualizarMidia($conteudoId, $caminhoRelativo);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['titulo'] ?? '')) === '') {
            $errors[] = 'Informe o título do conteúdo.';
        }

        if (!array_key_exists((string) ($data['tipo'] ?? ''), KidsConteudo::TIPOS)) {
            $errors[] = 'Selecione um tipo de conteúdo válido.';
        }

        $status = $data['status'] ?? 'publicado';
        if (!in_array($status, ['rascunho', 'publicado'], true)) {
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
            'breadcrumb' => ['Dashboard', 'Kids', 'Conteúdos', 'Não encontrado'],
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
