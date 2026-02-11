// Centraliza configurações do frontend.
// Dica: em projetos reais você costuma ter:
// - environment.ts (dev)
// - environment.prod.ts (prod)
// Aqui deixamos simples: quando apiBaseUrl = '' usamos o proxy do ng serve.
export const environment = {
    // Se vazio (''): o app chama '/login' e '/tasks' e o proxy.conf.json encaminha.
    // Se você quiser chamar a API direto (sem proxy), coloque 'http://127.0.0.1:8080'.
    apiBaseUrl: '',

    // Chave onde salvamos o token no localStorage (somente no browser).
    tokenStorageKey: 'task_api_token'
};
