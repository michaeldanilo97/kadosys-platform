<?php

declare(strict_types=1);

namespace Igrejas\Models;

use DateTimeImmutable;
use Igrejas\Core\Database;
use PDO;

/**
 * Model de KidsCrianca (modulo KADOSYS Kids): perfil de cada crianca
 * cadastrada no ministerio infantil - dados basicos, turma, responsavel
 * (Membro vinculado ou nome/telefone avulso), informacoes de seguranca
 * (autorizados a retirar, alergias/observacoes medicas) e as colunas de
 * gamificacao (xp/moedas/sequencia), incrementadas a cada check-in (ver
 * KidsCheckin::registrar()), alem dos itens de Avatar equipados
 * (chapeu/acessorio/fundo/titulo - ver KidsAvatar pro catalogo e formula
 * de nivel a partir do xp).
 */
final class KidsCrianca
{
    /** Tentativas de PIN erradas seguidas antes de bloquear temporariamente. */
    private const MAX_TENTATIVAS_PIN = 5;
    private const BLOQUEIO_PIN_MINUTOS = 15;

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $fotoPath,
        public readonly ?string $dataNascimento,
        public readonly ?string $genero,
        public readonly ?int $turmaId,
        public readonly ?string $turmaNome,
        public readonly ?int $responsavelMembroId,
        public readonly ?string $responsavelMembroNome,
        public readonly ?string $responsavelNome,
        public readonly ?string $responsavelTelefone,
        public readonly ?string $autorizadosRetirada,
        public readonly ?string $alergias,
        public readonly ?string $observacoesMedicas,
        public readonly ?string $observacoes,
        public readonly string $status,
        public readonly int $xp,
        public readonly int $moedas,
        public readonly int $sequenciaDias,
        public readonly int $sequenciaAppDias,
        public readonly ?string $ultimaVisitaAppEm,
        public readonly ?string $pinHash,
        public readonly ?string $pinDefinidoEm,
        public readonly int $pinTentativasInvalidas,
        public readonly ?string $pinBloqueadoAte,
        public readonly ?string $avatarChapeu,
        public readonly ?string $avatarAcessorio,
        public readonly ?string $avatarFundo,
        public readonly ?string $avatarTitulo,
        public readonly ?string $avatarPele,
        public readonly ?string $avatarRoupa,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];

        if ($search !== '') {
            $where = 'WHERE c.nome LIKE :search_nome OR t.nome LIKE :search_turma OR c.responsavel_nome LIKE :search_resp';
            $params['search_nome'] = '%' . $search . '%';
            $params['search_turma'] = '%' . $search . '%';
            $params['search_resp'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM kids_criancas c LEFT JOIN kids_turmas t ON t.id = c.turma_id {$where}"
        );
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             {$where}
             ORDER BY c.nome ASC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Criancas ativas, usadas na tela de Check-in (busca rapida por
     * nome/turma no proprio navegador, sem paginacao no servidor).
     *
     * @return array<int, self>
     */
    public static function allActive(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             WHERE c.status = 'ativo'
             ORDER BY c.nome ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Criancas ativas que ja tem PIN configurado - usado no seletor de
     * perfil da tela publica de login infantil (ver KidsLoginController).
     *
     * @return array<int, self>
     */
    public static function ativasComPin(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             WHERE c.status = 'ativo' AND c.pin_hash IS NOT NULL
             ORDER BY c.nome ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Filhos vinculados a um responsavel (Membro) especifico - usado
     * na area de autoatendimento "Meus filhos" (ver
     * KidsResponsavelController), onde o proprio responsavel gerencia
     * o PIN de acesso das criancas dele, sem depender da equipe.
     *
     * @return array<int, self>
     */
    public static function doResponsavel(int $membroId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             WHERE c.responsavel_membro_id = :membro_id AND c.status = 'ativo'
             ORDER BY c.nome ASC"
        );
        $stmt->execute(['membro_id' => $membroId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO kids_criancas
                (nome, data_nascimento, genero, turma_id, responsavel_membro_id, responsavel_nome,
                 responsavel_telefone, autorizados_retirada, alergias, observacoes_medicas, observacoes,
                 status, created_at)
             VALUES
                (:nome, :data_nascimento, :genero, :turma_id, :responsavel_membro_id, :responsavel_nome,
                 :responsavel_telefone, :autorizados_retirada, :alergias, :observacoes_medicas, :observacoes,
                 :status, NOW())'
        );
        $stmt->execute(self::bindings($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET
                nome = :nome, data_nascimento = :data_nascimento, genero = :genero, turma_id = :turma_id,
                responsavel_membro_id = :responsavel_membro_id, responsavel_nome = :responsavel_nome,
                responsavel_telefone = :responsavel_telefone, autorizados_retirada = :autorizados_retirada,
                alergias = :alergias, observacoes_medicas = :observacoes_medicas, observacoes = :observacoes,
                status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function atualizarFoto(int $id, string $fotoPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE kids_criancas SET foto_path = :foto_path WHERE id = :id');
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM kids_criancas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countAtivas(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM kids_criancas WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Concede XP/moedas e atualiza a sequencia de presenca - chamado
     * por KidsCheckin::registrar() a cada check-in.
     */
    public static function concederPontos(int $id, int $xp, int $moedas, int $sequenciaDias): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas
             SET xp = xp + :xp, moedas = moedas + :moedas, sequencia_dias = :sequencia_dias
             WHERE id = :id'
        );
        $stmt->execute(['xp' => $xp, 'moedas' => $moedas, 'sequencia_dias' => $sequenciaDias, 'id' => $id]);

        KidsRankingIgreja::somarXp($xp);
    }

    /**
     * Concede XP/moedas sem mexer na sequencia de presenca - usado ao
     * concluir um conteudo da Biblioteca (ver
     * KidsConteudo::registrarConclusaoPor()), que e independente do
     * check-in do dia.
     */
    public static function adicionarPontos(int $id, int $xp, int $moedas): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET xp = xp + :xp, moedas = moedas + :moedas WHERE id = :id'
        );
        $stmt->execute(['xp' => $xp, 'moedas' => $moedas, 'id' => $id]);

        KidsRankingIgreja::somarXp($xp);
    }

    /**
     * Desconta moedas da crianca, usado no botao "Pedir ajuda" do quiz
     * (ver KidsAppController::quizAjuda()) - condicional no proprio
     * UPDATE (moedas >= :custo) pra nunca deixar o saldo negativo,
     * mesmo em cliques duplicados/concorrentes. Retorna false (sem
     * gastar nada) se o saldo for insuficiente.
     */
    public static function gastarMoedas(int $id, int $custo): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET moedas = moedas - :custo WHERE id = :id AND moedas >= :custo_minimo'
        );
        $stmt->execute(['custo' => $custo, 'id' => $id, 'custo_minimo' => $custo]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Gera um PIN novo de 4 digitos pra crianca fazer login sozinha na
     * Biblioteca (ver KidsLoginController) - so permitido se ja existir
     * um responsavel vinculado (responsavel_membro_id), que funciona
     * como o consentimento minimo antes de liberar esse acesso
     * independente. Retorna o PIN em texto puro (pra equipe entregar
     * ao responsavel uma unica vez) ou null se a crianca nao tiver
     * responsavel vinculado.
     */
    public static function gerarESalvarPin(int $id): ?string
    {
        $crianca = self::find($id);

        if ($crianca === null || $crianca->responsavelMembroId === null) {
            return null;
        }

        $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas
             SET pin_hash = :pin_hash, pin_definido_em = NOW(), pin_tentativas_invalidas = 0, pin_bloqueado_ate = NULL
             WHERE id = :id'
        );
        $stmt->execute(['pin_hash' => password_hash($pin, PASSWORD_DEFAULT), 'id' => $id]);

        return $pin;
    }

    public static function removerPin(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas
             SET pin_hash = NULL, pin_definido_em = NULL, pin_tentativas_invalidas = 0, pin_bloqueado_ate = NULL
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Confere o PIN informado no login infantil (ver
     * KidsLoginController::autenticar()). Bloqueia temporariamente
     * depois de MAX_TENTATIVAS_PIN erros seguidos, ja que um PIN de 4
     * digitos e um espaco de busca pequeno pra tentativa por forca
     * bruta.
     */
    public static function autenticarPorPin(int $criancaId, string $pin): ?self
    {
        $crianca = self::find($criancaId);

        if ($crianca === null || $crianca->status !== 'ativo' || $crianca->pinHash === null) {
            return null;
        }

        if ($crianca->pinBloqueadoAte !== null && new DateTimeImmutable($crianca->pinBloqueadoAte) > new DateTimeImmutable()) {
            return null;
        }

        if (!password_verify($pin, $crianca->pinHash)) {
            self::registrarTentativaInvalida($criancaId, $crianca->pinTentativasInvalidas + 1);

            return null;
        }

        self::resetarTentativasPin($criancaId);

        return $crianca;
    }

    private static function registrarTentativaInvalida(int $id, int $tentativas): void
    {
        $bloqueado = $tentativas >= self::MAX_TENTATIVAS_PIN;

        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas
             SET pin_tentativas_invalidas = :tentativas,
                 pin_bloqueado_ate = ' . ($bloqueado ? 'DATE_ADD(NOW(), INTERVAL ' . self::BLOQUEIO_PIN_MINUTOS . ' MINUTE)' : 'pin_bloqueado_ate') . '
             WHERE id = :id'
        );
        $stmt->execute(['tentativas' => $tentativas, 'id' => $id]);
    }

    private static function resetarTentativasPin(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET pin_tentativas_invalidas = 0, pin_bloqueado_ate = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function temResponsavelVinculado(): bool
    {
        return $this->responsavelMembroId !== null;
    }

    public function temPin(): bool
    {
        return $this->pinHash !== null;
    }

    public function idade(): ?int
    {
        if ($this->dataNascimento === null) {
            return null;
        }

        return (new DateTimeImmutable($this->dataNascimento))->diff(new DateTimeImmutable())->y;
    }

    public function nomeResponsavel(): ?string
    {
        return $this->responsavelMembroNome ?? $this->responsavelNome;
    }

    public function nivel(): int
    {
        return KidsAvatar::nivel($this->xp);
    }

    /**
     * Salva os itens de Avatar equipados pela crianca (ver
     * KidsAppController::avatarSalvar()) - cada slug e conferido contra
     * o catalogo/nivel atual antes de gravar, pra nunca confiar so no
     * que veio do formulario (uma crianca nao pode "trapacear" e equipar
     * um item que ainda nao desbloqueou so editando o POST). Slugs
     * invalidos ou ainda bloqueados viram null (nenhum item equipado
     * naquela categoria) em vez de erro - a tela sempre volta pro estado
     * consistente mais proximo do que foi pedido.
     */
    public static function atualizarAvatar(
        int $id,
        ?string $chapeu,
        ?string $acessorio,
        ?string $fundo,
        ?string $titulo,
        ?string $pele,
        ?string $roupa,
    ): void {
        $crianca = self::find($id);

        if ($crianca === null) {
            return;
        }

        $nivel = $crianca->nivel();
        $comprados = KidsAvatarCompra::compradosPor($id);

        $chapeu = KidsAvatar::itemDesbloqueado(KidsAvatar::catalogoChapeus(), $chapeu, $nivel, $comprados['chapeu']) ? $chapeu : null;
        $acessorio = KidsAvatar::itemDesbloqueado(KidsAvatar::catalogoAcessorios(), $acessorio, $nivel, $comprados['acessorio']) ? $acessorio : null;
        $fundo = KidsAvatar::itemDesbloqueado(KidsAvatar::catalogoFundos(), $fundo, $nivel, $comprados['fundo']) ? $fundo : null;
        $titulo = KidsAvatar::itemDesbloqueado(KidsAvatar::catalogoTitulos(), $titulo, $nivel, $comprados['titulo']) ? $titulo : null;
        $pele = KidsAvatar::itemDesbloqueado(KidsAvatar::catalogoPeles(), $pele, $nivel, []) ? $pele : null;
        $roupa = KidsAvatar::itemDesbloqueado(KidsAvatar::catalogoRoupas(), $roupa, $nivel, $comprados['roupa']) ? $roupa : null;

        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas
             SET avatar_chapeu = :chapeu, avatar_acessorio = :acessorio, avatar_fundo = :fundo, avatar_titulo = :titulo,
                 avatar_pele = :pele, avatar_roupa = :roupa
             WHERE id = :id'
        );
        $stmt->execute([
            'chapeu' => $chapeu,
            'acessorio' => $acessorio,
            'fundo' => $fundo,
            'titulo' => $titulo,
            'pele' => $pele,
            'roupa' => $roupa,
            'id' => $id,
        ]);
    }

    /**
     * Bonus de XP/moedas por marco de sequencia de dias acessando a
     * Biblioteca (ver registrarAcessoApp()) - separado da recompensa de
     * cada conteudo concluido, e concedido so uma vez por marco
     * atingido (a sequencia so bate 3, 7, 14 ou 30 exatamente uma vez
     * antes de virar 4, 8, 15, 31...).
     *
     * @var array<int, array{xp: int, moedas: int}>
     */
    private const MARCOS_SEQUENCIA_APP = [
        3 => ['xp' => 15, 'moedas' => 10],
        7 => ['xp' => 30, 'moedas' => 20],
        14 => ['xp' => 50, 'moedas' => 35],
        30 => ['xp' => 100, 'moedas' => 70],
    ];

    /**
     * Registra que a crianca abriu a Biblioteca hoje e atualiza a
     * sequencia de dias seguidos (separada da sequencia de presenca
     * fisica na igreja - ver concederPontos()) - chamado uma vez por
     * dia, na home do modo crianca (KidsAppController::index()).
     * Retorna null se a visita de hoje ja tinha sido contabilizada
     * (evita inflar a sequencia com varios acessos no mesmo dia), ou os
     * dados pra mostrar um banner (sequencia atual + bonus, se bateu um
     * marco).
     *
     * @return array{sequencia: int, xp: int, moedas: int}|null
     */
    public static function registrarAcessoApp(int $id): ?array
    {
        $crianca = self::find($id);

        if ($crianca === null) {
            return null;
        }

        $hoje = date('Y-m-d');

        if ($crianca->ultimaVisitaAppEm === $hoje) {
            return null;
        }

        $ontem = date('Y-m-d', strtotime('-1 day'));
        $novaSequencia = $crianca->ultimaVisitaAppEm === $ontem ? $crianca->sequenciaAppDias + 1 : 1;

        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET sequencia_app_dias = :sequencia, ultima_visita_app_em = :hoje WHERE id = :id'
        );
        $stmt->execute(['sequencia' => $novaSequencia, 'hoje' => $hoje, 'id' => $id]);

        $marco = self::MARCOS_SEQUENCIA_APP[$novaSequencia] ?? null;

        if ($marco !== null) {
            self::adicionarPontos($id, $marco['xp'], $marco['moedas']);
        }

        return ['sequencia' => $novaSequencia, 'xp' => $marco['xp'] ?? 0, 'moedas' => $marco['moedas'] ?? 0];
    }

    /**
     * Ranking das criancas da propria igreja por XP total - so entre
     * criancas ativas, sempre com a posicao da propria crianca (mesmo
     * fora do top informado) pra ela se ver no ranking mesmo quando nao
     * esta entre as primeiras.
     *
     * @return array{top: array<int, array{id: int, nome: string, xp: int, nivel: int, souEu: bool}>, minhaPosicao: int, totalCriancas: int}
     */
    public static function rankingDaIgreja(int $criancaId, int $limite = 10): array
    {
        $pdo = Database::connection();

        $topStmt = $pdo->prepare(
            'SELECT id, nome, xp FROM kids_criancas WHERE status = "ativo" ORDER BY xp DESC, id ASC LIMIT :limite'
        );
        $topStmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $topStmt->execute();

        $top = array_map(
            static fn (array $row) => [
                'id' => (int) $row['id'],
                'nome' => (string) $row['nome'],
                'xp' => (int) $row['xp'],
                'nivel' => KidsAvatar::nivel((int) $row['xp']),
                'souEu' => (int) $row['id'] === $criancaId,
            ],
            $topStmt->fetchAll()
        );

        $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM kids_criancas WHERE status = "ativo"');
        $totalStmt->execute();
        $totalCriancas = (int) $totalStmt->fetchColumn();

        $minhaXp = 0;
        foreach ($top as $item) {
            if ($item['souEu']) {
                $minhaXp = $item['xp'];
                break;
            }
        }

        if ($minhaXp === 0) {
            $minhaXpStmt = $pdo->prepare('SELECT xp FROM kids_criancas WHERE id = :id');
            $minhaXpStmt->execute(['id' => $criancaId]);
            $minhaXp = (int) $minhaXpStmt->fetchColumn();
        }

        $posicaoStmt = $pdo->prepare(
            'SELECT COUNT(*) + 1 FROM kids_criancas WHERE status = "ativo" AND xp > :minha_xp'
        );
        $posicaoStmt->execute(['minha_xp' => $minhaXp]);

        return [
            'top' => $top,
            'minhaPosicao' => (int) $posicaoStmt->fetchColumn(),
            'totalCriancas' => $totalCriancas,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $turmaId = trim((string) ($data['turma_id'] ?? ''));
        $responsavelMembroId = trim((string) ($data['responsavel_membro_id'] ?? ''));
        $genero = trim((string) ($data['genero'] ?? ''));

        return [
            'nome' => trim((string) $data['nome']),
            'data_nascimento' => self::nullable($data['data_nascimento'] ?? null),
            'genero' => in_array($genero, ['masculino', 'feminino'], true) ? $genero : null,
            'turma_id' => $turmaId === '' ? null : (int) $turmaId,
            'responsavel_membro_id' => $responsavelMembroId === '' ? null : (int) $responsavelMembroId,
            'responsavel_nome' => self::nullable($data['responsavel_nome'] ?? null),
            'responsavel_telefone' => self::nullable($data['responsavel_telefone'] ?? null),
            'autorizados_retirada' => self::nullable($data['autorizados_retirada'] ?? null),
            'alergias' => self::nullable($data['alergias'] ?? null),
            'observacoes_medicas' => self::nullable($data['observacoes_medicas'] ?? null),
            'observacoes' => self::nullable($data['observacoes'] ?? null),
            'status' => in_array($data['status'] ?? null, ['ativo', 'inativo'], true) ? $data['status'] : 'ativo',
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            fotoPath: $row['foto_path'] ?? null,
            dataNascimento: $row['data_nascimento'],
            genero: $row['genero'],
            turmaId: $row['turma_id'] !== null ? (int) $row['turma_id'] : null,
            turmaNome: $row['turma_nome'] ?? null,
            responsavelMembroId: $row['responsavel_membro_id'] !== null ? (int) $row['responsavel_membro_id'] : null,
            responsavelMembroNome: $row['responsavel_membro_nome'] ?? null,
            responsavelNome: $row['responsavel_nome'],
            responsavelTelefone: $row['responsavel_telefone'],
            autorizadosRetirada: $row['autorizados_retirada'],
            alergias: $row['alergias'],
            observacoesMedicas: $row['observacoes_medicas'],
            observacoes: $row['observacoes'],
            status: (string) $row['status'],
            xp: (int) $row['xp'],
            moedas: (int) $row['moedas'],
            sequenciaDias: (int) $row['sequencia_dias'],
            sequenciaAppDias: (int) ($row['sequencia_app_dias'] ?? 0),
            ultimaVisitaAppEm: $row['ultima_visita_app_em'] ?? null,
            pinHash: $row['pin_hash'] ?? null,
            pinDefinidoEm: $row['pin_definido_em'] ?? null,
            pinTentativasInvalidas: (int) ($row['pin_tentativas_invalidas'] ?? 0),
            pinBloqueadoAte: $row['pin_bloqueado_ate'] ?? null,
            avatarChapeu: $row['avatar_chapeu'] ?? null,
            avatarAcessorio: $row['avatar_acessorio'] ?? null,
            avatarFundo: $row['avatar_fundo'] ?? null,
            avatarTitulo: $row['avatar_titulo'] ?? null,
            avatarPele: $row['avatar_pele'] ?? null,
            avatarRoupa: $row['avatar_roupa'] ?? null,
            createdAt: (string) $row['created_at'],
        );
    }
}
