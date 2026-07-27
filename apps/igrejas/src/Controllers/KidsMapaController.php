<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\KidsCrianca;
use Igrejas\Models\KidsMapaLocal;

/**
 * Mapa Biblico do "modo criança" (ver KidsAppController): mapa
 * ilustrado com pins clicaveis pros lugares mais importantes da
 * Biblia - catalogo estatico em KidsMapaLocal.
 */
final class KidsMapaController extends Controller
{
    public function index(): void
    {
        $crianca = $this->criancaLogada();

        echo $this->view('kids.mapa.index', [
            'pageTitle' => 'Mapa Bíblico - KADOSYS Kids',
            'crianca' => $crianca,
            'locais' => KidsMapaLocal::CATALOGO,
            'explorados' => KidsMapaLocal::exploradosPor($crianca->id),
        ], 'kids-app');
    }

    public function local(string $slug): void
    {
        $crianca = $this->criancaLogada();
        $local = KidsMapaLocal::CATALOGO[$slug] ?? null;

        if ($local === null) {
            $this->redirect('/kids/mapa');
        }

        // Visitar o local ja conta como "explorado", mesma filosofia
        // da Biblia Interativa - sem botao separado de concluir.
        $ganhouBonus = KidsMapaLocal::registrarExploracao($crianca->id, $slug);

        echo $this->view('kids.mapa.local', [
            'pageTitle' => $local['nome'] . ' - KADOSYS Kids',
            'crianca' => $crianca,
            'slug' => $slug,
            'local' => $local,
            'ganhouBonus' => $ganhouBonus,
        ], 'kids-app');
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
