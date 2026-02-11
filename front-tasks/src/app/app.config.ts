import { ApplicationConfig } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';

import { routes } from './app.routes';
import { provideClientHydration } from '@angular/platform-browser';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { authInterceptor } from './interceptors/auth.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    // Rotas do app (login, tasks, etc.)
    provideRouter(routes),

    // HttpClient para fazer chamadas REST.
    // withInterceptors: intercepta toda request e injeta Authorization: Bearer <token>
    provideHttpClient(withInterceptors([authInterceptor])),

    // SSR/hydration já vem nesse projeto; mantemos como estava.
    provideClientHydration(),
    provideAnimationsAsync()
  ]
};
