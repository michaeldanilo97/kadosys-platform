<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Database;
use Food\Core\Session;
use Food\Models\Impressora;
use Food\Models\Plano;
use Food\Models\Restaurante;
use Food\Models\User;

/**
 * Configuracoes: perfil/white-label do restaurante, chave Pix propria,
 * dados fiscais informativos, equipe (usuarios com acesso), impressoras
 * (so cadastro informativo) e exportacao de backup. So "admin" acessa -
 * "usuario" (equipe) nao gerencia a conta nem outros acessos, mesmo
 * padrao ja usado no Barbearias/Academias (so os dois papeis existem
 * hoje, sem ACL granular por modulo).
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
        $restauranteId = $usuario->restauranteId;

        echo $this->view('dashboard.configuracoes.index', [
            'pageTitle' => 'Configurações - KADOSYS Food',
            'activeMenu' => 'configuracoes',
            'user' => $usuario,
            'restaurante' => Restaurante::find($restauranteId),
            'planoLabel' => Plano::label(Restaurante::find($restauranteId)?->plano ?? Plano::ESSENCIAL),
            'equipe' => User::doRestaurante($restauranteId),
            'impressoras' => Impressora::doRestaurante($restauranteId),
            'perfilErrors' => Session::flash('config_perfil_errors') ?? [],
            'perfilSuccess' => Session::flash('config_perfil_success'),
            'fiscalErrors' => Session::flash('config_fiscal_errors') ?? [],
            'fiscalSuccess' => Session::flash('config_fiscal_success'),
            'equipeErrors' => Session::flash('config_equipe_errors') ?? [],
            'equipeOld' => Session::flash('config_equipe_old') ?? [],
            'equipeSuccess' => Session::flash('config_equipe_success'),
            'impressoraErrors' => Session::flash('config_impressora_errors') ?? [],
        ], 'dashboard');
    }

    public function atualizarPerfil(): void
    {
        $usuario = $this->exigirAdmin();
        $restauranteId = $usuario->restauranteId;

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $telefone = trim((string) $this->request->input('telefone', ''));
        $corPrimaria = trim((string) $this->request->input('cor_primaria', ''));
        $errors = [];

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe o nome do restaurante (mínimo 3 caracteres).';
        }

        if ($corPrimaria !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $corPrimaria)) {
            $errors[] = 'Cor de destaque inválida.';
        }

        if ($errors !== []) {
            Session::flash('config_perfil_errors', $errors);
            $this->redirect('/dashboard/configuracoes');
        }

        Restaurante::atualizarPerfil($restauranteId, $nome, $telefone !== '' ? $telefone : null);
        Restaurante::atualizarCorPrimaria($restauranteId, $corPrimaria !== '' ? strtoupper($corPrimaria) : null);
        $this->processarUploadLogo($restauranteId);

        Session::flash('config_perfil_success', 'Dados do restaurante atualizados.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Dados fiscais INFORMATIVOS - so aparecem em relatorios internos,
     * sem emissao de NF-e (ver Food\Models\Restaurante::atualizarDadosFiscais).
     */
    public function atualizarDadosFiscais(): void
    {
        $usuario = $this->exigirAdmin();
        $restauranteId = $usuario->restauranteId;

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $documentoTipo = (string) $this->request->input('documento_tipo', 'cpf');
        $documento = trim((string) $this->request->input('documento', ''));
        $razaoSocial = trim((string) $this->request->input('razao_social', ''));
        $errors = [];

        if ($documento !== '' && !in_array(strlen(preg_replace('/\D/', '', $documento) ?? ''), [11, 14], true)) {
            $errors[] = 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.';
        }

        if ($errors !== []) {
            Session::flash('config_fiscal_errors', $errors);
            $this->redirect('/dashboard/configuracoes');
        }

        Restaurante::atualizarDadosFiscais($restauranteId, $documentoTipo, $documento, $razaoSocial !== '' ? $razaoSocial : null);

        Session::flash('config_fiscal_success', 'Dados fiscais atualizados.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Chave Pix propria do restaurante (recebe direto na conta dele,
     * sem gateway) - usada pelo QR Code exibido no PDV (ver
     * CaixaController).
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

        Restaurante::atualizarPix($usuario->restauranteId, $chave, $nome, $cidade);

        Session::flash('config_perfil_success', $chave !== '' ? 'Chave Pix salva.' : 'Chave Pix removida.');
        $this->redirect('/dashboard/configuracoes');
    }

    private function processarUploadLogo(int $restauranteId): void
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

        $antigo = Restaurante::find($restauranteId);

        if ($antigo?->logoPath !== null) {
            $caminhoAntigo = dirname(__DIR__, 2) . '/public/' . $antigo->logoPath;

            if (is_file($caminhoAntigo)) {
                unlink($caminhoAntigo);
            }
        }

        $nomeArquivo = 'logo_' . $restauranteId . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return;
        }

        Restaurante::atualizarLogo($restauranteId, self::UPLOAD_DIR . '/' . $nomeArquivo);
    }

    public function criarUsuario(): void
    {
        $usuario = $this->exigirAdmin();
        $restauranteId = $usuario->restauranteId;

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

        User::create($restauranteId, (string) $dados['name'], (string) $dados['email'], (string) $dados['password'], $role);

        Session::flash('config_equipe_success', 'Acesso criado com sucesso.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function editarUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->restauranteId !== $usuario->restauranteId) {
            $this->redirect('/dashboard/configuracoes');
        }

        echo $this->view('dashboard.configuracoes.equipe-editar', [
            'pageTitle' => 'Editar acesso - KADOSYS Food',
            'activeMenu' => 'configuracoes',
            'user' => $usuario,
            'restaurante' => Restaurante::find($usuario->restauranteId),
            'membro' => $membro,
            'old' => Session::flash('config_equipe_editar_old') ?? [],
            'errors' => Session::flash('config_equipe_editar_errors') ?? [],
        ], 'dashboard');
    }

    public function atualizarUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $restauranteId = $usuario->restauranteId;
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->restauranteId !== $restauranteId) {
            $this->redirect('/dashboard/configuracoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $dados = $this->request->only(['name', 'email', 'role', 'password']);
        $errors = $this->validarEquipe($dados, $membro->id, exigirSenha: false);

        $active = $this->request->input('active') !== null;
        $role = $dados['role'] === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_USUARIO;

        // Nao deixa o restaurante ficar sem NENHUM admin ativo (senao
        // ninguem mais consegue gerenciar equipe/dados da conta) - so
        // bloqueia se essa edicao especifica tiraria o UNICO admin
        // ativo restante do cargo/status de admin ativo.
        $eraAdminAtivo = $membro->role === User::ROLE_ADMIN && $membro->active;
        $seriaAdminAtivo = $role === User::ROLE_ADMIN && $active;

        if ($eraAdminAtivo && !$seriaAdminAtivo && User::contarAdminsAtivos($restauranteId) <= 1) {
            $errors[] = 'Precisa existir pelo menos um administrador ativo.';
        }

        if ($errors !== []) {
            Session::flash('config_equipe_editar_errors', $errors);
            Session::flash('config_equipe_editar_old', $dados);
            $this->redirect('/dashboard/configuracoes/equipe/' . $id . '/editar');
        }

        User::update($membro->id, $restauranteId, (string) $dados['name'], (string) $dados['email'], $role, $active);

        $novaSenha = trim((string) ($dados['password'] ?? ''));

        if ($novaSenha !== '') {
            User::updatePassword($membro->id, $restauranteId, $novaSenha);
        }

        Session::flash('config_equipe_success', 'Acesso atualizado.');
        $this->redirect('/dashboard/configuracoes');
    }

    public function excluirUsuario(string $id): void
    {
        $usuario = $this->exigirAdmin();
        $restauranteId = $usuario->restauranteId;
        $membro = User::findById((int) $id);

        if ($membro === null || $membro->restauranteId !== $restauranteId) {
            $this->redirect('/dashboard/configuracoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        if ($membro->id === $usuario->id) {
            Session::flash('config_equipe_errors', ['Você não pode excluir o próprio acesso.']);
            $this->redirect('/dashboard/configuracoes');
        }

        if ($membro->role === User::ROLE_ADMIN && User::contarAdminsAtivos($restauranteId) <= 1) {
            Session::flash('config_equipe_errors', ['Precisa existir pelo menos um administrador ativo.']);
            $this->redirect('/dashboard/configuracoes');
        }

        User::delete($membro->id, $restauranteId);

        Session::flash('config_equipe_success', 'Acesso removido.');
        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Cadastro de impressora - so um registro informativo (nome/IP),
     * sem driver/protocolo real (ver Food\Models\Impressora).
     */
    public function criarImpressora(): void
    {
        $usuario = $this->exigirAdmin();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        $nome = trim((string) $this->request->input('nome', ''));
        $ip = trim((string) $this->request->input('ip', ''));

        if ($nome === '') {
            Session::flash('config_impressora_errors', ['Informe um nome pra identificar a impressora.']);
            $this->redirect('/dashboard/configuracoes');
        }

        Impressora::criar($usuario->restauranteId, $nome, $ip !== '' ? $ip : null);

        $this->redirect('/dashboard/configuracoes');
    }

    public function excluirImpressora(string $id): void
    {
        $usuario = $this->exigirAdmin();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/configuracoes');
        }

        Impressora::excluir((int) $id, $usuario->restauranteId);

        $this->redirect('/dashboard/configuracoes');
    }

    /**
     * Backup manual: dump JSON de todos os dados do proprio restaurante
     * pra download - NAO e um backup automatico de infraestrutura, so
     * uma exportacao sob demanda (ver plano da Fase 9).
     */
    public function backup(): void
    {
        $usuario = $this->exigirAdmin();
        $restauranteId = $usuario->restauranteId;
        $pdo = Database::connection();

        $consulta = static function (string $sql) use ($pdo, $restauranteId): array {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['restaurante_id' => $restauranteId]);

            return $stmt->fetchAll();
        };

        $dump = [
            'exportado_em' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'restaurante' => $consulta('SELECT id, nome, slug, telefone, documento_tipo, documento, razao_social, cor_primaria, plano, status, created_at FROM restaurantes WHERE id = :restaurante_id'),
            'usuarios' => $consulta('SELECT id, name, email, role, active, created_at FROM users WHERE restaurante_id = :restaurante_id'),
            'categorias' => $consulta('SELECT * FROM categorias WHERE restaurante_id = :restaurante_id'),
            'fornecedores' => $consulta('SELECT * FROM fornecedores WHERE restaurante_id = :restaurante_id'),
            'ingredientes' => $consulta('SELECT * FROM ingredientes WHERE restaurante_id = :restaurante_id'),
            'produtos' => $consulta('SELECT * FROM produtos WHERE restaurante_id = :restaurante_id'),
            'ficha_tecnica_itens' => $consulta('SELECT fti.* FROM ficha_tecnica_itens fti INNER JOIN produtos p ON p.id = fti.produto_id WHERE p.restaurante_id = :restaurante_id'),
            'custeio_config' => $consulta('SELECT * FROM custeio_config WHERE restaurante_id = :restaurante_id'),
            'clientes' => $consulta('SELECT * FROM clientes WHERE restaurante_id = :restaurante_id'),
            'pedidos' => $consulta('SELECT * FROM pedidos WHERE restaurante_id = :restaurante_id'),
            'pedido_itens' => $consulta('SELECT pi.* FROM pedido_itens pi INNER JOIN pedidos p ON p.id = pi.pedido_id WHERE p.restaurante_id = :restaurante_id'),
            'compras' => $consulta('SELECT * FROM compras WHERE restaurante_id = :restaurante_id'),
            'centros_custo' => $consulta('SELECT * FROM centros_custo WHERE restaurante_id = :restaurante_id'),
            'contas_a_pagar' => $consulta('SELECT * FROM contas_a_pagar WHERE restaurante_id = :restaurante_id'),
            'contas_a_receber' => $consulta('SELECT * FROM contas_a_receber WHERE restaurante_id = :restaurante_id'),
            'financeiro_lancamentos' => $consulta('SELECT * FROM financeiro_lancamentos WHERE restaurante_id = :restaurante_id'),
            'impressoras' => $consulta('SELECT id, nome, ip, ativo, created_at FROM impressoras WHERE restaurante_id = :restaurante_id'),
        ];

        $json = json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $nomeArquivo = 'backup-kadosys-food-' . (new \DateTimeImmutable())->format('Y-m-d') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen((string) $json));
        echo $json;
        exit;
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
