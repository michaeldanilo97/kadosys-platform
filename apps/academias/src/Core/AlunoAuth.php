<?php

declare(strict_types=1);

namespace Academias\Core;

use Academias\Models\Aluno;

/**
 * Autenticacao da area do aluno (/minha-conta/{slug}) - sessao
 * SEPARADA da Auth da equipe (Academias\Core\Auth), com chave propria
 * no mesmo $_SESSION, pra um admin logado no painel e um aluno logado
 * na propria area nao se confundirem no mesmo navegador.
 *
 * So guarda o id do aluno - a checagem de "esse aluno pertence a ESTA
 * academia" e feita a cada chamada de user(), passando o academia_id de
 * novo (Aluno::find ja filtra por ele). Isso cobre sozinho o caso de
 * alguem logado como aluno da Academia A abrir a area da Academia B no
 * mesmo navegador - aparece deslogado pra B, como deveria.
 */
final class AlunoAuth
{
    private const SESSION_ALUNO_ID = '_aluno_auth_id';

    public function __construct(private readonly array $config)
    {
    }

    public function attempt(int $academiaId, string $identificador, string $senha): bool
    {
        $aluno = Aluno::buscarPorIdentificador($academiaId, $identificador);

        if ($aluno === null || !$aluno->temSenha() || !$aluno->verifyPassword($senha)) {
            return false;
        }

        $this->login($aluno);

        return true;
    }

    public function login(Aluno $aluno): void
    {
        Session::regenerate();
        Session::set(self::SESSION_ALUNO_ID, $aluno->id);
    }

    public function logout(): void
    {
        Session::remove(self::SESSION_ALUNO_ID);
    }

    public function check(int $academiaId): bool
    {
        return $this->user($academiaId) !== null;
    }

    public function user(int $academiaId): ?Aluno
    {
        $alunoId = Session::get(self::SESSION_ALUNO_ID);

        return $alunoId !== null ? Aluno::find((int) $alunoId, $academiaId) : null;
    }
}
