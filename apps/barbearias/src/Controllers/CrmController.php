<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Cliente;
use Barbearias\Models\User;

/**
 * CRM basico: aniversariantes do mes e clientes inativos, pra a
 * barbearia saber com quem vale a pena entrar em contato. Sem nenhuma
 * automacao de envio (a aplicacao nao tem canal de e-mail/WhatsApp
 * configurado) - so uma lista com os dados de contato, pra equipe
 * ligar/mandar mensagem por fora.
 */
final class CrmController extends Controller
{
    private const DIAS_INATIVO_PADRAO = 60;

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $dias = max(1, (int) $this->request->input('dias', self::DIAS_INATIVO_PADRAO));
        $mesAtual = (int) (new \DateTimeImmutable())->format('n');

        echo $this->view('dashboard.crm.index', [
            'pageTitle' => 'CRM - KADOSYS Barbearias',
            'activeMenu' => 'crm',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'aniversariantes' => Cliente::aniversariantesDoMes($barbeariaId, $mesAtual),
            'inativos' => Cliente::inativos($barbeariaId, $dias),
            'dias' => $dias,
        ], 'dashboard');
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
