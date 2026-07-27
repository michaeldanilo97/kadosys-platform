<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\GaleriaMemoria;

/**
 * Modulo Galeria de Memorias: mural de fotos dos momentos marcantes da
 * igreja (cultos especiais, batismos, eventos, confraternizacoes).
 * Gerenciado pelo painel (upload/exclusao, login exigido) e exibido
 * publicamente em /galeria - sem login, pensado pra ser compartilhado
 * com a congregacao (mesmo espirito do quadro de avisos em /avisos).
 */
final class GaleriaController extends Controller
{
    private const UPLOAD_DIR = 'uploads/galeria';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const TAMANHO_MAXIMO = 8 * 1024 * 1024;

    public function index(): void
    {
        echo $this->view('dashboard.galeria.index', [
            'pageTitle' => 'Galeria de Memórias - KADOSYS Igrejas',
            'activeMenu' => 'galeria',
            'breadcrumb' => ['Dashboard', 'Galeria de Memórias'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'memorias' => GaleriaMemoria::todas(),
            'errors' => Session::flash('galeria_errors') ?? [],
            'success' => Session::flash('galeria_success'),
            'old' => Session::flash('galeria_old') ?? [],
            'csrf' => Csrf::field(),
            'galeriaUrlAbsoluta' => $this->urlAbsoluta('/galeria'),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('galeria_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/galeria');
        }

        $titulo = trim((string) $this->request->input('titulo', ''));
        $legenda = trim((string) $this->request->input('legenda', ''));
        $dataRegistro = trim((string) $this->request->input('data_registro', ''));

        if ($titulo === '') {
            Session::flash('galeria_errors', ['Informe um título para a foto.']);
            Session::flash('galeria_old', ['titulo' => $titulo, 'legenda' => $legenda, 'data_registro' => $dataRegistro]);
            $this->redirect('/dashboard/galeria');
        }

        $arquivo = $this->request->file('foto');

        if ($arquivo === null || $arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('galeria_errors', ['Selecione uma foto para enviar.']);
            Session::flash('galeria_old', ['titulo' => $titulo, 'legenda' => $legenda, 'data_registro' => $dataRegistro]);
            $this->redirect('/dashboard/galeria');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            Session::flash('galeria_errors', ['A foto deve ter no máximo 8MB.']);
            Session::flash('galeria_old', ['titulo' => $titulo, 'legenda' => $legenda, 'data_registro' => $dataRegistro]);
            $this->redirect('/dashboard/galeria');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('galeria_errors', ['Formato inválido. Envie PNG, JPG, WEBP ou GIF.']);
            Session::flash('galeria_old', ['titulo' => $titulo, 'legenda' => $legenda, 'data_registro' => $dataRegistro]);
            $this->redirect('/dashboard/galeria');
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('galeria_errors', ['Não foi possível salvar a foto no servidor.']);
            $this->redirect('/dashboard/galeria');
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('galeria_errors', ['Não foi possível salvar a foto no servidor.']);
            $this->redirect('/dashboard/galeria');
        }

        GaleriaMemoria::create(
            $titulo,
            $legenda,
            $dataRegistro !== '' ? $dataRegistro : null,
            self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo
        );

        Session::flash('galeria_success', 'Foto adicionada à galeria com sucesso.');
        $this->redirect('/dashboard/galeria');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $memoria = GaleriaMemoria::find((int) $id);

            if ($memoria) {
                $caminhoCompleto = dirname(__DIR__, 2) . '/public/' . $memoria->fotoPath;

                if (is_file($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }

                GaleriaMemoria::delete($memoria->id);
                Session::flash('galeria_success', 'Foto removida da galeria.');
            }
        }

        $this->redirect('/dashboard/galeria');
    }

    /**
     * Mural publico das memorias - sem login, pra compartilhar com a
     * congregacao (link no grupo do WhatsApp, QR code no templo etc.).
     */
    public function publica(): void
    {
        $configuracao = ConfiguracaoIgreja::atual();

        echo $this->view('galeria.publica', [
            'pageTitle' => ($configuracao->nomeIgreja ?? 'Igreja') . ' - Galeria de Memórias',
            'nomeIgreja' => $configuracao->nomeIgreja,
            'logoPath' => $configuracao->logoPath,
            'memorias' => GaleriaMemoria::todas(),
        ], 'avisos-publico');
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
