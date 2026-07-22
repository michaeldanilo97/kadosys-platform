<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\MercadoPagoClient;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\Plano;
use Academias\Models\User;

/**
 * Configuracoes: dados da academia + equipe (usuarios com acesso).
 * So "admin" pode acessar - "usuario" (equipe) nao gerencia a conta
 * nem outros acessos, ja que o Academias ainda nao tem um sistema de
 * permissoes granular por modulo (so os dois papeis: admin/usuario).
 */
final class ConfiguracaoController extends Controller
{
    private const UPLOAD_DIR = 'uploads/marca';
    private const TAMANHO_MAXIMO_LOGO = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        $usuario = $this->exigirAdmin();
        $academiaId = $usuario->academiaId;

        echo $this->view('dashboard.configuracoes.index', [
            'pageTitle' => 'Configurações - KADOSYS Academias',
            'activeMenu' => 'configuracoes',
            'user' => $usuario,
            'academia' => Academia::find($academiaId),
            'equipe' => User::daAcademia($academiaId),
            'planoLabel' => Plano::label(Academia::find($academiaId)?->plano ?? Plano::ESSENCIAL),
            'perfilErrors' => Session::flash('config_perfil_errors') ?? [],
            'perfilSuccess' => Session::flash('config_perfil_success'),
            'equipeErrors' => Session::flash('config_equipe_errors') ?? [],
            'equipeOld' => Session::flash('config_equipe_old') ?? [],
            'equipeSuccess' => Session::flash('config_equipe_success'),
        ], 'dashboard');
    }

    /**
     * Chave Pix propria da academia (recebe direto na conta dela, sem
     * gateway) - usada pelo QR Code exibido na hora de fechar um
     * atendimento (ver AgendamentoController::pagamentoForm).
     */
    public function salvarPix(): void
    {
        $usuario = $this->exigirAdmin();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $chave = trim((string) $this->request->input('pix_chave', ''));
        $nome = trim((string) $this->request->input('pix_nome_beneficiario', ''));
        $cidade = trim((string) $this->request->input('pix_cidade', ''));

        if ($chave !== '' && ($nome === '' || $cidade === '')) {
            Session::flash('config_perfil_errors', ['Pra ativar o Pix, preencha também o nome do beneficiário e a cidade.']);
            $this->redirect('/dashboard/configuracoes');
        }

        Academia::atualizarPix($usuario->academiaId, $chave, $nome, $cidade);

        Session::flash('config_perfil_success', $chave !== '' ? 'Chave Pix salva.' : 'Chave Pix removida.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Troca o plano da academia imediatamente - a proxima cobranca
     * (Pix ou cartao, ver AssinaturaController) ja sai no valor do
     * plano novo.
     */
    public function trocarPlano(): void
    {
        $usuario = $this->exigirAdmin();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $plano = (string) $this->request->input('plano', '');

        if (!Plano::valido($plano)) {
            Session::flash('config_perfil_errors', ['Escolha um plano válido.']);
            $this->redirect('/dashboard/configuracoes');
        }

        Academia::atualizarPlano($usuario->academiaId, $plano);

        Session::flash('config_perfil_success', 'Plano alterado para ' . Plano::label($plano) . '.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Cancelamento self-service: sem fidelidade, o acesso continua ate
     * o fim do ciclo ja pago (proximo_vencimento) - so depois disso o
     * cron suspender_assinaturas_canceladas.php bloqueia de fato (ver
     * Academias\Models\Academia::canceladasComCicloEncerrado). Quem
     * paga por cartao tem a cobranca recorrente PAUSADA na hora no
     * Mercado Pago, pra nao ser cobrado de novo - quem paga por Pix so
     * deixa de receber a proxima cobranca (cron/gerar_faturas_pix.php
     * ja ignora academia cancelada).
     */
    public function cancelarAssinatura(): void
    {
        $usuario = $this->exigirAdmin();
        $academia = Academia::find($usuario->academiaId);

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($academia === null || $academia->canceladoEm !== null) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($academia->metodoPagamento === 'cartao' && $academia->mpPreapprovalId !== null) {
            $mp = new MercadoPagoClient();

            try {
                $resposta = $mp->pausarAssinatura($academia->mpPreapprovalId);
            } catch (\RuntimeException) {
                Session::flash('config_perfil_errors', ['Não foi possível cancelar a cobrança recorrente agora. Tente novamente em instantes.']);
                $this->redirect('/dashboard/configuracoes');
            }

            if ($resposta['status'] >= 300) {
                Session::flash('config_perfil_errors', ['Não foi possível cancelar a cobrança recorrente agora. Tente novamente em instantes.']);
                $this->redirect('/dashboard/configuracoes');
            }
        }

        Academia::marcarCancelamento($academia->id);

        $mensagem = $academia->proximoVencimento !== null
            ? 'Assinatura cancelada. Você continua com acesso até ' . (new \DateTimeImmutable($academia->proximoVencimento))->format('d/m/Y') . '.'
            : 'Assinatura cancelada.';

        Session::flash('config_perfil_success', $mensagem);
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * So funciona enquanto a academia ainda esta 'ativo' (dentro do
     * ciclo ja pago) - se ja passou disso, o AuthMiddleware ja bloqueou
     * o acesso a esta pagina e manda direto pra /dashboard/assinatura,
     * que tem seu proprio fluxo de pagamento (e limpa cancelado_em via
     * WebhookController quando confirma).
     */
    public function reativarAssinatura(): void
    {
        $usuario = $this->exigirAdmin();
        $academia = Academia::find($usuario->academiaId);

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($academia === null || $academia->canceladoEm === null) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($academia->metodoPagamento === 'cartao' && $academia->mpPreapprovalId !== null) {
            $mp = new MercadoPagoClient();

            try {
                $resposta = $mp->reativarAssinatura($academia->mpPreapprovalId);
            } catch (\RuntimeException) {
                Session::flash('config_perfil_errors', ['Não foi possível reativar a cobrança recorrente agora. Tente novamente em instantes.']);
                $this->redirect('/dashboard/configuracoes');
            }

            if ($resposta['status'] >= 300) {
                Session::flash('config_perfil_errors', ['Não foi possível reativar a cobrança recorrente agora. Tente novamente em instantes.']);
                $this->redirect('/dashboard/configuracoes');
            }
        }

        Academia::cancelarCancelamento($academia->id);

        Session::flash('config_perfil_success', 'Assinatura reativada.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function atualizarPerfil(): void
    {
        $usuario = $this->exigirAdmin();
        $academiaId = $usuario->academiaId;

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $telefone = trim((string) $this->request->input('telefone', ''));
        $corPrimaria = trim((string) $this->request->input('cor_primaria', ''));
        $errors = [];

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe o nome da academia (mínimo 3 caracteres).';
        }

        if ($corPrimaria !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $corPrimaria)) {
            $errors[] = 'Cor de destaque inválida.';
        }

        if ($errors !== []) {
            Session::flash('config_perfil_errors', $errors);
            $this->redirect('/dashboard/configuracoes');
        }

        Academia::atualizarPerfil($academiaId, $nome, $telefone !== '' ? $telefone : null);
        Academia::atualizarCorPrimaria($academiaId, $corPrimaria !== '' ? strtoupper($corPrimaria) : null);
        $this->processarUploadLogo($academiaId);

        Session::flash('config_perfil_success', 'Dados da academia atualizados.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Faz o upload do logo (campo opcional dentro do mesmo form de
     * perfil) - se nao vier nenhum arquivo, nao mexe no logo atual.
     * Erros de upload NAO bloqueiam o salvamento do resto do perfil,
     * so viram um aviso.
     */
    private function processarUploadLogo(int $academiaId): void
    {
        $arquivo = $this->request->file('logo');

        if ($arquivo === null || $arquivo['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO_LOGO) {
            Session::flash('config_perfil_success', 'Dados salvos, mas o logo excede 5MB e não foi enviado.');

            return;
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('config_perfil_success', 'Dados salvos, mas o formato do logo é inválido (use PNG, JPG ou WEBP).');

            return;
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            return;
        }

        $antigo = Academia::find($academiaId);

        if ($antigo?->logoPath !== null) {
            $caminhoAntigo = dirname(__DIR__, 2) . '/public/' . $antigo->logoPath;

            if (is_file($caminhoAntigo)) {
                unlink($caminhoAntigo);
            }
        }

        $nomeArquivo = 'logo_' . $academiaId . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return;
        }

        Academia::atualizarLogo($academiaId, self::UPLOAD_DIR . '/' . $nomeArquivo);
    }

    public function criarUsuario(): void
    {
        $usuario = $this->exigirAdmin();
        $academiaId = $usuario->academiaId;

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

        User::create($academiaId, (string) $dados['name'], (string) $dados['email'], (string) $dados['password'], $role);

        Session::flash('config_equipe_success', 'Acesso criado com sucesso.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function editarUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->academiaId !== $usuario->academiaId) {
            $this->redirect('/dashboard/configuracoes');
        }

        echo $this->view('dashboard.configuracoes.equipe-editar', [
            'pageTitle' => 'Editar acesso - KADOSYS Academias',
            'activeMenu' => 'configuracoes',
            'user' => $usuario,
            'academia' => Academia::find($usuario->academiaId),
            'membro' => $membro,
            'old' => Session::flash('config_equipe_editar_old') ?? [],
            'errors' => Session::flash('config_equipe_editar_errors') ?? [],
        ], 'dashboard');
    }

    public function atualizarUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $academiaId = $usuario->academiaId;
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->academiaId !== $academiaId) {
            $this->redirect('/dashboard/configuracoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $dados = $this->request->only(['name', 'email', 'role', 'password']);
        $errors = $this->validarEquipe($dados, $membro->id, exigirSenha: false);

        $active = $this->request->input('active') !== null;
        $role = $dados['role'] === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_USUARIO;

        // Nao deixa a academia ficar sem NENHUM admin ativo (senao
        // ninguem mais consegue gerenciar equipe/dados da conta) - so
        // bloqueia se essa edicao especifica tiraria o UNICO admin
        // ativo restante do cargo/status de admin ativo.
        $eraAdminAtivo = $membro->role === User::ROLE_ADMIN && $membro->active;
        $seriaAdminAtivo = $role === User::ROLE_ADMIN && $active;

        if ($eraAdminAtivo && !$seriaAdminAtivo && User::contarAdminsAtivos($academiaId) <= 1) {
            $errors[] = 'Precisa existir pelo menos um administrador ativo.';
        }

        if ($errors !== []) {
            Session::flash('config_equipe_editar_errors', $errors);
            Session::flash('config_equipe_editar_old', $dados);
            $this->redirect('/dashboard/configuracoes/equipe/' . $id . '/editar');
        }

        User::update($membro->id, $academiaId, (string) $dados['name'], (string) $dados['email'], $role, $active);

        $novaSenha = trim((string) ($dados['password'] ?? ''));

        if ($novaSenha !== '') {
            User::updatePassword($membro->id, $academiaId, $novaSenha);
        }

        Session::flash('config_equipe_success', 'Acesso atualizado.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function excluirUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $academiaId = $usuario->academiaId;
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->academiaId !== $academiaId) {
            $this->redirect('/dashboard/configuracoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($membro->id === $usuario->id) {
            Session::flash('config_equipe_errors', ['Você não pode excluir o próprio acesso.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($membro->role === User::ROLE_ADMIN && User::contarAdminsAtivos($academiaId) <= 1) {
            Session::flash('config_equipe_errors', ['Precisa existir pelo menos um administrador ativo.']);
            $this->redirect('/dashboard/configuracoes');
        }

        User::delete($membro->id, $academiaId);

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
