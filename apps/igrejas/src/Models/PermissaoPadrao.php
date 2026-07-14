<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Perfil padrao de permissoes: o conjunto de modulos/niveis
 * (visualizar ou editar) que a igreja configura em Configuracoes >
 * Permissoes padrao pra todo novo acesso de usuario ja nascer com -
 * ver User::aplicarPerfilPadrao(), usado sempre que uma conta
 * 'usuario' e criada (cadastro combinado de membro, autocadastro
 * publico ou cadastro manual em Usuarios).
 *
 * Modulo ausente daqui = sem acesso por padrao (o admin libera na mao
 * depois, em Permissoes, se precisar) - mesma logica de allow-list de
 * user_modulos, só que compartilhada por toda a igreja.
 */
final class PermissaoPadrao
{
    /**
     * Perfil de fabrica usado quando a igreja ainda nao personalizou
     * nada (equivalente ao INSERT inicial da migracao 045) - mantido
     * aqui tambem pra permitir "restaurar padrao" na UI sem depender
     * de reconsultar o banco.
     *
     * @var array<string, string>
     */
    public const PADRAO_DE_FABRICA = [
        'agenda' => User::NIVEL_VISUALIZAR,
        'equipe' => User::NIVEL_VISUALIZAR,
        'cultos' => User::NIVEL_VISUALIZAR,
        'ministerios' => User::NIVEL_VISUALIZAR,
        'grupos' => User::NIVEL_VISUALIZAR,
        'membros' => User::NIVEL_VISUALIZAR,
        'playbacks' => User::NIVEL_VISUALIZAR,
    ];

    /**
     * @return array<string, string> slug => nivel
     */
    public static function todas(): array
    {
        $stmt = Database::connection()->prepare('SELECT modulo_slug, nivel FROM permissoes_padrao');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Substitui de uma vez todo o perfil padrao - modulo que nao
     * estiver em $modulosComNivel fica sem acesso por padrao.
     *
     * @param array<string, string> $modulosComNivel slug => nivel
     */
    public static function definir(array $modulosComNivel): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $pdo->exec('DELETE FROM permissoes_padrao');

        $insert = $pdo->prepare(
            'INSERT INTO permissoes_padrao (modulo_slug, nivel) VALUES (:modulo_slug, :nivel)'
        );
        foreach ($modulosComNivel as $slug => $nivel) {
            $nivel = in_array($nivel, [User::NIVEL_VISUALIZAR, User::NIVEL_EDITAR], true) ? $nivel : User::NIVEL_VISUALIZAR;
            $insert->execute(['modulo_slug' => $slug, 'nivel' => $nivel]);
        }

        $pdo->commit();
    }
}
