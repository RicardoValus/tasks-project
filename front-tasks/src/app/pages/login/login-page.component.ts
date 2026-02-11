import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { finalize } from 'rxjs';

import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';

import { AuthService } from '../../services/auth.service';

@Component({
    selector: 'app-login-page',
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule,
        MatCardModule,
        MatFormFieldModule,
        MatInputModule,
        MatButtonModule
    ],
    template: `
    <div class="page">
      <mat-card class="card">
        <mat-card-title>Login</mat-card-title>
        <mat-card-content>
          <form [formGroup]="form" (ngSubmit)="submit()" class="form">
            <mat-form-field appearance="outline">
              <mat-label>Email</mat-label>
              <input matInput type="email" formControlName="email" autocomplete="email" />
            </mat-form-field>

            <mat-form-field appearance="outline">
              <mat-label>Senha</mat-label>
              <input matInput type="password" formControlName="password" autocomplete="current-password" />
            </mat-form-field>

            <button mat-raised-button color="primary" type="submit" [disabled]="form.invalid || loading">
              Entrar
            </button>

            <p class="error" *ngIf="error">{{ error }}</p>
          </form>
        </mat-card-content>
      </mat-card>
    </div>
  `,
    styles: [
        `
      .page { min-height: 100vh; display: grid; place-items: center; padding: 16px; }
      .card { width: 100%; max-width: 420px; }
      .form { display: grid; gap: 12px; margin-top: 12px; }
      .error { margin: 8px 0 0; color: #b00020; }
    `
    ]
})
export class LoginPageComponent {
    private readonly fb = inject(FormBuilder);
    private readonly auth = inject(AuthService);
    private readonly router = inject(Router);

    loading = false;
    error = '';

    // Formulário reativo: Angular controla estado/validação.
    form = this.fb.group({
        email: ['', [Validators.required, Validators.email]],
        password: ['', [Validators.required]]
    });

    // Ao submeter: chama AuthService.login().
    // Se der certo, o token é salvo e navegamos para /tasks.
    //
    // TRÁFEGO DO DADO (ponta a ponta, login):
    // 1) (Front) LoginPageComponent.submit()
    // 2) (Front) AuthService.login(payload)
    // 3) (Front) HttpClient POST /login
    // 4) (Dev) proxy.conf.json encaminha /login -> http://127.0.0.1:8080/login
    // 5) (Back) Router /login -> Task\V1\Rpc\Login\LoginController::loginAction()
    // 6) (Back) LoginTable::findByEmail(email) -> SELECT users WHERE email = :email
    // 7) (Back) password_verify(password, user.password)
    // 8) (Back) LoginTable::createToken(user_id, token, expires_at)
    //            -> INSERT tokens (user_id, token, expires_at)
    // 9) (Back->Front) Resposta JSON { token, expires_at }
    // 10) (Front) AuthService.setToken(token) -> memória + localStorage
    // 11) (Front) Router.navigateByUrl('/tasks')
    submit(): void {
        if (this.form.invalid || this.loading) return;

        this.error = '';
        this.loading = true;

        const email = this.form.value.email ?? '';
        const password = this.form.value.password ?? '';

        this.auth
            .login({ email, password })
            .pipe(finalize(() => (this.loading = false)))
            .subscribe({
                next: () => this.router.navigateByUrl('/tasks'),
                error: (err) => {
                    // A API costuma devolver error em formatos variados.
                    // Aqui tentamos pegar message do body; se não tiver, usamos fallback.
                    const msg = err?.error?.message || err?.message;
                    this.error = msg ? String(msg) : 'Falha no login (verifique email/senha).';
                }
            });
    }
}
