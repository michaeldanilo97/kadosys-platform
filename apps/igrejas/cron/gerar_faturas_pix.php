<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Renovacao mensal das cobrancas Pix (KADOSYS Igrejas)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia (configurar no "Cron Jobs" do cPanel, ex.:
| php /home/kadosys1/apps/igrejas/cron/gerar_faturas_pix.php). So afeta
| tenants com metodo_pagamento = "pix" (quem paga por cartao renova
| sozinho via preapproval do Mercado Pago, sem passar por aqui).
|
| Pra cada tenant ativo:
|   - fatura paga e faltam poucos dias pro proximo vencimento -> gera a
|     fatura do proximo ciclo (o aviso no painel aparece a partir dai).
|   - fatura pendente e ainda dentro do prazo -> nao faz nada, o painel
|     ja mostra o aviso de vencimento.
|   - fatura pendente vencida -> marca expirada e gera uma cobranca nova
|     (o acesso fica bloqueado ate essa nova cobranca ser paga).
|   - fatura expirada (sobrou de uma rodada anterior sem pagamento) ->
|     gera uma cobranca nova, do mesmo jeito.
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Igrejas\Core\MercadoPagoClient;
use Igrejas\Models\FaturaPix;
use Igrejas\Models\Plano;
use Igrejas\Models\Tenant;

const DIAS_ANTECEDENCIA_AVISO = 7;
const DIAS_PRAZO_COBRANCA_PIX = 3;

function log_cron(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

function buscarEmailAdmin(Tenant $tenant): ?string
{
    $dbConfig = require dirname(__DIR__) . '/config/database.php';

    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['driver'],
        $dbConfig['host'],
        $dbConfig['port'],
        $tenant->dbName,
        $dbConfig['charset']
    );

    try {
        $pdo = new PDO($dsn, $tenant->dbUser, $tenant->dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $email = $pdo->query("SELECT email FROM users WHERE role = 'admin' AND active = 1 ORDER BY id ASC LIMIT 1")
            ->fetchColumn();
    } catch (\Throwable $exception) {
        log_cron("Tenant {$tenant->slug}: falha ao conectar no banco pra buscar o admin - {$exception->getMessage()}");

        return null;
    }

    return $email !== false ? (string) $email : null;
}

function gerarFatura(Tenant $tenant, MercadoPagoClient $mp, \DateTimeImmutable $vencimento): void
{
    $adminEmail = buscarEmailAdmin($tenant);

    if ($adminEmail === null) {
        log_cron("Tenant {$tenant->slug}: nenhum administrador ativo encontrado, pulando.");

        return;
    }

    $valor = Plano::VALOR_MENSAL[$tenant->plano] ?? null;

    if ($valor === null) {
        log_cron("Tenant {$tenant->slug}: plano '{$tenant->plano}' sem valor mensal definido (Pix so cobre Essencial/Premium), pulando.");

        return;
    }

    $referencia = 'kadosys-renovacao-' . $tenant->id . '-' . bin2hex(random_bytes(4));

    try {
        $resposta = $mp->criarPagamentoPix([
            'description' => 'KADOSYS Igrejas - Renovacao ' . Plano::label($tenant->plano),
            'amount' => $valor,
            'payerEmail' => $adminEmail,
            'externalReference' => $referencia,
            'expiraEm' => $vencimento,
        ]);
    } catch (\RuntimeException $exception) {
        log_cron("Tenant {$tenant->slug}: falha ao comunicar com o Mercado Pago - {$exception->getMessage()}");

        return;
    }

    $qrCode = $resposta['body']['point_of_interaction']['transaction_data']['qr_code'] ?? null;
    $qrCodeBase64 = $resposta['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

    if ($resposta['status'] >= 300 || !isset($resposta['body']['id']) || $qrCode === null) {
        log_cron("Tenant {$tenant->slug}: Mercado Pago recusou a cobranca Pix de renovacao (status {$resposta['status']}).");

        return;
    }

    FaturaPix::criar(
        $tenant->id,
        $tenant->plano,
        $valor,
        (string) $resposta['body']['id'],
        $qrCode,
        (string) $qrCodeBase64,
        $vencimento,
    );

    log_cron("Tenant {$tenant->slug}: nova fatura Pix gerada, vencimento em " . $vencimento->format('d/m/Y') . '.');
}

function processarTenant(Tenant $tenant, MercadoPagoClient $mp, \DateTimeImmutable $agora): void
{
    $ultima = FaturaPix::ultimaDoTenant($tenant->id);

    if ($ultima === null) {
        // Nao deveria acontecer - a fatura inicial e criada no proprio
        // cadastro publico (ver CadastroController::criarCobrancaPix).
        return;
    }

    if ($ultima->status === 'paga') {
        $proximoVencimento = (new \DateTimeImmutable($ultima->vencimento))->modify('+1 month');
        $inicioJanelaAviso = $proximoVencimento->modify('-' . DIAS_ANTECEDENCIA_AVISO . ' days');

        if ($agora >= $inicioJanelaAviso) {
            gerarFatura($tenant, $mp, $proximoVencimento);
        }

        return;
    }

    if ($ultima->status === 'pendente') {
        // Ainda dentro do prazo - o painel ja mostra o aviso de
        // vencimento a partir da data de criacao. marcarExpiradasVencidas()
        // (chamado antes do loop) cuida do caso de ter vencido.
        return;
    }

    if ($ultima->status === 'expirada') {
        gerarFatura($tenant, $mp, $agora->modify('+' . DIAS_PRAZO_COBRANCA_PIX . ' days'));
    }

    // 'cancelada': sem fluxo de cancelamento manual ainda - ignora.
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por um
// script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $mp = new MercadoPagoClient();

    if (!$mp->configurado()) {
        log_cron('Mercado Pago nao configurado neste servidor - abortando.');
        exit(1);
    }

    FaturaPix::marcarExpiradasVencidas();

    $tenants = Tenant::ativosComPagamentoPix();
    $agora = new \DateTimeImmutable();

    log_cron(sprintf('Verificando %d tenant(s) com pagamento via Pix.', count($tenants)));

    foreach ($tenants as $tenant) {
        try {
            processarTenant($tenant, $mp, $agora);
        } catch (\Throwable $exception) {
            log_cron("Tenant {$tenant->slug}: erro inesperado - {$exception->getMessage()}");
        }
    }

    log_cron('Concluido.');
}
