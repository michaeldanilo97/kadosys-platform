<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Membro;

/**
 * Auto-cadastro publico de membros: pensado pra ser aberto a partir de
 * um link "Cadastre-se" na tela de login de uma igreja - a pessoa
 * preenche os proprios dados e vira um Membro direto, sem precisar de
 * acesso ao painel. So funciona se a igreja tiver habilitado essa opcao
 * em Configuracoes (ver ConfiguracaoIgreja::cadastroMembrosHabilitado)
 * - senao, cada membro continua sendo cadastrado manualmente pelo
 * modulo Membros do painel.
 */
final class MembroPublicoController extends Controller
{
    public function form(): void
    {
        $configuracao = ConfiguracaoIgreja::atual();

        echo $this->view('cadastro.membro', [
            'pageTitle' => 'Cadastro de membro - ' . ($configuracao->nomeIgreja ?? 'KADOSYS Igrejas'),
            'nomeIgreja' => $configuracao->nomeIgreja,
            'habilitado' => $configuracao->cadastroMembrosHabilitado,
            'sucesso' => Session::flash('cadastro_membro_sucesso') ?? false,
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cadastro_membro_errors') ?? [],
            'old' => Session::flash('cadastro_membro_old') ?? [],
        ], 'auth');
    }

    public function enviar(): void
    {
        if (!ConfiguracaoIgreja::atual()->cadastroMembrosHabilitado) {
            $this->redirect('/cadastro');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('cadastro_membro_errors', ['Sessao expirada. Preencha o formulario novamente.']);
            $this->redirect('/cadastro');
        }

        $data = $this->request->only([
            'nome', 'email', 'telefone', 'data_nascimento', 'genero',
            'estado_civil', 'cep', 'endereco', 'cidade', 'estado',
        ]);

        $errors = $this->validar($data);

        if ($errors !== []) {
            Session::flash('cadastro_membro_errors', $errors);
            Session::flash('cadastro_membro_old', $data);
            $this->redirect('/cadastro');
        }

        Membro::create($data + ['status' => 'ativo']);

        Session::flash('cadastro_membro_sucesso', true);
        $this->redirect('/cadastro');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validar(array $data): array
    {
        $errors = [];

        $nome = trim((string) ($data['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe seu nome completo (minimo 3 caracteres).';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Informe um e-mail valido.';
            } elseif (Membro::emailEmUso($email)) {
                $errors[] = 'Esse e-mail ja esta cadastrado. Fale com a secretaria da igreja.';
            }
        }

        return $errors;
    }
}
