<?php

namespace Task\V1\Rpc\Login;

use Laminas\Db\Adapter\AdapterInterface as DbAdapterInterface;
use Psr\Container\ContainerInterface;

class LoginControllerFactory
{
    public function __invoke(ContainerInterface $container): LoginController
    {
        // Busca o adapter do banco registrado no ServiceManager
        /** @var DbAdapterInterface $dbAdapter */
        $dbAdapter  = $container->get('MySqlAdapter');

        // Cria a camada de acesso a dados (Table) e injeta no controller
        $loginTable = new LoginTable($dbAdapter);

        return new LoginController($loginTable);
    }
}
