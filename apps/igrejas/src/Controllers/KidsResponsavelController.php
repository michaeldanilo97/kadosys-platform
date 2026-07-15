<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\KidsCrianca;

/**
 * Área de autoatendimento "Meus filhos" (Kids): o próprio responsável
 * (Membro vinculado a um login de usuário) gera e gerencia o PIN de
 * acesso dos filhos dele à Biblioteca, sem depender da equipe da
 * igreja - mesma regra de sempre (só libera o PIN pra quem já está
 * vinculado como responsável, ver KidsCrianca::gerarESalvarPin()), só
 * que aqui a ação parte do próprio responsável, não de um admin.
 *
 * Acessível a qualquer usuário autenticado (ver isenção em
 * AuthMiddleware::bloquearSePermissaoNegada) - a "permissão" de
 * verdade é a posse: cada ação confere que a criança pertence mesmo
 * ao responsável logado antes de agir.
 */
final class KidsResponsavelController extends Controller
{
    public function index(): void
    {
        $user = (new Auth($this->config))->user();
        $filhos = $user?->membroId !== null ? KidsCrianca::doResponsavel($user->membroId) : [];

        echo $this->view('dashboard.kids.meus-filhos.index', [
            'pageTitle' => 'Meus filhos - KADOSYS Igrejas',
            'activeMenu' => 'kids',
            'breadcrumb' => ['Dashboard', 'Kids', 'Meus filhos'],
            'user' => $user,
            'modules' => DashboardController::modules(),
            'filhos' => $filhos,
            'success' => Session::flash('kids_meus_filhos_success'),
            'errors' => Session::flash('kids_meus_filhos_errors') ?? [],
            'pinGerado' => Session::flash('kids_meus_filhos_pin_gerado'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function gerarPin(string $id): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('kids_meus_filhos_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/kids/meus-filhos');
        }

        $crianca = $this->filhoDoResponsavelLogado((int) $id);

        if ($crianca === null) {
            Session::flash('kids_meus_filhos_errors', ['Criança não encontrada ou não vinculada a você.']);
            $this->redirect('/dashboard/kids/meus-filhos');
        }

        $pin = KidsCrianca::gerarESalvarPin($crianca->id);
        Session::flash('kids_meus_filhos_pin_gerado', ['id' => $crianca->id, 'pin' => $pin]);
        Session::flash('kids_meus_filhos_success', 'PIN gerado com sucesso.');
        $this->redirect('/dashboard/kids/meus-filhos');
    }

    public function removerPin(string $id): void
    {
        $crianca = Csrf::verify($this->request->input('_csrf_token'))
            ? $this->filhoDoResponsavelLogado((int) $id)
            : null;

        if ($crianca !== null) {
            KidsCrianca::removerPin($crianca->id);
            Session::flash('kids_meus_filhos_success', 'PIN removido.');
        }

        $this->redirect('/dashboard/kids/meus-filhos');
    }

    /**
     * Só retorna a criança se ela realmente pertencer ao responsável
     * logado - é essa checagem que substitui a permissão de módulo
     * pra essa área de autoatendimento.
     */
    private function filhoDoResponsavelLogado(int $criancaId): ?KidsCrianca
    {
        $user = (new Auth($this->config))->user();

        if ($user?->membroId === null) {
            return null;
        }

        $crianca = KidsCrianca::find($criancaId);

        return ($crianca !== null && $crianca->responsavelMembroId === $user->membroId) ? $crianca : null;
    }
}
