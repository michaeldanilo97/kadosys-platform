<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Culto;
use Igrejas\Models\Membro;

/**
 * Check-in geral por QR fixo: complementa o check-in do Kids (que e
 * operado pela equipe, ver KidsCheckinController) com um QR fixo na
 * entrada da igreja pra qualquer membro confirmar presenca sozinho,
 * pelo proprio celular - sem precisar de login, ja que a congregacao
 * (diferente da equipe/Usuarios) nao tem conta/senha no sistema.
 *
 * O QR e fixo (nao muda a cada culto): ele sempre resolve pro(s)
 * culto(s) agendado(s) para o dia em que for escaneado (ver
 * Culto::deHoje()). A identificacao de quem esta confirmando presenca
 * e feita por busca de nome (poucos resultados por vez, ver
 * Membro::buscarAtivosPorNome()) em vez de uma lista/grade com todos
 * os membros - o mesmo problema de "30 criancas pra procurar" da tela
 * de login do Kids, evitado aqui de proposito.
 */
final class CheckinController extends Controller
{
    /**
     * Tela do QR em tela cheia (pensada pra ficar num tablet/impressa
     * na entrada) - protegida pelo login da equipe.
     */
    public function qr(): void
    {
        $token = ConfiguracaoIgreja::garantirCheckinQrToken();

        echo $this->view('dashboard.cultos.checkin-qr', [
            'pageTitle' => 'QR de check-in - KADOSYS Igrejas',
            'activeMenu' => 'cultos',
            'breadcrumb' => ['Dashboard', 'Cultos', 'QR de check-in'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'urlCheckin' => $this->urlAbsoluta('/checkin/' . $token),
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function regenerarToken(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            ConfiguracaoIgreja::regenerarCheckinQrToken();
            Session::flash('checkin_qr_success', 'QR Code renovado - o QR antigo parou de funcionar.');
        }

        $this->redirect('/dashboard/cultos/checkin-qr');
    }

    /**
     * Pagina publica aberta ao escanear o QR: mostra o(s) culto(s) de
     * hoje e o campo de busca do proprio nome. Sem culto agendado pra
     * hoje, mostra so um aviso (evita confirmar presenca em culto
     * errado).
     */
    public function entrar(string $token): void
    {
        if (ConfiguracaoIgreja::findByCheckinQrToken($token) === null) {
            $this->renderNotFound();

            return;
        }

        $configuracao = ConfiguracaoIgreja::atual();
        $cultosHoje = Culto::deHoje();

        echo $this->view('checkin.entrar', [
            'pageTitle' => ($configuracao->nomeIgreja ?? 'Igreja') . ' - Check-in',
            'nomeIgreja' => $configuracao->nomeIgreja,
            'logoPath' => $configuracao->logoPath,
            'token' => $token,
            'cultosHoje' => $cultosHoje,
            'error' => Session::flash('checkin_error'),
            'csrf' => Csrf::field(),
        ], 'avisos-publico');
    }

    /**
     * Busca por nome (AJAX, JSON) - so id/nome, nunca dado sensivel,
     * e sempre limitada, ja que o endpoint e publico e sem login.
     */
    public function buscar(string $token): void
    {
        if (ConfiguracaoIgreja::findByCheckinQrToken($token) === null) {
            $this->jsonResponse(['resultados' => []], 404);
        }

        $termo = trim((string) $this->request->input('q', ''));

        if (mb_strlen($termo) < 2) {
            $this->jsonResponse(['resultados' => []]);
        }

        $this->jsonResponse(['resultados' => Membro::buscarAtivosPorNome($termo)]);
    }

    /**
     * Confirma a presenca do membro escolhido no culto escolhido -
     * ambos vindos da propria pagina publica (ver checkin.entrar).
     */
    public function confirmar(string $token): void
    {
        if (ConfiguracaoIgreja::findByCheckinQrToken($token) === null) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('checkin_error', 'Sessão expirada. Tente novamente.');
            $this->redirect('/checkin/' . $token);
        }

        $cultoId = (int) $this->request->input('culto_id', 0);
        $membroId = (int) $this->request->input('membro_id', 0);

        $culto = $cultoId > 0 ? Culto::find($cultoId) : null;
        $membro = $membroId > 0 ? Membro::find($membroId) : null;

        if ($culto === null || $membro === null || $culto->data !== (new \DateTimeImmutable())->format('Y-m-d')) {
            Session::flash('checkin_error', 'Não foi possível confirmar a presença. Tente novamente.');
            $this->redirect('/checkin/' . $token);
        }

        $jaConfirmado = Culto::jaConfirmouPresenca($culto->id, $membro->id);
        Culto::addPresenca($culto->id, $membro->id);

        echo $this->view('checkin.confirmado', [
            'pageTitle' => 'Check-in - ' . $culto->titulo,
            'membro' => $membro,
            'culto' => $culto,
            'jaConfirmado' => $jaConfirmado,
        ], 'avisos-publico');
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('checkin.nao-encontrado', [
            'pageTitle' => 'Check-in não encontrado',
        ], 'avisos-publico');
    }
}
