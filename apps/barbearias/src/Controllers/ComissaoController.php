<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\Caixa;
use Barbearias\Models\ComissaoPagamento;
use Barbearias\Models\FinanceiroLancamento;
use Barbearias\Models\Profissional;
use Barbearias\Models\User;

/**
 * Fechamento de comissao por profissional: soma o valor dos
 * atendimentos CONCLUIDOS num periodo e aplica o percentual de
 * comissao cadastrado em cada profissional (ver
 * Barbearias\Models\Profissional::$percentualComissao).
 *
 * "Pagar" uma comissao (ver pagar()) gera uma despesa no caixa aberto
 * e exige um comprovante anexado - nao mexe em dinheiro de verdade,
 * so registra que aquele valor saiu do caixa da barbearia.
 */
final class ComissaoController extends Controller
{
    private const UPLOAD_DIR = 'uploads/comprovantes';
    private const TAMANHO_MAXIMO_COMPROVANTE = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();

        $hoje = new \DateTimeImmutable('today');
        $dataInicio = trim((string) $this->request->input('data_inicio', ''));
        $dataFim = trim((string) $this->request->input('data_fim', ''));

        if ($dataInicio === '' || !$this->dataValida($dataInicio)) {
            $dataInicio = $hoje->modify('first day of this month')->format('Y-m-d');
        }

        if ($dataFim === '' || !$this->dataValida($dataFim)) {
            $dataFim = $hoje->modify('last day of this month')->format('Y-m-d');
        }

        $profissionalId = (int) $this->request->input('profissional_id', 0);

        $inicioSql = $dataInicio . ' 00:00:00';
        $fimSql = $dataFim . ' 23:59:59';

        $fechamento = Agendamento::comissoesPorProfissional($barbeariaId, $inicioSql, $fimSql, $profissionalId);

        $totalGeral = array_sum(array_column($fechamento, 'totalServicos'));
        $totalComissoes = array_sum(array_column($fechamento, 'totalComissao'));

        $detalhe = null;
        $comissaoPaga = null;

        if ($profissionalId > 0) {
            $profissional = Profissional::find($profissionalId, $barbeariaId);

            if ($profissional !== null) {
                $atendimentos = Agendamento::concluidosPorProfissionalNoPeriodo($barbeariaId, $profissionalId, $inicioSql, $fimSql);
                $valoresPagos = FinanceiroLancamento::mapaPorAgendamentos(array_map(static fn (Agendamento $a) => $a->id, $atendimentos));

                $detalhe = [
                    'profissional' => $profissional,
                    'atendimentos' => $atendimentos,
                    'valoresPagos' => $valoresPagos,
                ];

                $comissaoPaga = ComissaoPagamento::porPeriodo($barbeariaId, $profissionalId, $dataInicio, $dataFim);
            }
        }

        echo $this->view('dashboard.comissoes.index', [
            'pageTitle' => 'Comissões - KADOSYS Barbearias',
            'activeMenu' => 'comissoes',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissionais' => Profissional::ativos($barbeariaId),
            'fechamento' => $fechamento,
            'totalGeral' => $totalGeral,
            'totalComissoes' => $totalComissoes,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'profissionalId' => $profissionalId,
            'detalhe' => $detalhe,
            'comissaoPaga' => $comissaoPaga,
            'caixaAberto' => Caixa::aberto($barbeariaId) !== null,
            'errors' => Session::flash('comissao_errors') ?? [],
            'success' => Session::flash('comissao_success'),
        ], 'dashboard');
    }

    /**
     * Registra o pagamento da comissao do profissional no periodo
     * informado: recalcula o valor no servidor (nunca confia no que
     * vem do formulario), gera uma despesa no caixa aberto e guarda o
     * comprovante anexado.
     */
    public function pagar(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $profissional = Profissional::find((int) $id, $barbeariaId);

        $dataInicio = trim((string) $this->request->input('data_inicio', ''));
        $dataFim = trim((string) $this->request->input('data_fim', ''));

        if ($profissional === null || !$this->dataValida($dataInicio) || !$this->dataValida($dataFim)) {
            $this->redirect('/dashboard/comissoes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('comissao_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/comissoes?data_inicio=' . $dataInicio . '&data_fim=' . $dataFim . '&profissional_id=' . $id);
        }

        $voltarUrl = '/dashboard/comissoes?data_inicio=' . $dataInicio . '&data_fim=' . $dataFim . '&profissional_id=' . $id;

        if (ComissaoPagamento::porPeriodo($barbeariaId, (int) $id, $dataInicio, $dataFim) !== null) {
            Session::flash('comissao_errors', ['Essa comissão já foi paga nesse período.']);
            $this->redirect($voltarUrl);
        }

        $caixaAberto = Caixa::aberto($barbeariaId);

        if ($caixaAberto === null) {
            Session::flash('comissao_errors', ['Abra o caixa antes de pagar uma comissão.']);
            $this->redirect($voltarUrl);
        }

        $fechamento = Agendamento::comissoesPorProfissional(
            $barbeariaId,
            $dataInicio . ' 00:00:00',
            $dataFim . ' 23:59:59',
            (int) $id
        );

        $valor = $fechamento !== [] ? (float) $fechamento[0]['totalComissao'] : 0.0;

        if ($valor <= 0) {
            Session::flash('comissao_errors', ['Não há comissão a pagar nesse período.']);
            $this->redirect($voltarUrl);
        }

        $arquivo = $this->request->file('comprovante');

        if ($arquivo === null || $arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('comissao_errors', ['Anexe o comprovante do pagamento (imagem ou PDF).']);
            $this->redirect($voltarUrl);
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO_COMPROVANTE) {
            Session::flash('comissao_errors', ['O comprovante excede 5MB.']);
            $this->redirect($voltarUrl);
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('comissao_errors', ['Formato de comprovante inválido (use PNG, JPG, WEBP ou PDF).']);
            $this->redirect($voltarUrl);
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            Session::flash('comissao_errors', ['Não foi possível salvar o comprovante agora. Tente de novo.']);
            $this->redirect($voltarUrl);
        }

        $nomeArquivo = 'comissao_' . $id . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            Session::flash('comissao_errors', ['Não foi possível salvar o comprovante agora. Tente de novo.']);
            $this->redirect($voltarUrl);
        }

        $lancamentoId = FinanceiroLancamento::create(
            $barbeariaId,
            $caixaAberto->id,
            null,
            $this->usuario()?->id,
            FinanceiroLancamento::TIPO_DESPESA,
            'Comissão',
            'dinheiro',
            $valor,
            'Comissão de ' . $profissional->nome . ' - período ' . $dataInicio . ' a ' . $dataFim,
            (new \DateTimeImmutable('today'))->format('Y-m-d'),
        );

        ComissaoPagamento::create(
            $barbeariaId,
            (int) $id,
            $lancamentoId,
            $this->usuario()?->id,
            $dataInicio,
            $dataFim,
            $valor,
            self::UPLOAD_DIR . '/' . $nomeArquivo,
        );

        Session::flash('comissao_success', 'Comissão paga e descontada do caixa.');
        $this->redirect($voltarUrl);
    }

    private function dataValida(string $data): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $data);

        return $dt !== false && $dt->format('Y-m-d') === $data;
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
