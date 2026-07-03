<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Models\ComunicacaoAviso;
use Igrejas\Models\ConfiguracaoIgreja;

/**
 * Quadro de avisos publico da igreja - sem login, pensado pra ser
 * compartilhado com a congregacao (link no grupo do WhatsApp, QR code
 * no templo, etc.). Mostra so os avisos do modulo Comunicacao
 * publicados com publico_alvo "todos" - os marcados como "so
 * lideranca" continuam restritos ao painel administrativo.
 */
final class AvisoPublicoController extends Controller
{
    public function index(): void
    {
        $configuracao = ConfiguracaoIgreja::atual();

        echo $this->view('avisos.publico', [
            'pageTitle' => ($configuracao->nomeIgreja ?? 'Igreja') . ' - Avisos',
            'nomeIgreja' => $configuracao->nomeIgreja,
            'logoPath' => $configuracao->logoPath,
            'avisos' => ComunicacaoAviso::publicosParaTodos(),
        ], 'avisos-publico');
    }
}
