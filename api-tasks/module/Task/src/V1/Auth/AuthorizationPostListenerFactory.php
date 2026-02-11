<?php

declare(strict_types=1);

namespace Task\V1\Auth;

use Psr\Container\ContainerInterface;

final class AuthorizationPostListenerFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): AuthorizationPostListener
    {
        return new AuthorizationPostListener();
    }
}
