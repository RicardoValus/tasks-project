<?php

namespace Task\V1\Rpc\Login;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class LoginController extends AbstractActionController
{
    /**
     * TTL do token em segundos.
     *
     * Padrão “empresa”: token expira sempre (evita token eterno).
     */
    private const TOKEN_TTL_SECONDS = 7200; // 2h

    /** @var LoginTable */
    private $loginTable;

    /**
     * Recebe a LoginTable via construtor (injetada pela Factory).
     * O controller nunca conhece o adapter de banco diretamente.
     */
    public function __construct(LoginTable $loginTable)
    {
        $this->loginTable = $loginTable;
    }

    public function loginAction()
    {
        // TRÁFEGO DO DADO (login, backend):
        // 1) (HTTP) POST /login (JSON: {email, password})
        // 2) (Router) /login -> Task\V1\Rpc\Login\LoginController::loginAction()
        // 3) (Controller) lê JSON do body
        // 4) (DB) LoginTable::findByEmail(email)
        //    -> SELECT * FROM users WHERE email = :email
        // 5) (Controller) password_verify(password, users.password)
        // 6) (Controller) gera token e expires_at
        // 7) (DB) LoginTable::createToken(user_id, token, expires_at)
        //    -> INSERT INTO tokens (user_id, token, expires_at)
        // 8) (Resposta) JSON { token, expires_at }
        $request = $this->getRequest();

        // RPC: /login é uma “ação”, então aceitamos apenas POST.
        if (! $request->isPost()) {
            return new ApiProblemResponse(new ApiProblem(405, 'Método não permitido. Use POST.'));
        }

        // -----------------------------------------------------------------
        // 1) Lê o body JSON enviado pelo Postman (raw / application/json)
        // -----------------------------------------------------------------
        // Body vem como JSON (ex.: Postman -> raw JSON).
        $body = $request->getContent();
        $data = json_decode($body, true);
        if (! is_array($data)) {
            $data = [];
        }

        $email    = $data['email']    ?? null;
        $password = $data['password'] ?? null;

        // 400 = payload inválido (faltando campos obrigatórios)
        if (empty($email) || empty($password)) {
            return new ApiProblemResponse(new ApiProblem(400, 'Email e password são obrigatórios.'));
        }

        // -----------------------------------------------------------------
        // 2) Busca o usuário no banco pelo email (via LoginTable)
        // -----------------------------------------------------------------
        // 1) AuthN (autenticação): descobrir “quem é você?” (email/senha)
        $user = $this->loginTable->findByEmail($email);

        if (! $user) {
            return new ApiProblemResponse(new ApiProblem(401, 'Credenciais inválidas.'));
        }

        // -----------------------------------------------------------------
        // 3) Verifica a senha (hash bcrypt gravado na coluna `password`)
        // -----------------------------------------------------------------
        // 401 = credenciais inválidas
        // password_verify() é o padrão certo para comparar com o hash salvo no banco.
        if (! isset($user['password']) || ! is_string($user['password']) || ! password_verify($password, $user['password'])) {
            return new ApiProblemResponse(new ApiProblem(401, 'Credenciais inválidas.'));
        }

        // 2) Gera token forte e aleatório (NUNCA use md5(time()) / rand()).
        // 32 bytes => 64 chars hex.
        $token     = bin2hex(random_bytes(32));
        $expiresAt = (new \DateTimeImmutable('now'))->add(new \DateInterval('PT' . self::TOKEN_TTL_SECONDS . 'S'));

        // 3) Persistimos o token no DB, para cada request futura validar via banco.
        $ok = $this->loginTable->createToken((int) $user['id'], $token, $expiresAt);
        if (! $ok) {
            return new ApiProblemResponse(new ApiProblem(500, 'Falha ao gerar token.'));
        }

        // 4) Cliente guarda o token e manda em toda request:
        // Authorization: Bearer <token>
        return new JsonModel([
            'token' => $token,
            'expires_at' => $expiresAt->format(DATE_ATOM),
        ]);
    }
}
