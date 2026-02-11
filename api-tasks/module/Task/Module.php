<?php

namespace Task;

use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\ApiTools\Provider\ApiToolsProviderInterface;
use Laminas\Mvc\MvcEvent;

class Module implements ApiToolsProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e): void
    {
        $app = $e->getApplication();
        $events = $app->getEventManager();
        $container = $app->getServiceManager();

        // “Polimento empresa”: quando /tasks exige autenticação e vem sem token,
        // o API Tools por padrão acaba respondendo 403.
        // Aqui a gente converte isso para 401 (Unauthorized) quando a identidade é guest.
        // O evento AUTHORIZATION_POST para no primeiro listener que retornar Response.
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $container->get(\Task\V1\Auth\AuthorizationPostListener::class),
            1000
        );
    }

    public function getAutoloaderConfig()
    {
        return [
            'Laminas\ApiTools\Autoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src',
                ],
            ],
        ];
    }
}
