<?php

declare(strict_types=1);

namespace Kadosys\Core\Core;

use Closure;
use RuntimeException;

/**
 * Container
 *
 * Container de injecao de dependencias simples, responsavel por
 * resolver classes, bindings e singletons utilizados em toda a
 * plataforma (Service Providers, Controllers, Services, etc).
 */
final class Container
{
    /**
     * Instancia unica do container (o container em si e singleton).
     */
    private static ?Container $instance = null;

    /**
     * Bindings registrados: abstract => factory closure.
     *
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * Instancias singleton ja resolvidas.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registra um binding comum (nova instancia a cada resolve()).
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Registra um binding singleton (mesma instancia sempre).
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        unset($this->instances[$abstract]);
        $this->bindings[$abstract . '::singleton'] = $factory;
    }

    /**
     * Registra uma instancia ja construida diretamente no container.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve uma dependencia pelo identificador abstrato.
     * Caso nao exista binding, tenta autoresolver via reflection.
     */
    public function resolve(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract . '::singleton'])) {
            $instance = ($this->bindings[$abstract . '::singleton'])($this);
            $this->instances[$abstract] = $instance;
            return $instance;
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        return $this->autoResolve($abstract);
    }

    /**
     * Tenta instanciar a classe automaticamente via Reflection,
     * resolvendo dependencias do construtor recursivamente.
     */
    private function autoResolve(string $abstract): mixed
    {
        if (!class_exists($abstract)) {
            throw new RuntimeException("Nao foi possivel resolver a dependencia: {$abstract}");
        }

        $reflector = new \ReflectionClass($abstract);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Classe nao instanciavel: {$abstract}");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $abstract();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->resolve($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            $dependencies[] = null;
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Verifica se um identificador possui binding ou instancia registrada.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
