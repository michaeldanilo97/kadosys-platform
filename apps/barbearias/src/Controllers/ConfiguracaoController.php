<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Plano;
use Barbearias\Models\User;

/**
 * Configuracoes: dados da barbearia + equipe (usuarios com acesso).
 * So "admin" pode acessar - "usuario" (equipe) nao gerencia a conta
 * nem outros acessos, ja que o Barbearias ainda nao tem um sistema de
 * permissoes granular por modulo (so os dois papeis: admin/usuario).
 */
final class ConfiguracaoController extends Controller
{
    public function index(): void
    {
        $usuario = $this->exigirAdmin();
        $barbeariaId = $usuario->barbeariaId;

        echo $this->view('dashboard.configuracoes.index', [
            'pageTitle' => 'Configurações - KADOSYS Barbearias',
            'activeMenu' => 'configuracoes',
            'user' => $usuario,
            'barbearia' => Barbearia::find($barbeariaId),
            'equipe' => User::daBarbearia($barbeariaId),
            'planoLabel' => Plano::label(Barbearia::find($barbeariaId)?->plano ?? Plano::ESSENCIAL),
            'perfilErrors' => Session::flash('config_perfil_errors') ?? [],
            'perfilSuccess' => Session::flash('config_perfil_success'),
            'equipeErrors' => Session::flash('config_equipe_errors') ?? [],
            'equipeOld' => Session::flash('config_equipe_old') ?? [],
            'equipeSuccess' => Session::flash('config_equipe_success'),
        ], 'dashboard');
    }

    public function atualizarPerfil(): void
    {
        $usuario = $this->exigirAdmin();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $telefone = trim((string) $this->request->input('telefone', ''));

        if ($nome === '' || mb_strlen($nome) < 3) {
            Session::flash('config_perfil_errors', ['Informe o nome da barbearia (mínimo 3 caracteres).']);
            $this->redirect('/dashboard/configuracoes');
        }

        Barbearia::atualizarPerfil($usuario->barbeariaId, $nome, $telefone !== '' ? $telefone : null);

        Session::flash('config_perfil_success', 'Dados da barbearia atualizados.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function criarUsuario(): void
    {
        $usuario = $this->exigirAdmin();
        $barbeariaId = $usuario->barbeariaId;

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $dados = $this->request->only(['name', 'email', 'password', 'role']);
        $errors = $this->validarEquipe($dados, null);

        if ($errors !== []) {
            Session::flash('config_equipe_errors', $errors);
            Session::flash('config_equipe_old', $dados);
            $this->redirect('/dashboard/configuracoes');
        }

        $role = $dados['role'] === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_USUARIO;

        User::create($barbeariaId, (string) $dados['name'], (string) $dados['email'], (string) $dados['password'], $role);

        Session::flash('config_equipe_success', 'Acesso criado com sucesso.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function editarUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->barbeariaId !== $usuario->barbeariaId) {
            $this->redirect('/dashboard/configuracoes');
        }

        echo $this->view('dashboard.configuracoes.equipe-editar', [
            'pageTitle' => 'Editar acesso - KADOSYS Barbearias',
            'activeMenu' => 'configuracoes',
            'user' => $usuario,
            'barbearia' => Barbearia::find($usuario->barbeariaId),
            'membro' => $membro,
            'old' => Session::flash('config_equipe_editar_old') ?? [],
            'errors' => Session::flash('config_equipe_editar_errors') ?? [],
        ], 'dashboard');
    }

    public function atualizarUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $barbeariaId = $usuario->barbeariaId;
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->barbeariaId !== $barbeariaId) {
            $this->redirect('/dashboard/configuracoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $dados = $this->request->only(['name', 'email', 'role', 'password']);
        $errors = $this->validarEquipe($dados, $membro->id, exigirSenha: false);

        $active = $this->request->input('active') !== null;
        $role = $dados['role'] === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_USUARIO;

        // Nao deixa a barbearia ficar sem NENHUM admin ativo (senao
        // ninguem mais consegue gerenciar equipe/dados da conta) - so
        // bloqueia se essa edicao especifica tiraria o UNICO admin
        // ativo restante do cargo/status de admin ativo.
        $eraAdminAtivo = $membro->role === User::ROLE_ADMIN && $membro->active;
        $seriaAdminAtivo = $role === User::ROLE_ADMIN && $active;

        if ($eraAdminAtivo && !$seriaAdminAtivo && User::contarAdminsAtivos($barbeariaId) <= 1) {
            $errors[] = 'Precisa existir pelo menos um administrador ativo.';
        }

        if ($errors !== []) {
            Session::flash('config_equipe_editar_errors', $errors);
            Session::flash('config_equipe_editar_old', $dados);
            $this->redirect('/dashboard/configuracoes/equipe/' . $id . '/editar');
        }

        User::update($membro->id, $barbeariaId, (string) $dados['name'], (string) $dados['email'], $role, $active);

        $novaSenha = trim((string) ($dados['password'] ?? ''));

        if ($novaSenha !== '') {
            User::updatePassword($membro->id, $barbeariaId, $novaSenha);
        }

        Session::flash('config_equipe_success', 'Acesso atualizado.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function excluirUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $barbeariaId = $usuario->barbeariaId;
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->barbeariaId !== $barbeariaId) {
            $this->redirect('/dashboard/configuracoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($membro->id === $usuario->id) {
            Session::flash('config_equipe_errors', ['Você não pode excluir o próprio acesso.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($membro->role === User::ROLE_ADMIN && User::contarAdminsAtivos($barbeariaId) <= 1) {
            Session::flash('config_equipe_errors', ['Precisa existir pelo menos um administrador ativo.']);
            $this->redirect('/dashboard/configuracoes');
        }

        User::delete($membro->id, $barbeariaId);

        Session::flash('config_equipe_success', 'Acesso removido.');
        $this->redirect('/dashboard/configuracoes');
    }

    /** @return array<int, string> */
    private function validarEquipe(array $dados, ?int $exceptId, bool $exigirSenha = true): array
    {
        $errors = [];
        $name = trim((string) ($dados['name'] ?? ''));

        if ($name === '' || mb_strlen($name) < 3) {
            $errors[] = 'Informe o nome completo.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        } elseif (User::emailEmUso($email, $exceptId)) {
            $errors[] = 'Esse e-mail já está em uso.';
        }

        $senha = (string) ($dados['password'] ?? '');

        if ($exigirSenha && mb_strlen($senha) < 8) {
            $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif (!$exigirSenha && $senha !== '' && mb_strlen($senha) < 8) {
            $errors[] = 'A nova senha precisa ter pelo menos 8 caracteres.';
        }

        return $errors;
    }

    private function exigirAdmin(): User
    {
        $usuario = (new Auth($this->config))->user();

        if ($usuario === null) {
            $this->redirect('/login');
        }

        if ($usuario->role !== User::ROLE_ADMIN) {
            $this->redirect('/dashboard');
        }

        return $usuario;
    }
}
