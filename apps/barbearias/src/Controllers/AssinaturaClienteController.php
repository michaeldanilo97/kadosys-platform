<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\AssinaturaCliente;
use Barbearias\Models\AssinaturaConsumo;
use Barbearias\Models\AssinaturaPlano;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\User;

/**
 * Assinaturas de cliente: cadastro de planos (pacote de N atendimentos
 * por mes) e gestao de quem esta assinado. O consumo em si (marcar um
 * atendimento como "usado da assinatura") fica em
 * Barbearias\Controllers\AgendamentoController::usarAssinatura, junto
 * do resto do fluxo de conclusao de atendimento.
 */
final class AssinaturaClienteController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $termoBusca = trim((string) $this->request->input('busca_cliente', ''));
        $clientesEncontrados = $termoBusca !== '' ? Cliente::buscarParaFidelidade($barbeariaId, $termoBusca) : [];

        $hoje = new \DateTimeImmutable('today');
        $assinaturasAtivas = array_map(
            function (AssinaturaCliente $assinatura) use ($hoje) {
                $inicioCiclo = $assinatura->inicioCicloAtual($hoje);

                return [
                    'assinatura' => $assinatura,
                    'usados' => AssinaturaConsumo::contarNoCiclo($assinatura->id, $inicioCiclo),
                    'inicioCiclo' => $inicioCiclo,
                ];
            },
            AssinaturaCliente::ativas($barbeariaId),
        );

        echo $this->view('dashboard.assinaturas_clientes.index', [
            'pageTitle' => 'Assinaturas - KADOSYS Barbearias',
            'activeMenu' => 'assinaturas-clientes',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'planos' => AssinaturaPlano::todos($barbeariaId),
            'termoBusca' => $termoBusca,
            'clientesEncontrados' => $clientesEncontrados,
            'assinaturasAtivas' => $assinaturasAtivas,
            'success' => Session::flash('assinatura_cliente_success'),
            'errors' => Session::flash('assinatura_cliente_errors') ?? [],
        ], 'dashboard');
    }

    public function planoStore(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/assinaturas-clientes');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $precoInformado = str_replace(',', '.', (string) $this->request->input('preco', ''));
        $preco = is_numeric($precoInformado) ? (float) $precoInformado : -1;
        $atendimentosPorMes = (int) $this->request->input('atendimentos_por_mes', 0);
        $errors = [];

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do plano.';
        }

        if ($preco <= 0) {
            $errors[] = 'Informe um preço válido.';
        }

        if ($atendimentosPorMes < 1) {
            $errors[] = 'Informe quantos atendimentos por mês o plano inclui.';
        }

        if ($errors !== []) {
            Session::flash('assinatura_cliente_errors', $errors);
            $this->redirect('/dashboard/assinaturas-clientes');
        }

        AssinaturaPlano::create($barbeariaId, $nome, $preco, $atendimentosPorMes);

        Session::flash('assinatura_cliente_success', 'Plano cadastrado.');
        $this->redirect('/dashboard/assinaturas-clientes');
    }

    public function planoDestroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            AssinaturaPlano::delete((int) $id, $this->barbeariaId());
            Session::flash('assinatura_cliente_success', 'Plano removido.');
        }

        $this->redirect('/dashboard/assinaturas-clientes');
    }

    public function assinar(): void
    {
        $barbeariaId = $this->barbeariaId();
        $clienteId = (int) $this->request->input('cliente_id', 0);
        $planoId = (int) $this->request->input('plano_id', 0);

        $cliente = Cliente::find($clienteId, $barbeariaId);
        $plano = AssinaturaPlano::find($planoId, $barbeariaId);

        if ($cliente === null || $plano === null) {
            $this->redirect('/dashboard/assinaturas-clientes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/assinaturas-clientes');
        }

        if (AssinaturaCliente::ativaDoCliente($cliente->id, $barbeariaId) !== null) {
            Session::flash('assinatura_cliente_errors', ['Esse cliente já tem uma assinatura ativa.']);
            $this->redirect('/dashboard/assinaturas-clientes');
        }

        AssinaturaCliente::create($barbeariaId, $cliente->id, $plano->id, (new \DateTimeImmutable('today'))->format('Y-m-d'));

        Session::flash('assinatura_cliente_success', $cliente->nome . ' assinado no plano ' . $plano->nome . '.');
        $this->redirect('/dashboard/assinaturas-clientes');
    }

    public function cancelar(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            AssinaturaCliente::cancelar((int) $id, $this->barbeariaId());
            Session::flash('assinatura_cliente_success', 'Assinatura cancelada.');
        }

        $this->redirect('/dashboard/assinaturas-clientes');
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
