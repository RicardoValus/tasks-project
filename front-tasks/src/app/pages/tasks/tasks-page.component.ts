import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { finalize } from 'rxjs';

import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatListModule } from '@angular/material/list';

import { TasksService, Task } from '../../services/tasks.service';

@Component({
    selector: 'app-tasks-page',
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule,
        MatCardModule,
        MatFormFieldModule,
        MatInputModule,
        MatSelectModule,
        MatButtonModule,
        MatListModule
    ],
    template: `
    <div class="page">
      <mat-card class="card">
        <mat-card-title>Tarefas</mat-card-title>
        <mat-card-content>
          <form [formGroup]="form" (ngSubmit)="create()" class="form">
            <mat-form-field appearance="outline">
              <mat-label>Título</mat-label>
              <input matInput formControlName="title" />
            </mat-form-field>

            <mat-form-field appearance="outline">
              <mat-label>Descrição</mat-label>
              <textarea matInput rows="3" formControlName="description"></textarea>
            </mat-form-field>

            <mat-form-field appearance="outline">
              <mat-label>Status</mat-label>
              <mat-select formControlName="status">
                <!--
                  IMPORTANTE (Dia 6): esses valores precisam bater com o banco.
                  No seu MariaDB, a coluna tasks.status é ENUM:
                  'pendente' | 'em andamento' | 'concluída'
                  Se mandar qualquer outro texto (ex.: pending/done), o MySQL/MariaDB
                  rejeita com "Data truncated" e a API vira 500.
                -->
                <mat-option value="pendente">pendente</mat-option>
                <mat-option value="em andamento">em andamento</mat-option>
                <mat-option value="concluída">concluída</mat-option>
              </mat-select>
            </mat-form-field>

            <div class="actions">
              <button mat-raised-button color="primary" type="submit" [disabled]="form.invalid || creating">
                {{ editId ? 'Salvar' : 'Criar tarefa' }}
              </button>

              <button *ngIf="editId" mat-stroked-button type="button" (click)="cancelEdit()" [disabled]="creating">
                Cancelar
              </button>
            </div>

            <p class="error" *ngIf="error">{{ error }}</p>
          </form>

          <div class="list-header">
            <h3>Lista</h3>
            <button mat-stroked-button type="button" (click)="load()" [disabled]="loading">Recarregar</button>
          </div>

          <mat-list *ngIf="tasks.length; else empty">
            <mat-list-item *ngFor="let t of tasks">
              <div matListItemTitle>{{ t.title }}</div>
              <div matListItemLine>
                {{ t.description || '-' }}
              </div>
              <div matListItemLine>
                status: {{ t.status || '-' }}
              </div>

              <div matListItemMeta class="item-actions">
                <button mat-stroked-button type="button" (click)="startEdit(t)">Editar</button>
                <button mat-stroked-button type="button" (click)="toggleStatus(t)">
                  Avançar status
                </button>
                <button mat-stroked-button type="button" (click)="remove(t)">Excluir</button>
              </div>
            </mat-list-item>
          </mat-list>

          <ng-template #empty>
            <p class="muted">Nenhuma tarefa encontrada.</p>
          </ng-template>
        </mat-card-content>
      </mat-card>
    </div>
  `,
    styles: [
        `
      .page { min-height: 100vh; display: grid; place-items: start center; padding: 16px; }
      .card { width: 100%; max-width: 720px; }
      .form { display: grid; gap: 12px; margin-top: 12px; }
      .actions { display: flex; gap: 8px; align-items: center; }
      .list-header { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; }
      .muted { opacity: 0.75; }
      .error { margin: 8px 0 0; color: #b00020; }
      .item-actions { display: flex; gap: 8px; align-items: center; }
    `
    ]
})
export class TasksPageComponent {
    private readonly tasksService = inject(TasksService);
    private readonly fb = inject(FormBuilder);

    tasks: Task[] = [];
    loading = false;
    creating = false;
    error = '';

    // Quando editId != null, o formulário vira “modo edição”.
    // A mesma tela faz CREATE e UPDATE, mudando só o comportamento do submit.
    editId: number | null = null;

    // Form para criar/editar task.
    // Observação: no Dia 5 o foco é consumir API; por isso mantemos simples.
    form = this.fb.group({
        title: ['', [Validators.required]],
        description: [''],
        // Dia 6: o status precisa ser exatamente um dos valores do ENUM no banco.
        status: ['pendente', [Validators.required]]
    });

    constructor() {
        // Carrega a lista assim que a página abre.
        this.load();
    }

    // GET /tasks -> atualiza this.tasks
    //
    // TRÁFEGO DO DADO (listar tasks):
    // 1) (Front/UI) TasksPageComponent.load()
    // 2) (Front) TasksService.list() -> HttpClient GET /tasks
    // 3) (Front) authInterceptor injeta Authorization: Bearer <token>
    // 4) (Back) TokenAuthenticationAdapter valida token consultando tabela tokens
    // 5) (Back) TasksResource::fetchAll() filtra por user_id do token
    // 6) (DB) SELECT tasks WHERE user_id = :userId
    // 7) (Back->Front) HAL JSON com _embedded.tasks
    // 8) (Front) TasksService.list() extrai _embedded['tasks']
    load(): void {
        if (this.loading) return;
        this.error = '';
        this.loading = true;

        this.tasksService
            .list()
            .pipe(finalize(() => (this.loading = false)))
            .subscribe({
                next: (items) => (this.tasks = items),
                error: (err) => {
                    // Se o token estiver inválido/expirado, o backend pode retornar 401.
                    const msg = err?.error?.message || err?.message;
                    this.error = msg ? String(msg) : 'Falha ao carregar tarefas.';
                }
            });
    }

    // Submit do formulário:
    // - se editId = null => cria
    // - se editId != null => atualiza
    //
    // TRÁFEGO DO DADO (criar/editar):
    // 1) (Front/UI) usuário preenche form e envia (ngSubmit)
    // 2) (Front) TasksPageComponent.create()
    // 3) (Front) decide:
    //    - POST /tasks (create) quando editId == null
    //    - PATCH /tasks/:id (update) quando editId != null
    // 4) (Front) authInterceptor injeta Bearer token
    // 5) (Back) TokenAuthenticationAdapter valida token (SELECT tokens)
    // 6) (Back) TasksResource:
    //    - create(): injeta user_id e created_at, depois INSERT tasks
    //    - update(): UPDATE tasks WHERE id = :id AND user_id = :user
    // 7) (Front) ao sucesso: reseta form e chama load() para refletir no UI
    create(): void {
        if (this.form.invalid || this.creating) return;
        this.error = '';
        this.creating = true;

        const payload = {
            title: this.form.value.title ?? '',
            description: this.form.value.description ?? null,
            status: this.form.value.status ?? 'pendente'
        };

        const done = () => {
            this.form.reset({ title: '', description: '', status: 'pendente' });
            this.editId = null;
            this.load();
        };

        // Se estamos editando, fazemos PATCH /tasks/:id.
        // Caso contrário, fazemos POST /tasks.
        const request$ = this.editId
            ? this.tasksService.update(this.editId, payload)
            : this.tasksService.create(payload);

        request$
            .pipe(finalize(() => (this.creating = false)))
            .subscribe({
                next: () => done(),
                error: (err) => {
                    const msg = err?.error?.detail || err?.error?.message || err?.message;
                    // A mensagem 422 “Failed Validation” costuma cair aqui.
                    this.error = msg ? String(msg) : (this.editId ? 'Falha ao editar tarefa.' : 'Falha ao criar tarefa.');
                }
            });
    }

    // Coloca o formulário em modo edição (preenche campos a partir da task clicada).
    startEdit(task: Task): void {
        this.editId = task.id ?? null;
        this.error = '';

        this.form.patchValue({
            title: task.title,
            description: task.description ?? '',
            status: (task.status as any) ?? 'pendente'
        });
    }

    // Sai do modo edição e volta para “criar nova task”.
    cancelEdit(): void {
        this.editId = null;
        this.error = '';
        this.form.reset({ title: '', description: '', status: 'pendente' });
    }

    // Toggle simples de status.
    // Aqui fazemos um “ciclo” pelos 3 estados do ENUM do banco:
    // pendente -> em andamento -> concluída -> pendente
    //
    // TRÁFEGO DO DADO (status):
    // toggleStatus(task)
    //   -> TasksService.update(id, { status })
    //     -> PATCH /tasks/:id
    //       -> TasksResource::update()
    //         -> UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user
    toggleStatus(task: Task): void {
        if (!task.id) return;

        const current = task.status ?? 'pendente';
        const nextStatus = current === 'pendente'
            ? 'em andamento'
            : current === 'em andamento'
                ? 'concluída'
                : 'pendente';

        this.tasksService.update(task.id, { status: nextStatus }).subscribe({
            next: () => this.load(),
            error: (err) => {
                const msg = err?.error?.detail || err?.error?.message || err?.message;
                this.error = msg ? String(msg) : 'Falha ao atualizar status.';
            }
        });
    }

    // Exclui uma task.
    //
    // TRÁFEGO DO DADO (excluir):
    // remove(task)
    //   -> TasksService.delete(id)
    //     -> DELETE /tasks/:id
    //       -> TasksResource::delete()
    //         -> DELETE FROM tasks WHERE id = :id AND user_id = :user
    remove(task: Task): void {
        if (!task.id) return;

        this.tasksService.delete(task.id).subscribe({
            next: () => this.load(),
            error: (err) => {
                const msg = err?.error?.detail || err?.error?.message || err?.message;
                this.error = msg ? String(msg) : 'Falha ao excluir tarefa.';
            }
        });
    }
}
