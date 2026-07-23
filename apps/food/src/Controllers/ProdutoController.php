<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\Categoria;
use Food\Models\FichaTecnicaItem;
use Food\Models\Ingrediente;
use Food\Models\Produto;
use Food\Models\Restaurante;
use Food\Models\User;

final class ProdutoController extends Controller
{
    private const POR_PAGINA = 15;
    private const UPLOAD_DIR = 'uploads/produtos';
    private const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Produto::paginate($restauranteId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.produtos.index', [
            'pageTitle' => 'Produtos - KADOSYS Food',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'produtos' => $resultado['items'],
            'categoriasPorId' => $this->categoriasPorId($restauranteId),
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('produto_success'),
            'errors' => Session::flash('produto_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        $restauranteId = $this->restauranteId();

        echo $this->view('dashboard.produtos.form', [
            'pageTitle' => 'Novo produto - KADOSYS Food',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'produto' => null,
            'categorias' => Categoria::ativas($restauranteId),
            'statusValidos' => Produto::STATUS_VALIDOS,
            'old' => Session::flash('produto_old') ?? [],
            'errors' => Session::flash('produto_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/produtos');
        }

        $dados = $this->dadosDoFormulario();
        [$errors, $categoriaId, $rendimento, $precos, $extra] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('produto_errors', $errors);
            Session::flash('produto_old', $dados);
            $this->redirect('/dashboard/produtos/novo');
        }

        $restauranteId = $this->restauranteId();

        $id = Produto::create(
            $restauranteId,
            $categoriaId,
            (string) $dados['nome'],
            $rendimento,
            $precos['balcao'],
            $precos['whatsapp'],
            $precos['ifood'],
            $precos['promocao'],
            $precos['delivery'],
            $extra,
        );

        $this->processarUploadFoto($id, $restauranteId);

        Session::flash('produto_success', 'Produto cadastrado com sucesso.');
        $this->redirect('/dashboard/produtos');
    }

    public function edit(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $produto = Produto::find((int) $id, $restauranteId);

        if ($produto === null) {
            $this->redirect('/dashboard/produtos');
        }

        echo $this->view('dashboard.produtos.form', [
            'pageTitle' => 'Editar produto - KADOSYS Food',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'produto' => $produto,
            'categorias' => Categoria::ativas($restauranteId),
            'statusValidos' => Produto::STATUS_VALIDOS,
            'old' => Session::flash('produto_old') ?? [],
            'errors' => Session::flash('produto_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Produto::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/produtos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/produtos');
        }

        $dados = $this->dadosDoFormulario();
        [$errors, $categoriaId, $rendimento, $precos, $extra] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('produto_errors', $errors);
            Session::flash('produto_old', $dados);
            $this->redirect('/dashboard/produtos/' . $id . '/editar');
        }

        $status = (string) $this->request->input('status', Produto::STATUS_ATIVO);

        Produto::update(
            (int) $id,
            $restauranteId,
            $categoriaId,
            (string) $dados['nome'],
            $rendimento,
            $status,
            $precos['balcao'],
            $precos['whatsapp'],
            $precos['ifood'],
            $precos['promocao'],
            $precos['delivery'],
            $extra,
        );

        $this->processarUploadFoto((int) $id, $restauranteId);

        Session::flash('produto_success', 'Produto atualizado com sucesso.');
        $this->redirect('/dashboard/produtos');
    }

    public function destroy(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $produto = Produto::find((int) $id, $restauranteId);

            if ($produto !== null) {
                $this->removerArquivoFoto($produto->fotoPath);
            }

            Produto::delete((int) $id, $restauranteId);
            Session::flash('produto_success', 'Produto removido.');
        }

        $this->redirect('/dashboard/produtos');
    }

    /**
     * Ficha tecnica (receita) do produto - lista os ingredientes ja
     * adicionados e o resumo de custo/margem/preco ideal ja calculado
     * (ver Produto::recalcularCusto(), chamado a cada item
     * adicionado/removido).
     */
    public function fichaTecnica(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $produto = Produto::find((int) $id, $restauranteId);

        if ($produto === null) {
            $this->redirect('/dashboard/produtos');
        }

        echo $this->view('dashboard.produtos.ficha-tecnica', [
            'pageTitle' => 'Ficha técnica - KADOSYS Food',
            'activeMenu' => 'produtos',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'produto' => $produto,
            'itens' => FichaTecnicaItem::doProduto($produto->id),
            'ingredientes' => Ingrediente::ativos($restauranteId),
            'success' => Session::flash('ficha_tecnica_success'),
            'errors' => Session::flash('ficha_tecnica_errors') ?? [],
        ], 'dashboard');
    }

    public function fichaTecnicaAdicionar(string $id): void
    {
        $restauranteId = $this->restauranteId();
        $produto = Produto::find((int) $id, $restauranteId);

        if ($produto === null) {
            $this->redirect('/dashboard/produtos');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/produtos/' . $id . '/ficha-tecnica');
        }

        $ingredienteId = (int) $this->request->input('ingrediente_id', 0);
        $quantidadeInformada = str_replace(',', '.', (string) $this->request->input('quantidade', ''));
        $quantidade = is_numeric($quantidadeInformada) ? (float) $quantidadeInformada : -1;
        $perdaInformada = str_replace(',', '.', (string) $this->request->input('perda_percentual', '0'));
        $perdaPercentual = is_numeric($perdaInformada) ? (float) $perdaInformada : -1;

        $ingrediente = Ingrediente::find($ingredienteId, $restauranteId);
        $errors = [];

        if ($ingrediente === null) {
            $errors[] = 'Escolha um ingrediente válido.';
        }

        if ($quantidade <= 0) {
            $errors[] = 'Informe uma quantidade válida.';
        }

        if ($perdaPercentual < 0 || $perdaPercentual > 100) {
            $errors[] = 'A perda precisa ser um percentual entre 0 e 100.';
        }

        if ($errors !== []) {
            Session::flash('ficha_tecnica_errors', $errors);
            $this->redirect('/dashboard/produtos/' . $id . '/ficha-tecnica');
        }

        FichaTecnicaItem::create($produto->id, $ingredienteId, $quantidade, $ingrediente->unidade, $perdaPercentual);
        Produto::recalcularCusto($produto->id, $restauranteId);

        Session::flash('ficha_tecnica_success', 'Ingrediente adicionado à ficha técnica.');
        $this->redirect('/dashboard/produtos/' . $id . '/ficha-tecnica');
    }

    public function fichaTecnicaRemover(string $id, string $itemId): void
    {
        $restauranteId = $this->restauranteId();
        $produto = Produto::find((int) $id, $restauranteId);

        if ($produto === null) {
            $this->redirect('/dashboard/produtos');
        }

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            FichaTecnicaItem::delete((int) $itemId, $produto->id);
            Produto::recalcularCusto($produto->id, $restauranteId);
            Session::flash('ficha_tecnica_success', 'Ingrediente removido da ficha técnica.');
        }

        $this->redirect('/dashboard/produtos/' . $id . '/ficha-tecnica');
    }

    /** @return array<string, mixed> */
    private function dadosDoFormulario(): array
    {
        return $this->request->only([
            'nome', 'categoria_id', 'codigo', 'codigo_barras', 'descricao', 'tags', 'observacoes',
            'tempo_preparo_min', 'peso_g', 'rendimento', 'status',
            'preco_balcao', 'preco_whatsapp', 'preco_ifood', 'preco_promocao', 'preco_delivery_proprio',
            'custo_energia_override', 'custo_gas_override', 'custo_agua_override', 'custo_embalagem_override',
            'custo_etiqueta_override', 'custo_mao_obra_override', 'custo_taxa_operacional_override',
            'custo_desperdicio_override',
        ]);
    }

    /**
     * @return array{0: array<int, string>, 1: int|null, 2: int,
     *   3: array{balcao: float, whatsapp: ?float, ifood: ?float, promocao: ?float, delivery: ?float},
     *   4: array<string, mixed>}
     */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do produto.';
        }

        $categoriaIdInformado = trim((string) ($dados['categoria_id'] ?? ''));
        $categoriaId = null;

        if ($categoriaIdInformado !== '') {
            if (!ctype_digit($categoriaIdInformado)) {
                $errors[] = 'Categoria inválida.';
            } else {
                $categoriaId = (int) $categoriaIdInformado;

                if (Categoria::find($categoriaId, $this->restauranteId()) === null) {
                    $errors[] = 'Categoria inválida.';
                    $categoriaId = null;
                }
            }
        }

        $rendimentoInformado = trim((string) ($dados['rendimento'] ?? '1'));
        $rendimento = ctype_digit($rendimentoInformado) ? (int) $rendimentoInformado : 0;

        if ($rendimento < 1) {
            $errors[] = 'O rendimento precisa ser pelo menos 1 unidade.';
        }

        $precoBalcao = $this->parseDecimal($dados['preco_balcao'] ?? '');

        if ($precoBalcao === null || $precoBalcao < 0) {
            $errors[] = 'Informe um preço de balcão válido.';
        }

        $precos = [
            'balcao' => max(0.0, $precoBalcao ?? 0.0),
            'whatsapp' => $this->parseDecimalOpcional($dados['preco_whatsapp'] ?? ''),
            'ifood' => $this->parseDecimalOpcional($dados['preco_ifood'] ?? ''),
            'promocao' => $this->parseDecimalOpcional($dados['preco_promocao'] ?? ''),
            'delivery' => $this->parseDecimalOpcional($dados['preco_delivery_proprio'] ?? ''),
        ];

        $tempoPreparoInformado = trim((string) ($dados['tempo_preparo_min'] ?? ''));
        $tempoPreparoMin = $tempoPreparoInformado !== '' && ctype_digit($tempoPreparoInformado)
            ? (int) $tempoPreparoInformado
            : null;

        $extra = [
            'codigo' => $this->vazioParaNulo((string) ($dados['codigo'] ?? '')),
            'codigo_barras' => $this->vazioParaNulo((string) ($dados['codigo_barras'] ?? '')),
            'descricao' => $this->vazioParaNulo((string) ($dados['descricao'] ?? '')),
            'tags' => $this->vazioParaNulo((string) ($dados['tags'] ?? '')),
            'observacoes' => $this->vazioParaNulo((string) ($dados['observacoes'] ?? '')),
            'tempo_preparo_min' => $tempoPreparoMin,
            'peso_g' => $this->parseDecimalOpcional($dados['peso_g'] ?? ''),
            'energia' => $this->parseDecimalOpcional($dados['custo_energia_override'] ?? ''),
            'gas' => $this->parseDecimalOpcional($dados['custo_gas_override'] ?? ''),
            'agua' => $this->parseDecimalOpcional($dados['custo_agua_override'] ?? ''),
            'embalagem' => $this->parseDecimalOpcional($dados['custo_embalagem_override'] ?? ''),
            'etiqueta' => $this->parseDecimalOpcional($dados['custo_etiqueta_override'] ?? ''),
            'mao_obra' => $this->parseDecimalOpcional($dados['custo_mao_obra_override'] ?? ''),
            'taxa_operacional' => $this->parseDecimalOpcional($dados['custo_taxa_operacional_override'] ?? ''),
            'desperdicio' => $this->parseDecimalOpcional($dados['custo_desperdicio_override'] ?? ''),
        ];

        return [$errors, $categoriaId, max(1, $rendimento), $precos, $extra];
    }

    private function parseDecimal(mixed $valor): ?float
    {
        $texto = str_replace(',', '.', trim((string) $valor));

        return is_numeric($texto) ? (float) $texto : null;
    }

    private function parseDecimalOpcional(mixed $valor): ?float
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $this->parseDecimal($texto);
    }

    private function vazioParaNulo(string $valor): ?string
    {
        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }

    /** @return array<int, string> */
    private function categoriasPorId(int $restauranteId): array
    {
        $porId = [];

        foreach (Categoria::ativas($restauranteId) as $categoria) {
            $porId[$categoria->id] = $categoria->nome;
        }

        return $porId;
    }

    private function processarUploadFoto(int $id, int $restauranteId): void
    {
        $arquivo = $this->request->file('foto');

        if ($arquivo === null) {
            return;
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK || $arquivo['size'] > self::TAMANHO_MAXIMO_FOTO) {
            return;
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            return;
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            return;
        }

        $antigo = Produto::find($id, $restauranteId);

        if ($antigo !== null) {
            $this->removerArquivoFoto($antigo->fotoPath);
        }

        $nomeArquivo = 'produto_' . $id . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return;
        }

        Produto::atualizarFoto($id, $restauranteId, self::UPLOAD_DIR . '/' . $nomeArquivo);
    }

    private function removerArquivoFoto(?string $fotoPath): void
    {
        if ($fotoPath === null) {
            return;
        }

        $caminho = dirname(__DIR__, 2) . '/public/' . $fotoPath;

        if (is_file($caminho)) {
            unlink($caminho);
        }
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function restauranteId(): int
    {
        return $this->usuario()?->restauranteId ?? 0;
    }
}
