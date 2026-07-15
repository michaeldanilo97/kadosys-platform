<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Core\Csrf;
use Barbearias\Core\Session;
use Barbearias\Models\Barbearia;
use Barbearias\Models\PortfolioFoto;
use Barbearias\Models\Profissional;
use Barbearias\Models\Unidade;
use Barbearias\Models\User;

final class ProfissionalController extends Controller
{
    private const POR_PAGINA = 15;
    private const UPLOAD_DIR = 'uploads/profissionais';
    private const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const MIME_PARA_EXTENSAO = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $resultado = Profissional::paginate($barbeariaId, $page, self::POR_PAGINA, $search);

        echo $this->view('dashboard.profissionais.index', [
            'pageTitle' => 'Profissionais - KADOSYS Barbearias',
            'activeMenu' => 'profissionais',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissionais' => $resultado['items'],
            'total' => $resultado['total'],
            'page' => $resultado['page'],
            'lastPage' => $resultado['lastPage'],
            'search' => $search,
            'success' => Session::flash('profissional_success'),
        ], 'dashboard');
    }

    public function create(): void
    {
        $barbeariaId = $this->barbeariaId();

        echo $this->view('dashboard.profissionais.form', [
            'pageTitle' => 'Novo profissional - KADOSYS Barbearias',
            'activeMenu' => 'profissionais',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissional' => null,
            'unidades' => Unidade::temMultiplasAtivas($barbeariaId) ? Unidade::ativas($barbeariaId) : [],
            'unidadesAtuais' => [],
            'portfolio' => [],
            'old' => Session::flash('profissional_old') ?? [],
            'errors' => Session::flash('profissional_errors') ?? [],
        ], 'dashboard');
    }

    public function store(): void
    {
        $barbeariaId = $this->barbeariaId();

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/profissionais');
        }

        $dados = $this->dadosDoFormulario();
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('profissional_errors', $errors);
            Session::flash('profissional_old', $dados);
            $this->redirect('/dashboard/profissionais/novo');
        }

        $id = Profissional::create(
            $barbeariaId,
            (string) $dados['nome'],
            $dados['especialidade'],
            $dados['email'],
            $dados['telefone'],
            $dados['dias_atendimento'],
            $dados['horario_inicio'],
            $dados['horario_fim'],
            $dados['percentual_comissao'],
        );

        Profissional::definirUnidades($id, $this->unidadesDoFormulario($barbeariaId));
        $this->processarUploadFoto($id, $barbeariaId, $errors);

        Session::flash('profissional_success', 'Profissional cadastrado com sucesso.');
        $this->redirect('/dashboard/profissionais');
    }

    public function edit(string $id): void
    {
        $barbeariaId = $this->barbeariaId();
        $profissional = Profissional::find((int) $id, $barbeariaId);

        if ($profissional === null) {
            $this->redirect('/dashboard/profissionais');
        }

        echo $this->view('dashboard.profissionais.form', [
            'pageTitle' => 'Editar profissional - KADOSYS Barbearias',
            'activeMenu' => 'profissionais',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissional' => $profissional,
            'unidades' => Unidade::temMultiplasAtivas($barbeariaId) ? Unidade::ativas($barbeariaId) : [],
            'unidadesAtuais' => Profissional::unidadeIds($profissional->id),
            'portfolio' => PortfolioFoto::doProfissional($profissional->id, $barbeariaId),
            'old' => Session::flash('profissional_old') ?? [],
            'errors' => Session::flash('profissional_errors') ?? [],
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        $barbeariaId = $this->barbeariaId();

        if (Profissional::find((int) $id, $barbeariaId) === null) {
            $this->redirect('/dashboard/profissionais');
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            $this->redirect('/dashboard/profissionais');
        }

        $dados = $this->dadosDoFormulario();
        $errors = $this->validar($dados);

        if ($errors !== []) {
            Session::flash('profissional_errors', $errors);
            Session::flash('profissional_old', $dados);
            $this->redirect('/dashboard/profissionais/' . $id . '/editar');
        }

        $ativo = $this->request->input('ativo') !== null;

        Profissional::update(
            (int) $id,
            $barbeariaId,
            (string) $dados['nome'],
            $dados['especialidade'],
            $dados['email'],
            $dados['telefone'],
            $dados['dias_atendimento'],
            $dados['horario_inicio'],
            $dados['horario_fim'],
            $dados['percentual_comissao'],
            $ativo,
        );

        Profissional::definirUnidades((int) $id, $this->unidadesDoFormulario($barbeariaId));
        $this->processarUploadFoto((int) $id, $barbeariaId, $errors);

        Session::flash('profissional_success', 'Profissional atualizado com sucesso.');
        $this->redirect('/dashboard/profissionais');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            $barbeariaId = $this->barbeariaId();
            $profissional = Profissional::find((int) $id, $barbeariaId);

            if ($profissional !== null) {
                $this->removerArquivoFoto($profissional->fotoPath);
            }

            Profissional::delete((int) $id, $barbeariaId);
            Session::flash('profissional_success', 'Profissional removido.');
        }

        $this->redirect('/dashboard/profissionais');
    }

    /** @return array{nome:string, especialidade:?string, email:?string, telefone:?string, dias_atendimento:array<int,int>, horario_inicio:?string, horario_fim:?string, percentual_comissao:float} */
    private function dadosDoFormulario(): array
    {
        $diasInformados = $this->request->input('dias_atendimento', []);
        $dias = is_array($diasInformados) ? array_map('intval', $diasInformados) : [];

        return [
            'nome' => trim((string) $this->request->input('nome', '')),
            'especialidade' => $this->request->input('especialidade'),
            'email' => $this->request->input('email'),
            'telefone' => $this->request->input('telefone'),
            'dias_atendimento' => $dias,
            'horario_inicio' => $this->normalizarHora($this->request->input('horario_inicio')),
            'horario_fim' => $this->normalizarHora($this->request->input('horario_fim')),
            'percentual_comissao' => (float) str_replace(',', '.', (string) $this->request->input('percentual_comissao', '0')),
        ];
    }

    /**
     * Quando a barbearia tem so uma unidade, o profissional e vinculado
     * a ela automaticamente (sem nenhum campo no formulario) - so pede
     * pra escolher quando ha mais de uma unidade ativa, e so aceita ids
     * que realmente pertencem a essa barbearia.
     *
     * @return array<int, int>
     */
    private function unidadesDoFormulario(int $barbeariaId): array
    {
        if (!Unidade::temMultiplasAtivas($barbeariaId)) {
            $principal = Unidade::principal($barbeariaId);

            return $principal !== null ? [$principal->id] : [];
        }

        $informadas = $this->request->input('unidades', []);
        $informadas = is_array($informadas) ? array_map('intval', $informadas) : [];
        $validas = array_map(static fn ($unidade) => $unidade->id, Unidade::ativas($barbeariaId));

        return array_values(array_intersect($informadas, $validas));
    }

    private function normalizarHora(mixed $valor): ?string
    {
        $valor = trim((string) ($valor ?? ''));

        return $valor === '' ? null : $valor . ':00';
    }

    /** @param array<string, mixed> $dados */
    private function validar(array $dados): array
    {
        $errors = [];

        if ($dados['nome'] === '' || mb_strlen($dados['nome']) < 2) {
            $errors[] = 'Informe o nome do profissional.';
        }

        $email = trim((string) ($dados['email'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido (ou deixe em branco).';
        }

        if ($dados['horario_inicio'] !== null && $dados['horario_fim'] !== null && $dados['horario_inicio'] >= $dados['horario_fim']) {
            $errors[] = 'O horário de início precisa ser antes do horário de fim.';
        }

        if ($dados['percentual_comissao'] < 0 || $dados['percentual_comissao'] > 100) {
            $errors[] = 'O percentual de comissão precisa estar entre 0 e 100.';
        }

        return $errors;
    }

    /**
     * Faz o upload da foto (campo opcional) - se nao vier nenhum
     * arquivo, nao mexe na foto atual. Erros de upload NAO bloqueiam o
     * cadastro/edicao (o profissional ja foi salvo nesse momento) - so
     * viram um aviso.
     *
     * @param array<int, string> $errors
     */
    private function processarUploadFoto(int $id, int $barbeariaId, array &$errors): void
    {
        $arquivo = $this->request->file('foto');

        if ($arquivo === null) {
            return;
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Session::flash('profissional_success', 'Profissional salvo, mas houve falha no envio da foto.');

            return;
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO_FOTO) {
            Session::flash('profissional_success', 'Profissional salvo, mas a foto excede 5MB e não foi enviada.');

            return;
        }

        $mime = mime_content_type($arquivo['tmp_name']) ?: '';
        $extensao = self::MIME_PARA_EXTENSAO[$mime] ?? null;

        if ($extensao === null) {
            Session::flash('profissional_success', 'Profissional salvo, mas o formato da foto é inválido (use PNG, JPG ou WEBP).');

            return;
        }

        $destinoDir = dirname(__DIR__, 2) . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
            return;
        }

        $antigo = Profissional::find($id, $barbeariaId);

        if ($antigo !== null) {
            $this->removerArquivoFoto($antigo->fotoPath);
        }

        $nomeArquivo = 'profissional_' . $id . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], $destinoDir . '/' . $nomeArquivo)) {
            return;
        }

        Profissional::atualizarFoto($id, $barbeariaId, self::UPLOAD_DIR . '/' . $nomeArquivo);
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

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
