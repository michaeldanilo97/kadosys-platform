<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\KidsConteudo;
use Igrejas\Models\KidsCrianca;

/**
 * "Modo criança" de verdade: a mesma Biblioteca de conteúdo (ver
 * KidsConteudo), mas servida fora do painel administrativo, atrás do
 * login por PIN (ver KidsLoginController/KidsSessaoMiddleware) em vez
 * de exigir um adulto logado no sistema - pensado pra criança usar
 * sozinha num tablet/celular.
 */
final class KidsAppController extends Controller
{
    public function index(): void
    {
        $crianca = $this->criancaLogada();

        echo $this->view('kids.home', [
            'pageTitle' => 'KADOSYS Kids',
            'crianca' => $crianca,
            'contagens' => KidsConteudo::contagemPublicadaPorTipo(),
            'recentes' => KidsConteudo::recentesPublicados(6),
            'csrfToken' => Csrf::token(),
        ], 'kids-app');
    }

    public function porTipo(string $tipo): void
    {
        if (!array_key_exists($tipo, KidsConteudo::TIPOS)) {
            $this->redirect('/kids');
        }

        echo $this->view('kids.tipo', [
            'pageTitle' => KidsConteudo::TIPOS[$tipo]['label'] . ' - KADOSYS Kids',
            'crianca' => $this->criancaLogada(),
            'tipo' => $tipo,
            'conteudos' => KidsConteudo::publicadosPorTipo($tipo),
        ], 'kids-app');
    }

    public function show(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);

        if (!$conteudo || $conteudo->status !== 'publicado') {
            $this->redirect('/kids');
        }

        $crianca = $this->criancaLogada();

        echo $this->view('kids.show', [
            'pageTitle' => $conteudo->titulo . ' - KADOSYS Kids',
            'crianca' => $crianca,
            'conteudo' => $conteudo,
            'jaConcluido' => KidsConteudo::jaConcluidoPor($crianca->id, $conteudo->id),
            'csrfToken' => Csrf::token(),
            'pontosGanhos' => Session::flash('kids_app_pontos'),
        ], 'kids-app');
    }

    public function concluir(string $id): void
    {
        $conteudo = KidsConteudo::find((int) $id);
        $crianca = $this->criancaLogada();

        if ($conteudo !== null && Csrf::verify($this->request->input('_csrf_token'))) {
            $ganhouAgora = $conteudo->registrarConclusaoPor($crianca->id);

            if ($ganhouAgora) {
                Session::flash('kids_app_pontos', [
                    'xp' => $conteudo->xpRecompensa,
                    'moedas' => $conteudo->moedasRecompensa,
                ]);
            }
        }

        $this->redirect("/kids/conteudo/{$id}");
    }

    /**
     * A crianca sempre existe aqui - KidsSessaoMiddleware ja garantiu
     * isso antes do controller ser chamado.
     */
    private function criancaLogada(): KidsCrianca
    {
        return KidsCrianca::find((int) Session::get(KidsSessaoMiddleware::SESSION_KEY));
    }
}
