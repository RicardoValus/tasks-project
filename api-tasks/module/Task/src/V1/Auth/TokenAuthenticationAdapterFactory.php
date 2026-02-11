<?php

declare(strict_types=1);

namespace Task\V1\Auth;

use Laminas\Db\Adapter\AdapterInterface as DbAdapterInterface;
use Psr\Container\ContainerInterface;

final class TokenAuthenticationAdapterFactory
{
    public function __invoke(ContainerInterface $container): TokenAuthenticationAdapter
    {
        /** @var DbAdapterInterface $db */
        $db = $container->get('MySqlAdapter');

        return new TokenAuthenticationAdapter($db, 'tokens');
    }
}
