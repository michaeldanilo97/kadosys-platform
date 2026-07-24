<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Controller;
use Igrejas\Core\Middleware\KidsSessaoMiddleware;
use Igrejas\Core\Session;
use Igrejas\Models\BibliaLivro;
use Igrejas\Models\BibliaVersao;
use Igrejas\Models\BibliaVersiculo;
use Igrejas\Models\KidsBibliaLeitura;
use Igrejas\Models\KidsCrianca;

/**
 * Biblia Interativa do "modo criança" (ver KidsAppController): navega
 * pelos 66 livros ja seedados desde a migracao 005 e mostra o texto de
 * biblia_versiculos - o mesmo dado de referencia usado no
 * Preletor/Telão, so que aqui numa casca visual da Biblioteca Kids.
 *
 * O texto e importado a parte pela equipe (ver
 * BibliaVersiculo::textoImportado()/database/seed_biblia.php) - sem
 * ele, mostra um aviso simples em vez de telas vazias/quebradas.
 */
final class KidsBibliaController extends Controller
{
    public function index(): void
    {
        $crianca = $this->criancaLogada();
        $textoImportado = BibliaVersiculo::textoImportado();

        echo $this->view('kids.biblia.index', [
            'pageTitle' => 'Bíblia Interativa - KADOSYS Kids',
            'crianca' => $crianca,
            'textoImportado' => $textoImportado,
            'livros' => $textoImportado ? BibliaLivro::all() : [],
            'lidosPorLivro' => $textoImportado ? KidsBibliaLeitura::contagemPorLivro($crianca->id) : [],
        ], 'kids-app');
    }

    public function livro(string $livroId): void
    {
        $crianca = $this->criancaLogada();
        $livro = BibliaLivro::find((int) $livroId);

        if ($livro === null) {
            $this->redirect('/kids/biblia');
        }

        echo $this->view('kids.biblia.livro', [
            'pageTitle' => $livro->nome . ' - KADOSYS Kids',
            'crianca' => $crianca,
            'livro' => $livro,
            'lidos' => KidsBibliaLeitura::capitulosLidosDoLivro($crianca->id, $livro->id),
        ], 'kids-app');
    }

    public function capitulo(string $livroId, string $capitulo): void
    {
        $crianca = $this->criancaLogada();
        $livro = BibliaLivro::find((int) $livroId);
        $capituloNumero = (int) $capitulo;

        if ($livro === null || $capituloNumero < 1 || $capituloNumero > $livro->totalCapitulos) {
            $this->redirect('/kids/biblia');
        }

        $versiculos = BibliaVersiculo::doCapitulo(BibliaVersao::PADRAO, $livro->id, $capituloNumero);

        // Ler o capitulo ja conta como "lido" - sem botao separado de
        // concluir, o hábito de leitura é premiado pelo próprio ato de
        // abrir a página (mesma filosofia dos "checkins" do módulo).
        $ganhouBonus = $versiculos !== [] && KidsBibliaLeitura::registrarLeitura($crianca->id, $livro->id, $capituloNumero);

        echo $this->view('kids.biblia.capitulo', [
            'pageTitle' => $livro->nome . ' ' . $capituloNumero . ' - KADOSYS Kids',
            'crianca' => $crianca,
            'livro' => $livro,
            'capitulo' => $capituloNumero,
            'versiculos' => $versiculos,
            'ganhouBonus' => $ganhouBonus,
            'anterior' => BibliaVersiculo::referenciaAnterior(BibliaVersao::PADRAO, $livro->id, $capituloNumero, 1),
            'proximo' => BibliaVersiculo::proximaReferencia(BibliaVersao::PADRAO, $livro->id, $capituloNumero, PHP_INT_MAX),
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
