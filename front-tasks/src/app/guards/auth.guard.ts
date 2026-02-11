import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';

// Guard: decide se a navegação pode acontecer.
// Se não tiver token, manda para /login.
export const authGuard: CanActivateFn = () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    if (auth.isLoggedIn()) return true;
    router.navigateByUrl('/login');
    return false;
};
