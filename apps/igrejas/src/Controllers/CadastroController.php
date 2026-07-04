<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\CpanelUapiClient;
use Igrejas\Core\Csrf;
use Igrejas\Core\Documento;
use Igrejas\Core\Provisionador;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Plano;
use Igrejas\Models\Provisionamento;
use Igrejas\Models\Tenant;

/**
 * Cadastro publico autoatendido de uma igreja nova: so os dados da
 * igreja + administrador, sem escolha de plano/pagamento aqui - toda
 * igreja nova comeca com 7 dias de teste gratis (acesso completo,
 * ver Plano::disponivel()) e so escolhe/paga um plano depois, de
 * dentro do proprio painel (Configuracoes, ver AssinaturaController) -
 * pagamento nunca acontece nesta tela publica, pra nao parecer que a
 * KADOSYS esta revendendo planos na propria tela de criacao da conta.
 *
 * A mesma URL (/cadastro) serve dois propositos bem diferentes,
 * dependendo do dominio: no dominio principal (nenhum tenant
 * resolvido) e esta tela, de criar uma igreja nova. Ja dentro do
 * subdominio de uma igreja ja existente (TenantResolver::atual() != null)
 * uma igreja nova NAO faz sentido - delega pro auto-cadastro publico de
 * membros (ver MembroPublicoController), a mesma tela acessada pelo
 * link "Cadastre-se" na tela de login daquela igreja.
 */
final class CadastroController extends Controller
{
    private const SENHA_MINIMA = 8;

    public function form(): void
    {
        if (TenantResolver::atual() !== null) {
            (new MembroPublicoController($this->request, $this->config))->form();

            return;
        }

        echo $this->view('cadastro.form', [
            'pageTitle' => 'Criar conta - KADOSYS Igrejas',
            'csrf' => Csrf::field(),
            'errors' => Session::flash('cadastro_errors') ?? [],
            'old' => Session::flash('cadastro_old') ?? [],
        ], 'auth');
    }

    public function enviar(): void
    {
        if (TenantResolver::atual() !== null) {
            (new MembroPublicoController($this->request, $this->config))->enviar();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('cadastro_errors', ['Sessao expirada. Preencha o formulario novamente.']);
            $this->redirect('/cadastro');
        }

        $nomeIgreja = trim((string) $this->request->input('nome_igreja', ''));
        $slugInformado = trim((string) $this->request->input('slug', ''));
        $adminNome = trim((string) $this->request->input('admin_nome', ''));
        $adminEmail = trim((string) $this->request->input('admin_email', ''));
        $senha = (string) $this->request->input('senha', '');
        $senhaConfirmacao = (string) $this->request->input('senha_confirmacao', '');
        $documentoTipo = (string) $this->request->input('documento_tipo', 'cpf');
        $documentoInformado = trim((string) $this->request->input('documento', ''));
        $razaoSocial = trim((string) $this->request->input('razao_social', ''));

        if (!in_array($documentoTipo, ['cpf', 'cnpj'], true)) {
            $documentoTipo = 'cpf';
        }

        $documento = Documento::apenasDigitos($documentoInformado);

        $old = [
            'nome_igreja' => $nomeIgreja,
            'slug' => $slugInformado,
            'admin_nome' => $adminNome,
            'admin_email' => $adminEmail,
            'documento_tipo' => $documentoTipo,
            'documento' => $documentoInformado,
            'razao_social' => $razaoSocial,
        ];

        $slug = $this->normalizarSlug($slugInformado !== '' ? $slugInformado : $nomeIgreja);
        $errors = $this->validar(
            $nomeIgreja,
            $slug,
            $adminNome,
            $adminEmail,
            $senha,
            $senhaConfirmacao,
            $documentoTipo,
            $documento,
            $razaoSocial,
        );

        if ($documento !== '' && Tenant::documentoJaUsouTrial($documento)) {
            $errors[] = 'Esse CPF/CNPJ ja usou o teste gratis antes. Fale com a gente pra continuar.';
        }

        if ($errors !== []) {
            Session::flash('cadastro_errors', $errors);
            Session::flash('cadastro_old', $old);
            $this->redirect('/cadastro');
        }

        $razaoSocialFinal = $documentoTipo === 'cnpj' ? $razaoSocial : null;

        $this->criarContaTrial($nomeIgreja, $slug, $adminNome, $adminEmail, $documentoTipo, $documento, $razaoSocialFinal, $senha, Plano::ESSENCIAL);
    }

    /**
     * Teste gratis de 7 dias, unico caminho possivel nesta tela (ver
     * classe): sem Mercado Pago nenhum envolvido - cria o
     * provisionamento e ja dispara o provisionamento de verdade (banco,
     * subdominio, etc.) na hora, de forma sincrona, sem pagamento nenhum
     * pra esperar. Como e sincrono, ja sabemos o resultado final antes
     * de responder - mas o subdominio recem-criado no cPanel pode levar
     * um tempo pro DNS propagar (confirmado num teste real: "pagina nao
     * existe" mesmo com o subdominio ja existindo no painel), entao
     * manda pra tela de espera (pronto()) em vez de redirecionar direto
     * pra ele.
     */
    private function criarContaTrial(
        string $nomeIgreja,
        string $slug,
        string $adminNome,
        string $adminEmail,
        string $documentoTipo,
        string $documento,
        ?string $razaoSocial,
        string $senha,
        string $plano,
    ): void {
        $provisionamentoId = Provisionamento::criar(
            $nomeIgreja,
            $slug,
            $adminNome,
            $adminEmail,
            $documentoTipo,
            $documento,
            $razaoSocial,
            password_hash($senha, PASSWORD_BCRYPT),
            $plano,
            'trial',
        );

        if (!Provisionamento::reivindicarProcessamento($provisionamentoId)) {
            // Nao deveria acontecer (o provisionamento acabou de ser
            // criado agora mesmo, ninguem mais tem o id ainda) - por
            // seguranca, trata como falha em vez de travar o usuario.
            Session::flash('cadastro_errors', ['Nao foi possivel iniciar seu teste gratis agora. Tente novamente.']);
            $this->redirect('/cadastro');
        }

        $provisionamento = Provisionamento::buscarPorId($provisionamentoId);
        (new Provisionador(new CpanelUapiClient()))->provisionar($provisionamento);

        $provisionamentoFinal = Provisionamento::buscarPorId($provisionamentoId);

        if ($provisionamentoFinal?->status !== 'concluido' || $provisionamentoFinal->tenantId === null) {
            Session::flash('cadastro_errors', ['Tivemos um problema ao preparar sua conta de teste. Nossa equipe ja foi avisada - tente novamente em alguns minutos ou fale com o suporte.']);
            $this->redirect('/cadastro');
        }

        $this->redirect('/cadastro/pronto/' . $provisionamentoId);
    }

    /**
     * Tela de espera exibida logo depois do teste gratis ser criado (ver
     * criarContaTrial()) - o subdominio acabou de ser criado no cPanel,
     * mas o navegador as vezes ainda nao consegue abrir
     * https://{subdominio} na hora porque o DNS nao propagou o
     * suficiente. Em vez de arriscar um redirecionamento direto que da
     * "pagina nao existe", mostra uma tela "preparando tudo" que so
     * redireciona de verdade depois que o JS (cadastro-pronto.js)
     * confirma que o subdominio ja responde.
     */
    public function pronto(string $id): void
    {
        $provisionamento = Provisionamento::buscarPorId((int) $id);

        if ($provisionamento === null || $provisionamento->status !== 'concluido' || $provisionamento->tenantId === null) {
            $this->redirect('/cadastro');
        }

        $tenant = Tenant::buscarPorId($provisionamento->tenantId);

        if ($tenant === null) {
            $this->redirect('/cadastro');
        }

        echo $this->view('cadastro.pronto', [
            'pageTitle' => 'Preparando sua conta - KADOSYS Igrejas',
            'subdominio' => $tenant->subdominio,
            'provisionamentoId' => $provisionamento->id,
        ], 'auth');
    }

    /**
     * Endpoint de polling (JS da tela de espera "pronto") - verifica se
     * o subdominio recem-criado ja esta respondendo de verdade.
     *
     * Antes esse check era feito no navegador com um fetch
     * "no-cors" direto pro subdominio: como esse modo so enxerga se a
     * conexao foi estabelecida (nunca o status/corpo da resposta - isso
     * e opaco por causa do CORS), qualquer resposta HTTP contava como
     * "pronto", inclusive uma pagina de erro do proprio servidor
     * (dominio ainda sem vhost carregado, banco daquele tenant ainda
     * nao acessivel, etc.) - o usuario era mandado pro login mesmo com
     * o site ainda quebrado. Fazendo a checagem aqui no servidor da pra
     * ler o status E o corpo de verdade, e so confirmar "pronto" quando
     * a pagina de login renderizou por completo.
     */
    public function prontoStatus(string $id): void
    {
        $provisionamento = Provisionamento::buscarPorId((int) $id);

        if ($provisionamento === null || $provisionamento->status !== 'concluido' || $provisionamento->tenantId === null) {
            $this->jsonResponse(['pronto' => false]);
        }

        $tenant = Tenant::buscarPorId($provisionamento->tenantId);

        if ($tenant === null) {
            $this->jsonResponse(['pronto' => false]);
        }

        $this->jsonResponse(['pronto' => self::subdominioRespondendo($tenant->subdominio)]);
    }

    private static function subdominioRespondendo(string $subdominio): bool
    {
        $ch = curl_init('https://' . $subdominio . '/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return self::respostaIndicaProntidao((int) $status, is_string($body) ? $body : null);
    }

    /**
     * Confirma que a resposta e a pagina de login de verdade (status
     * 200 e marcador do formulario no corpo), nao so que o servidor
     * respondeu alguma coisa (o que incluiria uma pagina de erro).
     * Separado do curl acima pra dar pra testar sem depender de rede.
     */
    private static function respostaIndicaProntidao(int $status, ?string $body): bool
    {
        return $status === 200 && $body !== null && str_contains($body, 'name="_csrf_token"');
    }

    /** @return array<int, string> */
    private function validar(
        string $nomeIgreja,
        string $slug,
        string $adminNome,
        string $adminEmail,
        string $senha,
        string $senhaConfirmacao,
        string $documentoTipo,
        string $documento,
        string $razaoSocial,
    ): array {
        $errors = [];

        if ($nomeIgreja === '' || mb_strlen($nomeIgreja) < 3) {
            $errors[] = 'Informe o nome da igreja (minimo 3 caracteres).';
        }

        if ($slug === '' || mb_strlen($slug) < 3) {
            $errors[] = 'O subdominio precisa ter pelo menos 3 letras (so letras, numeros e hifen).';
        } elseif (!Tenant::slugDisponivel($slug) || Provisionamento::slugReservado($slug)) {
            $errors[] = 'Esse subdominio ja esta em uso. Escolha outro.';
        }

        if ($adminNome === '' || mb_strlen($adminNome) < 3) {
            $errors[] = 'Informe seu nome completo.';
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail valido.';
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $errors[] = 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.';
        } elseif ($senha !== $senhaConfirmacao) {
            $errors[] = 'A confirmacao de senha nao confere.';
        }

        if (!Documento::validar($documentoTipo, $documento)) {
            $errors[] = $documentoTipo === 'cnpj' ? 'Informe um CNPJ valido.' : 'Informe um CPF valido.';
        }

        if ($documentoTipo === 'cnpj' && ($razaoSocial === '' || mb_strlen($razaoSocial) < 3)) {
            $errors[] = 'Informe a razao social da igreja/instituicao.';
        }

        return $errors;
    }

    /**
     * Normaliza um texto livre (nome da igreja ou subdominio digitado)
     * pro formato exigido por um subdominio: minusculo, sem acento, so
     * letras/numeros/hifen.
     */
    private function normalizarSlug(string $valor): string
    {
        $valor = mb_strtolower($valor);
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT', $valor);
        $valor = $transliterado !== false ? $transliterado : $valor;
        $valor = preg_replace('/[^a-z0-9]+/', '-', $valor) ?? '';

        return trim($valor, '-');
    }
}
