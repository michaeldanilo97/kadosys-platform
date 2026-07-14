<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Csrf;
use Igrejas\Core\Controller;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\User;

/**
 * Autoatendimento: o proprio usuario logado edita SEU PROPRIO perfil
 * (foto, cargo e instrumento) - usado pela tela Equipe (ver
 * EquipeController). Deliberadamente separado do UsuarioController
 * (que so admin acessa e mexe em role/active/senha de QUALQUER
 * usuario) - aqui so cargo/instrumento/foto do proprio usuario, sem
 * checagem de User::MODULOS_SOMENTE_ADMIN.
 */
final class PerfilController extends Controller
{
    private const UPLOAD_DIR = 'uploads/perfil';

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO = 5 * 1024 * 1024;

    public function editar(): void
    {
        $user = (new Auth($this->config))->user();

        echo $this->view('dashboard.perfil', [
            'pageTitle' => 'Meu perfil - KADOSYS Igrejas',
            'activeMenu' => 'perfil',
            'breadcrumb' => ['Dashboard', 'Meu perfil'],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'success' => Session::flash('perfil_success'),
            'errors' => Session::flash('perfil_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function atualizar(): void
    {
        $user = (new Auth($this->config))->user();

        if ($user === null) {
            $this->redirect('/login');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('perfil_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/perfil');
        }

        $cargo = (string) $this->request->input('cargo', User::CARGO_MEMBRO);
        $instrumento = (string) $this->request->input('instrumento', '');

        $user->atualizarCargo($cargo, $instrumento);

        $arquivo = $this->request->file('foto');

        if ($arquivo !== null && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
            $erro = $this->salvarFoto($user, $arquivo);

            if ($erro !== null) {
                Session::flash('perfil_errors', [$erro]);
                $this->redirect('/dashboard/perfil');
            }
        }

        Session::flash('perfil_success', 'Perfil atualizado com sucesso.');
        $this->redirect('/dashboard/perfil');
    }

    /**
     * @param array{tmp_name: string, size: int, error: int} $arquivo
     */
    private function salvarFoto(User $user, array $arquivo): ?string
    {
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio da foto. Tente novamente.';
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            return 'A foto deve ter no máximo 5MB.';
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            return 'Formato inválido. Envie PNG, JPG ou WEBP.';
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral();

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            return 'Não foi possível salvar a foto no servidor.';
        }

        $fotoAntiga = $user->fotoPath !== null ? dirname(__DIR__, 2) . '/public/' . $user->fotoPath : null;

        $nomeArquivo = 'user-' . $user->id . '-' . bin2hex(random_bytes(8)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return 'Não foi possível salvar a foto no servidor.';
        }

        $user->atualizarFoto(self::UPLOAD_DIR . '/' . $this->tenantSlugOuCentral() . '/' . $nomeArquivo);

        if ($fotoAntiga !== null && is_file($fotoAntiga)) {
            unlink($fotoAntiga);
        }

        return null;
    }

    private function tenantSlugOuCentral(): string
    {
        return TenantResolver::atual()?->slug ?? 'central';
    }
}
