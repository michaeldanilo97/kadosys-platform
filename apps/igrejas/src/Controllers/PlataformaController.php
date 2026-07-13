<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\CpanelUapiClient;
use Igrejas\Core\Desprovisionador;
use Igrejas\Core\Middleware\PlataformaAuthMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\AssinaturaTenant;
use Igrejas\Models\FaturaPix;
use Igrejas\Models\PlataformaAviso;
use Igrejas\Models\Tenant;

/**
 * Painel administrativo da plataforma (dono do sistema) - lista e
 * exclui igrejas provisionadas automaticamente. Autenticacao propria
 * (uma unica chave mestra, ver config/plataforma.php), separada do
 * login normal de cada igreja.
 */
final class PlataformaController extends Controller
{
    public function entrar(): void
    {
        if (Session::get(PlataformaAuthMiddleware::SESSION_KEY) === true) {
            $this->redirect('/plataforma/igrejas');
        }

        echo $this->view('plataforma.entrar', [
            'pageTitle' => 'Painel da Plataforma - KADOSYS',
            'csrf' => Csrf::field(),
            'error' => Session::flash('plataforma_login_error'),
        ], 'auth');
    }

    public function autenticar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('plataforma_login_error', 'Sessão expirada. Tente novamente.');
            $this->redirect('/plataforma/entrar');
        }

        $chave = (string) $this->request->input('chave', '');
        $config = require dirname(__DIR__, 2) . '/config/plataforma.php';

        if ($config['senha_hash'] === '' || !password_verify($chave, $config['senha_hash'])) {
            Session::flash('plataforma_login_error', 'Chave inválida.');
            $this->redirect('/plataforma/entrar');
        }

        Session::regenerate();
        Session::set(PlataformaAuthMiddleware::SESSION_KEY, true);

        $this->redirect('/plataforma/igrejas');
    }

    public function sair(): void
    {
        Session::remove(PlataformaAuthMiddleware::SESSION_KEY);
        $this->redirect('/plataforma/entrar');
    }

    public function igrejas(): void
    {
        $igrejas = Tenant::listarTodas();

        echo $this->view('plataforma.igrejas', [
            'pageTitle' => 'Igrejas - Painel da Plataforma',
            'activeMenu' => 'igrejas',
            'igrejas' => $igrejas,
            'situacoesPagamento' => $this->situacoesPagamento($igrejas),
            'success' => Session::flash('plataforma_success'),
            'errors' => Session::flash('plataforma_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'plataforma');
    }

    /**
     * Resumo da situacao de pagamento de cada igreja, pro dono da
     * plataforma acompanhar de relance quem esta em dia e quem precisa de
     * atencao - mesma logica de bloqueio ja usada em
     * Igrejas\Core\Middleware\AuthMiddleware (trial vencido/fatura Pix
     * vencida), so que resumida aqui numa unica badge por igreja em vez
     * de decidir se bloqueia ou nao o acesso.
     *
     * @param array<int, Tenant> $igrejas
     * @return array<int, array{label:string, classe:string}>
     */
    private function situacoesPagamento(array $igrejas): array
    {
        $situacoes = [];

        foreach ($igrejas as $tenant) {
            $situacoes[$tenant->id] = match ($tenant->metodoPagamento) {
                'trial' => $this->situacaoTrial($tenant),
                'cartao' => $this->situacaoCartao($tenant),
                'pix' => $this->situacaoPix($tenant),
                default => ['label' => 'Desconhecido', 'classe' => 'pendente'],
            };
        }

        return $situacoes;
    }

    /** @return array{label:string, classe:string} */
    private function situacaoTrial(Tenant $tenant): array
    {
        if ($tenant->trialExpiraEm === null) {
            return ['label' => 'Em teste', 'classe' => 'pendente'];
        }

        $expiraEm = new \DateTimeImmutable($tenant->trialExpiraEm);

        if (new \DateTimeImmutable() > $expiraEm) {
            return ['label' => 'Teste vencido', 'classe' => 'cancelada'];
        }

        return ['label' => 'Em teste até ' . $expiraEm->format('d/m/Y'), 'classe' => 'pendente'];
    }

    /** @return array{label:string, classe:string} */
    private function situacaoCartao(Tenant $tenant): array
    {
        $assinatura = AssinaturaTenant::ultimaDoTenant($tenant->id);

        if ($assinatura === null) {
            return ['label' => 'Sem assinatura', 'classe' => 'pendente'];
        }

        return match ($assinatura->status) {
            'autorizada' => ['label' => 'Em dia', 'classe' => 'autorizada'],
            'pausada' => ['label' => 'Pausada', 'classe' => 'pausada'],
            'cancelada' => ['label' => 'Cancelada', 'classe' => 'cancelada'],
            default => ['label' => 'Pagamento pendente', 'classe' => 'pendente'],
        };
    }

    /** @return array{label:string, classe:string} */
    private function situacaoPix(Tenant $tenant): array
    {
        $fatura = FaturaPix::ultimaDoTenant($tenant->id);

        if ($fatura === null) {
            return ['label' => 'Sem fatura', 'classe' => 'pendente'];
        }

        if ($fatura->status === 'paga') {
            return ['label' => 'Em dia', 'classe' => 'autorizada'];
        }

        if ($fatura->status === 'cancelada') {
            return ['label' => 'Cancelada', 'classe' => 'cancelada'];
        }

        // 'pendente' ainda dentro do prazo (nao vencida) ou 'expirada'
        // (vencida sem pagamento, ver FaturaPix::marcarExpiradasVencidas)
        // - mesmo criterio de bloqueio do AuthMiddleware.
        $vencida = $fatura->status === 'expirada'
            || new \DateTimeImmutable() > new \DateTimeImmutable($fatura->vencimento);

        return $vencida
            ? ['label' => 'Atrasado', 'classe' => 'cancelada']
            : ['label' => 'Fatura em aberto', 'classe' => 'pendente'];
    }

    /**
     * Exclui uma igreja de vez: banco de dados, usuario do banco e
     * subdominio no cPanel, alem do registro central - ver
     * Igrejas\Core\Desprovisionador. Acao irreversivel de proposito
     * sem "lixeira"/soft-delete, entao a confirmacao na tela (ver
     * plataforma/igrejas.php) precisa ser bem clara.
     */
    public function excluirIgreja(string $id): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('plataforma_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/plataforma/igrejas');
        }

        $tenant = Tenant::buscarPorId((int) $id);

        if ($tenant === null) {
            Session::flash('plataforma_errors', ['Igreja não encontrada.']);
            $this->redirect('/plataforma/igrejas');
        }

        $resultado = (new Desprovisionador(new CpanelUapiClient()))->excluir($tenant);

        if ($resultado['erros'] === []) {
            Session::flash('plataforma_success', array_merge(
                ['"' . $tenant->nomeIgreja . '" foi excluída (banco de dados e usuário removidos do cPanel, registro removido do sistema).'],
                $resultado['avisos']
            ));
        } else {
            Session::flash('plataforma_errors', array_merge(
                ['"' . $tenant->nomeIgreja . '" foi removida do sistema, mas algumas etapas no cPanel falharam - confira manualmente:'],
                $resultado['erros'],
                $resultado['avisos']
            ));
        }

        $this->redirect('/plataforma/igrejas');
    }

    /**
     * Avisos do dono da plataforma pra todas as igrejas cadastradas
     * (ver PlataformaAviso) - mostrados no sino de notificacoes de
     * cada uma delas.
     */
    public function avisos(): void
    {
        echo $this->view('plataforma.avisos', [
            'pageTitle' => 'Avisos - Painel da Plataforma',
            'activeMenu' => 'avisos',
            'avisoAtivo' => PlataformaAviso::ativo(),
            'historico' => PlataformaAviso::todos(),
            'success' => Session::flash('plataforma_success'),
            'errors' => Session::flash('plataforma_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'plataforma');
    }

    public function publicarAviso(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('plataforma_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/plataforma/avisos');
        }

        $mensagem = trim((string) $this->request->input('mensagem', ''));
        $publicoAlvo = (string) $this->request->input('publico_alvo', PlataformaAviso::PUBLICO_TODOS);

        if ($mensagem === '') {
            Session::flash('plataforma_errors', ['Escreva a mensagem do aviso.']);
            $this->redirect('/plataforma/avisos');
        }

        PlataformaAviso::publicar($mensagem, $publicoAlvo);

        Session::flash('plataforma_success', [
            'Aviso publicado para "' . (PlataformaAviso::PUBLICO_LABELS[$publicoAlvo] ?? PlataformaAviso::PUBLICO_LABELS[PlataformaAviso::PUBLICO_TODOS]) . '" - vai aparecer no sino de notificações de todas as igrejas.',
        ]);
        $this->redirect('/plataforma/avisos');
    }

    public function encerrarAviso(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            PlataformaAviso::encerrar((int) $id);
            Session::flash('plataforma_success', ['Aviso encerrado.']);
        }

        $this->redirect('/plataforma/avisos');
    }
}
