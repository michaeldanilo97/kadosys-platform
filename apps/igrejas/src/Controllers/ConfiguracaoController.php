<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\ConfiguracaoIgreja;

/**
 * Controller de Configuracoes gerais da igreja.
 *
 * Nesta etapa cobre apenas a logo usada no fadeout da projecao de video
 * (modulo Projecao/Telao). Demais preferencias serao adicionadas conforme
 * o modulo Configuracoes crescer.
 */
final class ConfiguracaoController extends Controller
{
    private const UPLOAD_DIR = 'uploads/logo';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    private const TAMANHO_MAXIMO = 5 * 1024 * 1024;

    public function index(): void
    {
        echo $this->view('dashboard.configuracoes.index', [
            'pageTitle' => 'Configuracoes - KADOSYS Igrejas',
            'activeMenu' => 'configuracoes',
            'breadcrumb' => ['Dashboard', 'Configuracoes'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'configuracao' => ConfiguracaoIgreja::atual(),
            'success' => Session::flash('config_success'),
            'errors' => Session::flash('config_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function atualizarLogo(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('config_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $arquivo = $this->request->file('logo');

        if ($arquivo === null) {
            Session::flash('config_errors', ['Selecione um arquivo de imagem.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('config_errors', ['Falha no envio do arquivo. Tente novamente.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            Session::flash('config_errors', ['A imagem deve ter no maximo 5MB.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('config_errors', ['Formato invalido. Envie PNG, JPG, WEBP ou SVG.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('config_errors', ['Nao foi possivel salvar a imagem no servidor.']);
            $this->redirect('/dashboard/configuracoes');
        }

        $this->removerArquivosLogo($destinoDir);

        $nomeArquivo = 'logo.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('config_errors', ['Nao foi possivel salvar a imagem no servidor.']);
            $this->redirect('/dashboard/configuracoes');
        }

        ConfiguracaoIgreja::atualizarLogo(self::UPLOAD_DIR . '/' . $nomeArquivo);

        Session::flash('config_success', 'Logo atualizada com sucesso.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function removerLogo(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $this->removerArquivosLogo(dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR);
            ConfiguracaoIgreja::removerLogo();
            Session::flash('config_success', 'Logo removida.');
        }

        $this->redirect('/dashboard/configuracoes');
    }

    private function removerArquivosLogo(string $destinoDir): void
    {
        foreach (glob($destinoDir . '/logo.*') ?: [] as $arquivoAntigo) {
            unlink($arquivoAntigo);
        }
    }
}
