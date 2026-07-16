<?php

declare(strict_types=1);

namespace Superadmin\Core;

use Superadmin\Models\SiteIgreja;

/**
 * Exclusao definitiva de uma igreja (banco de dados e usuario do banco
 * no cPanel, alem do registro central) - reimplementacao, sob o
 * namespace Superadmin, da mesma logica de Igrejas\Core\Desprovisionador
 * (o monorepo nao compartilha codigo entre apps, cada um e standalone).
 *
 * O SUBDOMINIO nao e excluido automaticamente: essa hospedagem
 * (reseller) nao expoe funcao de exclusao de subdominio via UAPI com
 * token - vira sempre um AVISO de remocao manual, nunca um erro de fato.
 *
 * Faz melhor esforco nas chamadas ao cPanel (uma falha numa nao impede
 * as outras) e SEMPRE remove o registro central no final, mesmo se algo
 * falhar no cPanel.
 */
final class Desprovisionador
{
    public function __construct(
        private readonly CpanelUapiClient $cpanel,
    ) {
    }

    /**
     * @return array{erros: array<int, string>, avisos: array<int, string>}
     */
    public function excluir(SiteIgreja $site): array
    {
        if (!$this->cpanel->configurado()) {
            SiteIgreja::excluir($site->id);

            return [
                'erros' => ['Credenciais do cPanel nao configuradas no servidor - nada foi excluido la, so o registro aqui do sistema.'],
                'avisos' => [],
            ];
        }

        $resultado = [
            'erros' => $this->excluirRecursosCpanel($site),
            'avisos' => [$this->mensagemLembreteSubdominio($site)],
        ];

        SiteIgreja::excluir($site->id);

        return $resultado;
    }

    /**
     * @return array<int, string>
     */
    private function excluirRecursosCpanel(SiteIgreja $site): array
    {
        $erros = [];

        foreach ([
            'banco de dados' => fn () => $this->cpanel->excluirBancoDeDados($site->dbName),
            'usuario do banco' => fn () => $this->cpanel->excluirUsuarioBanco($site->dbUser),
        ] as $etapa => $chamada) {
            try {
                $resposta = $chamada();

                if (!$resposta['sucesso']) {
                    $erros[] = ucfirst($etapa) . ': ' . $this->mensagemErro($resposta);
                }
            } catch (\Throwable $exception) {
                $erros[] = ucfirst($etapa) . ': ' . $exception->getMessage();
            }
        }

        return $erros;
    }

    private function mensagemLembreteSubdominio(SiteIgreja $site): string
    {
        return "Remova o subdominio \"{$site->subdominio}\" manualmente pelo cPanel (Domains) - essa hospedagem nao suporta exclusao de subdominio via API.";
    }

    /**
     * @param array{sucesso:bool, status_http:int, body:array} $resposta
     */
    private function mensagemErro(array $resposta): string
    {
        return implode('; ', array_map('strval', $resposta['body']['errors'] ?? ['resposta inesperada do cPanel']));
    }
}
