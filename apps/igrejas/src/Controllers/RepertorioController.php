<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\Louvor;
use Igrejas\Models\Repertorio;
use Igrejas\Models\RepertorioMensagem;
use Igrejas\Models\User;

/**
 * Controller do modulo Repertorio (programacao de culto): o lider de
 * louvor (ver User::liderLouvor) monta/arrasta a ordem dos louvores de
 * um culto e avanca qual esta tocando agora; os demais musicos so
 * acompanham (Modo Culto) via polling e trocam avisos rapidos entre si.
 * Todas as acoes de edicao (criar, adicionar/remover item, reordenar,
 * avancar/voltar, encerrar) exigem lider - ver exigirLider().
 */
final class RepertorioController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();

        echo $this->view('dashboard.louvores.repertorios.index', [
            'pageTitle' => 'Programação de Culto - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', 'Programação de Culto'],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'repertorios' => Repertorio::todos(),
            'ehLider' => $this->ehLider($user),
            'success' => Session::flash('repertorio_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        $this->exigirLider();

        echo $this->view('dashboard.louvores.repertorios.form', [
            'pageTitle' => 'Novo repertório - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', 'Programação de Culto', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'errors' => Session::flash('repertorio_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        $this->exigirLider();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('repertorio_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/louvores/repertorios/novo');
        }

        $titulo = trim((string) $this->request->input('titulo', ''));

        if ($titulo === '') {
            Session::flash('repertorio_errors', ['Informe um título para o repertório (ex.: "Culto de domingo").']);
            $this->redirect('/dashboard/louvores/repertorios/novo');
        }

        $userId = (new Auth($this->config))->user()?->id;
        $id = Repertorio::create($titulo, $userId);

        $this->redirect("/dashboard/louvores/repertorios/{$id}/editar");
    }

    /**
     * Pagina do lider: montar/arrastar a ordem dos louvores do culto.
     */
    public function editor(string $id): void
    {
        $this->exigirLider();

        $repertorio = Repertorio::find((int) $id);

        if (!$repertorio) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.louvores.repertorios.editor', [
            'pageTitle' => $repertorio->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', 'Programação de Culto', $repertorio->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'repertorio' => $repertorio,
            'louvoresDisponiveis' => Louvor::listaParaSelect(),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function adicionarItem(string $id): void
    {
        $this->exigirLiderJson();

        $louvorId = (int) $this->request->input('louvor_id', 0);

        if ($louvorId <= 0 || !Louvor::find($louvorId)) {
            $this->jsonResponse(['erro' => 'Louvor inválido.'], 422);
        }

        Repertorio::adicionarItem((int) $id, $louvorId);
        $this->jsonResponse(['ok' => true] + (Repertorio::find((int) $id)?->paraJson() ?? []));
    }

    public function removerItem(string $id, string $itemId): void
    {
        $this->exigirLiderJson();

        Repertorio::removerItem((int) $id, (int) $itemId);
        $this->jsonResponse(['ok' => true] + (Repertorio::find((int) $id)?->paraJson() ?? []));
    }

    public function reordenar(string $id): void
    {
        $this->exigirLiderJson();

        $itensBrutos = (string) $this->request->input('itens', '');
        $itemIds = array_filter(array_map('intval', explode(',', $itensBrutos)));

        Repertorio::reordenar((int) $id, $itemIds);
        $this->jsonResponse(['ok' => true] + (Repertorio::find((int) $id)?->paraJson() ?? []));
    }

    public function avancar(string $id): void
    {
        $this->moverAtual((int) $id, 1);
    }

    public function voltar(string $id): void
    {
        $this->moverAtual((int) $id, -1);
    }

    private function moverAtual(int $id, int $direcao): void
    {
        $this->exigirLiderJson();

        $repertorio = Repertorio::find($id);

        if (!$repertorio) {
            $this->jsonResponse(['erro' => 'Repertório não encontrado.'], 404);
        }

        $itens = $repertorio->itens;

        if ($itens === []) {
            $this->jsonResponse(['ok' => true] + $repertorio->paraJson());
        }

        $posicaoAtual = null;
        foreach ($itens as $indice => $item) {
            if ($item->id === $repertorio->atualItemId) {
                $posicaoAtual = $indice;
                break;
            }
        }

        // Nenhuma musica "tocando agora" ainda - "proxima" comeca do
        // primeiro item; "anterior" nesse caso nao faz nada.
        if ($posicaoAtual === null) {
            $novaPosicao = $direcao > 0 ? 0 : null;
        } else {
            $novaPosicao = $posicaoAtual + $direcao;
        }

        if ($novaPosicao === null || $novaPosicao < 0 || $novaPosicao >= count($itens)) {
            $this->jsonResponse(['ok' => true] + $repertorio->paraJson());
        }

        Repertorio::definirAtual($id, $itens[$novaPosicao]->id);
        $this->jsonResponse(['ok' => true] + (Repertorio::find($id)?->paraJson() ?? []));
    }

    public function encerrar(string $id): void
    {
        $this->exigirLider();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Repertorio::encerrar((int) $id);
            Session::flash('repertorio_success', 'Repertório encerrado.');
        }

        $this->redirect('/dashboard/louvores/repertorios');
    }

    /**
     * Modo Culto: tela dedicada, sem menu do painel, mostrando so a
     * musica atual (cifra/tom/andamento) e o chat rapido entre musicos -
     * pensada pra ficar aberta no celular/tablet de cada musico durante
     * o culto. O lider ve tambem os controles de proxima/anterior aqui
     * mesmo (nao precisa alternar pro editor durante o culto).
     */
    public function culto(string $id): void
    {
        $repertorio = Repertorio::find((int) $id);

        if (!$repertorio) {
            $this->renderNotFound();

            return;
        }

        $user = (new Auth($this->config))->user();

        echo $this->view('dashboard.louvores.repertorios.culto', [
            'pageTitle' => $repertorio->titulo . ' - KADOSYS Igrejas',
            'repertorio' => $repertorio,
            'ehLider' => $this->ehLider($user),
            'mensagensIniciais' => RepertorioMensagem::ultimas($repertorio->id),
        ], 'modo-culto');
    }

    /**
     * Estado atual do repertorio pro polling do Modo Culto - qual musica
     * esta tocando, a ordem completa (pro "proximo" aparecer) e as
     * ultimas mensagens do chat. Mesmo mecanismo (sem WebSocket) ja
     * usado em Projecao/Telao - funciona na hospedagem compartilhada.
     */
    public function estado(string $id): void
    {
        header('Cache-Control: no-store');

        $repertorio = Repertorio::find((int) $id);

        if (!$repertorio) {
            $this->jsonResponse(['ativo' => false]);
        }

        $mensagens = array_map(
            static fn ($mensagem) => $mensagem->paraJson(),
            RepertorioMensagem::ultimas($repertorio->id)
        );

        $this->jsonResponse(['ativo' => true, 'mensagens' => $mensagens] + $repertorio->paraJson());
    }

    public function mensagem(string $id): void
    {
        $repertorio = Repertorio::find((int) $id);

        if (!$repertorio) {
            $this->jsonResponse(['erro' => 'Repertório não encontrado.'], 404);
        }

        $user = (new Auth($this->config))->user();
        $texto = trim((string) $this->request->input('texto', ''));

        if ($texto === '') {
            $this->jsonResponse(['erro' => 'Escreva algo antes de enviar.'], 422);
        }

        RepertorioMensagem::enviar($repertorio->id, $user?->id, $texto);
        $this->jsonResponse(['ok' => true]);
    }

    /**
     * Confirma se o usuario logado e admin ou lider de louvor - unico
     * papel que pode montar/arrastar o repertorio e avancar a musica
     * atual (ver User::liderLouvor). Redireciona (e encerra a
     * requisicao, ver Controller::redirect()) quem nao e; quem chama
     * so precisa invocar isso no comeco do metodo, sem checar retorno.
     */
    private function exigirLider(): void
    {
        if ($this->ehLider((new Auth($this->config))->user())) {
            return;
        }

        $this->redirect('/dashboard/sem-permissao?modulo=louvores');
    }

    /**
     * Mesma checagem de exigirLider(), mas pros endpoints JSON
     * (adicionar/remover/reordenar/avancar/voltar) - responde 403 em
     * vez de redirecionar, ja que sao chamados via fetch() pelo
     * JavaScript do editor, nao por navegacao direta do navegador.
     */
    private function exigirLiderJson(): void
    {
        if ($this->ehLider((new Auth($this->config))->user())) {
            return;
        }

        $this->jsonResponse(['erro' => 'Só o líder de louvor pode fazer isso.'], 403);
    }

    private function ehLider(?User $user): bool
    {
        return $user !== null && ($user->role === User::ROLE_ADMIN || $user->liderLouvor);
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'louvores',
            'breadcrumb' => ['Dashboard', 'Louvores', 'Programação de Culto', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
