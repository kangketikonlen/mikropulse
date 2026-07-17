export function isDark() {
    return document.documentElement.classList.contains('dark');
}

export function subscribeToRouterUpdates(callback) {
    if (!window.Echo) return;
    window.Echo.channel('router-updates')
        .listen('RouterDataUpdated', (e) => {
            callback(e);
        });
}

export function renderEmpty(container, message) {
    if (!container) return;
    container.innerHTML = `<div class="list-item text-gray-400">${message}</div>`;
}
