<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Renovacao mensal das cobrancas Pix (KADOSYS Academias)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia (configurar no "Cron Jobs" do cPanel, ex.:
| php /home/kadosys1/apps/academias/cron/gerar_faturas_pix.php). So
| afeta academias com metodo_pagamento = "pix" (quem paga por cartao
| renova sozinho via preapproval do Mercado Pago, sem passar por aqui).
|
| Bem mais simples que o equivalente em Igrejas (nao precisa conectar
| num banco por tenant pra achar o e-mail do admin - e tudo a mesma
| tabela `users`, so filtrada por academia_id).
|
| Pra cada academia ativa:
|   - fatura paga e faltam poucos dias pro proximo vencimento -> gera a
|     fatura do proximo ciclo (o aviso no painel aparece a partir dai).
|   - fatura pendente e ainda dentro do prazo -> nao faz nada.
|   - fatura pendente vencida -> marcarExpiradasVencidas() (chamada
|     antes do loop) ja marcou como expirada nesta mesma rodada.
|   - fatura expirada -> gera uma cobranca nova (o acesso fica
|     bloqueado ate essa nova cobranca ser paga - ver AuthMiddleware).
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Academias\Core\MercadoPagoClient;
use Academias\Models\Academia;
use Academias\Models\FaturaAcademia;
use Academias\Models\Plano;
use Academias\Models\User;

const DIAS_ANTECEDENCIA_AVISO = 7;
const DIAS_PRAZO_COBRANCA_PIX = 3;

function log_cron(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

function buscarEmailAdmin(Academia $academia): ?string
{
    foreach (User::daAcademia($academia->id) as $usuario) {
        if ($usuario->role === User::ROLE_ADMIN && $usuario->active) {
            return $usuario->email;
        }
    }

    return null;
}

function gerarFatura(Academia $academia, MercadoPagoClient $mp, \DateTimeImmutable $vencimento): void
{
    $adminEmail = buscarEmailAdmin($academia);

    if ($adminEmail === null) {
        log_cron("Academia {$academia->slug}: nenhum administrador ativo encontrado, pulando.");

        return;
    }

    $valor = Plano::VALOR_MENSAL[$academia->plano] ?? null;

    if ($valor === null) {
        log_cron("Academia {$academia->slug}: plano '{$academia->plano}' sem valor mensal definido, pulando.");

        return;
    }

    $referencia = 'kadosys-academias-renovacao-' . $academia->id . '-' . bin2hex(random_bytes(4));

    try {
        $resposta = $mp->criarPagamentoPix([
            'description' => 'KADOSYS Academias - Renovação ' . Plano::label($academia->plano),
            'amount' => $valor,
            'payerEmail' => $adminEmail,
            'externalReference' => $referencia,
            'expiraEm' => $vencimento,
        ]);
    } catch (\RuntimeException $exception) {
        log_cron("Academia {$academia->slug}: falha ao comunicar com o Mercado Pago - {$exception->getMessage()}");

        return;
    }

    $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
    $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

    if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
        log_cron("Academia {$academia->slug}: Mercado Pago recusou a cobrança Pix de renovação (status {$resposta['status']}).");

        return;
    }

    FaturaAcademia::criar(
        $academia->id,
        $academia->plano,
        FaturaAcademia::TIPO_RENOVACAO,
        $valor,
        (string) $resposta['body']['id'],
        $qrCode,
        $qrCodeBase64 !== null ? (string) $qrCodeBase64 : null,
        $vencimento,
    );

    log_cron("Academia {$academia->slug}: nova fatura Pix gerada, vencimento em " . $vencimento->format('d/m/Y') . '.');
}

function processarAcademia(Academia $academia, MercadoPagoClient $mp, \DateTimeImmutable $agora): void
{
    // Assinatura cancelada (ver ConfiguracaoController::cancelarAssinatura)
    // - nao gera fatura nova, so deixa o acesso terminar no fim do ciclo
    // ja pago (o cron suspender_assinaturas_canceladas.php cuida disso).
    if ($academia->canceladoEm !== null) {
        return;
    }

    $ultima = FaturaAcademia::ultimaDaAcademia($academia->id);

    if ($ultima === null) {
        // Nao deveria acontecer - a fatura inicial e criada no proprio
        // cadastro publico (ver CadastroController::criarCobrancaPix).
        return;
    }

    if ($ultima->status === FaturaAcademia::STATUS_PAGA) {
        $proximoVencimento = (new \DateTimeImmutable($ultima->vencimento))->modify('+1 month');
        $inicioJanelaAviso = $proximoVencimento->modify('-' . DIAS_ANTECEDENCIA_AVISO . ' days');

        if ($agora >= $inicioJanelaAviso) {
            gerarFatura($academia, $mp, $proximoVencimento);
        }

        return;
    }

    if ($ultima->status === FaturaAcademia::STATUS_PENDENTE) {
        // Ainda dentro do prazo - marcarExpiradasVencidas() (chamado
        // antes do loop) cuida do caso de ja ter vencido.
        return;
    }

    if ($ultima->status === FaturaAcademia::STATUS_EXPIRADA) {
        gerarFatura($academia, $mp, $agora->modify('+' . DIAS_PRAZO_COBRANCA_PIX . ' days'));
    }

    // 'cancelada': sem fluxo de cancelamento manual ainda - ignora.
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por
// um script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $mp = new MercadoPagoClient();

    if (!$mp->configurado()) {
        log_cron('Mercado Pago nao configurado neste servidor - abortando.');
        exit(1);
    }

    FaturaAcademia::marcarExpiradasVencidas();

    $academias = Academia::ativasComPagamentoPix();
    $agora = new \DateTimeImmutable();

    log_cron(sprintf('Verificando %d academia(s) com pagamento via Pix.', count($academias)));

    foreach ($academias as $academia) {
        try {
            processarAcademia($academia, $mp, $agora);
        } catch (\Throwable $exception) {
            log_cron("Academia {$academia->slug}: erro inesperado - {$exception->getMessage()}");
        }
    }

    log_cron('Concluido.');
}
