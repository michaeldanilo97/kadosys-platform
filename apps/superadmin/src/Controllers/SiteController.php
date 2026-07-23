<?php

declare(strict_types=1);

namespace Superadmin\Controllers;

use Superadmin\Core\Controller;
use Superadmin\Core\Csrf;
use Superadmin\Core\CpanelUapiClient;
use Superadmin\Core\Desprovisionador;
use Superadmin\Core\Session;
use Superadmin\Models\SiteBarbearia;
use Superadmin\Models\SiteFood;
use Superadmin\Models\SiteIgreja;

/**
 * Listagem unificada de sites (Igrejas + Barbearias + Food) e acoes
 * administrativas sobre eles: suspender, reativar, estender acesso
 * (ativar/postergar trial ou vencimento) e excluir.
 *
 * "Excluir" e IRREVERSIVEL - remove banco/usuario do cPanel (Igreja) ou
 * a linha + tudo em cascata (Barbearia/Food), sem backup automatico.
 * Por isso exige digitar o nome exato do site antes de confirmar
 * (confirmarExclusao/excluir).
 */
final class SiteController extends Controller
{
    private const PRODUTOS_VALIDOS = ['igrejas', 'barbearias', 'food'];

    public function index(): void
    {
        $busca = trim((string) $this->request->input('busca', ''));

        $sites = array_merge(
            array_map(self::normalizarIgreja(...), SiteIgreja::listarTodas()),
            array_map(self::normalizarBarbearia(...), SiteBarbearia::listarTodas()),
            array_map(self::normalizarFood(...), SiteFood::listarTodas()),
        );

        if ($busca !== '') {
            $buscaLower = mb_strtolower($busca);
            $sites = array_values(array_filter(
                $sites,
                static fn (array $site) => str_contains(mb_strtolower($site['nome']), $buscaLower)
                    || str_contains(mb_strtolower($site['identificador']), $buscaLower)
            ));
        }

        usort($sites, static fn (array $a, array $b) => strcmp($b['criado_em'], $a['criado_em']));

        echo $this->view('sites.index', [
            'pageTitle' => 'Sites - KADOSYS Super Admin',
            'activeMenu' => 'sites',
            'sites' => $sites,
            'busca' => $busca,
            'totalIgrejas' => count(array_filter($sites, static fn ($s) => $s['produto'] === 'igrejas')),
            'totalBarbearias' => count(array_filter($sites, static fn ($s) => $s['produto'] === 'barbearias')),
            'totalFood' => count(array_filter($sites, static fn ($s) => $s['produto'] === 'food')),
            'sucesso' => Session::flash('sites_sucesso'),
            'erro' => Session::flash('sites_erro'),
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function suspender(string $produto, string $id): void
    {
        $this->validarCsrf();
        $this->atualizarStatus($produto, (int) $id, 'suspenso');
        Session::flash('sites_sucesso', 'Site suspenso.');
        $this->redirect('/sites');
    }

    public function reativar(string $produto, string $id): void
    {
        $this->validarCsrf();
        $this->atualizarStatus($produto, (int) $id, 'ativo');
        Session::flash('sites_sucesso', 'Site reativado.');
        $this->redirect('/sites');
    }

    public function estender(string $produto, string $id): void
    {
        $this->validarCsrf();

        if (!in_array($produto, self::PRODUTOS_VALIDOS, true)) {
            $this->redirect('/sites');
        }

        $dias = (int) $this->request->input('dias', 30);
        $dias = max(1, min(365, $dias));

        $mensagem = match ($produto) {
            'igrejas' => SiteIgreja::estenderAcesso((int) $id, $dias),
            'barbearias' => SiteBarbearia::estenderAcesso((int) $id, $dias),
            default => SiteFood::estenderAcesso((int) $id, $dias),
        };

        Session::flash('sites_sucesso', $mensagem);
        $this->redirect('/sites');
    }

    public function confirmarExclusao(string $produto, string $id): void
    {
        $site = $this->buscarSite($produto, (int) $id);

        if ($site === null) {
            $this->redirect('/sites');
        }

        echo $this->view('sites.excluir', [
            'pageTitle' => 'Excluir site - KADOSYS Super Admin',
            'activeMenu' => 'sites',
            'site' => $site,
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function excluir(string $produto, string $id): void
    {
        $this->validarCsrf();

        if (!in_array($produto, self::PRODUTOS_VALIDOS, true)) {
            $this->redirect('/sites');
        }

        $site = $this->buscarSite($produto, (int) $id);

        if ($site === null) {
            $this->redirect('/sites');
        }

        $nomeDigitado = trim((string) $this->request->input('confirmacao_nome', ''));

        if ($nomeDigitado !== $site['nome']) {
            Session::flash('sites_erro', 'O nome digitado nao confere - nada foi excluido.');
            $this->redirect('/sites/' . $produto . '/' . $id . '/excluir');
        }

        if ($produto === 'igrejas') {
            $tenant = SiteIgreja::find((int) $id);

            if ($tenant !== null) {
                $desprovisionador = new Desprovisionador(new CpanelUapiClient());
                $resultado = $desprovisionador->excluir($tenant);

                $mensagem = 'Igreja excluida.';
                if ($resultado['erros'] !== [] || $resultado['avisos'] !== []) {
                    $mensagem .= ' Atencao: ' . implode(' ', array_merge($resultado['erros'], $resultado['avisos']));
                }

                Session::flash('sites_sucesso', $mensagem);
            }
        } elseif ($produto === 'barbearias') {
            SiteBarbearia::excluir((int) $id);
            Session::flash('sites_sucesso', 'Barbearia excluida.');
        } else {
            SiteFood::excluir((int) $id);
            Session::flash('sites_sucesso', 'Restaurante excluido.');
        }

        $this->redirect('/sites');
    }

    private function validarCsrf(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('sites_erro', 'Sessão expirada. Tente novamente.');
            $this->redirect('/sites');
        }
    }

    private function atualizarStatus(string $produto, int $id, string $status): void
    {
        if ($produto === 'igrejas') {
            SiteIgreja::atualizarStatus($id, $status);
        } elseif ($produto === 'barbearias') {
            SiteBarbearia::atualizarStatus($id, $status);
        } elseif ($produto === 'food') {
            SiteFood::atualizarStatus($id, $status);
        }
    }

    /**
     * @return array{produto:string, nome:string}|null
     */
    private function buscarSite(string $produto, int $id): ?array
    {
        if ($produto === 'igrejas') {
            $tenant = SiteIgreja::find($id);

            return $tenant ? self::normalizarIgreja($tenant) : null;
        }

        if ($produto === 'barbearias') {
            $barbearia = SiteBarbearia::find($id);

            return $barbearia ? self::normalizarBarbearia($barbearia) : null;
        }

        if ($produto === 'food') {
            $restaurante = SiteFood::find($id);

            return $restaurante ? self::normalizarFood($restaurante) : null;
        }

        return null;
    }

    private static function normalizarIgreja(SiteIgreja $tenant): array
    {
        $acesso = $tenant->acesso();

        return [
            'produto' => 'igrejas',
            'id' => $tenant->id,
            'nome' => $tenant->nomeIgreja,
            'identificador' => $tenant->subdominio,
            'url' => 'https://' . $tenant->subdominio,
            'plano' => $tenant->plano,
            'metodo_pagamento' => $tenant->metodoPagamento,
            'status' => $tenant->status,
            'criado_em' => $tenant->criadoEm,
            'ultimo_acesso_em' => $tenant->ultimoAcessoEm,
            'acesso_bloqueado' => $acesso['bloqueado'],
            'acesso_motivo' => $acesso['motivo'],
            'acesso_vencimento' => $acesso['vencimento'],
        ];
    }

    private static function normalizarBarbearia(SiteBarbearia $barbearia): array
    {
        $acesso = $barbearia->acesso();

        return [
            'produto' => 'barbearias',
            'id' => $barbearia->id,
            'nome' => $barbearia->nome,
            'identificador' => $barbearia->slug,
            'url' => null,
            'plano' => $barbearia->plano,
            'metodo_pagamento' => $barbearia->metodoPagamento,
            'status' => $barbearia->status,
            'criado_em' => $barbearia->criadoEm,
            'ultimo_acesso_em' => $barbearia->ultimoAcessoEm,
            'acesso_bloqueado' => $acesso['bloqueado'],
            'acesso_motivo' => $acesso['motivo'],
            'acesso_vencimento' => $acesso['vencimento'],
        ];
    }

    private static function normalizarFood(SiteFood $restaurante): array
    {
        $acesso = $restaurante->acesso();

        return [
            'produto' => 'food',
            'id' => $restaurante->id,
            'nome' => $restaurante->nome,
            'identificador' => $restaurante->slug,
            'url' => null,
            'plano' => $restaurante->plano,
            'metodo_pagamento' => $restaurante->metodoPagamento,
            'status' => $restaurante->status,
            'criado_em' => $restaurante->criadoEm,
            'ultimo_acesso_em' => $restaurante->ultimoAcessoEm,
            'acesso_bloqueado' => $acesso['bloqueado'],
            'acesso_motivo' => $acesso['motivo'],
            'acesso_vencimento' => $acesso['vencimento'],
        ];
    }
}
