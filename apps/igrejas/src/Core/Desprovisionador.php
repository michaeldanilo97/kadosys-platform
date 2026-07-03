<?php

declare(strict_types=1);

namespace Igrejas\Core;

use Igrejas\Models\Tenant;

/**
 * Reverso do Provisionador: exclui uma igreja provisionada
 * automaticamente - banco de dados, usuario do banco e subdominio no
 * cPanel, alem do registro central (ver Igrejas\Models\Tenant). Usado
 * pelo painel administrativo da plataforma (ver PlataformaController),
 * nunca por um usuario comum.
 *
 * Faz melhor esforco nas 3 chamadas ao cPanel (uma falha numa nao
 * impede as outras de rodar) e SEMPRE remove o registro central no
 * final, mesmo se algo falhar no cPanel - a alternativa (travar a
 * exclusao pra sempre por causa de um recurso que ja nao existe mais,
 * por exemplo) e pior. As mensagens de erro de cada etapa que falhou
 * sao devolvidas pra quem chamou, pra mostrar ao administrador e
 * permitir limpeza manual se precisar.
 */
final class Desprovisionador
{
    public function __construct(
        private readonly CpanelUapiClient $cpanel,
    ) {
    }

    /**
     * @return array<int, string> mensagens de erro de etapas que falharam (vazio = tudo certo)
     */
    public function excluir(Tenant $tenant): array
    {
        $erros = $this->cpanel->configurado()
            ? $this->excluirRecursosCpanel($tenant)
            : ['Credenciais do cPanel nao configuradas no servidor - nada foi excluido la, so o registro aqui do sistema.'];

        Tenant::excluir($tenant->id);

        return $erros;
    }

    /**
     * @return array<int, string>
     */
    private function excluirRecursosCpanel(Tenant $tenant): array
    {
        $erros = [];

        foreach ([
            'subdominio' => fn () => $this->cpanel->excluirSubdominio($tenant->subdominio),
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

    /**
     * @param array{sucesso:bool, status_http:int, body:array} $resposta
     */
    private function mensagemErro(array $resposta): string
    {
        return implode('; ', array_map('strval', $resposta['body']['errors'] ?? ['resposta inesperada do cPanel']));
    }
}
