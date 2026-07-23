<?php

declare(strict_types=1);

namespace Food\Controllers;

use Food\Core\Auth;
use Food\Core\Controller;
use Food\Core\Csrf;
use Food\Core\Session;
use Food\Models\FichaTecnicaItem;
use Food\Models\Fornecedor;
use Food\Models\Ingrediente;
use Food\Models\Produto;
use Food\Models\Restaurante;
use Food\Models\User;

final class IngredienteController extends Controller
{
    private const POR_PAGINA = 15;
    private const UPLOAD_DIR = 'uploads/ingredientes';
    private const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /** @var array<int, string> */
    private const UNIDADES = ['un', 'kg', 'g', 'l', 'ml', 'cx', 'pct', 'dz'];

    public function index(): void
    {
        $restauranteId = $this->restauranteId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Ingrediente::paginate($restauranteId, $page, self::POR_PAGINA, $search);
        $fornecedores = Fornecedor::doRestaurante($restauranteId);
        $fornecedoresPorId = [];

        foreach ($fornecedores as $fornecedor) {
            $fornecedoresPorId[$fornecedor->id] = $fornecedor->nome;
        }

        echo $this->view('dashboard.ingredientes.index', [
            'pageTitle' => 'Ingredientes - KADOSYS Food',
            'activeMenu' => 'ingredientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($restauranteId),
            'ingredientes' => $resultado['items'],
            'fornecedoresPorId' => $fornecedoresPorId,
            'estoqueBaixo' => Ingrediente::comEstoqueBaixo($restauranteId),
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('ingrediente_success'),
            'errors' => Session::flash('ingrediente_errors') ?? [],
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.ingredientes.form', [
            'pageTitle' => 'Novo ingrediente - KADOSYS Food',
            'activeMenu' => 'ingredientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'ingrediente' => null,
            'fornecedores' => Fornecedor::doRestaurante($this->restauranteId()),
            'unidades' => self::UNIDADES,
            'old' => Session::flash('ingrediente_old') ?? [],
            'errors' => Session::flash('ingrediente_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/ingredientes');
        }

        $dados = $this->request->only([
            'nome', 'categoria', 'fornecedor_id', 'codigo', 'unidade',
            'preco_atual', 'estoque_atual', 'estoque_minimo', 'localizacao', 'observacao',
        ]);
        [$errors, $fornecedorId, $precoAtual, $estoqueAtual, $estoqueMinimo] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('ingrediente_errors', $errors);
            Session::flash('ingrediente_old', $dados);
            $this->redirect('/dashboard/ingredientes/novo');
        }

        $restauranteId = $this->restauranteId();

        $id = Ingrediente::create(
            $restauranteId,
            (string) $dados['nome'],
            $dados['categoria'] !== null ? (string) $dados['categoria'] : null,
            $fornecedorId,
            $dados['codigo'] !== null ? (string) $dados['codigo'] : null,
            (string) $dados['unidade'],
            $precoAtual,
            $estoqueAtual,
            $estoqueMinimo,
            $dados['localizacao'] !== null ? (string) $dados['localizacao'] : null,
            $dados['observacao'] !== null ? (string) $dados['observacao'] : null,
        );

        $this->processarUploadFoto($id, $restauranteId);

        Session::flash('ingrediente_success', 'Ingrediente cadastrado com sucesso.');
        $this->redirect('/dashboard/ingredientes');
    }

    public function edit(string $id): void
    {
        $ingrediente = Ingrediente::find((int) $id, $this->restauranteId());

        if ($ingrediente === null) {
            $this->redirect('/dashboard/ingredientes');
        }

        echo $this->view('dashboard.ingredientes.form', [
            'pageTitle' => 'Editar ingrediente - KADOSYS Food',
            'activeMenu' => 'ingredientes',
            'user' => $this->usuario(),
            'restaurante' => Restaurante::find($this->restauranteId()),
            'ingrediente' => $ingrediente,
            'fornecedores' => Fornecedor::doRestaurante($this->restauranteId()),
            'unidades' => self::UNIDADES,
            'old' => Session::flash('ingrediente_old') ?? [],
            'errors' => Session::flash('ingrediente_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Ingrediente::find((int) $id, $restauranteId) === null) {
            $this->redirect('/dashboard/ingredientes');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/ingredientes');
        }

        $dados = $this->request->only([
            'nome', 'categoria', 'fornecedor_id', 'codigo', 'unidade',
            'preco_atual', 'estoque_atual', 'estoque_minimo', 'localizacao', 'observacao',
        ]);
        [$errors, $fornecedorId, $precoAtual, $estoqueAtual, $estoqueMinimo] = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('ingrediente_errors', $errors);
            Session::flash('ingrediente_old', $dados);
            $this->redirect('/dashboard/ingredientes/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Ingrediente::update(
            (int) $id,
            $restauranteId,
            (string) $dados['nome'],
            $dados['categoria'] !== null ? (string) $dados['categoria'] : null,
            $fornecedorId,
            $dados['codigo'] !== null ? (string) $dados['codigo'] : null,
            (string) $dados['unidade'],
            $precoAtual,
            $estoqueAtual,
            $estoqueMinimo,
            $dados['localizacao'] !== null ? (string) $dados['localizacao'] : null,
            $dados['observacao'] !== null ? (string) $dados['observacao'] : null,
            $ativo,
        );

        $this->processarUploadFoto((int) $id, $restauranteId);

        // Preco pode ter mudado - recalcula em cascata o custo/preco
        // ideal de todo produto cuja ficha tecnica usa esse ingrediente,
        // no mesmo request (sincrono, sem fila).
        Produto::recalcularCustoDeProdutosComIngrediente((int) $id, $restauranteId);

        Session::flash('ingrediente_success', 'Ingrediente atualizado com sucesso.');
        $this->redirect('/dashboard/ingredientes');
    }

    public function destroy(string $id): void
    {
        $restauranteId = $this->restauranteId();

        if (Csrf::verify($this->request->input('_csrf_token'))) {
            if (FichaTecnicaItem::produtoIdsComIngrediente((int) $id, $restauranteId) !== []) {
                Session::flash('ingrediente_errors', ['Esse ingrediente está em uso na ficha técnica de um ou mais produtos e não pode ser excluído.']);
                $this->redirect('/dashboard/ingredientes');
            }

            $ingrediente = Ingrediente::find((int) $id, $restauranteId);

            if ($ingrediente !== null) {
                $this->removerArquivoFoto($ingrediente->fotoPath);
            }

            Ingrediente::delete((int) $id, $restauranteId);
            Session::flash('ingrediente_success', 'Ingrediente removido.');
        }

        $this->redirect('/dashboard/ingredientes');
    }

    /** @return array{0: array<int, string>, 1: int|null, 2: float, 3: float, 4: float} */
    private function validar(array $dados): array
    {
        $errors = [];
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '' || mb_strlen($nome) < 2) {
            $errors[] = 'Informe o nome do ingrediente.';
        }

        $unidade = (string) ($dados['unidade'] ?? '');

        if (!in_array($unidade, self::UNIDADES, true)) {
            $errors[] = 'Escolha uma unidade de medida válida.';
        }

        $precoInformado = str_replace(',', '.', (string) ($dados['preco_atual'] ?? ''));
        $precoAtual = is_numeric($precoInformado) ? (float) $precoInformado : -1;

        if ($precoAtual < 0) {
            $errors[] = 'Informe um preço válido.';
        }

        $estoqueAtualInformado = str_replace(',', '.', (string) ($dados['estoque_atual'] ?? ''));
        $estoqueAtual = is_numeric($estoqueAtualInformado) ? (float) $estoqueAtualInformado : -1;

        if ($estoqueAtual < 0) {
            $errors[] = 'O estoque atual não pode ser negativo.';
        }

        $estoqueMinimoInformado = str_replace(',', '.', (string) ($dados['estoque_minimo'] ?? ''));
        $estoqueMinimo = is_numeric($estoqueMinimoInformado) ? (float) $estoqueMinimoInformado : -1;

        if ($estoqueMinimo < 0) {
            $errors[] = 'O estoque mínimo não pode ser negativo.';
        }

        $fornecedorIdInformado = trim((string) ($dados['fornecedor_id'] ?? ''));
        $fornecedorId = null;

        if ($fornecedorIdInformado !== '') {
            if (!ctype_digit($fornecedorIdInformado)) {
                $errors[] = 'Fornecedor inválido.';
            } else {
                $fornecedorId = (int) $fornecedorIdInformado;

                if (Fornecedor::find($fornecedorId, $this->restauranteId()) === null) {
                    $errors[] = 'Fornecedor inválido.';
                    $fornecedorId = null;
                }
            }
        }

        return [$errors, $fornecedorId, max(0.0, $precoAtual), max(0.0, $estoqueAtual), max(0.0, $estoqueMinimo)];
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

        $antigo = Ingrediente::find($id, $restauranteId);

        if ($antigo !== null) {
            $this->removerArquivoFoto($antigo->fotoPath);
        }

        $nomeArquivo = 'ingrediente_' . $id . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return;
        }

        Ingrediente::atualizarFoto($id, $restauranteId, self::UPLOAD_DIR . '/' . $nomeArquivo);
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
