<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;

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
    /** Intervalo entre os horarios sugeridos, em minutos. */
    private const GRANULARIDADE_MINUTOS = 15;

    public function form(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('public.agendar', [
            'pageTitle' => 'Agendar horário - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'profissionais' => Profissional::ativos($barbearia->id),
            'servicos' => Servico::ativos($barbearia->id),
            'csrf' => Csrf::field(),
            'errors' => Session::flash('agendamento_publico_errors') ?? [],
            'old' => Session::flash('agendamento_publico_old') ?? [],
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

        if ($barbearia === null) {
            $this->jsonResponse(['horarios' => []], 404);
        }

        $profissional = Profissional::find((int) $this->request->input('profissional_id', 0), $barbearia->id);
        $servico = Servico::find((int) $this->request->input('servico_id', 0), $barbearia->id);
        $data = (string) $this->request->input('data', '');

        if ($profissional === null || $servico === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $this->jsonResponse(['horarios' => []]);
        }

        $this->jsonResponse(['horarios' => $this->calcularHorariosDisponiveis($barbearia, $profissional, $servico, $data)]);
    }

    public function enviar(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('agendamento_publico_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/agendar/' . $slug);
        }

        $dados = $this->request->only(['profissional_id', 'servico_id', 'data', 'hora', 'nome', 'telefone', 'email']);
        $profissional = Profissional::find((int) ($dados['profissional_id'] ?? 0), $barbearia->id);
        $servico = Servico::find((int) ($dados['servico_id'] ?? 0), $barbearia->id);

        $errors = $this->validar($dados, $profissional, $servico);

        if ($errors === [] && $profissional !== null && $servico !== null) {
            // Sempre revalida a disponibilidade no servidor no momento
            // de confirmar - o horario pode ter sido preenchido numa
            // aba aberta ha um tempo, ou ocupado por outra pessoa
            // enquanto essa pessoa preenchia nome/telefone.
            $disponiveis = $this->calcularHorariosDisponiveis($barbearia, $profissional, $servico, (string) $dados['data']);

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
            Cliente::update($clienteId, $barbearia->id, (string) $dados['nome'], $telefone, $dados['email'] ?? $cliente->email);
        }

        Agendamento::create(
            $barbearia->id,
            $profissional->id,
            $servico->id,
            $clienteId,
            $dados['data'] . ' ' . $dados['hora'] . ':00',
            null,
        );

        Session::flash('agendamento_publico_confirmado', [
            'profissional' => $profissional->nome,
            'servico' => $servico->nome,
            'data' => (string) $dados['data'],
            'hora' => (string) $dados['hora'],
        ]);
        $this->redirect('/agendar/' . $slug . '/confirmado');
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
    private function calcularHorariosDisponiveis(Barbearia $barbearia, Profissional $profissional, Servico $servico, string $data): array
    {
        if ($profissional->horarioInicio === null || $profissional->horarioFim === null) {
            return [];
        }

        try {
            $dia = new \DateTimeImmutable($data);
        } catch (\Exception) {
            return [];
        }

        $hoje = new \DateTimeImmutable('today');

        if ($dia < $hoje) {
            return [];
        }

        $diaSemana = (int) $dia->format('w');

        if (!in_array($diaSemana, $profissional->diasAtendimento, true)) {
            return [];
        }

        $inicioExpediente = new \DateTimeImmutable($data . ' ' . $profissional->horarioInicio);
        $fimExpediente = new \DateTimeImmutable($data . ' ' . $profissional->horarioFim);
        $duracao = $servico->duracaoMinutos;
        $agora = new \DateTimeImmutable();

        $ocupados = Agendamento::doDiaPorProfissional($barbearia->id, $profissional->id, $data);

        $slots = [];
        $cursor = $inicioExpediente;

        while ($cursor->modify('+' . $duracao . ' minutes') <= $fimExpediente) {
            $fimSlot = $cursor->modify('+' . $duracao . ' minutes');

            if ($cursor >= $agora) {
                $livre = true;

                foreach ($ocupados as $ocupado) {
                    $ocupadoInicio = new \DateTimeImmutable($ocupado->dataHora);
                    $ocupadoFim = $ocupadoInicio->modify('+' . $ocupado->servicoDuracao . ' minutes');

                    if ($cursor < $ocupadoFim && $fimSlot > $ocupadoInicio) {
                        $livre = false;

                        break;
                    }
                }

                if ($livre) {
                    $slots[] = $cursor->format('H:i');
                }
            }

            $cursor = $cursor->modify('+' . self::GRANULARIDADE_MINUTOS . ' minutes');
        }

        return $slots;
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
