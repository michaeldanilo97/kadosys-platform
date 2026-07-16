<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\ClienteAuth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Disponibilidade;
use Barbearias\Core\Session;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Avaliacao;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;

/**
 * Area do cliente (/minha-conta/{slug}) - login proprio do cliente
 * final, separado do painel administrativo da equipe (ver
 * Barbearias\Core\ClienteAuth). Mostra proximos agendamentos,
 * historico e permite avaliar atendimentos concluidos.
 */
final class ClienteAreaController extends Controller
{
    public function painel(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        $cliente = (new ClienteAuth($this->config))->user($barbearia->id);

        if ($cliente === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        $proximos = Agendamento::proximosDoCliente($barbearia->id, $cliente->id);
        $historico = Agendamento::historicoDoCliente($barbearia->id, $cliente->id);
        $avaliados = Avaliacao::agendamentosAvaliados(array_map(static fn (Agendamento $a) => $a->id, $historico));

        echo $this->view('public.minha-conta.painel', [
            'pageTitle' => 'Minha conta - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'cliente' => $cliente,
            'proximos' => $proximos,
            'historico' => $historico,
            'avaliados' => $avaliados,
            'csrf' => Csrf::field(),
            'success' => Session::flash('cliente_area_success'),
            'errors' => Session::flash('cliente_area_errors') ?? [],
        ], 'site');
    }

    public function showEntrar(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if ((new ClienteAuth($this->config))->check($barbearia->id)) {
            $this->redirect('/minha-conta/' . $slug);
        }

        echo $this->view('public.minha-conta.entrar', [
            'pageTitle' => 'Entrar - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cliente_login_errors') ?? [],
            'old' => Session::flash('cliente_login_old') ?? [],
        ], 'site');
    }

    public function entrar(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        $telefone = $this->apenasDigitos((string) $this->request->input('telefone', ''));
        $senha = (string) $this->request->input('senha', '');

        if (!(new ClienteAuth($this->config))->attempt($barbearia->id, $telefone, $senha)) {
            Session::flash('cliente_login_errors', ['Telefone ou senha incorretos.']);
            Session::flash('cliente_login_old', ['telefone' => $this->request->input('telefone', '')]);
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        $this->redirect('/minha-conta/' . $slug);
    }

    public function showCadastro(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if ((new ClienteAuth($this->config))->check($barbearia->id)) {
            $this->redirect('/minha-conta/' . $slug);
        }

        echo $this->view('public.minha-conta.cadastro', [
            'pageTitle' => 'Criar conta - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cliente_cadastro_errors') ?? [],
            'old' => Session::flash('cliente_cadastro_old') ?? [],
        ], 'site');
    }

    public function cadastro(string $slug): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug . '/cadastro');
        }

        $dados = $this->request->only(['nome', 'telefone', 'email', 'senha', 'senha_confirmacao']);
        $telefone = $this->apenasDigitos((string) ($dados['telefone'] ?? ''));
        $errors = $this->validarCadastro($dados, $telefone);

        $existente = $telefone !== '' ? Cliente::buscarPorTelefone($barbearia->id, $telefone) : null;

        if ($existente !== null && $existente->temSenha()) {
            $errors[] = 'Esse telefone já tem uma conta. Faça login em vez de se cadastrar.';
        }

        if ($errors !== []) {
            Session::flash('cliente_cadastro_errors', $errors);
            Session::flash('cliente_cadastro_old', $dados);
            $this->redirect('/minha-conta/' . $slug . '/cadastro');
        }

        $nome = trim((string) $dados['nome']);
        $emailInformado = trim((string) ($dados['email'] ?? ''));
        $email = $emailInformado !== '' ? $emailInformado : null;

        if ($existente !== null) {
            // Ja tinha agendado antes sem conta - so "reivindica" o
            // cadastro que ja existe (mantem o historico de
            // agendamentos ligado a ele) em vez de duplicar.
            Cliente::update($existente->id, $barbearia->id, $nome, $telefone, $email ?? $existente->email, $existente->dataNascimento, $existente->cpf);
            Cliente::definirSenha($existente->id, $barbearia->id, (string) $dados['senha']);
            $clienteId = $existente->id;
        } else {
            $clienteId = Cliente::create($barbearia->id, $nome, $telefone, $email);
            Cliente::definirSenha($clienteId, $barbearia->id, (string) $dados['senha']);
        }

        $cliente = Cliente::find($clienteId, $barbearia->id);

        if ($cliente !== null) {
            (new ClienteAuth($this->config))->login($cliente);
        }

        $this->redirect('/minha-conta/' . $slug);
    }

    public function sair(string $slug): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            (new ClienteAuth($this->config))->logout();
        }

        $this->redirect('/minha-conta/' . $slug . '/entrar');
    }

    public function avaliar(string $slug, string $agendamentoId): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        $cliente = (new ClienteAuth($this->config))->user($barbearia->id);

        if ($cliente === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug);
        }

        $agendamento = Agendamento::find((int) $agendamentoId, $barbearia->id);

        if ($agendamento === null || $agendamento->clienteId !== $cliente->id || $agendamento->status !== Agendamento::STATUS_CONCLUIDO) {
            $this->redirect('/minha-conta/' . $slug);
        }

        if (Avaliacao::buscarPorAgendamento($agendamento->id, $barbearia->id) !== null) {
            $this->redirect('/minha-conta/' . $slug);
        }

        $nota = (int) $this->request->input('nota', 0);
        $comentario = $this->request->input('comentario');

        if ($nota < 1 || $nota > 5) {
            Session::flash('cliente_area_errors', ['Escolha uma nota de 1 a 5 estrelas.']);
            $this->redirect('/minha-conta/' . $slug);
        }

        Avaliacao::criar($barbearia->id, $agendamento->id, $cliente->id, $agendamento->profissionalId, $nota, $comentario);

        Session::flash('cliente_area_success', 'Obrigado pela avaliação!');
        $this->redirect('/minha-conta/' . $slug);
    }

    public function cancelar(string $slug, string $agendamentoId): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        $cliente = (new ClienteAuth($this->config))->user($barbearia->id);

        if ($cliente === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug);
        }

        $agendamento = $this->agendamentoDoCliente($barbearia->id, $cliente->id, $agendamentoId);

        if ($agendamento === null) {
            $this->redirect('/minha-conta/' . $slug);
        }

        Agendamento::atualizarStatus($agendamento->id, $barbearia->id, Agendamento::STATUS_CANCELADO);

        Session::flash('cliente_area_success', 'Agendamento cancelado.');
        $this->redirect('/minha-conta/' . $slug);
    }

    public function reagendarForm(string $slug, string $agendamentoId): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        $cliente = (new ClienteAuth($this->config))->user($barbearia->id);

        if ($cliente === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        $agendamento = $this->agendamentoDoCliente($barbearia->id, $cliente->id, $agendamentoId);

        if ($agendamento === null) {
            $this->redirect('/minha-conta/' . $slug);
        }

        echo $this->view('public.minha-conta.reagendar', [
            'pageTitle' => 'Reagendar - ' . $barbearia->nome,
            'barbearia' => $barbearia,
            'agendamento' => $agendamento,
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cliente_reagendar_errors') ?? [],
            'old' => Session::flash('cliente_reagendar_old') ?? [],
        ], 'site');
    }

    /**
     * Endpoint JSON (fetch pelo JS da tela de reagendamento) - so
     * autenticado e so do proprio dono do agendamento, ao contrario do
     * endpoint publico de horarios (que qualquer um pode chamar) -
     * exclui o proprio agendamento do calculo de conflito, ja que ele
     * ainda esta 'agendado' ate a troca ser confirmada.
     */
    public function horariosParaReagendar(string $slug, string $agendamentoId): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->jsonResponse(['horarios' => []], 404);
        }

        $cliente = (new ClienteAuth($this->config))->user($barbearia->id);

        if ($cliente === null) {
            $this->jsonResponse(['horarios' => []], 401);
        }

        $agendamento = $this->agendamentoDoCliente($barbearia->id, $cliente->id, $agendamentoId);
        $data = (string) $this->request->input('data', '');

        if ($agendamento === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $this->jsonResponse(['horarios' => []]);
        }

        $profissional = Profissional::find($agendamento->profissionalId, $barbearia->id);
        $servico = Servico::find($agendamento->servicoId, $barbearia->id);

        if ($profissional === null || $servico === null) {
            $this->jsonResponse(['horarios' => []]);
        }

        $this->jsonResponse([
            'horarios' => Disponibilidade::horariosLivres($barbearia->id, $profissional, $servico, $data, $agendamento->id),
        ]);
    }

    public function reagendar(string $slug, string $agendamentoId): void
    {
        $barbearia = Barbearia::findBySlugAtiva($slug);

        if ($barbearia === null) {
            $this->renderNotFound();

            return;
        }

        $cliente = (new ClienteAuth($this->config))->user($barbearia->id);

        if ($cliente === null) {
            $this->redirect('/minha-conta/' . $slug . '/entrar');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/minha-conta/' . $slug);
        }

        $agendamento = $this->agendamentoDoCliente($barbearia->id, $cliente->id, $agendamentoId);

        if ($agendamento === null) {
            $this->redirect('/minha-conta/' . $slug);
        }

        $dados = $this->request->only(['data', 'hora']);
        $data = (string) ($dados['data'] ?? '');
        $hora = (string) ($dados['hora'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
            Session::flash('cliente_reagendar_errors', ['Escolha uma data e horário válidos.']);
            $this->redirect('/minha-conta/' . $slug . '/agendamentos/' . $agendamentoId . '/reagendar');
        }

        $profissional = Profissional::find($agendamento->profissionalId, $barbearia->id);
        $servico = Servico::find($agendamento->servicoId, $barbearia->id);

        if ($profissional === null || $servico === null) {
            $this->redirect('/minha-conta/' . $slug);
        }

        // Sempre revalida a disponibilidade no servidor no momento de
        // confirmar - mesmo motivo do agendamento publico.
        $disponiveis = Disponibilidade::horariosLivres($barbearia->id, $profissional, $servico, $data, $agendamento->id);

        if (!in_array($hora, $disponiveis, true)) {
            Session::flash('cliente_reagendar_errors', ['Esse horário acabou de ficar indisponível. Escolha outro.']);
            $this->redirect('/minha-conta/' . $slug . '/agendamentos/' . $agendamentoId . '/reagendar');
        }

        Agendamento::reagendar($agendamento->id, $barbearia->id, $data . ' ' . $hora . ':00');

        Session::flash('cliente_area_success', 'Agendamento reagendado com sucesso.');
        $this->redirect('/minha-conta/' . $slug);
    }

    /**
     * So retorna o agendamento se ele pertencer mesmo a esse cliente e
     * ainda estiver com status 'agendado' - cancelar/reagendar um
     * atendimento ja concluido ou cancelado nao faz sentido.
     */
    private function agendamentoDoCliente(int $barbeariaId, int $clienteId, string $agendamentoId): ?Agendamento
    {
        $agendamento = Agendamento::find((int) $agendamentoId, $barbeariaId);

        if ($agendamento === null || $agendamento->clienteId !== $clienteId || $agendamento->status !== Agendamento::STATUS_AGENDADO) {
            return null;
        }

        return $agendamento;
    }

    /** @return array<int, string> */
    private function validarCadastro(array $dados, string $telefone): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        if (mb_strlen($telefone) < 10) {
            $errors[] = 'Informe um telefone válido com DDD.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido (ou deixe em branco).';
        }

        $senha = (string) ($dados['senha'] ?? '');

        if (mb_strlen($senha) < 8) {
            $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif ($senha !== ($dados['senha_confirmacao'] ?? '')) {
            $errors[] = 'A confirmação de senha não confere.';
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
