<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Unidade;
use Barbearias\Models\User;

final class UnidadeController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();

        echo $this->view('dashboard.unidades.index', [
            'pageTitle' => 'Unidades - KADOSYS Barbearias',
            'activeMenu' => 'unidades',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'unidades' => Unidade::todas($barbeariaId),
            'success' => Session::flash('unidade_success'),
            'errors' => Session::flash('unidade_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.unidades.form', [
            'pageTitle' => 'Nova unidade - KADOSYS Barbearias',
            'activeMenu' => 'unidades',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'unidade' => null,
            'old' => Session::flash('unidade_old') ?? [],
            'errors' => Session::flash('unidade_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/unidades');
        }

        $dados = $this->dadosDoFormulario();
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('unidade_errors', $errors);
            Session::flash('unidade_old', $dados);
            $this->redirect('/dashboard/unidades/nova');
        }

        Unidade::criar(
            $barbeariaId,
            (string) $dados['nome'],
            $dados['endereco'],
            $dados['cidade'],
            $dados['estado'],
            $dados['cep'],
            $dados['telefone'],
            $dados['whatsapp'],
        );

        Session::flash('unidade_success', 'Unidade cadastrada com sucesso.');
        $this->redirect('/dashboard/unidades');
    }

    public function edit(string $id): void
    {
        $unidade = Unidade::find((int) $id, $this->barbeariaId());

        if ($unidade === null) {
            $this->redirect('/dashboard/unidades');
        }

        echo $this->view('dashboard.unidades.form', [
            'pageTitle' => 'Editar unidade - KADOSYS Barbearias',
            'activeMenu' => 'unidades',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($this->barbeariaId()),
            'unidade' => $unidade,
            'old' => Session::flash('unidade_old') ?? [],
            'errors' => Session::flash('unidade_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $unidade = Unidade::find((int) $id, $barbeariaId);

        if ($unidade === null) {
            $this->redirect('/dashboard/unidades');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/unidades');
        }

        $dados = $this->dadosDoFormulario();
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('unidade_errors', $errors);
            Session::flash('unidade_old', $dados);
            $this->redirect('/dashboard/unidades/' . $id . '/editar');
        }

        Unidade::atualizar(
            (int) $id,
            $barbeariaId,
            (string) $dados['nome'],
            $dados['endereco'],
            $dados['cidade'],
            $dados['estado'],
            $dados['cep'],
            $dados['telefone'],
            $dados['whatsapp'],
        );

        $ativa = $this->request->input('ativa') !== null;

        if ($ativa !== $unidade->ativa && !Unidade::alternarAtiva((int) $id, $barbeariaId, $ativa)) {
            Session::flash('unidade_success', 'Unidade atualizada, mas não foi possível desativá-la: precisa sobrar ao menos uma unidade ativa.');
            $this->redirect('/dashboard/unidades');
        }

        Session::flash('unidade_success', 'Unidade atualizada com sucesso.');
        $this->redirect('/dashboard/unidades');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (Unidade::excluir((int) $id, $this->barbeariaId())) {
                Session::flash('unidade_success', 'Unidade removida.');
            } else {
                Session::flash('unidade_errors', ['Não é possível remover a única unidade da barbearia.']);
            }
        }

        $this->redirect('/dashboard/unidades');
    }

    /** @return array{nome:string, endereco:?string, cidade:?string, estado:?string, cep:?string, telefone:?string, whatsapp:?string} */
    private function dadosDoFormulario(): array
    {
        return [
            'nome' => trim((string) $this->request->input('nome', '')),
            'endereco' => $this->request->input('endereco'),
            'cidade' => $this->request->input('cidade'),
            'estado' => $this->request->input('estado'),
            'cep' => $this->request->input('cep'),
            'telefone' => $this->request->input('telefone'),
            'whatsapp' => $this->request->input('whatsapp'),
        ];
    }

    /** @return array<int, string> */
    private function validar(array $dados): array
    {
        $errors = [];

        if ($dados['nome'] === '' || mb_strlen($dados['nome']) < 2) {
            $errors[] = 'Informe o nome da unidade.';
        }

        $estado = trim((string) ($dados['estado'] ?? ''));

        if ($estado !== '' && mb_strlen($estado) !== 2) {
            $errors[] = 'Informe o estado com a sigla (ex.: SP).';
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
