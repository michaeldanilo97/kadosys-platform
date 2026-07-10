<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\BibliaLivro;
use Igrejas\Models\BibliaVersao;
use Igrejas\Models\BibliaVersiculo;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\ProjecaoEstado;
use Igrejas\Models\ProjecaoImagem;
use Igrejas\Models\ProjecaoSessao;

/**
 * Painel de controle do operador (notebook do culto): inicia/encerra a
 * sessao de projecao e exibe o link do telao + PIN do preletor. Os
 * controles de biblia/video propriamente ditos falam diretamente com o
 * ProjecaoEstadoController (endpoints publicos por token), via JS.
 *
 * Tambem gerencia a galeria de imagens (upload/favoritar/excluir) usada
 * na exibicao rapida - diferente dos comandos de exibir/trocar, que sao
 * publicos por token, gerenciar a galeria em si exige login (evita que
 * qualquer um com o link do telao consiga subir arquivos no servidor).
 */
final class ProjecaoController extends Controller
{
    private const UPLOAD_DIR = 'uploads/projecao';

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
            'doacaoPixHabilitada' => ConfiguracaoIgreja::atual()->doacaoPixHabilitada(),
            'imagens' => ProjecaoImagem::todas(),
            'imagensErrors' => Session::flash('projecao_imagens_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function enviarImagem(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('projecao_imagens_errors', ['Sessao expirada. Tente novamente.']);
            $this->redirect('/dashboard/projecao');
        }

        $arquivo = $this->request->file('imagem');

        if ($arquivo === null || $arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('projecao_imagens_errors', ['Selecione uma imagem para enviar.']);
            $this->redirect('/dashboard/projecao');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            Session::flash('projecao_imagens_errors', ['A imagem deve ter no maximo 8MB.']);
            $this->redirect('/dashboard/projecao');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('projecao_imagens_errors', ['Formato invalido. Envie PNG, JPG, WEBP ou GIF.']);
            $this->redirect('/dashboard/projecao');
        }

        $destinoDir = $this->diretorioTenant();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('projecao_imagens_errors', ['Nao foi possivel salvar a imagem no servidor.']);
            $this->redirect('/dashboard/projecao');
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('projecao_imagens_errors', ['Nao foi possivel salvar a imagem no servidor.']);
            $this->redirect('/dashboard/projecao');
        }

        ProjecaoImagem::create(
            (string) ($arquivo['name'] ?? $nomeArquivo),
            self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo
        );

        $this->redirect('/dashboard/projecao');
    }

    public function favoritarImagem(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ProjecaoImagem::alternarFavorita((int) $id);
        }

        $this->redirect('/dashboard/projecao');
    }

    public function excluirImagem(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $imagem = ProjecaoImagem::find((int) $id);

            if ($imagem) {
                $caminhoCompleto = dirname(__DIR__, 2) . '/public/' . $imagem->path;

                if (is_file($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }

                ProjecaoImagem::delete($imagem->id);
            }
        }

        $this->redirect('/dashboard/projecao');
    }

    private function tenantSlugOuCentral(): string
    {
        return TenantResolver::atual()?->slug ?? 'central';
    }

    private function diretorioTenant(): string
    {
        return dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral();
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
