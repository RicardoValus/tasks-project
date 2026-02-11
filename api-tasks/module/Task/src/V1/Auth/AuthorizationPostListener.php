<?php

declare(strict_types=1);

namespace Task\V1\Auth;

use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Response as HttpResponse;

final class AuthorizationPostListener
{
    /**
     * Se falhou autorização e o usuário é guest, responde 401 (em vez de 403).
     * Se falhou e já estava autenticado, mantém 403.
     *
     * Regra prática de APIs:
     * - Sem login/token: 401
     * - Logado, mas sem permissão: 403
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = $mvcEvent->getResponse();

        if ($mvcAuthEvent->isAuthorized()) {
            if ($response instanceof HttpResponse && $response->getStatusCode() !== 200) {
                $response->setStatusCode(200);
            }
            return;
        }

        if (! $response instanceof HttpResponse) {
            return $response;
        }

        $identity = $mvcAuthEvent->getIdentity();
        if ($identity instanceof GuestIdentity) {
            $response->setStatusCode(401);
            $response->setReasonPhrase('Unauthorized');
            $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer');
            return $response;
        }

        $response->setStatusCode(403);
        $response->setReasonPhrase('Forbidden');
        return $response;
    }
}
