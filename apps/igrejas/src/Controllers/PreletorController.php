<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Session;
use Igrejas\Models\BibliaLivro;
use Igrejas\Models\BibliaVersao;
use Igrejas\Models\ProjecaoSessao;

/**
 * Tela publica do preletor (tablet do pastor): acesso por PIN de 6
 * digitos (sem senha administrativa), mostra o mesmo conteudo do telao
 * e permite trocar a referencia biblica projetada.
 */
final class PreletorController extends Controller
{
    private const SESSION_KEY = '_projecao_dispositivo_sessao_id';

    public function entrar(): void
    {
        echo $this->view('telao.preletor-entrar', [
            'pageTitle' => 'Preletor - KADOSYS Igrejas',
            'error' => Session::flash('preletor_error'),
        ], 'telao');
    }

    public function autenticar(): void
    {
        $pin = trim((string) $this->request->input('pin', ''));
        $sessao = $pin !== '' ? ProjecaoSessao::findAtivaByPin($pin) : null;

        if (!$sessao) {
            Session::flash('preletor_error', 'PIN invalido ou sessao de projecao encerrada.');
            $this->redirect('/preletor');
        }

        Session::set(self::SESSION_KEY, $sessao->id);
        $this->redirect('/preletor/painel');
    }

    public function painel(): void
    {
        $sessao = $this->sessaoAutorizada();

        if (!$sessao) {
            $this->redirect('/preletor');
        }

        echo $this->view('telao.preletor-painel', [
            'pageTitle' => 'Preletor - KADOSYS Igrejas',
            'token' => $sessao->token,
            'livros' => BibliaLivro::all(),
            'versoes' => BibliaVersao::todas(),
        ], 'telao');
    }

    public function sair(): void
    {
        Session::remove(self::SESSION_KEY);
        $this->redirect('/preletor');
    }

    private function sessaoAutorizada(): ?ProjecaoSessao
    {
        $sessaoId = Session::get(self::SESSION_KEY);

        if ($sessaoId === null) {
            return null;
        }

        $sessao = ProjecaoSessao::find((int) $sessaoId);

        return ($sessao && $sessao->ativo) ? $sessao : null;
    }
}
