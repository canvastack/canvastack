<?php

namespace Canvastack\Canvastack\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The application container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Load test helper functions FIRST before Laravel helpers
        require_once __DIR__ . '/Support/helpers.php';

        // Create a custom container with storagePath method
        $this->app = new class () extends Container {
            public function storagePath($path = '')
            {
                $basePath = $this->make('path.storage');

                return $path ? $basePath . '/' . $path : $basePath;
            }

            public function basePath($path = '')
            {
                $basePath = __DIR__ . '/..';

                return $path ? $basePath . '/' . $path : $basePath;
            }

            public function runningInConsole()
            {
                return true; // Always return true for tests
            }

            public function runningUnitTests()
            {
                return true; // Always return true for tests
            }
        };

        // Add storagePath method via macro (if available) or extend
        $this->app->bind('path.storage', function () {
            return __DIR__ . '/../storage';
        });

        $this->app->bind('path.resources', function () {
            return __DIR__ . '/../resources';
        });

        Container::setInstance($this->app);

        // Add storagePath method to container
        $this->app->bind('path.storage', function () {
            return __DIR__ . '/../storage';
        });

        // Setup config repository
        $config = new ConfigRepository($this->getDefaultConfig());
        $this->app->instance('config', $config);

        // Setup cache (simple mock for testing with tag support)
        $this->app->singleton('cache', function ($app) {
            // Shared storage for all cache instances
            $sharedStorage = new class () {
                public array $data = [];
            };

            // Create a simple cache mock that supports tags
            return new class ($sharedStorage) {
                protected $sharedStorage;

                public function __construct($sharedStorage)
                {
                    $this->sharedStorage = $sharedStorage;
                }

                public function tags($names)
                {
                    $tags = is_array($names) ? $names : func_get_args();
                    $sharedStorage = $this->sharedStorage;

                    return new class ($tags, $sharedStorage) {
                        protected array $tags;

                        protected $sharedStorage;

                        public function __construct(array $tags, $sharedStorage)
                        {
                            $this->tags = $tags;
                            $this->sharedStorage = $sharedStorage;
                        }

                        protected function getTagKey($key)
                        {
                            return implode(':', $this->tags) . ':' . $key;
                        }

                        public function get($key, $default = null)
                        {
                            $tagKey = $this->getTagKey($key);

                            return $this->sharedStorage->data[$tagKey] ?? $default;
                        }

                        public function put($key, $value, $seconds)
                        {
                            $tagKey = $this->getTagKey($key);
                            $this->sharedStorage->data[$tagKey] = $value;

                            return true;
                        }

                        public function has($key)
                        {
                            $tagKey = $this->getTagKey($key);

                            return isset($this->sharedStorage->data[$tagKey]);
                        }

                        public function forget($key)
                        {
                            $tagKey = $this->getTagKey($key);
                            unset($this->sharedStorage->data[$tagKey]);

                            return true;
                        }

                        public function flush()
                        {
                            // Remove all keys that contain ANY of these tags
                            $keysToRemove = [];
                            foreach ($this->tags as $tag) {
                                $prefix = $tag . ':';
                                foreach (array_keys($this->sharedStorage->data) as $key) {
                                    // Check if key contains this tag
                                    if (str_contains($key, $prefix) || str_starts_with($key, $prefix)) {
                                        $keysToRemove[] = $key;
                                    }
                                }
                            }

                            foreach (array_unique($keysToRemove) as $key) {
                                unset($this->sharedStorage->data[$key]);
                            }

                            return true;
                        }

                        public function remember($key, $ttl, $callback)
                        {
                            if ($this->has($key)) {
                                return $this->get($key);
                            }

                            $value = $callback();
                            $this->put($key, $value, $ttl);

                            return $value;
                        }
                    };
                }

                public function get($key, $default = null)
                {
                    return $this->sharedStorage->data[$key] ?? $default;
                }

                public function put($key, $value, $seconds)
                {
                    $this->sharedStorage->data[$key] = $value;

                    return true;
                }

                public function has($key)
                {
                    return isset($this->sharedStorage->data[$key]);
                }

                public function forget($key)
                {
                    unset($this->sharedStorage->data[$key]);

                    return true;
                }

                public function flush()
                {
                    $this->sharedStorage->data = [];

                    return true;
                }

                public function remember($key, $ttl, $callback)
                {
                    if ($this->has($key)) {
                        return $this->get($key);
                    }

                    $value = $callback();
                    $this->put($key, $value, $ttl);

                    return $value;
                }
            };
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app->make('cache');
        });

        // Setup translator for trans() helper and locale management
        $this->app->singleton('translator', function ($app) {
            $loader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem(), __DIR__ . '/../resources/lang');
            $translator = new \Illuminate\Translation\Translator($loader, 'en');
            $translator->setFallback('en');

            // Add canvastack namespace
            $loader->addNamespace('canvastack', __DIR__ . '/../resources/lang');

            return $translator;
        });

        // Setup request for helper functions
        $this->app->singleton('request', function () {
            return new \Illuminate\Http\Request();
        });

        // Setup session for LocaleManager
        $this->app->singleton('session', function () {
            return new \Illuminate\Session\Store('array', new \Illuminate\Session\ArraySessionHandler(60));
        });

        // Setup events dispatcher
        $this->app->singleton('events', function () {
            return new \Illuminate\Events\Dispatcher();
        });

        // Setup filesystem for File facade
        $this->app->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem();
        });

        // Setup log for Log facade
        $this->app->singleton('log', function () {
            $logger = new \Monolog\Logger('testing');
            $logger->pushHandler(new \Monolog\Handler\NullHandler());

            return new \Illuminate\Log\Logger($logger);
        });

        // Setup encrypter for QueryEncryption
        $this->app->singleton('encrypter', function () {
            // Use a test key (32 bytes for AES-256)
            $key = str_repeat('a', 32);

            return new \Illuminate\Encryption\Encrypter($key, 'AES-256-CBC');
        });

        // Also bind the contract
        $this->app->singleton(\Illuminate\Contracts\Encryption\Encrypter::class, function ($app) {
            return $app->make('encrypter');
        });

        // Setup validator for FileValidator
        $this->app->singleton('validator', function ($app) {
            $translator = $app->make('translator');

            return new \Illuminate\Validation\Factory($translator, $app);
        });

        // Also bind the contract
        $this->app->singleton(\Illuminate\Contracts\Validation\Factory::class, function ($app) {
            return $app->make('validator');
        });

        // Setup hash for password hashing
        $this->app->singleton('hash', function () {
            return new \Illuminate\Hashing\BcryptHasher();
        });

        // Also bind the contract
        $this->app->singleton(\Illuminate\Contracts\Hashing\Hasher::class, function ($app) {
            return $app->make('hash');
        });

        // Setup router for Route facade
        $this->app->singleton('router', function ($app) {
            $router = new \Illuminate\Routing\Router(
                $app->make('events'),
                $app
            );

            return $router;
        });

        // Also bind the contract
        $this->app->singleton(\Illuminate\Contracts\Routing\Registrar::class, function ($app) {
            return $app->make('router');
        });

        // Setup URL generator for asset() helper (lazy - always gets latest routes)
        $this->app->singleton('url', function ($app) {
            // Create a wrapper that always gets latest routes from router
            return new class ($app) extends \Illuminate\Routing\UrlGenerator {
                private $app;

                public function __construct($app)
                {
                    $this->app = $app;
                    $router = $app->make('router');
                    $routes = $router->getRoutes();
                    $request = $app->make('request');

                    // Set base URL for testing
                    $request->server->set('HTTP_HOST', 'localhost');
                    $request->server->set('SERVER_NAME', 'localhost');
                    $request->server->set('SERVER_PORT', '80');
                    $request->server->set('REQUEST_URI', '/');

                    parent::__construct($routes, $request, null);
                    $this->forceRootUrl('http://localhost');
                }

                // Override to always get fresh routes
                public function to($path, $extra = [], $secure = null)
                {
                    // Refresh routes from router
                    $router = $this->app->make('router');
                    $this->routes = $router->getRoutes();

                    return parent::to($path, $extra, $secure);
                }

                public function route($name, $parameters = [], $absolute = true)
                {
                    // Refresh routes from router
                    $router = $this->app->make('router');
                    $this->routes = $router->getRoutes();

                    return parent::route($name, $parameters, $absolute);
                }
            };
        });

        // Also bind the contract
        $this->app->singleton(\Illuminate\Contracts\Routing\UrlGenerator::class, function ($app) {
            return $app->make('url');
        });

        // Setup view factory for view() helper
        $this->app->singleton('view', function ($app) {
            $filesystem = new \Illuminate\Filesystem\Filesystem();
            $resolver = new \Illuminate\View\Engines\EngineResolver();

            // Register PHP engine
            $resolver->register('php', function () use ($filesystem) {
                return new \Illuminate\View\Engines\PhpEngine($filesystem);
            });

            // Register Blade engine
            $resolver->register('blade', function () use ($app, $filesystem) {
                $compiler = new \Illuminate\View\Compilers\BladeCompiler(
                    $filesystem,
                    __DIR__ . '/../storage/framework/views'
                );

                return new \Illuminate\View\Engines\CompilerEngine($compiler, $filesystem);
            });

            $finder = new \Illuminate\View\FileViewFinder(
                $filesystem,
                [__DIR__ . '/../resources/views']
            );

            // Add canvastack namespace
            $finder->addNamespace('canvastack', [__DIR__ . '/../resources/views']);

            $factory = new \Illuminate\View\Factory(
                $resolver,
                $finder,
                new \Illuminate\Events\Dispatcher()
            );

            return $factory;
        });

        // Also bind the contract
        $this->app->singleton(\Illuminate\Contracts\View\Factory::class, function ($app) {
            return $app->make('view');
        });

        // Setup response factory for response() helper
        $this->app->singleton(\Illuminate\Contracts\Routing\ResponseFactory::class, function ($app) {
            // Create a Redirector instance
            $redirector = new \Illuminate\Routing\Redirector($app->make('url'));

            return new \Illuminate\Routing\ResponseFactory(
                $app->make('view'),
                $redirector
            );
        });

        // Setup filesystem for Storage facade
        $this->app->singleton('filesystem', function ($app) {
            return new \Illuminate\Filesystem\FilesystemManager($app);
        });

        // Bind Filesystem Factory contract
        $this->app->singleton(\Illuminate\Contracts\Filesystem\Factory::class, function ($app) {
            return $app->make('filesystem');
        });

        $this->app->singleton('filesystem.disk', function ($app) {
            return $app->make('filesystem')->disk();
        });

        $this->app->singleton('filesystem.cloud', function ($app) {
            return $app->make('filesystem')->disk('cloud');
        });

        // Setup ParallelTesting for UploadedFile::fake()
        $this->app->singleton(\Illuminate\Testing\ParallelTesting::class, function () {
            return new \Illuminate\Testing\ParallelTesting($this->app);
        });

        // Bind Container contract to instance
        $this->app->singleton(\Illuminate\Contracts\Container\Container::class, function () {
            return $this->app;
        });

        // Setup Facade application instance
        Facade::setFacadeApplication($this->app);

        // Setup Gate for authorization
        $this->app->singleton(\Illuminate\Contracts\Auth\Access\Gate::class, function ($app) {
            return new \Illuminate\Auth\Access\Gate($app, function () {
                return null; // No user by default in tests
            });
        });

        // Bind resource path helper
        $this->app->bind('path.resources', function () {
            return __DIR__ . '/../resources';
        });

        // Create a simple app wrapper with necessary methods
        $appInstance = new class () {
            private $container;

            public function __construct()
            {
                $this->container = Container::getInstance();
            }

            public function setLocale($locale)
            {
                $this->container->make('translator')->setLocale($locale);
            }

            public function getLocale()
            {
                return $this->container->make('translator')->getLocale();
            }

            public function storagePath($path = '')
            {
                $basePath = $this->container->make('path.storage');

                return $path ? $basePath . '/' . $path : $basePath;
            }

            public function resourcePath($path = '')
            {
                $basePath = $this->container->make('path.resources');

                return $path ? $basePath . '/' . $path : $basePath;
            }

            public function basePath($path = '')
            {
                $basePath = __DIR__ . '/..';

                return $path ? $basePath . '/' . $path : $basePath;
            }

            public function __call($method, $parameters)
            {
                return $this->container->$method(...$parameters);
            }

            public function offsetExists($key): bool
            {
                return $this->container->bound($key);
            }

            public function offsetGet($key): mixed
            {
                return $this->container->make($key);
            }

            public function offsetSet($key, $value): void
            {
                $this->container->instance($key, $value);
            }

            public function offsetUnset($key): void
            {
                unset($this->container[$key]);
            }
        };

        $this->app->instance('app', $appInstance);

        // Set the facade application
        Facade::setFacadeApplication($this->app);

        // Load helper functions
        require_once __DIR__ . '/../src/Support/Localization/helpers.php';

        // Register translation services
        $this->registerTranslationServices($this->app);

        // Register theme services
        $this->registerThemeServices($this->app);

        // Register auth service (CRITICAL: must be before RBAC services)
        // This ensures TemplateVariableResolver can resolve {{auth.id}} and other auth variables
        $this->registerAuthService($this->app);

        // Register RBAC services
        $this->registerRBACServices($this->app);

        // Setup database connection
        $this->setupDatabase($this->app);

        // Load package routes for testing (MUST be after router setup)
        $this->loadPackageRoutes($this->app);

        // Refresh URL generator to pick up new routes
        $this->refreshUrlGenerator($this->app);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear the container instance
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    /**
     * Assert that a given table has a record matching the attributes.
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $count = Capsule::table($table)->where($data)->count();

        $this->assertGreaterThan(
            0,
            $count,
            sprintf(
                'Failed asserting that table [%s] contains a row matching %s',
                $table,
                json_encode($data)
            )
        );
    }

    /**
     * Assert that a given table does not have a record matching the attributes.
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $count = Capsule::table($table)->where($data)->count();

        $this->assertEquals(
            0,
            $count,
            sprintf(
                'Failed asserting that table [%s] does not contain a row matching %s',
                $table,
                json_encode($data)
            )
        );
    }

    /**
     * Setup in-memory SQLite database for testing.
     */
    protected function setupDatabase($app): void
    {
        $capsule = new Capsule($app);

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true, // Enable foreign key constraints
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Enable foreign key constraints for SQLite
        $capsule->getConnection()->statement('PRAGMA foreign_keys = ON');

        // Set the database manager in the container
        $app->instance('db', $capsule->getDatabaseManager());

        // Bind db.schema for Schema facade
        $app->singleton('db.schema', function ($app) use ($capsule) {
            return $capsule->getConnection()->getSchemaBuilder();
        });

        // Register DB facade alias
        if (!class_exists('DB')) {
            class_alias(\Illuminate\Support\Facades\DB::class, 'DB');
        }

        // Create test tables
        $this->createTestTables($capsule);
    }

    /**
     * Register translation services for testing.
     */
    protected function registerTranslationServices($app): void
    {
        // Register LocaleManager
        $app->singleton('canvastack.locale', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\LocaleManager();
        });

        // Also bind the class name
        $app->singleton(\Canvastack\Canvastack\Support\Localization\LocaleManager::class, function ($app) {
            return $app->make('canvastack.locale');
        });

        // Register RtlSupport
        $app->singleton(\Canvastack\Canvastack\Support\Localization\RtlSupport::class, function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\RtlSupport(
                $app->make('canvastack.locale')
            );
        });

        // Register TranslationLoader with mock
        $loaderMock = new class () extends \Canvastack\Canvastack\Support\Localization\TranslationLoader {
            protected function loadPaths(): void
            {
                // Package translations
                $this->paths[] = __DIR__ . '/../../../resources/lang';

                // Skip application translations in tests
                // Skip custom paths in tests
            }
        };

        $app->singleton('canvastack.translation.loader', function () use ($loaderMock) {
            return $loaderMock;
        });

        // Also bind the class name
        $app->singleton(\Canvastack\Canvastack\Support\Localization\TranslationLoader::class, function () use ($loaderMock) {
            return $loaderMock;
        });

        // Register TranslationCache
        $app->singleton('canvastack.translation.cache', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationCache(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register MissingTranslationDetector
        $app->singleton('canvastack.translation.detector', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\MissingTranslationDetector(
                $app->make('canvastack.translation.loader'),
                $app->make('canvastack.locale')
            );
        });

        // Register TranslationFallback
        $app->singleton('canvastack.translation.fallback', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationFallback(
                $app->make('canvastack.translation.loader'),
                $app->make('canvastack.locale'),
                $app->make('canvastack.translation.detector')
            );
        });

        // Register TranslationManager
        $app->singleton('canvastack.translation', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationManager(
                $app->make('translator'),
                $app->make('canvastack.translation.cache'),
                $app->make('canvastack.translation.fallback'),
                $app->make('canvastack.locale')
            );
        });
    }

    /**
     * Register theme services for testing.
     */
    protected function registerThemeServices($app): void
    {
        // Register ThemeRepository
        $app->singleton(\Canvastack\Canvastack\Support\Theme\ThemeRepository::class, function () {
            return new \Canvastack\Canvastack\Support\Theme\ThemeRepository();
        });

        // Register ThemeLoader
        $app->singleton(\Canvastack\Canvastack\Support\Theme\ThemeLoader::class, function ($app) {
            $themePath = __DIR__ . '/../resources/themes';

            return new \Canvastack\Canvastack\Support\Theme\ThemeLoader(
                $themePath,
                $app->make('files')
            );
        });

        // Register ThemeCache
        $app->singleton(\Canvastack\Canvastack\Support\Theme\ThemeCache::class, function () {
            return new \Canvastack\Canvastack\Support\Theme\ThemeCache();
        });

        // Register ThemeManager
        $app->singleton(\Canvastack\Canvastack\Support\Theme\ThemeManager::class, function ($app) {
            $manager = new \Canvastack\Canvastack\Support\Theme\ThemeManager(
                $app->make(\Canvastack\Canvastack\Support\Theme\ThemeRepository::class),
                $app->make(\Canvastack\Canvastack\Support\Theme\ThemeLoader::class),
                $app->make(\Canvastack\Canvastack\Support\Theme\ThemeCache::class)
            );
            $manager->initialize();

            return $manager;
        });
    }

    /**
     * Register auth service for testing.
     *
     * CRITICAL: This must be called BEFORE registerRBACServices() because
     * TemplateVariableResolver uses app('auth') to resolve template variables
     * like {{auth.id}}, {{auth.role}}, etc.
     */
    protected function registerAuthService($app): void
    {
        // Create a mock auth guard that can be used across all tests
        $mockGuard = new class () {
            private $user;

            public function setUser($user): void
            {
                $this->user = $user;
            }

            public function user()
            {
                return $this->user;
            }

            public function id()
            {
                return $this->user?->id;
            }

            public function check(): bool
            {
                return $this->user !== null;
            }

            public function login($user): void
            {
                $this->setUser($user);
            }

            public function logout(): void
            {
                $this->user = null;
            }
        };

        // Bind auth service to container
        $app->singleton('auth', function () use ($mockGuard) {
            return new class ($mockGuard) implements \Illuminate\Contracts\Auth\Factory {
                private $guard;

                public function __construct($guard)
                {
                    $this->guard = $guard;
                }

                public function guard($name = null)
                {
                    return $this->guard;
                }

                public function user()
                {
                    return $this->guard->user();
                }

                public function id()
                {
                    return $this->guard->id();
                }

                public function check(): bool
                {
                    return $this->guard->check();
                }

                public function setUser($user): void
                {
                    $this->guard->setUser($user);
                }

                public function login($user): void
                {
                    $this->guard->login($user);
                }

                public function logout(): void
                {
                    $this->guard->logout();
                }

                // Required by Factory interface
                public function shouldUse($name)
                {
                    // Not implemented for tests
                }

                public function setDefaultDriver($name)
                {
                    // Not implemented for tests
                }

                public function getDefaultDriver()
                {
                    return 'web';
                }

                public function userResolver()
                {
                    return function () {
                        return $this->user();
                    };
                }

                public function resolveUsersUsing(\Closure $userResolver)
                {
                    // Not implemented for tests
                }

                public function extend($driver, \Closure $callback)
                {
                    // Not implemented for tests
                }

                public function provider($name, \Closure $callback)
                {
                    // Not implemented for tests
                }

                public function hasResolvedGuards()
                {
                    return true;
                }

                public function forgetGuards()
                {
                    // Not implemented for tests
                }
            };
        });

        // Also bind the Auth Factory contract for auth() helper
        $app->singleton(\Illuminate\Contracts\Auth\Factory::class, function ($app) {
            return $app->make('auth');
        });
    }

    /**
     * Register RBAC services for testing.
     */
    protected function registerRBACServices($app): void
    {
        // Register RoleManager
        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\RoleManager::class, function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\RoleManager();
        });

        // Register PermissionManager
        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\PermissionManager::class, function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\PermissionManager();
        });

        // Register TemplateVariableResolver
        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver::class, function () {
            return new \Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver();
        });

        // Register PermissionRuleManager
        $app->singleton('canvastack.rbac.rule.manager', function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager(
                $app->make(\Canvastack\Canvastack\Auth\RBAC\RoleManager::class),
                $app->make(\Canvastack\Canvastack\Auth\RBAC\PermissionManager::class),
                $app->make(\Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver::class)
            );
        });

        // Also bind the class name
        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager::class, function ($app) {
            return $app->make('canvastack.rbac.rule.manager');
        });
    }

    /**
     * Load package routes for testing.
     *
     * This method loads all routes defined in routes/web.php to ensure
     * they are available during testing. This fixes the "Route not defined"
     * errors that occur when tests try to use route() helper.
     */
    protected function loadPackageRoutes($app): void
    {
        // Get the router instance
        $router = $app->make('router');

        // Register Ajax Sync route
        $route = $router->post('/canvastack/ajax/sync', [\Canvastack\Canvastack\Http\Controllers\AjaxSyncController::class, 'handle'])
            ->middleware(['web']);
        $route->name('canvastack.ajax.sync');

        // Register Locale Switch route
        $route = $router->post('/locale/switch', [\Canvastack\Canvastack\Http\Controllers\LocaleController::class, 'switch'])
            ->middleware(['web']);
        $route->name('locale.switch');

        // Register DataTable route
        $route = $router->match(['get', 'post'], '/datatable/data', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'getData'])
            ->middleware(['web']);
        $route->name('datatable.data');

        // Register Admin Dashboard route
        $route = $router->get('/admin/dashboard', function () {
            return view('canvastack::admin.dashboard');
        })->middleware(['web']);
        $route->name('admin.dashboard');

        // Register Admin Profile route
        $route = $router->get('/admin/profile', function () {
            return view('canvastack::admin.profile');
        })->middleware(['web']);
        $route->name('admin.profile');

        // Register Admin Settings route
        $route = $router->get('/admin/settings', function () {
            return view('canvastack::admin.settings');
        })->middleware(['web']);
        $route->name('admin.settings');

        // Register Logout route
        $route = $router->post('/logout', function () {
            auth()->logout();

            return redirect('/');
        })->middleware(['web']);
        $route->name('logout');

        // Register Admin Theme Management routes
        $router->prefix('admin/themes')->name('admin.themes.')->middleware(['web'])->group(function ($router) {
            $route = $router->get('/', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'index']);
            $route->name('index');

            $route = $router->get('/{theme}', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'show']);
            $route->name('show');

            $route = $router->post('/{theme}/activate', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'activate']);
            $route->name('activate');

            $route = $router->post('/clear-cache', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'clearCache']);
            $route->name('clear-cache');

            $route = $router->post('/reload', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'reload']);
            $route->name('reload');

            $route = $router->get('/{theme}/export/{format}', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'export']);
            $route->name('export');

            $route = $router->get('/{theme}/preview', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'preview']);
            $route->name('preview');

            $route = $router->get('/stats/all', [\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class, 'stats']);
            $route->name('stats');
        });

        // Register Admin Locale Management routes
        $router->prefix('admin/locales')->name('admin.locales.')->middleware(['web'])->group(function ($router) {
            $route = $router->get('/', [\Canvastack\Canvastack\Http\Controllers\Admin\LocaleController::class, 'index']);
            $route->name('index');
        });

        // Force refresh of route collection name index
        $router->getRoutes()->refreshNameLookups();
    }

    /**
     * Refresh URL generator to pick up newly registered routes.
     *
     * The URL generator caches routes when it's created, so we need to
     * recreate it after loading routes to ensure route() helper works.
     */
    protected function refreshUrlGenerator($app): void
    {
        // The URL generator we created in setUp() always gets fresh routes
        // from the router, so no action needed here. This method exists
        // for documentation purposes and future extensibility.
    }

    /**
     * Create test tables for testing.
     */
    protected function createTestTables(Capsule $capsule): void
    {
        // Create translatable_products table for testing
        $capsule->schema()->create('translatable_products', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        // Create translations table
        $capsule->schema()->create('translations', function ($table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 10);
            $table->string('attribute');  // Changed from 'key' to 'attribute'
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['translatable_type', 'translatable_id']);
            $table->index(['locale', 'attribute']);  // Changed from 'key' to 'attribute'

            // Unique constraint: one translation per model + attribute + locale
            $table->unique(
                ['translatable_type', 'translatable_id', 'attribute', 'locale'],
                'unique_translation'
            );
        });

        // Create roles table
        $capsule->schema()->create('roles', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('level')->default(99);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Create permissions table
        $capsule->schema()->create('permissions', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module')->nullable();
            $table->timestamps();
        });

        // Create role_user pivot table
        $capsule->schema()->create('role_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        // Create permission_role pivot table
        $capsule->schema()->create('permission_role', function ($table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });

        // Create permission_user pivot table
        $capsule->schema()->create('permission_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['permission_id', 'user_id']);
        });

        // Create permission_rules table for fine-grained permissions
        $capsule->schema()->create('permission_rules', function ($table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->enum('rule_type', ['row', 'column', 'json_attribute', 'conditional']);
            $table->json('rule_config');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['permission_id', 'rule_type'], 'idx_permission_rule_type');
            $table->index('priority', 'idx_priority');
        });

        // Add foreign key separately
        $capsule->schema()->table('permission_rules', function ($table) {
            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });

        // Create user_permission_overrides table for fine-grained permissions
        $capsule->schema()->create('user_permission_overrides', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('field_name')->nullable();
            $table->json('rule_config')->nullable();
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'permission_id', 'model_type', 'model_id'], 'user_perm_model_idx');
            $table->index('model_type');
        });

        // Add foreign keys separately
        $capsule->schema()->table('user_permission_overrides', function ($table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });

        // Create users table for RBAC testing
        $capsule->schema()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable(); // Add email verification timestamp
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('active')->default(true); // Add active column for property tests
            $table->unsignedBigInteger('organization_id')->nullable(); // For multi-tenant tests
            $table->unsignedBigInteger('team_id')->nullable(); // For team-based tests
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes support
        });

        // Create posts table for testing
        $capsule->schema()->create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->text('excerpt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        // Add foreign key for posts
        $capsule->schema()->table('posts', function ($table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Create test_provinces table for property testing
        $capsule->schema()->create('test_provinces', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        // Create test_cities table for property testing
        $capsule->schema()->create('test_cities', function ($table) {
            $table->id();
            $table->unsignedBigInteger('province_id');
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->timestamps();

            $table->index('province_id');
        });

        // Add foreign key for test_cities
        $capsule->schema()->table('test_cities', function ($table) {
            $table->foreign('province_id')
                ->references('id')
                ->on('test_provinces')
                ->onDelete('cascade');
        });

        // Create activity_logs table for activity logging
        $capsule->schema()->create('activity_logs', function ($table) {
            $table->id();

            // User information
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('user_fullname')->nullable();
            $table->string('user_email')->nullable();

            // Group/Role information
            $table->unsignedBigInteger('user_group_id')->nullable();
            $table->string('user_group_name')->nullable();
            $table->string('user_group_info')->nullable();

            // Request information
            $table->string('route_path')->nullable();
            $table->string('module_name')->nullable();
            $table->string('page_info')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();

            // Context information
            $table->string('context', 50)->default('admin');
            $table->string('action', 100)->nullable();
            $table->string('description')->nullable();

            // Technical information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('sql_dump')->nullable();

            // Performance metrics
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('memory_usage')->nullable();

            // Status
            $table->string('status', 20)->default('success');

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('user_group_id');
            $table->index('context');
            $table->index('action');
            $table->index('status');
            $table->index('created_at');
        });

        // Create table_tab_sessions table for TableBuilder tab persistence
        $capsule->schema()->create('table_tab_sessions', function ($table) {
            $table->id();
            $table->string('session_id', 255);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('table_name', 255);
            $table->string('active_tab', 255);
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('table_name');
            $table->index(['session_id', 'table_name'], 'idx_tab_session_table');
        });

        // Add foreign key for table_tab_sessions
        $capsule->schema()->table('table_tab_sessions', function ($table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Create table_filter_sessions table for TableBuilder filter persistence
        $capsule->schema()->create('table_filter_sessions', function ($table) {
            $table->id();
            $table->string('session_id', 255);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('table_name', 255);
            $table->json('filters');
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('table_name');
            $table->index(['session_id', 'table_name'], 'idx_filter_session_table');
        });

        // Add foreign key for table_filter_sessions
        $capsule->schema()->table('table_filter_sessions', function ($table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Create table_display_preferences table for TableBuilder display preferences
        $capsule->schema()->create('table_display_preferences', function ($table) {
            $table->id();
            $table->string('session_id', 255);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('table_name', 255);
            $table->string('display_limit', 10)->default('10');
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('table_name');
            $table->index(['session_id', 'table_name'], 'idx_display_session_table');
        });

        // Add foreign key for table_display_preferences
        $capsule->schema()->table('table_display_preferences', function ($table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Get default configuration for tests.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'filesystems' => [
                'default' => 'local',
                'disks' => [
                    'local' => [
                        'driver' => 'local',
                        'root' => __DIR__ . '/../storage/app',
                    ],
                    'public' => [
                        'driver' => 'local',
                        'root' => __DIR__ . '/../storage/app/public',
                        'url' => '/storage',
                        'visibility' => 'public',
                    ],
                ],
            ],
            'canvastack-rbac' => [
                'cache' => [
                    'enabled' => true,
                    'ttl' => 3600,
                    'key_prefix' => 'canvastack:rbac:',
                    'tags' => [
                        'roles' => 'canvastack:rbac:roles',
                        'permissions' => 'canvastack:rbac:permissions',
                        'user_permissions' => 'canvastack:rbac:user_permissions',
                    ],
                ],
                'tables' => [
                    'roles' => 'roles',
                    'permissions' => 'permissions',
                    'role_user' => 'role_user',
                    'permission_role' => 'permission_role',
                    'permission_user' => 'permission_user',
                ],
                'models' => [
                    'role' => 'Canvastack\\Canvastack\\Models\\Role',
                    'permission' => 'Canvastack\\Canvastack\\Models\\Permission',
                    'user' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\User',
                ],
                'fine_grained' => [
                    'enabled' => true,
                    'cache' => [
                        'enabled' => true,
                        'ttl' => [
                            'row' => 3600,
                            'column' => 3600,
                            'json_attribute' => 3600,
                            'conditional' => 1800,
                        ],
                        'key_prefix' => 'canvastack:rbac:rules:',
                        'tags' => [
                            'rules' => 'rbac:rules',
                            'user' => 'rbac:user:{userId}',
                            'permission' => 'rbac:permission:{permissionId}',
                        ],
                    ],
                    'row_level' => [
                        'enabled' => true,
                        'template_variables' => [
                            'auth.id' => fn () => app('auth')->id(),
                            'auth.role' => fn () => app('auth')->user()?->role,
                            'auth.department' => fn () => app('auth')->user()?->department_id,
                            'auth.email' => fn () => app('auth')->user()?->email,
                        ],
                    ],
                    'column_level' => [
                        'enabled' => true,
                        'default_deny' => false,
                    ],
                    'json_attribute' => [
                        'enabled' => true,
                        'path_separator' => '.',
                    ],
                    'conditional' => [
                        'enabled' => true,
                        'allowed_operators' => [
                            '===', '!==', '>', '<', '>=', '<=',
                            'in', 'not_in', 'AND', 'OR', 'NOT',
                        ],
                        'allowed_functions' => ['count', 'sum', 'avg'],
                    ],
                    'audit' => [
                        'enabled' => true,
                        'log_denials' => true,
                        'log_channel' => 'rbac',
                    ],
                ],
            ],
            'canvastack' => [
                'i18n' => [
                    'default_locale' => 'en',
                    'fallback_locale' => 'en',
                    'available_locales' => ['en', 'id', 'ar', 'he'],
                    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
                    'date_formats' => [
                        'en' => [
                            'date' => 'Y-m-d',
                            'time' => 'H:i:s',
                            'datetime' => 'Y-m-d H:i:s',
                            'long_date' => 'F j, Y',
                            'short_date' => 'M j, Y',
                        ],
                        'id' => [
                            'date' => 'd-m-Y',
                            'time' => 'H:i:s',
                            'datetime' => 'd-m-Y H:i:s',
                            'long_date' => 'j F Y',
                            'short_date' => 'j M Y',
                        ],
                    ],
                    'number_formats' => [
                        'en' => [
                            'decimal_separator' => '.',
                            'thousands_separator' => ',',
                            'decimals' => 2,
                        ],
                        'id' => [
                            'decimal_separator' => ',',
                            'thousands_separator' => '.',
                            'decimals' => 2,
                        ],
                    ],
                    'currencies' => [
                        'USD' => [
                            'symbol' => '$',
                            'name' => 'US Dollar',
                            'code' => 'USD',
                            'decimals' => 2,
                            'position' => 'before',
                            'space' => false,
                        ],
                        'EUR' => [
                            'symbol' => '€',
                            'name' => 'Euro',
                            'code' => 'EUR',
                            'decimals' => 2,
                            'position' => 'after',
                            'space' => true,
                        ],
                        'IDR' => [
                            'symbol' => 'Rp',
                            'name' => 'Indonesian Rupiah',
                            'code' => 'IDR',
                            'decimals' => 0,
                            'position' => 'before',
                            'space' => true,
                        ],
                        'GBP' => [
                            'symbol' => '£',
                            'name' => 'British Pound',
                            'code' => 'GBP',
                            'decimals' => 2,
                            'position' => 'before',
                            'space' => false,
                        ],
                        'JPY' => [
                            'symbol' => '¥',
                            'name' => 'Japanese Yen',
                            'code' => 'JPY',
                            'decimals' => 0,
                            'position' => 'before',
                            'space' => false,
                        ],
                    ],
                ],
                'localization' => [
                    'default_locale' => 'en',
                    'fallback_locale' => 'en',
                    'available_locales' => [
                        'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
                        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
                        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦'],
                        'he' => ['name' => 'Hebrew', 'native' => 'עברית', 'flag' => '🇮🇱'],
                        'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'flag' => '🇮🇷'],
                        'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'flag' => '🇵🇰'],
                    ],
                    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
                    'storage' => 'session',
                    'detect_browser' => false,
                ],
            ],
        ];
    }

    /**
     * Create a TableBuilder instance with all necessary dependencies.
     *
     * This helper method ensures all tests use a properly configured TableBuilder
     * with all required services (cache, query optimizer, renderers, etc.).
     *
     * @return \Canvastack\Canvastack\Components\Table\TableBuilder
     */
    protected function createTableBuilder(): \Canvastack\Canvastack\Components\Table\TableBuilder
    {
        return app(\Canvastack\Canvastack\Components\Table\TableBuilder::class);
    }
}
