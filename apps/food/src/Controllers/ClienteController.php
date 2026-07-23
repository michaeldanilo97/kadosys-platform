<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Cliente;
use Food\Models\Restaurante;
use Food\Models\User;

final class ClienteController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Cliente::paginate($restauranteId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.clientes.index', [
            'pageTitle' => 'Clientes - KADOSYS Food',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'clientes' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('cliente_success'),
            'errors' => Session::flash('cliente_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.clientes.form', [
            'pageTitle' => 'Novo cliente - KADOSYS Food',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
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

        $dados = $this->dadosDoFormulario();
        [$errors, $aniversario] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('cliente_errors', $errors);
            Session::flash('cliente_old', $dados);
            $this->redirect('/dashboard/clientes/novo');
        }

        Cliente::create(
            $this->restauranteId(),
            (string) $dados['nome'],
            $this->vazioParaNulo((string) ($dados['telefone'] ?? '')),
            $this->vazioParaNulo((string) ($dados['whatsapp'] ?? '')),
            $aniversario,
            $this->vazioParaNulo((string) ($dados['endereco'] ?? '')),
            $this->vazioParaNulo((string) ($dados['observacoes'] ?? '')),
        );

        Session::flash('cliente_success', 'Cliente cadastrado com sucesso.');
        $this->redirect('/dashboard/clientes');
    }

    public function edit(string $id): void
    {
        $cliente = Cliente::find((int) $id, $this->restauranteId());

        if ($cliente === null) {
            $this->redirect('/dashboard/clientes');
        }

        echo $this->view('dashboard.clientes.form', [
            'pageTitle' => 'Editar cliente - KADOSYS Food',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'cliente' => $cliente,
            'old' => Session::flash('cliente_old') ?? [],
            'errors' => Session::flash('cliente_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Cliente::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/clientes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/clientes');
        }

        $dados = $this->dadosDoFormulario();
        [$errors, $aniversario] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('cliente_errors', $errors);
            Session::flash('cliente_old', $dados);
            $this->redirect('/dashboard/clientes/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Cliente::update(
            (int) $id,
            $restauranteId,
            (string) $dados['nome'],
            $this->vazioParaNulo((string) ($dados['telefone'] ?? '')),
            $this->vazioParaNulo((string) ($dados['whatsapp'] ?? '')),
            $aniversario,
            $this->vazioParaNulo((string) ($dados['endereco'] ?? '')),
            $this->vazioParaNulo((string) ($dados['observacoes'] ?? '')),
            $ativo,
        );

        Session::flash('cliente_success', 'Cliente atualizado com sucesso.');
        $this->redirect('/dashboard/clientes');
    }

    public function show(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $cliente = Cliente::find((int) $id, $restauranteId);

        if ($cliente === null) {
            $this->redirect('/dashboard/clientes');
        }

        echo $this->view('dashboard.clientes.show', [
            'pageTitle' => htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') . ' - KADOSYS Food',
            'activeMenu' => 'clientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'cliente' => $cliente,
            'estatisticas' => Cliente::estatisticas($cliente->id, $restauranteId),
        ], 'dashboard');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Cliente::delete((int) $id, $this->restauranteId());
            Session::flash('cliente_success', 'Cliente removido.');
        }

        $this->redirect('/dashboard/clientes');
    }

    /** @return array<string, mixed> */
    private function dadosDoFormulario(): array
    {
        return $this->request->only(['nome', 'telefone', 'whatsapp', 'aniversario', 'endereco', 'observacoes']);
    }

    /** @return array{0: array<int, string>, 1: ?string} */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do cliente.';
        }

        $aniversarioInformado = trim((string) ($dados['aniversario'] ?? ''));
        $aniversario = null;

        if ($aniversarioInformado !== '') {
            $data = \DateTimeImmutable::createFromFormat('Y-m-d', $aniversarioInformado);

            if ($data === false || $data->format('Y-m-d') !== $aniversarioInformado) {
                $errors[] = 'Informe uma data de aniversário válida.';
            } else {
                $aniversario = $aniversarioInformado;
            }
        }

        return [$errors, $aniversario];
    }

    private function vazioParaNulo(string $valor): ?string
    {
        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function restauranteId(): int
    {
        return $this->usuario()?->restauranteId ?? 0;
    }
}
