<?php

declare(strict_types=1);

namespace Task\V1\Auth;

use Laminas\ApiTools\MvcAuth\Authentication\AdapterInterface;
use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Result;
use Laminas\Db\Adapter\AdapterInterface as DbAdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Http\Request;
use Laminas\Http\Response;

use function preg_match;
use function trim;

final class TokenAuthenticationAdapter implements AdapterInterface
{
    /**
     * Nome do tipo de autenticação.
     *
     * Este valor precisa bater com o `api-tools-mvc-auth.authentication.map`.
     */
    private const TYPE = 'token';

    public function __construct(
        private readonly DbAdapterInterface $db,
        private readonly string $tokensTable = 'tokens',
    ) {}

    public function provides()
    {
        return [self::TYPE];
    }

    public function matches($type)
    {
        return $type === self::TYPE;
    }

    public function getTypeFromRequest(Request $request)
    {
        // “Middleware”: aqui a gente detecta se o request está tentando autenticar.
        // Se não tem header Authorization, não tenta autenticar (vira GuestIdentity).
        $authHeader = $request->getHeader('Authorization', false);
        if (! $authHeader) {
            return false;
        }

        $value = trim($authHeader->getFieldValue());
        if (preg_match('/^Bearer\s+\S+$/i', $value) !== 1) {
            return false;
        }

        return self::TYPE;
    }

    public function preAuth(Request $request, Response $response)
    {
        // No challenge by default.
    }

    public function authenticate(Request $request, Response $response, MvcAuthEvent $mvcAuthEvent)
    {
        // TRÁFEGO DO DADO (autenticação via token):
        // 1) (HTTP) chega request em /tasks com header: Authorization: Bearer <token>
        // 2) (Adapter) extrai <token> do header
        // 3) (DB) findTokenRow(token)
        //    -> SELECT * FROM tokens WHERE token = :token
        //    -> valida expires_at (não expirado)
        // 4) (AuthN OK) cria AuthenticatedIdentity com user_id
        // 5) (Uso) TasksResource lê getIdentity() e aplica user_id no WHERE
        // Se não tem Authorization, a identidade é “guest”.
        // A autorização (AuthZ) decide se guest pode acessar /tasks.
        $authHeader = $request->getHeader('Authorization', false);
        if (! $authHeader) {
            return new GuestIdentity();
        }

        $value = trim($authHeader->getFieldValue());
        if (preg_match('/^Bearer\s+(?<token>\S+)$/i', $value, $m) !== 1) {
            // Header existe mas está malformado => 401.
            $mvcAuthEvent->setAuthenticationResult(new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Malformed Authorization header']));
            $response->setStatusCode(401);
            $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer');
            return false;
        }

        $token = $m['token'];

        $row = $this->findTokenRow($token);
        if (! $row) {
            // Token não existe ou expirou => 401.
            $mvcAuthEvent->setAuthenticationResult(new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid or expired token']));
            $response->setStatusCode(401);
            $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer');
            return false;
        }

        $identityData = [
            'user_id' => (int) $row['user_id'],
            'token' => (string) $row['token'],
            'expires_at' => (string) $row['expires_at'],
        ];

        $mvcAuthEvent->setAuthenticationResult(new Result(Result::SUCCESS, $identityData));

        // Se chegou aqui, AuthN OK.
        // A identidade fica disponível durante a request (ex.: para “amarrar” tasks por user).
        $identity = new AuthenticatedIdentity($identityData);
        $identity->setName((string) $identityData['user_id']);

        return $identity;
    }

    private function findTokenRow(string $token): array|false
    {
        // TRÁFEGO DO DADO (DB):
        // TokenAuthenticationAdapter::authenticate()
        //   -> findTokenRow(token)
        //     -> SELECT tokens WHERE token = :token
        //     -> valida expires_at
        //   <- row { user_id, token, expires_at, ... } ou false
        // Validação simples via banco:
        // - token existe
        // - expires_at ainda é futuro
        $sql = new Sql($this->db);
        $select = $sql->select($this->tokensTable)->where(['token' => $token]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        if (! $row || ! isset($row['expires_at'])) {
            return false;
        }

        try {
            $expiresAt = new \DateTimeImmutable((string) $row['expires_at']);
        } catch (\Exception $e) {
            return false;
        }

        if ($expiresAt <= new \DateTimeImmutable('now')) {
            return false;
        }

        return $row;
    }
}
