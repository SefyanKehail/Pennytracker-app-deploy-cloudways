<?php

declare(strict_types=1);

use App\Auth;
use App\Config;
use App\Contracts\AuthInterface;
use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\CsrfFailureHandler;
use App\DTO\SessionParamsDTO;
use App\Entity\User;
use App\Enum\AppEnvironment;
use App\Enum\SameSite;
use App\Enum\StorageAdapter;
use App\Filters\UserFilter;
use App\RouteEntityBindingStrategy;
use App\Services\EntityManagerService;
use App\Services\UserService;
use App\Session;
use App\Validators\ValidatorFactory;
use Aws\S3\S3Client;
use Clockwork\DataSource\DoctrineDataSource;
use Clockwork\Storage\FileStorage;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use DoctrineExtensions\Query\Mysql\DateFormat;
use DoctrineExtensions\Query\Mysql\Day;
use DoctrineExtensions\Query\Mysql\Hour;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\Week;
use DoctrineExtensions\Query\Mysql\WeekDay;
use DoctrineExtensions\Query\Mysql\Year;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Clockwork\Clockwork;
use Psr\SimpleCache\CacheInterface;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Routing\RouteParser;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;
use Twig\Extra\Intl\IntlExtension;

use function DI\create;

return array(
    App::class                       => function (ContainerInterface $container) {
        $addMiddlewares = require CONFIG_PATH . '/middleware.php';
        $router         = require CONFIG_PATH . '/routes/web.php';

        AppFactory::setContainer($container);

        $app = AppFactory::create();


        // invocation strategy
        $app->getRouteCollector()->setDefaultInvocationStrategy(new RouteEntityBindingStrategy(
                $container->get(EntityManagerServiceInterface::class),
                $app->getResponseFactory()
            )
        );

        $router($app);

        $addMiddlewares($app);

        return $app;
    },
    Config::class                    => create(Config::class)->constructor(require CONFIG_PATH . '/app.php'),
    EntityManagerInterface::class    => function (Config $config) {
        $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
            $config->get('doctrine.entity_dir'),
            $config->get('doctrine.dev_mode'),
        );

        $ormConfig->addFilter('user', UserFilter::class);

        $ormConfig->addCustomDatetimeFunction('YEAR', Year::class);
        $ormConfig->addCustomDatetimeFunction('MONTH', Month::class);
        $ormConfig->addCustomDatetimeFunction('WEEK', Week::class);
        $ormConfig->addCustomDatetimeFunction('DAY', Day::class);
        $ormConfig->addCustomDatetimeFunction('WEEKDAY', WeekDay::class);
        $ormConfig->addCustomDatetimeFunction('HOUR', Hour::class);
        $ormConfig->addCustomDatetimeFunction('DATE_FORMAT', DateFormat::class);

        return new EntityManager(
            DriverManager::getConnection($config->get('doctrine.connection'), $ormConfig),
            $ormConfig
        );
    },
    Twig::class                      => function (Config $config, ContainerInterface $container) {
        $twig = Twig::create(VIEW_PATH, array(
            'cache'       => STORAGE_PATH . '/cache/templates',
            'auto_reload' => AppEnvironment::isDevelopment($config->get('app_environment')),
        ));

        $twig->addExtension(new IntlExtension());
        $twig->addExtension(new EntryFilesTwigExtension($container));
        $twig->addExtension(new AssetExtension($container->get('webpack_encore.packages')));

        return $twig;
    },
    ResponseFactoryInterface::class  => fn(App $app) => $app->getResponseFactory()
    ,
    /**
     * The following two bindings are needed for EntryFilesTwigExtension & AssetExtension to work for Twig
     */
    'webpack_encore.packages'        => fn() => new Packages(
        new Package(new JsonManifestVersionStrategy(BUILD_PATH . '/manifest.json'))
    ),
    'webpack_encore.tag_renderer'    => fn(ContainerInterface $container) => new TagRenderer(
        new EntrypointLookup(BUILD_PATH . '/entrypoints.json'),
        $container->get('webpack_encore.packages')
    ),
    AuthInterface::class             => fn(ContainerInterface $container) => $container->get(Auth::class),
    UserInterface::class             => fn(ContainerInterface $container) => $container->get(User::class),
    UserServiceInterface::class      => fn(ContainerInterface $container
    ) => $container->get(UserService::class),
    SessionInterface::class          => fn(Config $config) => new Session(new SessionParamsDTO(
            $config->get('session.name', ''),
            $config->get('session.secure', true),
            $config->get('session.httponly', true),
            SameSite::tryFrom($config->get('session.samesite', 'lax')),
        )
    ),
    ValidatorFactoryInterface::class => fn(ContainerInterface $container) => $container->get(ValidatorFactory::class),
    'csrf'                           => fn(
        ContainerInterface       $container,
        ResponseFactoryInterface $responseFactory
    ) => new Guard($responseFactory,
        failureHandler: $container->get(CsrfFailureHandler::class)->handleFailure(),
        persistentTokenMode: true
    ),

    Filesystem::class => function (Config $config) {
        $storage = $config->get('storage.adapter');

        $digitalOcean = function (array $options): AwsS3V3Adapter {
            $client = new S3Client([
                'credentials' => [
                    'key' => $options['key'],
                    'secret' => $options['secret']
                ],
                'region' => $options['region'],
                'version' => $options['version'],
                'endpoint' => $options['endpoint']
            ]);

            return new AwsS3V3Adapter(
                $client,
                $options['bucket']
            );
        };

        $adapter = match ($storage) {
            StorageAdapter::Local => new LocalFilesystemAdapter(STORAGE_PATH),
            StorageAdapter::Remote_DO => $digitalOcean($config->get('storage.s3'))
        };
        return new Filesystem($adapter);
    },

    // this one because we want to add doctrine to see executed queries by adding datasource
    Clockwork::class  => function (ContainerInterface $container) {
        $clockwork = new Clockwork();
        $clockwork->storage(new FileStorage(STORAGE_PATH . DIRECTORY_SEPARATOR . 'clockwork'));
        $clockwork->addDataSource(new DoctrineDataSource($container->get(EntityManagerInterface::class)));

        return $clockwork;
    },

    EntityManagerServiceInterface::class => fn(ContainerInterface $container
    ) => $container->get(EntityManagerService::class)
    ,

    MailerInterface::class => function (Config $config) {


        $transport = Transport::fromDsn($config->get('mailer.dsn'));

        return new Mailer($transport);
    },

    BodyRendererInterface::class => fn(Twig $twig) => new BodyRenderer($twig->getEnvironment()),

    RouteParserInterface::class => fn(App $app) => $app->getRouteCollector()->getRouteParser(),

    RedisAdapter::class => function (Config $config) {
        $redis  = new Redis();
        $config = $config->get('redis');
        $redis->connect($config['host'], 6379);
        return new RedisAdapter($redis);
    },

    CacheInterface::class => fn(RedisAdapter $redisAdapter) => new Psr16Cache($redisAdapter)
    ,

    RateLimiterFactory::class => function (Config $config, RedisAdapter $redisAdapter) {
        $storage = new CacheStorage($redisAdapter);

        return new RateLimiterFactory($config->get('rate_limiter_config'), $storage);
    }
);
