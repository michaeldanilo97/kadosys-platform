<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\KidsCrianca;

/**
 * Login publico da criança na Biblioteca Kids: sem e-mail/senha, cada
 * criança entra escolhendo o próprio perfil (foto/nome) e digitando um
 * PIN de 4 dígitos gerado pela equipe (ver
 * KidsCriancaController::gerarPin()) - mesmo padrão de acesso por PIN
 * já usado no Preletor/Telão, só que aqui a "sessão" é a própria
 * criança, não um dispositivo de culto.
 *
 * Só existe PIN pra crianças com um responsável (Membro) vinculado -
 * é a forma mais simples de garantir que alguém autorizado sabe desse
 * acesso e pode revogá-lo a qualquer momento.
 */
final class KidsLoginController extends Controller
{
    public function entrar(): void
    {
        echo $this->view('kids.entrar', [
            'pageTitle' => 'Entrar - KADOSYS Kids',
            'criancas' => KidsCrianca::ativasComPin(),
            'criancaSelecionadaId' => (int) $this->request->input('crianca_id', 0),
            'error' => Session::flash('kids_login_error'),
        ], 'kids-app');
    }

    public function autenticar(): void
    {
        $criancaId = (int) $this->request->input('crianca_id', 0);
        $pin = trim((string) $this->request->input('pin', ''));

        $crianca = $criancaId > 0 ? KidsCrianca::autenticarPorPin($criancaId, $pin) : null;

        if ($crianca === null) {
            Session::flash('kids_login_error', 'PIN incorreto. Peça ajuda a um responsável ou à equipe.');
            $this->redirect('/kids/entrar?crianca_id=' . $criancaId);
        }

        Session::regenerate();
        Session::set(KidsSessaoMiddleware::SESSION_KEY, $crianca->id);
        $this->redirect('/kids');
    }

    public function sair(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Session::remove(KidsSessaoMiddleware::SESSION_KEY);
        }

        $this->redirect('/kids/entrar');
    }
}
