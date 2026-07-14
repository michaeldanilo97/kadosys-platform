<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\KidsCheckin;
use Igrejas\Models\KidsCrianca;

/**
 * Controller do modulo KADOSYS Kids > Check-in: tela operacional usada
 * na porta da sala infantil - registra entrada (gera o código de
 * segurança entregue ao responsável) e saída (só libera com o código
 * confere) de cada criança no dia.
 */
final class KidsCheckinController extends Controller
{
    public function index(): void
    {
        echo $this->view('dashboard.kids.checkin.index', [
            'pageTitle' => 'Check-in Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Check-in'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'criancasAtivas' => KidsCrianca::allActive(),
            'abertosHoje' => KidsCheckin::abertosHoje(),
            'encerradosHoje' => KidsCheckin::encerradosHoje(),
            'success' => Session::flash('kids_checkin_success'),
            'codigoGerado' => Session::flash('kids_checkin_codigo'),
            'errors' => Session::flash('kids_checkin_errors') ?? [],
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function registrar(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_checkin_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/kids/checkin');
        }

        $criancaId = (int) $this->request->input('crianca_id', 0);
        $crianca = $criancaId > 0 ? KidsCrianca::find($criancaId) : null;

        if ($crianca === null) {
            Session::flash('kids_checkin_errors', ['Selecione uma criança válida.']);
            $this->redirect('/dashboard/kids/checkin');
        }

        if (KidsCheckin::criancaComCheckinAbertoHoje($criancaId)) {
            Session::flash('kids_checkin_errors', [$crianca->nome . ' já está com check-in em aberto hoje.']);
            $this->redirect('/dashboard/kids/checkin');
        }

        $entreguePor = trim((string) $this->request->input('entregue_por', ''));
        $user = (new Auth($this->config))->user();

        $checkin = KidsCheckin::registrar($criancaId, $entreguePor !== '' ? $entreguePor : null, $user?->id);

        Session::flash('kids_checkin_success', $crianca->nome . ' fez check-in com sucesso!');
        Session::flash('kids_checkin_codigo', ['nome' => $crianca->nome, 'codigo' => $checkin->codigoSeguranca]);
        $this->redirect('/dashboard/kids/checkin');
    }

    public function encerrar(string $id): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_checkin_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/kids/checkin');
        }

        $codigo = trim((string) $this->request->input('codigo_seguranca', ''));
        $retiradoPor = trim((string) $this->request->input('retirado_por', ''));
        $user = (new Auth($this->config))->user();

        $ok = KidsCheckin::encerrar(
            (int) $id,
            $codigo,
            $retiradoPor !== '' ? $retiradoPor : null,
            $user?->id
        );

        if ($ok) {
            Session::flash('kids_checkin_success', 'Saída registrada com sucesso.');
        } else {
            Session::flash('kids_checkin_errors', ['Código de segurança incorreto. Confira com o responsável.']);
        }

        $this->redirect('/dashboard/kids/checkin');
    }
}
