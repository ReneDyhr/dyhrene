const stateKey = '__dyhreneMobileApp';
const mountedWindows = new WeakSet();

export function mountMobileApp(environment = window) {
    const { document, navigator, window: browserWindow = environment } = environment;
    const state = browserWindow[stateKey];
    if (!state || mountedWindows.has(browserWindow)) return () => {};

    mountedWindows.add(browserWindow);
    const media = browserWindow.matchMedia?.('(display-mode: standalone)');
    const isStandalone = () => Boolean(media?.matches || navigator?.standalone || state.installed);

    function sync() {
        const wrapper = document.querySelector('[data-app-install-controls]');
        const button = document.querySelector('[data-app-install]');
        const help = document.querySelector('[data-app-install-help]');
        const standalone = isStandalone();
        state.customUiReady = Boolean(wrapper && button && help && !standalone);

        if (wrapper) wrapper.hidden = standalone;
        if (button) {
            button.hidden = standalone || !state.pendingInstallEvent || state.prompting;
            button.disabled = Boolean(state.prompting);
        }
        if (help) help.hidden = standalone;
    }

    function onInstallState() {
        sync();
    }

    async function onClick(event) {
        const target = event.target?.closest?.('[data-app-install]');
        if (!target || state.prompting || !state.pendingInstallEvent || isStandalone()) return;

        const promptEvent = state.pendingInstallEvent;
        state.pendingInstallEvent = null;
        state.prompting = true;
        sync();
        try {
            await promptEvent.prompt();
            await promptEvent.userChoice;
        } catch {
            // A consumed or rejected browser prompt falls back to the manual instructions.
        } finally {
            state.prompting = false;
            state.pendingInstallEvent = null;
            sync();
        }
    }

    function onInstalled() {
        state.installed = true;
        state.pendingInstallEvent = null;
        state.prompting = false;
        state.customUiReady = false;
        sync();
    }

    function onNavigating() {
        state.customUiReady = false;
        state.pendingInstallEvent = null;
        state.prompting = false;
        const button = document.querySelector('[data-app-install]');
        if (button) {
            button.hidden = true;
            button.disabled = false;
        }
    }

    function onNavigated() {
        sync();
    }

    browserWindow.addEventListener('dyhrene:install-state', onInstallState);
    browserWindow.addEventListener('appinstalled', onInstalled);
    browserWindow.addEventListener('pageshow', sync);
    browserWindow.addEventListener('livewire:navigating', onNavigating);
    browserWindow.addEventListener('livewire:navigated', onNavigated);
    document.addEventListener('click', onClick);
    document.addEventListener('livewire:navigating', onNavigating);
    document.addEventListener('livewire:navigated', onNavigated);
    media?.addEventListener?.('change', sync);
    sync();

    return () => {
        browserWindow.removeEventListener('dyhrene:install-state', onInstallState);
        browserWindow.removeEventListener('appinstalled', onInstalled);
        browserWindow.removeEventListener('pageshow', sync);
        browserWindow.removeEventListener('livewire:navigating', onNavigating);
        browserWindow.removeEventListener('livewire:navigated', onNavigated);
        document.removeEventListener('click', onClick);
        document.removeEventListener('livewire:navigating', onNavigating);
        document.removeEventListener('livewire:navigated', onNavigated);
        media?.removeEventListener?.('change', sync);
        mountedWindows.delete(browserWindow);
    };
}

if (typeof window !== 'undefined' && window.__dyhreneMobileApp) {
    mountMobileApp({ window, document, navigator });
}
