<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\Professor;
use Academias\Models\User;

final class ProfessorController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Professor::paginate($academiaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.professores.index', [
            'pageTitle' => 'Professores - KADOSYS Academias',
            'activeMenu' => 'professores',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'professores' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('professor_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.professores.form', [
            'pageTitle' => 'Novo professor - KADOSYS Academias',
            'activeMenu' => 'professores',
            'user' => $this->usuario(),
            'academia' => Academia::find($this->academiaId()),
            'professor' => null,
            'old' => Session::flash('professor_old') ?? [],
            'errors' => Session::flash('professor_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/professores');
        }

        $dados = $this->request->only(['nome', 'email', 'telefone', 'especialidade']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('professor_errors', $errors);
            Session::flash('professor_old', $dados);
            $this->redirect('/dashboard/professores/novo');
        }

        Professor::create($this->academiaId(), (string) $dados['nome'], $dados['email'], $dados['telefone'], $dados['especialidade']);

        Session::flash('professor_success', 'Professor cadastrado com sucesso.');
        $this->redirect('/dashboard/professores');
    }

    public function edit(string $id): void
    {
        $professor = Professor::find((int) $id, $this->academiaId());

        if ($professor === null) {
            $this->redirect('/dashboard/professores');
        }

        echo $this->view('dashboard.professores.form', [
            'pageTitle' => 'Editar professor - KADOSYS Academias',
            'activeMenu' => 'professores',
            'user' => $this->usuario(),
            'academia' => Academia::find($this->academiaId()),
            'professor' => $professor,
            'old' => Session::flash('professor_old') ?? [],
            'errors' => Session::flash('professor_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $academiaId = $this->academiaId();

        if (Professor::find((int) $id, $academiaId) === null) {
            $this->redirect('/dashboard/professores');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/professores');
        }

        $dados = $this->request->only(['nome', 'email', 'telefone', 'especialidade', 'ativo']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('professor_errors', $errors);
            Session::flash('professor_old', $dados);
            $this->redirect('/dashboard/professores/' . $id . '/editar');
        }

        Professor::update(
            (int) $id,
            $academiaId,
            (string) $dados['nome'],
            $dados['email'],
            $dados['telefone'],
            $dados['especialidade'],
            (bool) ($dados['ativo'] ?? false),
        );

        Session::flash('professor_success', 'Professor atualizado com sucesso.');
        $this->redirect('/dashboard/professores');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Professor::delete((int) $id, $this->academiaId());
            Session::flash('professor_success', 'Professor removido.');
        }

        $this->redirect('/dashboard/professores');
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do professor.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido (ou deixe em branco).';
        }

        return $errors;
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function academiaId(): int
    {
        return $this->usuario()?->academiaId ?? 0;
    }
}
