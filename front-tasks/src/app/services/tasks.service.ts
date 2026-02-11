import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { map, Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Task {
    id?: number;
    title: string;
    description?: string | null;
    status?: string | null;
    user_id?: number;
}

type HalCollection<T> = {
    _embedded?: Record<string, T[]>;
    total_items?: number;
    page_size?: number;
};

@Injectable({ providedIn: 'root' })
export class TasksService {
    private readonly http = inject(HttpClient);

    // GET /tasks
    // O Laminas API Tools geralmente responde em HAL:
    // { _embedded: { tasks: [...] }, total_items, page_size, ... }
    // Aqui extraímos só a lista de tasks.
    //
    // TRÁFEGO DO DADO (listar):
    // TasksPageComponent.load()
    //   -> TasksService.list()
    //     -> HttpClient GET /tasks (com Bearer via interceptor)
    //       -> (Back) TasksResource::fetchAll()
    //         -> SELECT tasks WHERE user_id = <do token>
    list(): Observable<Task[]> {
        return this.http.get<HalCollection<Task>>(this.url('/tasks')).pipe(
            map((res) => res._embedded?.['tasks'] ?? [])
        );
    }

    // POST /tasks
    // O backend cria e retorna a task criada.
    //
    // TRÁFEGO DO DADO (criar):
    // TasksPageComponent.create() (editId == null)
    //   -> TasksService.create(payload)
    //     -> HttpClient POST /tasks (com Bearer via interceptor)
    //       -> (Back) TasksResource::create()
    //         -> server injeta user_id a partir do token
    //         -> INSERT em tasks
    create(payload: Omit<Task, 'id'>): Observable<Task> {
        return this.http.post<Task>(this.url('/tasks'), payload);
    }

    // PATCH /tasks/:id
    // Usamos PATCH porque geralmente é mais natural para “alterar só alguns campos”
    // (ex.: trocar apenas o status, sem reenviar tudo).
    //
    // TRÁFEGO DO DADO (editar/status):
    // TasksPageComponent.create() (editId != null) OU toggleStatus()
    //   -> TasksService.update(id, payload)
    //     -> HttpClient PATCH /tasks/:id (com Bearer)
    //       -> (Back) TasksResource::update()
    //         -> UPDATE tasks SET ... WHERE id = :id AND user_id = <do token>
    update(id: number, payload: Partial<Omit<Task, 'id'>>): Observable<Task> {
        return this.http.patch<Task>(this.url(`/tasks/${id}`), payload);
    }

    // DELETE /tasks/:id
    //
    // TRÁFEGO DO DADO (excluir):
    // TasksPageComponent.remove(task)
    //   -> TasksService.delete(id)
    //     -> HttpClient DELETE /tasks/:id (com Bearer)
    //       -> (Back) TasksResource::delete()
    //         -> DELETE FROM tasks WHERE id = :id AND user_id = <do token>
    delete(id: number): Observable<void> {
        return this.http.delete<void>(this.url(`/tasks/${id}`));
    }

    // Mesmo helper de URL do AuthService.
    private url(path: string): string {
        const base = (environment.apiBaseUrl || '').replace(/\/$/, '');
        return base ? `${base}${path}` : path;
    }
}
