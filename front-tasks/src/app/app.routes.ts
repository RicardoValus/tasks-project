import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';
import { LoginPageComponent } from './pages/login/login-page.component';
import { TasksPageComponent } from './pages/tasks/tasks-page.component';

// Rotas do app.
// A ideia: login é público, tasks é protegido.
export const routes: Routes = [
    { path: '', pathMatch: 'full', redirectTo: 'login' },
    { path: 'login', component: LoginPageComponent },

    // Só entra em /tasks se tiver token salvo.
    { path: 'tasks', component: TasksPageComponent, canActivate: [authGuard] },
    { path: '**', redirectTo: 'login' }
];
