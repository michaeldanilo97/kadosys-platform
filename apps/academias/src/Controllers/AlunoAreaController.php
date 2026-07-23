<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\AlunoAuth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\AcademiaCheckin;
use Academias\Models\Aluno;

/**
 * Area do aluno (/minha-conta/{slug}) - login proprio do aluno,
 * separado do painel administrativo da equipe (ver
 * Academias\Core\AlunoAuth). Nesta Fase 3, o painel mostra so o que
 * envolve check-in (status atual, historico, streak/pontos) - treino
 * do dia, evolucao de carga e avaliacao fisica entram nas fases
 * seguintes, ampliando esta mesma tela.
 */
final class AlunoAreaController extends Controller
{
    public function painel(string $slug): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        $aluno = (new AlunoAuth($this->config))->user($academia->id);

        if ($aluno === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        $checkinAberto = AcademiaCheckin::emAberto($academia->id, $aluno->id);
        $historico = AcademiaCheckin::doAluno($academia->id, $aluno->id, 15);

        echo $this->view('public.minha-conta.painel', [
            'pageTitle' => 'Minha conta - ' . $academia->nome,
            'academia' => $academia,
            'aluno' => $aluno,
            'checkinAberto' => $checkinAberto,
            'historico' => $historico,
            'csrf' => Csrf::field(),
        ], 'site');
    }

    public function showEntrar(string $slug): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        if ((new AlunoAuth($this->config))->check($academia->id)) {
            $this->redirect($this->destinoAposLogin($slug));
        }

        echo $this->view('public.minha-conta.entrar', [
            'pageTitle' => 'Entrar - ' . $academia->nome,
            'academia' => $academia,
            'csrf' => Csrf::field(),
            'errors' => Session::flash('aluno_login_errors') ?? [],
            'old' => Session::flash('aluno_login_old') ?? [],
            'next' => (string) $this->request->input('next', ''),
        ], 'site');
    }

    public function entrar(string $slug): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        $identificador = trim((string) $this->request->input('identificador', ''));
        $senha = (string) $this->request->input('senha', '');
        $next = (string) $this->request->input('next', '');

        if (!(new AlunoAuth($this->config))->attempt($academia->id, $identificador, $senha)) {
            Session::flash('aluno_login_errors', ['Telefone/e-mail ou senha incorretos.']);
            Session::flash('aluno_login_old', ['identificador' => $identificador]);
            $this->redirect('/minha-conta/' . $slug . '/entrar' . ($next !== '' ? '?next=' . urlencode($next) : ''));
        }

        $this->redirect($this->destinoAposLogin($slug, $next));
    }

    public function showCadastro(string $slug): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        if ((new AlunoAuth($this->config))->check($academia->id)) {
            $this->redirect('/minha-conta/' . $slug);
        }

        echo $this->view('public.minha-conta.cadastro', [
            'pageTitle' => 'Criar acesso - ' . $academia->nome,
            'academia' => $academia,
            'csrf' => Csrf::field(),
            'errors' => Session::flash('aluno_cadastro_errors') ?? [],
            'old' => Session::flash('aluno_cadastro_old') ?? [],
            'next' => (string) $this->request->input('next', ''),
        ], 'site');
    }

    /**
     * "Reivindica" o cadastro que a academia ja criou pra esse aluno
     * (ver Academias\Controllers\AlunoController) - o aluno so precisa
     * confirmar o telefone/e-mail que a equipe cadastrou e escolher uma
     * senha. Nao cria um aluno novo: quem faz isso e sempre a equipe,
     * pelo painel administrativo.
     */
    public function cadastro(string $slug): void
    {
        $academia = Academia::findBySlug($slug);

        if ($academia === null) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug . '/cadastro');
        }

        $identificador = trim((string) $this->request->input('identificador', ''));
        $senha = (string) $this->request->input('senha', '');
        $senhaConfirmacao = (string) $this->request->input('senha_confirmacao', '');
        $next = (string) $this->request->input('next', '');
        $errors = [];

        if ($identificador === '') {
            $errors[] = 'Informe o telefone ou e-mail cadastrado pela sua academia.';
        }

        if (mb_strlen($senha) < 8) {
            $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif ($senha !== $senhaConfirmacao) {
            $errors[] = 'A confirmação de senha não confere.';
        }

        $aluno = $identificador !== '' ? Aluno::buscarPorIdentificador($academia->id, $identificador) : null;

        if ($identificador !== '' && $aluno === null) {
            $errors[] = 'Não encontramos esse telefone/e-mail cadastrado. Fale com a recepção da academia.';
        } elseif ($aluno !== null && $aluno->temSenha()) {
            $errors[] = 'Esse cadastro já tem acesso criado. Faça login em vez de criar um novo.';
        }

        if ($errors !== []) {
            Session::flash('aluno_cadastro_errors', $errors);
            Session::flash('aluno_cadastro_old', ['identificador' => $identificador]);
            $this->redirect('/minha-conta/' . $slug . '/cadastro');
        }

        Aluno::definirSenha($aluno->id, $academia->id, $senha);
        (new AlunoAuth($this->config))->login(Aluno::find($aluno->id, $academia->id));

        $this->redirect($this->destinoAposLogin($slug, $next));
    }

    public function sair(string $slug): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            (new AlunoAuth($this->config))->logout();
        }

        $this->redirect('/minha-conta/' . $slug . '/entrar');
    }

    /**
     * So um redirect relativo dentro do proprio dominio e aceito (nunca
     * segue "next" pra fora, pra nao virar um open redirect).
     */
    private function destinoAposLogin(string $slug, string $next = ''): string
    {
        if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
            return $next;
        }

        return '/minha-conta/' . $slug;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada'], 'site');
    }
}
