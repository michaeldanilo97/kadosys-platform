<?php

declare(strict_types=1);

namespace Barbearias\Core;

use Barbearias\Models\Cliente;

/**
 * Autenticacao da area do cliente (/minha-conta/{slug}) - sessao
 * SEPARADA da Auth da equipe (Barbearias\Core\Auth), com chave propria
 * no mesmo $_SESSION, pra um admin logado no painel e um cliente
 * logado na propria area nao se confundirem no mesmo navegador.
 *
 * So guarda o id do cliente - a checagem de "esse cliente pertence a
 * ESTA barbearia" e feita a cada chamada de user(), passando o
 * barbearia_id de novo (Cliente::find ja filtra por ele). Isso cobre
 * sozinho o caso de alguem logado como cliente da Barbearia A abrir a
 * area da Barbearia B no mesmo navegador - aparece deslogado pra B,
 * como deveria.
 */
final class ClienteAuth
{
    private const SESSION_CLIENTE_ID = '_cliente_auth_id';

    public function __construct(private readonly array $config)
    {
    }

    public function attempt(int $barbeariaId, string $telefone, string $senha): bool
    {
        $cliente = Cliente::buscarPorTelefone($barbeariaId, $telefone);

        if ($cliente === null || !$cliente->temSenha() || !$cliente->verifyPassword($senha)) {
            return false;
        }

        $this->login($cliente);

        return true;
    }

    public function login(Cliente $cliente): void
    {
        Session::regenerate();
        Session::set(self::SESSION_CLIENTE_ID, $cliente->id);
    }

    public function logout(): void
    {
        Session::remove(self::SESSION_CLIENTE_ID);
    }

    public function check(int $barbeariaId): bool
    {
        return $this->user($barbeariaId) !== null;
    }

    public function user(int $barbeariaId): ?Cliente
    {
        $clienteId = Session::get(self::SESSION_CLIENTE_ID);

        return $clienteId !== null ? Cliente::find((int) $clienteId, $barbeariaId) : null;
    }
}
