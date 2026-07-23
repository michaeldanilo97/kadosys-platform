<?php

declare(strict_types=1);

namespace Academias\Controllers;

use Academias\Core\Auth;
use Academias\Core\AlunoAuth;
use Academias\Core\Controller;
use Academias\Core\Csrf;
use Academias\Core\Session;
use Academias\Models\Academia;
use Academias\Models\AcademiaCheckin;
use Academias\Models\Aluno;
use Academias\Models\User;

/**
 * Check-in/checkout por QR fixo. Mecanica (conforme descrita pelo
 * usuario): a academia deixa UM QR fixo na entrada (nao um QR por
 * aluno) - o aluno, ja logado no painel dele pelo celular, escaneia
 * pra entrar e escaneia de novo pra sair. Cada leitura gera um
 * registro (aluno, data, hora) visivel tanto pro aluno quanto pra
 * academia - e tambem a prova de presenca fisica, entao nao existe
 * botao manual de check-in no painel do aluno.
 */
final class CheckinController extends Controller
{
    private const POR_PAGINA = 20;

    /**
     * Tela do QR em tela cheia (pensada pra ficar num tablet/impressa
     * na entrada) - protegida pelo login da equipe.
     */
    public function qr(): void
    {
        $academiaId = $this->academiaId();
        $academia = Academia::find($academiaId);

        if ($academia === null) {
            $this->redirect('/dashboard');
        }

        $url = $this->urlAbsoluta('/checkin/' . $academia->slug . '/' . $academia->qrCheckinToken);

        echo $this->view('dashboard.checkin.qr', [
            'pageTitle' => 'QR de check-in - KADOSYS Academias',
            'activeMenu' => 'checkin',
            'user' => $this->usuario(),
            'academia' => $academia,
            'urlCheckin' => $url,
        ], 'dashboard');
    }

    public function regenerarToken(): void
    {
        $academiaId = $this->academiaId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Academia::regenerarQrToken($academiaId);
            Session::flash('checkin_success', 'QR Code renovado - o QR antigo parou de funcionar.');
        }

        $this->redirect('/dashboard/checkin/qr');
    }

    /**
     * Painel ao vivo: quem esta dentro da academia agora.
     */
    public function index(): void
    {
        $academiaId = $this->academiaId();
        $academia = Academia::find($academiaId);

        echo $this->view('dashboard.checkin.index', [
            'pageTitle' => 'Check-in - KADOSYS Academias',
            'activeMenu' => 'checkin',
            'user' => $this->usuario(),
            'academia' => $academia,
            'presentes' => AcademiaCheckin::presentesAgora($academiaId),
            'success' => Session::flash('checkin_success'),
        ], 'dashboard');
    }

    public function historico(): void
    {
        $academiaId = $this->academiaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $resultado = AcademiaCheckin::historico($academiaId, $page, self::POR_PAGINA);

        echo $this->view('dashboard.checkin.historico', [
            'pageTitle' => 'Histórico de check-in - KADOSYS Academias',
            'activeMenu' => 'checkin',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'historico' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
        ], 'dashboard');
    }

    public function ranking(): void
    {
        $academiaId = $this->academiaId();

        echo $this->view('dashboard.checkin.ranking', [
            'pageTitle' => 'Ranking de frequência - KADOSYS Academias',
            'activeMenu' => 'ranking',
            'user' => $this->usuario(),
            'academia' => Academia::find($academiaId),
            'ranking' => AcademiaCheckin::rankingDoMes($academiaId),
        ], 'dashboard');
    }

    /**
     * Endpoint publico (sem login de equipe) que o celular do aluno
     * abre ao escanear o QR fixo. Exige o aluno logado no painel dele
     * pra esta MESMA academia - se nao estiver, manda pro login com
     * retorno pra esta mesma URL, pra o check-in acontecer assim que
     * autenticar.
     */
    public function processar(string $slug, string $token): void
    {
        $academia = Academia::findByQrTokenAtiva($token);

        if ($academia === null || $academia->slug !== $slug) {
            $this->renderNotFound();

            return;
        }

        $alunoAuth = new AlunoAuth($this->config);
        $aluno = $alunoAuth->user($academia->id);

        if ($aluno === null) {
            $proximo = '/checkin/' . $slug . '/' . $token;
            $this->redirect('/minha-conta/' . $slug . '/entrar?next=' . urlencode($proximo));
        }

        $checkinAberto = AcademiaCheckin::emAberto($academia->id, $aluno->id);

        if ($checkinAberto === null) {
            AcademiaCheckin::criar($academia->id, $aluno->id);
            Aluno::registrarCheckinGamificacao($aluno->id, $academia->id);

            echo $this->view('checkin.confirmado', [
                'pageTitle' => 'Check-in - ' . $academia->nome,
                'academia' => $academia,
                'aluno' => Aluno::find($aluno->id, $academia->id),
                'tipo' => 'entrada',
                'horario' => new \DateTimeImmutable(),
            ], 'site');

            return;
        }

        AcademiaCheckin::fecharSaida($checkinAberto->id);

        $entrada = new \DateTimeImmutable($checkinAberto->entradaEm);
        $saida = new \DateTimeImmutable();
        $duracaoMinutos = max(0, (int) (($saida->getTimestamp() - $entrada->getTimestamp()) / 60));

        echo $this->view('checkin.confirmado', [
            'pageTitle' => 'Check-out - ' . $academia->nome,
            'academia' => $academia,
            'aluno' => $aluno,
            'tipo' => 'saida',
            'horario' => $saida,
            'duracaoMinutos' => $duracaoMinutos,
        ], 'site');
    }

    private function urlAbsoluta(string $path): string
    {
        $host = (string) ($this->request->server['HTTP_HOST'] ?? 'academias.kadosys.com.br');
        $https = ($this->request->server['HTTPS'] ?? '') !== '' && ($this->request->server['HTTPS'] ?? '') !== 'off';

        return ($https ? 'https://' : 'http://') . $host . $this->url($path);
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        echo $this->view('errors.404', ['pageTitle' => 'Página não encontrada'], 'site');
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function academiaId(): int
    {
        return $this->usuario()?->academiaId ?? 0;
    }
}
