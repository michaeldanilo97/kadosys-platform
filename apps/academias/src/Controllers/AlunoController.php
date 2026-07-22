<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Documento;
use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\PlanoMatricula;
use Academias\Models\User;
use Academias\Core\Session;

final class AlunoController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Aluno::paginate($academiaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.alunos.index', [
            'pageTitle' => 'Alunos - KADOSYS Academias',
            'activeMenu' => 'alunos',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'alunos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('aluno_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.alunos.form', [
            'pageTitle' => 'Novo aluno - KADOSYS Academias',
            'activeMenu' => 'alunos',
            'user' => $this->usuario(),
            'academia' => Academia::find($this->academiaId()),
            'aluno' => null,
            'planosMatricula' => PlanoMatricula::ativos($this->academiaId()),
            'old' => Session::flash('aluno_old') ?? [],
            'errors' => Session::flash('aluno_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/alunos');
        }

        $dados = $this->request->only([
            'nome', 'telefone', 'email', 'cpf', 'data_nascimento', 'plano_matricula_id',
            'matricula_inicio', 'matricula_vencimento', 'objetivo', 'observacoes_saude',
        ]);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('aluno_errors', $errors);
            Session::flash('aluno_old', $dados);
            $this->redirect('/dashboard/alunos/novo');
        }

        $cpf = trim((string) ($dados['cpf'] ?? ''));
        $planoId = trim((string) ($dados['plano_matricula_id'] ?? ''));

        Aluno::create(
            $this->academiaId(),
            (string) $dados['nome'],
            $dados['telefone'],
            $dados['email'],
            $cpf !== '' ? Documento::apenasDigitos($cpf) : null,
            $dados['data_nascimento'],
            $planoId !== '' ? (int) $planoId : null,
            $dados['matricula_inicio'],
            $dados['matricula_vencimento'],
            $dados['objetivo'],
            $dados['observacoes_saude'],
        );

        Session::flash('aluno_success', 'Aluno cadastrado com sucesso.');
        $this->redirect('/dashboard/alunos');
    }

    public function edit(string $id): void
    {
        $aluno = Aluno::find((int) $id, $this->academiaId());

        if ($aluno === null) {
            $this->redirect('/dashboard/alunos');
        }

        echo $this->view('dashboard.alunos.form', [
            'pageTitle' => 'Editar aluno - KADOSYS Academias',
            'activeMenu' => 'alunos',
            'user' => $this->usuario(),
            'academia' => Academia::find($this->academiaId()),
            'aluno' => $aluno,
            'planosMatricula' => PlanoMatricula::ativos($this->academiaId()),
            'old' => Session::flash('aluno_old') ?? [],
            'errors' => Session::flash('aluno_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $academiaId = $this->academiaId();

        if (Aluno::find((int) $id, $academiaId) === null) {
            $this->redirect('/dashboard/alunos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/alunos');
        }

        $dados = $this->request->only([
            'nome', 'telefone', 'email', 'cpf', 'data_nascimento', 'plano_matricula_id',
            'matricula_inicio', 'matricula_vencimento', 'status', 'objetivo', 'observacoes_saude',
        ]);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('aluno_errors', $errors);
            Session::flash('aluno_old', $dados);
            $this->redirect('/dashboard/alunos/' . $id . '/editar');
        }

        $cpf = trim((string) ($dados['cpf'] ?? ''));
        $planoId = trim((string) ($dados['plano_matricula_id'] ?? ''));

        Aluno::update(
            (int) $id,
            $academiaId,
            (string) $dados['nome'],
            $dados['telefone'],
            $dados['email'],
            $cpf !== '' ? Documento::apenasDigitos($cpf) : null,
            $dados['data_nascimento'],
            $planoId !== '' ? (int) $planoId : null,
            $dados['matricula_inicio'],
            $dados['matricula_vencimento'],
            (string) ($dados['status'] ?? Aluno::STATUS_ATIVO),
            $dados['objetivo'],
            $dados['observacoes_saude'],
        );

        Session::flash('aluno_success', 'Aluno atualizado com sucesso.');
        $this->redirect('/dashboard/alunos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Aluno::delete((int) $id, $this->academiaId());
            Session::flash('aluno_success', 'Aluno removido.');
        }

        $this->redirect('/dashboard/alunos');
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do aluno.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido (ou deixe em branco).';
        }

        $telefone = trim((string) ($dados['telefone'] ?? ''));
        $cpf = trim((string) ($dados['cpf'] ?? ''));

        if ($cpf !== '' && !Documento::validarCpf($cpf)) {
            $errors[] = 'Informe um CPF válido (ou deixe em branco).';
        }

        if ($telefone === '' && $email === '' && $cpf === '') {
            $errors[] = 'Informe pelo menos um contato: telefone, e-mail ou CPF.';
        }

        $dataNascimento = trim((string) ($dados['data_nascimento'] ?? ''));

        if ($dataNascimento !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascimento) || $dataNascimento > (new \DateTimeImmutable())->format('Y-m-d'))) {
            $errors[] = 'Informe uma data de nascimento válida (ou deixe em branco).';
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
