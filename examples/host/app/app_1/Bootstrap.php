<?php

declare(strict_types=1);

namespace Apps\App1;

use Apps\App1\Extensions\AppInfoExtension;
use Apps\App1\Services\DatabaseUserRepository;
use Apps\App1\Services\DemoOAuthIntrospector;
use Apps\App1\Services\UserRepository;
use Bluewater\Application;
use Bluewater\ApplicationBootstrap;
use Bluewater\Auth\ApiKeyProvider;
use Bluewater\Auth\AuthManager;
use Bluewater\Auth\JwtProvider;
use Bluewater\Auth\OAuthBearerProvider;
use Bluewater\Database\Database;
use Bluewater\Database\PdoDatabase;
use Bluewater\Middleware\RequestLoggingMiddleware;

final class Bootstrap implements ApplicationBootstrap
{
    public function register(Application $app): void
    {
        $config = $app->config();
        $services = $app->services();

        if ((bool) $config->get('features.DATABASE', true)) {
            $dataDir = $app->definition()->root . '/data';
            if (!is_dir($dataDir)) { mkdir($dataDir, 0775, true); }
            $database = PdoDatabase::connect(
                (string) $config->get('database.DSN'),
                (string) $config->get('database.USERNAME', '') ?: null,
                (string) $config->get('database.PASSWORD', '') ?: null,
            );
            $database->execute('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, name TEXT NOT NULL)');
            if (($database->fetchOne('SELECT COUNT(*) AS total FROM users')['total'] ?? 0) == 0) {
                $database->execute('INSERT INTO users (email, name) VALUES (:email, :name)', ['email' => 'demo@example.com', 'name' => 'Demo User']);
            }
            $services->instance(Database::class, $database);
            $services->bind(UserRepository::class, DatabaseUserRepository::class);
        }

        if ((bool) $config->get('features.AUTH', true)) {
            $auth = new AuthManager();
            $apiKey = (string) $config->get('auth.API_KEY', 'demo-key');
            $auth->register('api_key', new ApiKeyProvider([$apiKey => ['id' => 'app_1', 'scopes' => ['admin']]]));
            $auth->register('jwt', new JwtProvider((string) $config->get('auth.JWT_SECRET', 'change-me')));
            $auth->register('oauth', new OAuthBearerProvider(new DemoOAuthIntrospector()));
            $services->instance(AuthManager::class, $auth);
        }

        $app->extensions()->add(AppInfoExtension::class);
    }

    public function boot(Application $app): void
    {
        if ((bool) $app->config()->get('features.LOGGING', true)) {
            $app->middleware()->add(RequestLoggingMiddleware::class);
        }
    }
}
