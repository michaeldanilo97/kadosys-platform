<?php

declare(strict_types=1);

namespace Superadmin\Controllers;

use Superadmin\Core\Controller;
use Superadmin\Core\Csrf;
use Superadmin\Core\Session;
use Superadmin\Models\AvisoBarbearia;
use Superadmin\Models\AvisoFood;
use Superadmin\Models\AvisoIgreja;

/**
 * Envio de avisos da plataforma - aparecem no sino de notificacoes de
 * cada produto (Igrejas: plataforma_avisos; Barbearias: barbearia_avisos;
 * Food: restaurante_avisos). O admin escolhe o publico: um produto so ou
 * Todos.
 */
final class AvisoController extends Controller
{
    private const PRODUTOS_VALIDOS = ['igrejas', 'barbearias', 'food'];

    public function index(): void
    {
        echo $this->view('avisos.index', [
            'pageTitle' => 'Avisos - KADOSYS Super Admin',
            'activeMenu' => 'avisos',
            'avisoIgreja' => AvisoIgreja::ativo(),
            'avisoBarbearia' => AvisoBarbearia::ativo(),
            'avisoFood' => AvisoFood::ativo(),
            'historicoIgreja' => AvisoIgreja::todos(),
            'historicoBarbearia' => AvisoBarbearia::todos(),
            'historicoFood' => AvisoFood::todos(),
            'sucesso' => Session::flash('avisos_sucesso'),
            'erro' => Session::flash('avisos_erro'),
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function publicar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('avisos_erro', 'Sessão expirada. Tente novamente.');
            $this->redirect('/avisos');
        }

        $mensagem = trim((string) $this->request->input('mensagem', ''));
        $publico = (string) $this->request->input('publico', 'todos');

        if ($mensagem === '') {
            Session::flash('avisos_erro', 'Escreva a mensagem do aviso.');
            $this->redirect('/avisos');
        }

        $alvos = match ($publico) {
            'igrejas' => ['igrejas'],
            'barbearias' => ['barbearias'],
            'food' => ['food'],
            default => ['igrejas', 'barbearias', 'food'],
        };

        if (in_array('igrejas', $alvos, true)) {
            AvisoIgreja::publicar($mensagem);
        }

        if (in_array('barbearias', $alvos, true)) {
            AvisoBarbearia::publicar($mensagem);
        }

        if (in_array('food', $alvos, true)) {
            AvisoFood::publicar($mensagem);
        }

        $rotulo = match ($publico) {
            'igrejas' => 'Igrejas',
            'barbearias' => 'Barbearias',
            'food' => 'Food',
            default => 'Igrejas, Barbearias e Food',
        };

        Session::flash('avisos_sucesso', 'Aviso publicado para ' . $rotulo . '.');
        $this->redirect('/avisos');
    }

    public function encerrar(string $produto, string $id): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('avisos_erro', 'Sessão expirada. Tente novamente.');
            $this->redirect('/avisos');
        }

        if (!in_array($produto, self::PRODUTOS_VALIDOS, true)) {
            $this->redirect('/avisos');
        }

        match ($produto) {
            'igrejas' => AvisoIgreja::encerrar((int) $id),
            'barbearias' => AvisoBarbearia::encerrar((int) $id),
            default => AvisoFood::encerrar((int) $id),
        };

        Session::flash('avisos_sucesso', 'Aviso encerrado.');
        $this->redirect('/avisos');
    }
}
