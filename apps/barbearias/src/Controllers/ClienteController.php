<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Documento;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\User;

final class ClienteController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Cliente::paginate($barbeariaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.clientes.index', [
            'pageTitle' => 'Clientes - KADOSYS Barbearias',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'clientes' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('cliente_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.clientes.form', [
            'pageTitle' => 'Novo cliente - KADOSYS Barbearias',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'cliente' => null,
            'old' => Session::flash('cliente_old') ?? [],
            'errors' => Session::flash('cliente_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/clientes');
        }

        $dados = $this->request->only(['nome', 'telefone', 'email', 'cpf', 'data_nascimento']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('cliente_errors', $errors);
            Session::flash('cliente_old', $dados);
            $this->redirect('/dashboard/clientes/novo');
        }

        $cpf = trim((string) ($dados['cpf'] ?? ''));

        Cliente::create($this->barbeariaId(), (string) $dados['nome'], $dados['telefone'], $dados['email'], $dados['data_nascimento'], $cpf !== '' ? Documento::apenasDigitos($cpf) : null);

        Session::flash('cliente_success', 'Cliente cadastrado com sucesso.');
        $this->redirect('/dashboard/clientes');
    }

    public function edit(string $id): void
    {
        $cliente = Cliente::find((int) $id, $this->barbeariaId());

        if ($cliente === null) {
            $this->redirect('/dashboard/clientes');
        }

        echo $this->view('dashboard.clientes.form', [
            'pageTitle' => 'Editar cliente - KADOSYS Barbearias',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'cliente' => $cliente,
            'old' => Session::flash('cliente_old') ?? [],
            'errors' => Session::flash('cliente_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Cliente::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/clientes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/clientes');
        }

        $dados = $this->request->only(['nome', 'telefone', 'email', 'cpf', 'data_nascimento']);
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('cliente_errors', $errors);
            Session::flash('cliente_old', $dados);
            $this->redirect('/dashboard/clientes/' . $id . '/editar');
        }

        $cpf = trim((string) ($dados['cpf'] ?? ''));

        Cliente::update((int) $id, $barbeariaId, (string) $dados['nome'], $dados['telefone'], $dados['email'], $dados['data_nascimento'], $cpf !== '' ? Documento::apenasDigitos($cpf) : null);

        Session::flash('cliente_success', 'Cliente atualizado com sucesso.');
        $this->redirect('/dashboard/clientes');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Cliente::delete((int) $id, $this->barbeariaId());
            Session::flash('cliente_success', 'Cliente removido.');
        }

        $this->redirect('/dashboard/clientes');
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do cliente.';
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
            $errors[] = 'Informe pelo menos um contato: telefone, e-mail ou CPF - é o que identifica o cliente pra vincular pontos e histórico.';
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

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
