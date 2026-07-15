<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\PortfolioFoto;
use Barbearias\Models\Profissional;
use Barbearias\Models\User;

final class PortfolioController extends Controller
{
    private const UPLOAD_DIR = 'uploads/portfolio';
    private const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public function upload(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $profissional = Profissional::find((int) $id, $barbeariaId);

        if ($profissional === null) {
            $this->redirect('/dashboard/profissionais');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        $arquivo = $this->request->file('foto');
        $legenda = $this->request->input('legenda');

        if ($arquivo === null || $arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('profissional_errors', ['Escolha uma foto válida pro portfólio.']);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO_FOTO) {
            Session::flash('profissional_errors', ['A foto excede 5MB.']);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('profissional_errors', ['Formato de foto inválido (use PNG, JPG ou WEBP).']);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('profissional_errors', ['Não foi possível salvar a foto agora. Tente de novo.']);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        $nomeArquivo = 'portfolio_' . $id . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('profissional_errors', ['Não foi possível salvar a foto agora. Tente de novo.']);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        PortfolioFoto::create($barbeariaId, (int) $id, self::UPLOAD_DIR . '/' . $nomeArquivo, $legenda);

        Session::flash('profissional_success', 'Foto adicionada ao portfólio.');
        $this->redirect('/dashboard/profissionais/' . $id . '/editar');
    }

    public function destroy(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $foto = PortfolioFoto::find((int) $id, $barbeariaId);

        if ($foto !== null && Csrf::verify($this->request->input('_csrf_token'))) {
            $caminho = dirname(__DIR__, 2) . '/public/' . $foto->fotoPath;

            if (is_file($caminho)) {
                unlink($caminho);
            }

            PortfolioFoto::delete($foto->id, $barbeariaId);
            Session::flash('profissional_success', 'Foto removida do portfólio.');
        }

        $this->redirect('/dashboard/profissionais/' . ($foto?->profissionalId ?? '') . '/editar');
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
