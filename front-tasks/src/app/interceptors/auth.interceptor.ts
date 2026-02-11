import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';

// Interceptor: roda ANTES de cada request HTTP.
// Aqui a gente anexa o header Authorization: Bearer <token>
// em qualquer chamada (ex: /tasks).
//
// TRÁFEGO DO DADO (token em requests protegidas):
// 1) (Front) TasksService.list/create/update/delete chama HttpClient em /tasks...
// 2) (Front) authInterceptor roda e lê AuthService.getToken()
// 3) (Front->Back) Request vai com header: Authorization: Bearer <token>
// 4) (Back) TokenAuthenticationAdapter::authenticate() extrai o token do header
// 5) (Back->DB) SELECT tokens WHERE token = :token (e valida expires_at)
// 6) (Back) identidade autenticada (user_id) fica disponível para o Resource
export const authInterceptor: HttpInterceptorFn = (req, next) => {
    const auth = inject(AuthService);
    const token = auth.getToken();

    // Se não tem token, não mexe na request.
    if (!token) return next(req);

    // Se tem token, clona a request (imutável) adicionando o header.
    return next(
        req.clone({
            setHeaders: {
                Authorization: `Bearer ${token}`
            }
        })
    );
};
