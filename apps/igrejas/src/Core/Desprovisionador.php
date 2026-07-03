<?php

declare(strict_types=1);

namespace Igrejas\Core;

use Igrejas\Models\Tenant;

/**
 * Reverso do Provisionador: exclui uma igreja provisionada
 * automaticamente - banco de dados e usuario do banco no cPanel, alem
 * do registro central (ver Igrejas\Models\Tenant). Usado pelo painel
 * administrativo da plataforma (ver PlataformaController), nunca por
 * um usuario comum.
 *
 * O SUBDOMINIO nao e excluido automaticamente de proposito: essa
 * hospedagem (reseller) nao expoe nenhuma funcao de exclusao de
 * subdominio via UAPI com token - confirmado investigando o codigo
 * fonte do cPanel direto no servidor (a unica chamada de exclusao que
 * existe, Cpanel::AdminBin::Call::call('Cpanel', 'subdomain', 'DEL',
 * ...), e uma rotina interna/privada usada so como rollback ao
 * converter um dominio temporario, nao uma funcao publica). Por isso
 * essa etapa sempre vira um AVISO de remocao manual, nunca um erro de
 * fato (ver excluir()).
 *
 * Faz melhor esforco nas chamadas ao cPanel (uma falha numa nao impede
 * as outras de rodar) e SEMPRE remove o registro central no final,
 * mesmo se algo falhar no cPanel - a alternativa (travar a exclusao
 * pra sempre por causa de um recurso que ja nao existe mais, por
 * exemplo) e pior.
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
    public function excluir(Tenant $tenant): array
    {
        if (!$this->cpanel->configurado()) {
            Tenant::excluir($tenant->id);

            return [
                'erros' => ['Credenciais do cPanel nao configuradas no servidor - nada foi excluido la, so o registro aqui do sistema.'],
                'avisos' => [],
            ];
        }

        $resultado = [
            'erros' => $this->excluirRecursosCpanel($tenant),
            'avisos' => [$this->mensagemLembreteSubdominio($tenant)],
        ];

        Tenant::excluir($tenant->id);

        return $resultado;
    }

    /**
     * @return array<int, string>
     */
    private function excluirRecursosCpanel(Tenant $tenant): array
    {
        $erros = [];

        foreach ([
            'banco de dados' => fn () => $this->cpanel->excluirBancoDeDados($tenant->dbName),
            'usuario do banco' => fn () => $this->cpanel->excluirUsuarioBanco($tenant->dbUser),
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

    private function mensagemLembreteSubdominio(Tenant $tenant): string
    {
        return "Remova o subdominio \"{$tenant->subdominio}\" manualmente pelo cPanel (Domains) - essa hospedagem nao suporta exclusao de subdominio via API.";
    }

    /**
     * @param array{sucesso:bool, status_http:int, body:array} $resposta
     */
    private function mensagemErro(array $resposta): string
    {
        return implode('; ', array_map('strval', $resposta['body']['errors'] ?? ['resposta inesperada do cPanel']));
    }
}
