<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\Csrf;
use Igrejas\Core\Session;
use Igrejas\Models\Culto;
use Igrejas\Models\Membro;

/**
 * Controller do modulo Cultos: programacao de cultos, listagem com busca
 * e paginacao, edicao, remocao e registro de frequencia (membros
 * presentes em cada culto).
 */
final class CultoController extends Controller
{
    private const PER_PAGE = 15;

    // Limite de seguranca pra recorrencia semanal (ver store()) - evita
    // gerar uma quantidade absurda de cultos se alguem digitar uma data
    // limite muito distante por engano. 60 semanas cobre mais de um ano.
    private const MAX_OCORRENCIAS_RECORRENCIA = 60;

    public function index(): void
    {
        $page = max(1, (int) $this->request->input('pagina', 1));
        $search = trim((string) $this->request->input('busca', ''));

        $result = Culto::paginate($page, self::PER_PAGE, $search);

        echo $this->view('dashboard.cultos.index', [
            'pageTitle' => 'Cultos - KADOSYS Igrejas',
            'activeMenu' => 'cultos',
            'breadcrumb' => ['Dashboard', 'Cultos'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'cultos' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['lastPage'],
            'search' => $search,
            'success' => Session::flash('culto_success'),
            'csrfToken' => Csrf::token(),
        ], 'dashboard');
    }

    public function create(): void
    {
        echo $this->view('dashboard.cultos.form', [
            'pageTitle' => 'Novo culto - KADOSYS Igrejas',
            'activeMenu' => 'cultos',
            'breadcrumb' => ['Dashboard', 'Cultos', 'Novo'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'culto' => null,
            'presentes' => [],
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('culto_old') ?? [],
            'errors' => Session::flash('culto_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function store(): void
    {
        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('culto_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect('/dashboard/cultos/novo');
        }

        $data = $this->request->only(['titulo', 'data', 'hora', 'local', 'descricao', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('culto_errors', $errors);
            Session::flash('culto_old', $data);
            $this->redirect('/dashboard/cultos/novo');
        }

        Culto::create($data);
        $criados = 1 + $this->criarOcorrenciasRecorrentes($data);

        Session::flash(
            'culto_success',
            $criados > 1 ? "Culto cadastrado - {$criados} datas geradas (recorrência semanal)." : 'Culto cadastrado com sucesso.'
        );
        $this->redirect('/dashboard/cultos');
    }

    /**
     * Quando "Repetir toda semana" e marcado no cadastro, gera cultos
     * independentes (mesmo titulo/horario/local) toda semana a partir
     * da data informada ate a data limite escolhida - cada ocorrencia
     * fica com sua propria linha (e sua propria lista de frequencia),
     * sem vinculo entre elas.
     *
     * @param array<string, mixed> $data
     */
    private function criarOcorrenciasRecorrentes(array $data): int
    {
        $recorrencia = (string) $this->request->input('recorrencia', 'nenhuma');
        $repetirAte = trim((string) $this->request->input('repetir_ate', ''));

        if ($recorrencia !== 'semanal' || $repetirAte === '' || \DateTime::createFromFormat('Y-m-d', $repetirAte) === false) {
            return 0;
        }

        $dataLimite = new \DateTime($repetirAte);
        $proxima = (new \DateTime((string) $data['data']))->modify('+1 week');
        $geradas = 0;

        while ($proxima <= $dataLimite && $geradas < self::MAX_OCORRENCIAS_RECORRENCIA) {
            Culto::create([
                'titulo' => $data['titulo'],
                'data' => $proxima->format('Y-m-d'),
                'hora' => $data['hora'] ?? null,
                'local' => $data['local'] ?? null,
                'descricao' => $data['descricao'] ?? null,
                'status' => 'agendado',
            ]);
            $proxima->modify('+1 week');
            $geradas++;
        }

        return $geradas;
    }

    public function edit(string $id): void
    {
        $culto = Culto::find((int) $id);

        if (!$culto) {
            $this->renderNotFound();

            return;
        }

        echo $this->view('dashboard.cultos.form', [
            'pageTitle' => 'Editar ' . $culto->titulo . ' - KADOSYS Igrejas',
            'activeMenu' => 'cultos',
            'breadcrumb' => ['Dashboard', 'Cultos', $culto->titulo],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'culto' => $culto,
            'presentes' => Culto::presentes($culto->id),
            'membrosAtivos' => Membro::allActive(),
            'old' => Session::flash('culto_old') ?? [],
            'errors' => Session::flash('culto_errors') ?? [],
            'csrf' => Csrf::field(),
        ], 'dashboard');
    }

    public function update(string $id): void
    {
        if (!Culto::find((int) $id)) {
            $this->renderNotFound();

            return;
        }

        if (!Csrf::verify($this->request->input('_csrf_token'))) {
            Session::flash('culto_errors', ['Sessão expirada. Tente novamente.']);
            $this->redirect("/dashboard/cultos/{$id}/editar");
        }

        $data = $this->request->only(['titulo', 'data', 'hora', 'local', 'descricao', 'status']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            Session::flash('culto_errors', $errors);
            Session::flash('culto_old', $data);
            $this->redirect("/dashboard/cultos/{$id}/editar");
        }

        Culto::update((int) $id, $data);

        Session::flash('culto_success', 'Culto atualizado com sucesso.');
        $this->redirect('/dashboard/cultos');
    }

    public function destroy(string $id): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Culto::delete((int) $id);
            Session::flash('culto_success', 'Culto removido com sucesso.');
        }

        $this->redirect('/dashboard/cultos');
    }

    public function addPresenca(string $id): void
    {
        $membroId = (int) $this->request->input('membro_id', 0);

        if (Csrf::verify($this->request->input('_csrf_token')) && $membroId > 0) {
            Culto::addPresenca((int) $id, $membroId);
            Session::flash('culto_success', 'Presença registrada.');
        }

        $this->redirect("/dashboard/cultos/{$id}/editar");
    }

    public function removePresenca(string $id, string $membroId): void
    {
        if (Csrf::verify($this->request->input('_csrf_token'))) {
            Culto::removePresenca((int) $id, (int) $membroId);
            Session::flash('culto_success', 'Presença removida.');
        }

        $this->redirect("/dashboard/cultos/{$id}/editar");
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['titulo'] ?? '')) === '') {
            $errors[] = 'Informe o título do culto.';
        }

        $dataCulto = trim((string) ($data['data'] ?? ''));
        if ($dataCulto === '' || \DateTime::createFromFormat('Y-m-d', $dataCulto) === false) {
            $errors[] = 'Informe uma data válida.';
        }

        return $errors;
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        echo $this->view('errors.404', [
            'pageTitle' => 'Página não encontrada - KADOSYS Igrejas',
            'activeMenu' => 'cultos',
            'breadcrumb' => ['Dashboard', 'Cultos', 'Não encontrado'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
        ], 'dashboard');
    }
}
