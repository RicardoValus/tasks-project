<?php

declare(strict_types=1);

namespace Task\V1\Auth;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

final class AttachTokenAdapterDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, callable $callback, ?array $options = null)
    {
        /** @var DefaultAuthenticationListener $listener */
        $listener = $callback();

        // Pluga nosso adapter de token no pipeline de autenticação do API Tools.
        // Isso roda ANTES do controller/resource (é o “middleware” de auth).
        $listener->attach($container->get(TokenAuthenticationAdapter::class));

        return $listener;
    }

    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        return $this($serviceLocator, $requestedName, $callback);
    }
}
