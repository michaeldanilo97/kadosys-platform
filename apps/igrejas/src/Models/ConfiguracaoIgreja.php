<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Configuracoes gerais da igreja (linha unica, id = 1).
 * Por enquanto guarda apenas a logo usada no fadeout da projecao de
 * video; podera ser expandida conforme o modulo Configuracoes crescer.
 */
final class ConfiguracaoIgreja
{
    public function __construct(
        public readonly ?string $nomeIgreja,
        public readonly ?string $logoPath,
        public readonly string $plano = Plano::ESSENCIAL,
        public readonly bool $cadastroMembrosHabilitado = false,
        public readonly ?string $cep = null,
        public readonly ?string $endereco = null,
        public readonly ?string $numero = null,
        public readonly ?string $cidade = null,
        public readonly ?string $estado = null,
        public readonly ?string $pixChave = null,
        public readonly ?string $pixNomeBeneficiario = null,
    ) {
    }

    public static function atual(): self
    {
        $stmt = Database::connection()->prepare(
            'SELECT nome_igreja, logo_path, plano, cadastro_membros_habilitado, cep, endereco, numero, cidade, estado,
                    pix_chave, pix_nome_beneficiario
             FROM configuracoes_igreja WHERE id = 1 LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return new self(
            nomeIgreja: $row['nome_igreja'] ?? null,
            logoPath: $row['logo_path'] ?? null,
            plano: $row['plano'] ?? Plano::ESSENCIAL,
            cadastroMembrosHabilitado: (bool) ($row['cadastro_membros_habilitado'] ?? false),
            cep: $row['cep'] ?? null,
            endereco: $row['endereco'] ?? null,
            numero: $row['numero'] ?? null,
            cidade: $row['cidade'] ?? null,
            estado: $row['estado'] ?? null,
            pixChave: $row['pix_chave'] ?? null,
            pixNomeBeneficiario: $row['pix_nome_beneficiario'] ?? null,
        );
    }

    /**
     * Doacao via Pix estatico so fica disponivel depois que a igreja
     * cadastra a propria chave (ver DoacaoController) - sem isso nao
     * ha pra quem mandar o dinheiro.
     */
    public function doacaoPixHabilitada(): bool
    {
        return $this->pixChave !== null && trim($this->pixChave) !== '';
    }

    public static function atualizarChavePix(string $chave, string $nomeBeneficiario): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, pix_chave, pix_nome_beneficiario)
             VALUES (1, :chave, :nome)
             ON DUPLICATE KEY UPDATE pix_chave = VALUES(pix_chave), pix_nome_beneficiario = VALUES(pix_nome_beneficiario)'
        );
        $stmt->execute(['chave' => $chave, 'nome' => $nomeBeneficiario]);
    }

    public static function removerChavePix(): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE configuracoes_igreja SET pix_chave = NULL, pix_nome_beneficiario = NULL WHERE id = 1'
        );
        $stmt->execute();
    }

    public static function atualizarPlano(string $plano): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, plano)
             VALUES (1, :plano)
             ON DUPLICATE KEY UPDATE plano = VALUES(plano)'
        );
        $stmt->execute(['plano' => $plano]);
    }

    public static function atualizarCadastroMembros(bool $habilitado): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, cadastro_membros_habilitado)
             VALUES (1, :habilitado)
             ON DUPLICATE KEY UPDATE cadastro_membros_habilitado = VALUES(cadastro_membros_habilitado)'
        );
        $stmt->execute(['habilitado' => $habilitado ? 1 : 0]);
    }

    public static function atualizarLogo(string $logoPath): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, logo_path)
             VALUES (1, :logo_path)
             ON DUPLICATE KEY UPDATE logo_path = VALUES(logo_path)'
        );
        $stmt->execute(['logo_path' => $logoPath]);
    }

    public static function removerLogo(): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE configuracoes_igreja SET logo_path = NULL WHERE id = 1'
        );
        $stmt->execute();
    }
}
