<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Unidade;
use Barbearias\Models\User;

/**
 * Painel de recepcao: tela em tela cheia (sem o layout de dashboard
 * com sidebar) pensada pra ficar aberta numa TV/tablet da recepcao,
 * mostrando a fila de atendimentos do dia com auto-atualizacao.
 */
final class RecepcaoController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $unidadeId = (int) $this->request->input('unidade_id', 0);
        $unidades = Unidade::temMultiplasAtivas($barbeariaId) ? Unidade::ativas($barbeariaId) : [];

        echo $this->view('dashboard.recepcao.index', [
            'pageTitle' => 'Painel de recepção - KADOSYS Barbearias',
            'barbearia' => Barbearia::find($barbeariaId),
            'agendamentos' => Agendamento::doDia($barbeariaId, new \DateTimeImmutable('today'), $unidadeId),
            'unidades' => $unidades,
            'unidadeId' => $unidadeId,
        ]);
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
