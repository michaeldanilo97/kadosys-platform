<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Membro;
use Igrejas\Models\User;

/**
 * Auto-cadastro publico de membros: pensado pra ser aberto a partir de
 * um link "Cadastre-se" na tela de login de uma igreja - a pessoa
 * preenche os proprios dados, vira um Membro E ganha um login (User)
 * pra entrar no painel, sem precisar que a secretaria cadastre nada
 * manualmente. So funciona se a igreja tiver habilitado essa opcao em
 * Configuracoes (ver ConfiguracaoIgreja::cadastroMembrosHabilitado) -
 * senao, cada membro continua sendo cadastrado manualmente pelo modulo
 * Membros do painel.
 *
 * O login criado ganha o papel 'usuario' com o PERFIL PADRAO
 * configurado pela igreja em Configuracoes > Permissoes padrao (ver
 * User::aplicarPerfilPadrao/PermissaoPadrao) - por padrao de fabrica,
 * um acesso "seguro" que nao inclui Financeiro/Projecao e afins, e
 * deixa os modulos de uso geral (Agenda, Cultos, Ministerios etc.)
 * como so-visualizar. O admin pode ajustar isso a qualquer momento em
 * Permissoes, individualmente por usuario.
 */
final class MembroPublicoController extends Controller
{
    private const SENHA_MINIMA = 8;

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
            Session::flash('cadastro_membro_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/cadastro');
        }

        $data = $this->request->only([
            'nome', 'email', 'telefone', 'data_nascimento', 'genero',
            'estado_civil', 'cep', 'endereco', 'cidade', 'estado',
        ]);
        $senha = (string) $this->request->input('senha', '');
        $senhaConfirmacao = (string) $this->request->input('senha_confirmacao', '');

        $errors = $this->validar($data, $senha, $senhaConfirmacao);

        if ($errors !== []) {
            Session::flash('cadastro_membro_errors', $errors);
            Session::flash('cadastro_membro_old', $data);
            $this->redirect('/cadastro');
        }

        Membro::create($data + ['status' => 'ativo']);

        $userId = User::create([
            'name' => trim((string) $data['nome']),
            'email' => trim((string) $data['email']),
            'password' => $senha,
            'role' => User::ROLE_USUARIO,
        ]);
        User::aplicarPerfilPadrao($userId);

        Session::flash('cadastro_membro_sucesso', true);
        $this->redirect('/cadastro');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validar(array $data, string $senha, string $senhaConfirmacao): array
    {
        $errors = [];

        $nome = trim((string) ($data['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe seu nome completo (mínimo 3 caracteres).';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            $errors[] = 'Informe seu e-mail - ele vira seu login de acesso.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        } elseif (Membro::emailEmUso($email) || User::emailEmUso($email)) {
            $errors[] = 'Esse e-mail já está cadastrado. Fale com a secretaria da igreja.';
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $errors[] = 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.';
        } elseif ($senha !== $senhaConfirmacao) {
            $errors[] = 'A confirmação de senha não confere.';
        }

        return $errors;
    }
}
