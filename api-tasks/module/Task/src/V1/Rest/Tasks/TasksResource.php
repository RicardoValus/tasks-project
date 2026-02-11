<?php

declare(strict_types=1);

namespace Task\V1\Rest\Tasks;

use Laminas\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\DbConnectedResource;
use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\Db\Sql\Select;
use Laminas\Paginator\Adapter\DbTableGateway as TableGatewayPaginator;

/**
 * Resource (Dia 6): CRUD completo de tasks, “ponta a ponta”.
 *
 * Por que precisamos de um Resource customizado?
 * - O DB-Connected padrão faz insert/update exatamente com o payload do cliente.
 * - Em um sistema real, o cliente NÃO deveria informar user_id nem created_at.
 * - O user_id deve vir do token (AuthN) e o created_at deve ser do servidor.
 *
 * Além disso, aqui a gente “amarrra” a listagem/edição/exclusão para o usuário logado:
 * - você só enxerga e mexe nas SUAS tasks.
 */
final class TasksResource extends DbConnectedResource
{
    /**
     * Pega o user_id da identidade autenticada (token Bearer).
     *
     * Retorna ApiProblem(401) se não estiver autenticado.
     */
    private function getUserIdOrProblem(): int|ApiProblem
    {
        $identity = $this->getIdentity();

        // Segurança defensiva: a rota /tasks já está protegida,
        // mas ainda assim conferimos aqui.
        if (! $identity || $identity instanceof GuestIdentity) {
            return new ApiProblem(401, 'Unauthorized');
        }

        if (! $identity instanceof AuthenticatedIdentity) {
            return new ApiProblem(401, 'Unauthorized');
        }

        $authData = $identity->getAuthenticationIdentity();
        $userId = (int) ($authData['user_id'] ?? 0);
        if ($userId <= 0) {
            return new ApiProblem(401, 'Unauthorized');
        }

        return $userId;
    }

    /**
     * GET /tasks
     *
     * Retorna apenas tasks do usuário logado.
     *
     * TRÁFEGO DO DADO (listar):
     * (Front) TasksPageComponent.load() -> TasksService.list() -> GET /tasks (Bearer)
     * (Back) TokenAuthenticationAdapter autentica e injeta user_id na identidade
     * (Back) TasksResource::fetchAll() aplica WHERE user_id = <do token>
     * (DB)   SELECT * FROM tasks WHERE user_id = :userId
     */
    public function fetchAll($data = [])
    {
        $userId = $this->getUserIdOrProblem();
        if ($userId instanceof ApiProblem) {
            return $userId;
        }

        // Paginator com filtro: SELECT * FROM tasks WHERE user_id = :user
        $select = new Select($this->table->getTable());
        $select->where(['user_id' => $userId]);

        $adapter = new TableGatewayPaginator($this->table, $select);
        return new $this->collectionClass($adapter);
    }

    /**
     * GET /tasks/:id
     *
     * Garante que o :id pertence ao usuário logado.
     *
     * TRÁFEGO DO DADO (buscar 1):
     * (Back) fetch(id) -> SELECT tasks WHERE id = :id AND user_id = :userId
     */
    public function fetch($id)
    {
        $userId = $this->getUserIdOrProblem();
        if ($userId instanceof ApiProblem) {
            return $userId;
        }

        $resultSet = $this->table->select([
            $this->identifierName => $id,
            'user_id' => $userId,
        ]);

        if (0 === $resultSet->count()) {
            throw new DomainException('Item not found', 404);
        }

        return $resultSet->current();
    }

    /**
     * POST /tasks
     *
     * Regra “empresa”:
     * - user_id vem do token
     * - created_at vem do servidor (ou do DEFAULT do banco)
     *
     * TRÁFEGO DO DADO (criar):
     * (Front) TasksPageComponent.create() (modo criar) -> POST /tasks (Bearer)
     * (Back) create(): retrieveData() valida/filtra, injeta user_id/created_at
     * (DB)   INSERT INTO tasks (..., user_id, created_at)
     * (Back) depois faz fetch(id) para retornar a entidade criada
     */
    public function create($data)
    {
        $userId = $this->getUserIdOrProblem();
        if ($userId instanceof ApiProblem) {
            return $userId;
        }

        // retrieveData() usa o input filter quando existe (validação do API Tools).
        // Isso é bom: aproveitamos trim/strip-tags/string-length etc.
        $values = $this->retrieveData($data);

        // Segurança: não confiamos em user_id/created_at vindos do cliente.
        unset($values['user_id'], $values['created_at']);

        $values['user_id'] = $userId;
        $values['created_at'] = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $this->table->insert($values);
        $id = $this->table->getLastInsertValue();
        return $this->fetch($id);
    }

    /**
     * PATCH/PUT /tasks/:id
     *
     * Atualiza apenas tasks do próprio usuário.
     *
     * TRÁFEGO DO DADO (editar/status):
     * (Front) TasksPageComponent.create() (modo editar) OU toggleStatus()
     *   -> PATCH /tasks/:id (Bearer)
     * (Back) update(): retrieveData() valida/filtra, impede trocar user_id/created_at
     * (DB)   UPDATE tasks SET ... WHERE id = :id AND user_id = :userId
     * (Back) fetch(id) para retornar a entidade atualizada
     */
    public function update($id, $data)
    {
        $userId = $this->getUserIdOrProblem();
        if ($userId instanceof ApiProblem) {
            return $userId;
        }

        $values = $this->retrieveData($data);

        // Não permitimos trocar o dono da task nem o created_at.
        unset($values['user_id'], $values['created_at']);

        $affected = $this->table->update(
            $values,
            [
                $this->identifierName => $id,
                'user_id' => $userId,
            ]
        );

        if ($affected === 0) {
            throw new DomainException('Item not found', 404);
        }

        return $this->fetch($id);
    }

    /**
     * DELETE /tasks/:id
     *
     * TRÁFEGO DO DADO (excluir):
     * (Front) TasksPageComponent.remove() -> DELETE /tasks/:id (Bearer)
     * (Back) delete(id)
     * (DB)   DELETE FROM tasks WHERE id = :id AND user_id = :userId
     */
    public function delete($id)
    {
        $userId = $this->getUserIdOrProblem();
        if ($userId instanceof ApiProblem) {
            return $userId;
        }

        $affected = $this->table->delete([
            $this->identifierName => $id,
            'user_id' => $userId,
        ]);

        if ($affected === 0) {
            throw new DomainException('Item not found', 404);
        }

        return true;
    }
}
