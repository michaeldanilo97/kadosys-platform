<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Servico;
use Barbearias\Models\User;

final class ServicoController extends Controller
{
    private const POR_PAGINA = 15;

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Servico::paginate($barbeariaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.servicos.index', [
            'pageTitle' => 'Serviços - KADOSYS Barbearias',
            'activeMenu' => 'servicos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'servicos' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('servico_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.servicos.form', [
            'pageTitle' => 'Novo serviço - KADOSYS Barbearias',
            'activeMenu' => 'servicos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'servico' => null,
            'old' => Session::flash('servico_old') ?? [],
            'errors' => Session::flash('servico_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/servicos');
        }

        $dados = $this->request->only(['nome', 'duracao_minutos', 'preco']);
        [$errors, $duracao, $preco] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('servico_errors', $errors);
            Session::flash('servico_old', $dados);
            $this->redirect('/dashboard/servicos/novo');
        }

        Servico::create($this->barbeariaId(), (string) $dados['nome'], $duracao, $preco);

        Session::flash('servico_success', 'Serviço cadastrado com sucesso.');
        $this->redirect('/dashboard/servicos');
    }

    public function edit(string $id): void
    {
        $servico = Servico::find((int) $id, $this->barbeariaId());

        if ($servico === null) {
            $this->redirect('/dashboard/servicos');
        }

        echo $this->view('dashboard.servicos.form', [
            'pageTitle' => 'Editar serviço - KADOSYS Barbearias',
            'activeMenu' => 'servicos',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'servico' => $servico,
            'old' => Session::flash('servico_old') ?? [],
            'errors' => Session::flash('servico_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Servico::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/servicos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/servicos');
        }

        $dados = $this->request->only(['nome', 'duracao_minutos', 'preco']);
        [$errors, $duracao, $preco] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('servico_errors', $errors);
            Session::flash('servico_old', $dados);
            $this->redirect('/dashboard/servicos/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Servico::update((int) $id, $barbeariaId, (string) $dados['nome'], $duracao, $preco, $ativo);

        Session::flash('servico_success', 'Serviço atualizado com sucesso.');
        $this->redirect('/dashboard/servicos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Servico::delete((int) $id, $this->barbeariaId());
            Session::flash('servico_success', 'Serviço removido.');
        }

        $this->redirect('/dashboard/servicos');
    }

    /** @return array{0: array<int, string>, 1: int, 2: float} */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do serviço.';
        }

        $duracao = (int) ($dados['duracao_minutos'] ?? 0);

        if ($duracao < 5 || $duracao > 480) {
            $errors[] = 'A duração precisa estar entre 5 e 480 minutos.';
        }

        $precoInformado = str_replace(',', '.', (string) ($dados['preco'] ?? ''));
        $preco = is_numeric($precoInformado) ? (float) $precoInformado : -1;

        if ($preco < 0) {
            $errors[] = 'Informe um preço válido.';
        }

        return [$errors, $duracao, max(0, $preco)];
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
