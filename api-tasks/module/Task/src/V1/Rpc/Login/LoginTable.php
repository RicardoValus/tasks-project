<?php

namespace Task\V1\Rpc\Login;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

/**
 * Camada de acesso a dados (Table Gateway simplificado).
 *
 * Toda query relacionada ao login fica aqui — o controller
 * nunca conhece o adapter nem monta SQL diretamente.
 */
class LoginTable
{
    /** @var AdapterInterface */
    private $dbAdapter;

    public function __construct(AdapterInterface $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * Busca um usuário pelo e-mail.
     *
     * TRÁFEGO DO DADO (DB):
     * LoginController::loginAction()
     *   -> LoginTable::findByEmail(email)
     *     -> SELECT users WHERE email = :email
     *   <- row do usuário (id, email, password hash, ...)
     *
     * @param  string $email
     * @return array|false  Retorna o registro do usuário ou false se não encontrar.
     */
    public function findByEmail(string $email)
    {
        // Busca usuário para o login (AuthN).
        $sql    = new Sql($this->dbAdapter);
        $select = $sql->select('users')->where(['email' => $email]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return $result->current() ?: false;
    }

    /**
     * Persiste um token de autenticação no banco.
     *
     * Tabela usada: `tokens` (conforme seu phpMyAdmin).
     * Campos esperados: user_id, token, expires_at, created_at.
     *
     * TRÁFEGO DO DADO (DB):
     * LoginController::loginAction()
     *   -> LoginTable::createToken(user_id, token, expires_at)
     *     -> INSERT tokens (user_id, token, expires_at)
     *   <- affected rows (1 = ok)
     */
    public function createToken(int $userId, string $token, \DateTimeImmutable $expiresAt): bool
    {
        $sql    = new Sql($this->dbAdapter);
        $insert = $sql->insert('tokens')->values([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        $stmt   = $sql->prepareStatementForSqlObject($insert);
        $result = $stmt->execute();

        return (bool) $result->getAffectedRows();
    }

    /**
     * Busca um token válido (existente e não expirado).
     *
     * Esse método é útil para o “middleware” (adapter) que roda antes do controller.
     *
     * @return array|false
     */
    public function findValidToken(string $token)
    {
        $sql    = new Sql($this->dbAdapter);
        $select = $sql->select('tokens')->where(['token' => $token]);
        $stmt   = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row    = $result->current();

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
