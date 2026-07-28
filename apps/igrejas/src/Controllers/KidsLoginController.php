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
 *
 * Igrejas com mais de uma turma passam primeiro por uma escolha de
 * turma antes da grade de fotos - sem isso, uma igreja com dezenas de
 * crianças cadastradas vira uma grade enorme pra rolar até achar a
 * própria foto. Com uma turma só (ou nenhuma configurada), esse passo
 * é pulado e o comportamento continua igual a antes.
 */
final class KidsLoginController extends Controller
{
    private const SEM_TURMA = 'sem-turma';

    public function entrar(): void
    {
        $criancaId = (int) $this->request->input('crianca_id', 0);
        $turmaParam = $this->request->input('turma_id');

        if ($criancaId > 0) {
            $this->mostrarEtapaPin($criancaId, $turmaParam);

            return;
        }

        $turmas = KidsCrianca::turmasComPinParaLogin();

        if (count($turmas) > 1 && $turmaParam === null) {
            echo $this->view('kids.entrar', [
                'pageTitle' => 'Entrar - KADOSYS Kids',
                'etapa' => 'turma',
                'turmas' => $turmas,
                'semTurmaSlug' => self::SEM_TURMA,
            ], 'kids-app');

            return;
        }

        $criancas = count($turmas) > 1
            ? KidsCrianca::ativasComPinDaTurma($this->turmaIdFiltro($turmaParam))
            : KidsCrianca::ativasComPin();

        echo $this->view('kids.entrar', [
            'pageTitle' => 'Entrar - KADOSYS Kids',
            'etapa' => 'perfil',
            'criancas' => $criancas,
            'turmaParam' => $turmaParam,
            'mostrarVoltarTurmas' => count($turmas) > 1,
            'error' => Session::flash('kids_login_error'),
        ], 'kids-app');
    }

    public function autenticar(): void
    {
        $criancaId = (int) $this->request->input('crianca_id', 0);
        $turmaParam = $this->request->input('turma_id');
        $pin = trim((string) $this->request->input('pin', ''));

        $crianca = $criancaId > 0 ? KidsCrianca::autenticarPorPin($criancaId, $pin) : null;

        if ($crianca === null) {
            Session::flash('kids_login_error', 'PIN incorreto. Peça ajuda a um responsável ou à equipe.');
            $this->redirect($this->urlEntrar($criancaId, $turmaParam));
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

    private function mostrarEtapaPin(int $criancaId, ?string $turmaParam): void
    {
        $crianca = KidsCrianca::find($criancaId);

        if ($crianca === null || $crianca->status !== 'ativo' || $crianca->pinHash === null) {
            $this->redirect('/kids/entrar');
        }

        echo $this->view('kids.entrar', [
            'pageTitle' => 'Entrar - KADOSYS Kids',
            'etapa' => 'pin',
            'crianca' => $crianca,
            'turmaParam' => $turmaParam,
            'voltarUrl' => $this->urlEntrar(null, $turmaParam),
            'error' => Session::flash('kids_login_error'),
        ], 'kids-app');
    }

    private function turmaIdFiltro(?string $turmaParam): ?int
    {
        if ($turmaParam === null || $turmaParam === self::SEM_TURMA) {
            return null;
        }

        return (int) $turmaParam;
    }

    private function urlEntrar(?int $criancaId, ?string $turmaParam): string
    {
        $params = [];

        if ($criancaId !== null && $criancaId > 0) {
            $params['crianca_id'] = (string) $criancaId;
        }

        if ($turmaParam !== null) {
            $params['turma_id'] = $turmaParam;
        }

        return '/kids/entrar' . ($params === [] ? '' : '?' . http_build_query($params));
    }
}
