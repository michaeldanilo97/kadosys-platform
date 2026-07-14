<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\KidsConteudo;
use Igrejas\Models\KidsCrianca;

/**
 * Controller da Biblioteca Kids no "modo criança": tela colorida e
 * ilustrada (cartoon) onde a criança navega pelos conteúdos por tipo
 * (histórias, vídeos, quiz etc.) e ganha XP/moedas ao concluir cada um.
 *
 * Ainda não existe login próprio da criança (ver o brief completo do
 * módulo, seção "Perfil Infantil") - por enquanto, quem está
 * navegando é escolhido por um seletor simples guardado na sessão do
 * adulto logado (ex.: tablet fixo na sala), só pra permitir testar e
 * demonstrar a gamificação antes de construir a autenticação infantil
 * de verdade numa fase futura.
 */
final class KidsBibliotecaController extends Controller
{
    private const SESSION_KEY = 'kids_crianca_ativa_id';

    public function index(): void
    {
        echo $this->view('dashboard.kids.biblioteca.index', [
            'pageTitle' => 'Biblioteca Kids - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Biblioteca'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'contagens' => KidsConteudo::contagemPublicadaPorTipo(),
            'recentes' => KidsConteudo::recentesPublicados(6),
            'criancasAtivas' => KidsCrianca::allActive(),
            'criancaAtiva' => $this->criancaAtiva(),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function porTipo(string $tipo): void
    {
        if (!array_key_exists($tipo, KidsConteudo::TIPOS)) {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
                'activeMenu' => 'kids',
                'breadcrumb' => ['Dashboard', 'Kids', 'Biblioteca', 'Não encontrado'],
                'user' => (new Auth($this->config))->user(),
                'modules' => DashboardController::modules(),
            ], 'dashboard');

            return;
        }

        echo $this->view('dashboard.kids.biblioteca.tipo', [
            'pageTitle' => KidsConteudo::TIPOS[$tipo]['label'] . ' - KADOSYS Kids',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Biblioteca', KidsConteudo::TIPOS[$tipo]['label']],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'tipo' => $tipo,
            'conteudos' => KidsConteudo::publicadosPorTipo($tipo),
            'criancaAtiva' => $this->criancaAtiva(),
        ], 'dashboard');
    }

    public function show(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);

        if (!$conteudo || $conteudo->status !== 'publicado') {
            http_response_code(404);

            echo $this->view('errors.404', [
                'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
                'activeMenu' => 'kids',
                'breadcrumb' => ['Dashboard', 'Kids', 'Biblioteca', 'Não encontrado'],
                'user' => (new Auth($this->config))->user(),
                'modules' => DashboardController::modules(),
            ], 'dashboard');

            return;
        }

        $criancaAtiva = $this->criancaAtiva();

        echo $this->view('dashboard.kids.biblioteca.show', [
            'pageTitle' => $conteudo->titulo . ' - KADOSYS Kids',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Biblioteca', $conteudo->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'conteudo' => $conteudo,
            'criancaAtiva' => $criancaAtiva,
            'jaConcluido' => $criancaAtiva !== null && KidsConteudo::jaConcluidoPor($criancaAtiva->id, $conteudo->id),
            'csrfToken' => Csrf::token(),
            'pontosGanhos' => Session::flash('kids_biblioteca_pontos'),
        ], 'dashboard');
    }

    public function selecionarCrianca(): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $criancaId = (int) $this->request->input('crianca_id', 0);

            if ($criancaId > 0 && KidsCrianca::find($criancaId) !== null) {
                Session::set(self::SESSION_KEY, $criancaId);
            } else {
                Session::remove(self::SESSION_KEY);
            }
        }

        $this->back();
    }

    public function concluir(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);
        $criancaAtiva = $this->criancaAtiva();

        if ($conteudo !== null && $criancaAtiva !== null && Csrf::verify($this->request->input('_csrf_token'))) {
            $ganhouAgora = $conteudo->registrarConclusaoPor($criancaAtiva->id);

            if ($ganhouAgora) {
                Session::flash('kids_biblioteca_pontos', [
                    'xp' => $conteudo->xpRecompensa,
                    'moedas' => $conteudo->moedasRecompensa,
                ]);
            }
        }

        $this->redirect("/dashboard/kids/biblioteca/conteudo/{$id}");
    }

    private function criancaAtiva(): ?KidsCrianca
    {
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? KidsCrianca::find($id) : null;
    }
}
