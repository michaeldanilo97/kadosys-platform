<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\PixEstatico;
use Igrejas\Core\Session;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\FinanceiroCategoria;
use Igrejas\Models\FinanceiroDoacao;

/**
 * Doacao publica via Pix estatico (chave da propria igreja - sem login,
 * pensado pra ser compartilhado como link/QR code com a congregacao,
 * igual ao quadro de avisos em AvisoPublicoController). O dinheiro cai
 * direto na conta da igreja; o sistema so monta o QR/copia-e-cola (ver
 * Igrejas\Core\PixEstatico) e registra a intencao de doacao ate o
 * doador confirmar manualmente que pagou (nao ha webhook - ver
 * FinanceiroDoacao::confirmar()).
 */
final class DoacaoController extends Controller
{
    public function formulario(): void
    {
        $configuracao = ConfiguracaoIgreja::atual();

        echo $this->view('doacao.formulario', [
            'pageTitle' => 'Doar - ' . ($configuracao->nomeIgreja ?? 'Igreja'),
            'nomeIgreja' => $configuracao->nomeIgreja,
            'logoPath' => $configuracao->logoPath,
            'habilitado' => $configuracao->doacaoPixHabilitada(),
            'categorias' => FinanceiroCategoria::ativasPorTipo('entrada'),
            'csrf' => Csrf::field(),
            'errors' => Session::flash('doacao_errors') ?? [],
            'old' => Session::flash('doacao_old') ?? [],
        ], 'auth');
    }

    public function criar(): void
    {
        $configuracao = ConfiguracaoIgreja::atual();

        if (!$configuracao->doacaoPixHabilitada()) {
            $this->redirect('/doar');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('doacao_errors', ['Sessão expirada. Preencha o formulário novamente.']);
            $this->redirect('/doar');
        }

        $data = $this->request->only(['nome_doador', 'categoria_id', 'valor', 'mensagem']);
        $errors = $this->validar($data);

        if ($errors !== []) {
            Session::flash('doacao_errors', $errors);
            Session::flash('doacao_old', $data);
            $this->redirect('/doar');
        }

        $categoriaId = trim((string) ($data['categoria_id'] ?? ''));

        $id = FinanceiroDoacao::criar([
            'nome_doador' => $data['nome_doador'] ?? null,
            'categoria_id' => $categoriaId === '' ? null : (int) $categoriaId,
            'valor' => (float) str_replace(',', '.', (string) $data['valor']),
            'mensagem' => $data['mensagem'] ?? null,
        ]);

        $this->redirect('/doar/' . $id);
    }

    public function mostrar(string $id): void
    {
        $doacao = FinanceiroDoacao::find((int) $id);

        if (!$doacao) {
            http_response_code(404);
            echo $this->view('doacao.nao-encontrada', [
                'pageTitle' => 'Doação não encontrada',
            ], 'auth');

            return;
        }

        $configuracao = ConfiguracaoIgreja::atual();

        $payload = PixEstatico::montarPayload(
            chave: (string) $configuracao->pixChave,
            nomeBeneficiario: $configuracao->pixNomeBeneficiario ?? (string) $configuracao->nomeIgreja,
            cidade: (string) ($configuracao->cidade ?? 'BRASIL'),
            valor: $doacao->valor,
            txid: $doacao->txid,
            descricao: $doacao->categoriaNome,
        );

        echo $this->view('doacao.mostrar', [
            'pageTitle' => 'Doar - ' . ($configuracao->nomeIgreja ?? 'Igreja'),
            'nomeIgreja' => $configuracao->nomeIgreja,
            'doacao' => $doacao,
            'pixPayload' => $payload,
            'csrf' => Csrf::field(),
        ], 'auth');
    }

    public function confirmar(string $id): void
    {
        $doacao = FinanceiroDoacao::find((int) $id);

        if ($doacao && Csrf::verify($this->request->input('_csrf_token'))) {
            FinanceiroDoacao::confirmar((int) $id);
        }

        $this->redirect('/doar/' . $id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validar(array $data): array
    {
        $errors = [];

        $valor = (float) str_replace(',', '.', (string) ($data['valor'] ?? '0'));

        if ($valor <= 0) {
            $errors[] = 'Informe um valor válido para a doação.';
        }

        $categoriaId = trim((string) ($data['categoria_id'] ?? ''));

        if ($categoriaId !== '' && !FinanceiroCategoria::find((int) $categoriaId)) {
            $errors[] = 'Categoria inválida.';
        }

        return $errors;
    }
}
