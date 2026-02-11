import { HttpClient } from '@angular/common/http';
import { Injectable, PLATFORM_ID, inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

export interface LoginRequest {
    email: string;
    password: string;
}

export interface LoginResponse {
    token: string;
    expires_at?: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
    private readonly http = inject(HttpClient);
    private readonly platformId = inject(PLATFORM_ID);

    // Cache em memória para evitar ficar lendo localStorage toda hora.
    private inMemoryToken: string | null = null;

    // Retorna o token atual (ou null).
    // Importante: em SSR (server-side rendering), não existe localStorage.
    // Por isso checamos isPlatformBrowser.
    getToken(): string | null {
        if (this.inMemoryToken) return this.inMemoryToken;
        if (!isPlatformBrowser(this.platformId)) return null;
        const token = localStorage.getItem(environment.tokenStorageKey);
        this.inMemoryToken = token;
        return token;
    }

    // “Estou logado?” (para UI e Guard). É uma checagem simples.
    isLoggedIn(): boolean {
        return !!this.getToken();
    }

    // Faz POST /login e salva o token que vier na resposta.
    // O backend deve devolver { token: "..." }.
    //
    // TRÁFEGO DO DADO (login, do ponto de vista do service):
    // LoginPageComponent.submit()
    //   -> AuthService.login(payload)
    //     -> HttpClient.post('/login', payload)
    //       -> (proxy ng serve) /login -> backend :8080
    //         -> LoginController::loginAction()
    //           -> LoginTable::findByEmail() (users)
    //           -> LoginTable::createToken() (tokens)
    //         <- { token }
    //     -> tap(res => AuthService.setToken(res.token))
    // Token salvo aqui passa a ser usado pelo authInterceptor nas próximas requests (/tasks).
    login(payload: LoginRequest): Observable<LoginResponse> {
        return this.http.post<LoginResponse>(this.url('/login'), payload).pipe(
            tap((res) => this.setToken(res.token))
        );
    }

    // Limpa token (logout “frontend-only”).
    logout(): void {
        this.inMemoryToken = null;
        if (isPlatformBrowser(this.platformId)) {
            localStorage.removeItem(environment.tokenStorageKey);
        }
    }

    // Centraliza o “onde guardar” o token.
    private setToken(token: string): void {
        this.inMemoryToken = token;
        if (isPlatformBrowser(this.platformId)) {
            localStorage.setItem(environment.tokenStorageKey, token);
        }
    }

    // Monta a URL final.
    // Quando apiBaseUrl = '' => retorna só o path (ex: '/login') para o proxy cuidar.
    private url(path: string): string {
        const base = (environment.apiBaseUrl || '').replace(/\/$/, '');
        return base ? `${base}${path}` : path;
    }
}
