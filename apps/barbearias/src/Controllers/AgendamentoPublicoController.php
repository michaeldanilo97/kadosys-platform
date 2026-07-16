<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Disponibilidade;
use Barbearias\Core\Session;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\ListaEspera;
use Barbearias\Models\PortfolioFoto;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;
use Barbearias\Models\Unidade;

/**
 * Agendamento publico (sem login) - o cliente final da barbearia entra
 * direto pelo link "/agendar/{slug}" (compartilhado no Instagram/
 * WhatsApp da barbearia), escolhe profissional + servico + horario
 * disponivel, informa nome/telefone e confirma. Nao existe conta de
 * cliente (usuario/senha) - o telefone e quem identifica um cliente
 * que ja agendou antes (ver Cliente::buscarPorTelefone).
 */
final class AgendamentoPublicoController extends Controller
{
    public function form(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if ($barbearia->usaFila()) {
            $this->redirect('/fila/' . $slug);
        }

        $profissionais = Profissional::ativos($barbearia->id);
        $profissionalIds = array_map(static fn (Profissional $p) => $p->id, $profissionais);

        echo $this->view('public.agendar', [
            'pageTitle' => 'Agendar horário - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'profissionais' => $profissionais,
            'servicos' => Servico::ativos($barbearia->id),
            'unidades' => Unidade::temMultiplasAtivas($barbearia->id) ? Unidade::ativas($barbearia->id) : [],
            'mapaUnidades' => Profissional::mapaUnidades($profissionalIds),
            'mapaPortfolio' => PortfolioFoto::mapaDosProfissionais($profissionalIds),
            'csrf' => Csrf::field(),
            'errors' => Session::flash('agendamento_publico_errors') ?? [],
            'old' => Session::flash('agendamento_publico_old') ?? [],
            'success' => Session::flash('agendamento_publico_success'),
        ], 'site');
    }

    /**
     * Endpoint JSON (chamado via fetch pelo JS da pagina de agendamento)
     * - devolve os horarios livres de um profissional numa data, pro
     * servico escolhido.
     */
    public function horarios(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null || $barbearia->usaFila()) {
            $this->jsonResponse(['horarios' => []], 404);
        }

        $profissional = Profissional::find((int) $this->request->input('profissional_id', 0), $barbearia->id);
        $servico = Servico::find((int) $this->request->input('servico_id', 0), $barbearia->id);
        $data = (string) $this->request->input('data', '');

        if ($profissional === null || $servico === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $this->jsonResponse(['horarios' => []]);
        }

        $this->jsonResponse(['horarios' => Disponibilidade::horariosLivres($barbearia->id, $profissional, $servico, $data)]);
    }

    public function enviar(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if ($barbearia->usaFila()) {
            $this->redirect('/fila/' . $slug);
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('agendamento_publico_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/agendar/' . $slug);
        }

        $dados = $this->request->only(['unidade_id', 'profissional_id', 'servico_id', 'data', 'hora', 'nome', 'telefone', 'email']);
        $profissional = Profissional::find((int) ($dados['profissional_id'] ?? 0), $barbearia->id);
        $servico = Servico::find((int) ($dados['servico_id'] ?? 0), $barbearia->id);
        $unidadeId = $this->resolverUnidade($barbearia->id, (int) ($dados['unidade_id'] ?? 0));

        $errors = $this->validar($dados, $profissional, $servico);

        if ($errors === [] && Unidade::temMultiplasAtivas($barbearia->id)) {
            if ($unidadeId === null) {
                $errors[] = 'Escolha uma unidade válida.';
            } elseif ($profissional !== null && !in_array($unidadeId, Profissional::unidadeIds($profissional->id), true)) {
                $errors[] = 'Esse profissional não atende na unidade escolhida.';
            }
        }

        if ($errors === [] && $profissional !== null && $servico !== null) {
            // Sempre revalida a disponibilidade no servidor no momento
            // de confirmar - o horario pode ter sido preenchido numa
            // aba aberta ha um tempo, ou ocupado por outra pessoa
            // enquanto essa pessoa preenchia nome/telefone.
            $disponiveis = Disponibilidade::horariosLivres($barbearia->id, $profissional, $servico, (string) $dados['data']);

            if (!in_array((string) $dados['hora'], $disponiveis, true)) {
                $errors[] = 'Esse horário acabou de ficar indisponível. Escolha outro.';
            }
        }

        if ($errors !== []) {
            Session::flash('agendamento_publico_errors', $errors);
            Session::flash('agendamento_publico_old', $dados);
            $this->redirect('/agendar/' . $slug);
        }

        $telefone = $this->apenasDigitos((string) $dados['telefone']);
        $cliente = Cliente::buscarPorTelefone($barbearia->id, $telefone);

        if ($cliente === null) {
            $clienteId = Cliente::create($barbearia->id, (string) $dados['nome'], $telefone, $dados['email']);
        } else {
            $clienteId = $cliente->id;
            Cliente::update($clienteId, $barbearia->id, (string) $dados['nome'], $telefone, $dados['email'] ?? $cliente->email, $cliente->dataNascimento, $cliente->cpf);
        }

        Agendamento::create(
            $barbearia->id,
            $profissional->id,
            $servico->id,
            $clienteId,
            $dados['data'] . ' ' . $dados['hora'] . ':00',
            null,
            $unidadeId,
        );

        Session::flash('agendamento_publico_confirmado', [
            'profissional' => $profissional->nome,
            'servico' => $servico->nome,
            'data' => (string) $dados['data'],
            'hora' => (string) $dados['hora'],
        ]);
        $this->redirect('/agendar/' . $slug . '/confirmado');
    }

    /**
     * Quando nao ha nenhum horario livre no dia escolhido, o cliente
     * pode entrar na lista de espera em vez de agendar - reaproveita os
     * mesmos campos (nome/telefone/email/profissional/servico/data) do
     * formulario principal, so que sem exigir um horario.
     */
    public function listaEspera(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if ($barbearia->usaFila()) {
            $this->redirect('/fila/' . $slug);
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('agendamento_publico_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/agendar/' . $slug);
        }

        $dados = $this->request->only(['profissional_id', 'servico_id', 'data', 'nome', 'telefone', 'email']);
        $profissionalId = (int) ($dados['profissional_id'] ?? 0);
        $profissional = $profissionalId > 0 ? Profissional::find($profissionalId, $barbearia->id) : null;
        $servico = Servico::find((int) ($dados['servico_id'] ?? 0), $barbearia->id);

        $errors = $this->validarListaEspera($dados, $servico);

        if ($errors !== []) {
            Session::flash('agendamento_publico_errors', $errors);
            Session::flash('agendamento_publico_old', $dados);
            $this->redirect('/agendar/' . $slug);
        }

        $telefone = $this->apenasDigitos((string) $dados['telefone']);
        $cliente = Cliente::buscarPorTelefone($barbearia->id, $telefone);

        if ($cliente === null) {
            $clienteId = Cliente::create($barbearia->id, (string) $dados['nome'], $telefone, $dados['email']);
        } else {
            $clienteId = $cliente->id;
            Cliente::update($clienteId, $barbearia->id, (string) $dados['nome'], $telefone, $dados['email'] ?? $cliente->email, $cliente->dataNascimento, $cliente->cpf);
        }

        ListaEspera::create(
            $barbearia->id,
            $profissional?->id,
            $servico->id,
            $clienteId,
            (string) $dados['data'],
            null,
        );

        Session::flash('agendamento_publico_success', 'Você entrou na lista de espera! Vamos entrar em contato assim que um horário abrir.');
        $this->redirect('/agendar/' . $slug);
    }

    public function confirmado(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);
        $confirmacao = Session::flash('agendamento_publico_confirmado');

        if ($barbearia === null || $confirmacao === null) {
            $this->redirect('/agendar/' . $slug);
        }

        echo $this->view('public.agendar-confirmado', [
            'pageTitle' => 'Agendamento confirmado - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'confirmacao' => $confirmacao,
        ], 'site');
    }

    /** @return array<int, string> */
    private function validar(array $dados, ?Profissional $profissional, ?Servico $servico): array
    {
        $errors = [];

        if ($profissional === null) {
            $errors[] = 'Escolha um profissional válido.';
        }

        if ($servico === null) {
            $errors[] = 'Escolha um serviço válido.';
        }

        $data = (string) ($dados['data'] ?? '');
        $hora = (string) ($dados['hora'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
            $errors[] = 'Escolha uma data e horário válidos.';
        }

        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        $telefone = $this->apenasDigitos((string) ($dados['telefone'] ?? ''));

        if (mb_strlen($telefone) < 10) {
            $errors[] = 'Informe um telefone válido com DDD.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido (ou deixe em branco).';
        }

        return $errors;
    }

    /** @return array<int, string> */
    private function validarListaEspera(array $dados, ?Servico $servico): array
    {
        $errors = [];

        if ($servico === null) {
            $errors[] = 'Escolha um serviço válido.';
        }

        $data = (string) ($dados['data'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data < (new \DateTimeImmutable('today'))->format('Y-m-d')) {
            $errors[] = 'Escolha uma data válida.';
        }

        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        $telefone = $this->apenasDigitos((string) ($dados['telefone'] ?? ''));

        if (mb_strlen($telefone) < 10) {
            $errors[] = 'Informe um telefone válido com DDD.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido (ou deixe em branco).';
        }

        return $errors;
    }

    /**
     * Quando a barbearia tem so uma unidade, o agendamento e vinculado
     * a ela automaticamente sem exigir nenhuma escolha do cliente - so
     * pede pra escolher quando ha mais de uma unidade ativa.
     */
    private function resolverUnidade(int $barbeariaId, int $unidadeIdInformado): ?int
    {
        if (!Unidade::temMultiplasAtivas($barbeariaId)) {
            return Unidade::principal($barbeariaId)?->id;
        }

        $idsAtivas = array_map(static fn (Unidade $u) => $u->id, Unidade::ativas($barbeariaId));

        return $unidadeIdInformado > 0 && in_array($unidadeIdInformado, $idsAtivas, true) ? $unidadeIdInformado : null;
    }

    private function apenasDigitos(string $valor): string
    {
        return preg_replace('/\D+/', '', $valor) ?? '';
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada'], 'site');
    }
}
