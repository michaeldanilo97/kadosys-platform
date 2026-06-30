<?php

declare(strict_types=1);

namespace Kadosys\Core\Core;

use Kadosys\Core\Exceptions\ExceptionHandler;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Routing\Router;

/**
 * Application
 *
 * Kernel principal da Kadosys Platform. Responsavel por:
 * - Carregar ambiente (.env) e configuracoes (config/).
 * - Inicializar o container de servicos.
 * - Registrar Service Providers do Core e do aplicativo ativo.
 * - Resolver a requisicao HTTP atual atraves do Router.
 *
 * Existe apenas UMA instancia desta classe por requisicao,
 * compartilhada entre todos os aplicativos da plataforma.
 */
final class Application
{
    private static ?Application $instance = null;

    private Container $container;

    private Router $router;

    /**
     * Caminho absoluto da raiz da plataforma (public_html/).
     */
    private string $basePath;

    /**
     * Identificador do aplicativo ativo (ex: igrejas, crm).
     */
    private string $activeApp;

    /**
     * Lista de Service Providers do Core, registrados sempre.
     *
     * @var array<int, class-string>
     */
    private array $coreProviders = [
        \Kadosys\Core\Providers\AppServiceProvider::class,
        \Kadosys\Core\Providers\RouteServiceProvider::class,
        \Kadosys\Core\Providers\DatabaseServiceProvider::class,
        \Kadosys\Core\Providers\ViewServiceProvider::class,
    ];

    private function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->container = Container::getInstance();
        $this->container->instance(Application::class, $this);
        $this->container->instance(self::class, $this);
    }

    /**
     * Cria (ou retorna) a instancia unica da aplicacao.
     */
    public static function bootstrap(string $basePath): self
    {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
            self::$instance->initialize();
        }

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('A aplicacao ainda nao foi inicializada. Chame Application::bootstrap() primeiro.');
        }

        return self::$instance;
    }

    /**
     * Inicializa ambiente, configuracoes, providers e router.
     */
    private function initialize(): void
    {
        Env::load($this->basePath . '/.env');
        Config::load($this->basePath . '/config');

        $this->activeApp = (string) Env::get('APP_ACTIVE', 'igrejas');

        $this->router = new Router(
            (string) Config::get('app.base_path', '')
        );
        $this->container->instance(Router::class, $this->router);

        $this->registerCoreProviders();
        $this->loadAppConfig();
        $this->loadAppRoutes();
    }

    /**
     * Registra todos os Service Providers do Core.
     */
    private function registerCoreProviders(): void
    {
        foreach ($this->coreProviders as $providerClass) {
            /** @var \Kadosys\Core\Providers\ServiceProvider $provider */
            $provider = new $providerClass($this->container);
            $provider->register();
            $provider->boot();
        }
    }

    /**
     * Carrega o arquivo de configuracao especifico do aplicativo ativo,
     * caso exista em apps/{app}/Config/config.php.
     */
    private function loadAppConfig(): void
    {
        $configFile = $this->appPath() . '/Config/config.php';

        if (is_file($configFile)) {
            $appConfig = require $configFile;

            if (is_array($appConfig)) {
                Config::merge($this->activeApp, $appConfig);
            }
        }
    }

    /**
     * Carrega o arquivo de rotas do aplicativo ativo.
     */
    private function loadAppRoutes(): void
    {
        $routesFile = $this->appPath() . '/Routes/web.php';

        if (is_file($routesFile)) {
            $router = $this->router;
            require $routesFile;
        }
    }

    /**
     * Trata a requisicao HTTP atual e envia a resposta correspondente.
     */
    public function handle(): void
    {
        $request = Request::capture((string) Config::get('app.base_path', ''));
        $this->container->instance(Request::class, $request);

        try {
            $response = $this->router->dispatch($request);
        } catch (\Throwable $exception) {
            $response = ExceptionHandler::handle($exception);
        }

        $response->send();
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public function activeApp(): string
    {
        return $this->activeApp;
    }

    /**
     * Caminho absoluto da pasta do aplicativo ativo (apps/{app}).
     */
    public function appPath(string $path = ''): string
    {
        $base = $this->basePath . '/apps/' . $this->activeApp;

        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}
