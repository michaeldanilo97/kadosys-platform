<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Playback;

/**
 * Controller do modulo Playbacks: biblioteca de audios (backing tracks) do
 * ministerio de louvor - upload, listagem com busca/paginacao, edicao de
 * metadados e remocao.
 */
final class PlaybackController extends Controller
{
    private const PER_PAGE = 15;

    /** Diretorio base dos arquivos - cada igreja/tenant tem sua propria
     * subpasta (ver diretorioTenant()) porque todos os subdominios
     * compartilham o mesmo docroot (so o banco muda por tenant, ver
     * TenantResolver) - sem isso, igrejas diferentes sobrescreveriam os
     * arquivos umas das outras. */
    private const UPLOAD_DIR = 'uploads/playbacks';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/wave' => 'wav',
        'audio/ogg' => 'ogg',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
        'audio/aac' => 'aac',
    ];

    private const TAMANHO_MAXIMO = 25 * 1024 * 1024;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Playback::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.playbacks.index', [
            'pageTitle' => 'Playbacks - KADOSYS Igrejas',
            'activeMenu' => 'playbacks',
            'breadcrumb' => ['Dashboard', 'Playbacks'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'playbacks' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('playback_success'),
            'errors' => Session::flash('playback_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.playbacks.form', [
            'pageTitle' => 'Novo playback - KADOSYS Igrejas',
            'activeMenu' => 'playbacks',
            'breadcrumb' => ['Dashboard', 'Playbacks', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'playback' => null,
            'old' => Session::flash('playback_old') ?? [],
            'errors' => Session::flash('playback_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('playback_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/playbacks/novo');
        }

        $titulo = trim((string) $this->request->input('titulo', ''));

        if ($titulo === '') {
            Session::flash('playback_errors', ['Informe o titulo do playback.']);
            Session::flash('playback_old', ['titulo' => $titulo, 'artista' => $this->request->input('artista', '')]);
            $this->redirect('/dashboard/playbacks/novo');
        }

        $arquivo = $this->request->file('audio');

        if ($arquivo === null) {
            Session::flash('playback_errors', ['Selecione um arquivo de audio.']);
            Session::flash('playback_old', ['titulo' => $titulo, 'artista' => $this->request->input('artista', '')]);
            $this->redirect('/dashboard/playbacks/novo');
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('playback_errors', ['Falha no envio do arquivo. Tente novamente.']);
            $this->redirect('/dashboard/playbacks/novo');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            Session::flash('playback_errors', ['O audio deve ter no maximo 25MB.']);
            $this->redirect('/dashboard/playbacks/novo');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('playback_errors', ['Formato invalido. Envie MP3, WAV, OGG, M4A ou AAC.']);
            $this->redirect('/dashboard/playbacks/novo');
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('playback_errors', ['Nao foi possivel salvar o audio no servidor.']);
            $this->redirect('/dashboard/playbacks/novo');
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('playback_errors', ['Nao foi possivel salvar o audio no servidor.']);
            $this->redirect('/dashboard/playbacks/novo');
        }

        Playback::create([
            'titulo' => $titulo,
            'artista' => $this->request->input('artista', ''),
            'arquivo_path' => self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo,
            'tamanho_bytes' => $arquivo['size'],
            'status' => 'ativo',
        ]);

        Session::flash('playback_success', 'Playback enviado com sucesso.');
        $this->redirect('/dashboard/playbacks');
    }

    public function edit(string $id): void
    {
        $playback = Playback::find((int) $id);

        if (!$playback) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.playbacks.form', [
            'pageTitle' => 'Editar ' . $playback->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'playbacks',
            'breadcrumb' => ['Dashboard', 'Playbacks', $playback->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'playback' => $playback,
            'old' => Session::flash('playback_old') ?? [],
            'errors' => Session::flash('playback_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!Playback::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('playback_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect("/dashboard/playbacks/{$id}/editar");
        }

        $titulo = trim((string) $this->request->input('titulo', ''));

        if ($titulo === '') {
            Session::flash('playback_errors', ['Informe o titulo do playback.']);
            $this->redirect("/dashboard/playbacks/{$id}/editar");
        }

        Playback::update((int) $id, [
            'titulo' => $titulo,
            'artista' => $this->request->input('artista', ''),
            'status' => $this->request->input('status', 'ativo'),
        ]);

        Session::flash('playback_success', 'Playback atualizado com sucesso.');
        $this->redirect('/dashboard/playbacks');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $playback = Playback::find((int) $id);

            if ($playback) {
                $caminhoCompleto = dirname(__DIR__, 2) . '/public/' . $playback->arquivoPath;

                if (is_file($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }

                Playback::delete((int) $id);
            }

            Session::flash('playback_success', 'Playback removido com sucesso.');
        }

        $this->redirect('/dashboard/playbacks');
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
            'pageTitle' => 'Pagina nao encontrada - KADOSYS Igrejas',
            'activeMenu' => 'playbacks',
            'breadcrumb' => ['Dashboard', 'Playbacks', 'Nao encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
