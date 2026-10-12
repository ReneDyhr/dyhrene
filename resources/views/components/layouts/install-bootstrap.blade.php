<script>
    (function () {
        const key = '__dyhreneMobileApp';
        const state = window[key] || (window[key] = {
            pendingInstallEvent: null,
            customUiReady: false,
            installed: false,
            prompting: false,
            captureBound: false,
        });

        if (state.captureBound) return;

        state.pendingInstallEvent = state.pendingInstallEvent || null;
        state.customUiReady = Boolean(state.customUiReady);
        state.installed = Boolean(state.installed);
        state.prompting = Boolean(state.prompting);
        state.captureBound = true;

        window.addEventListener('beforeinstallprompt', function (event) {
            state.pendingInstallEvent = event;
            if (state.customUiReady && !state.installed) event.preventDefault();
            window.dispatchEvent(new CustomEvent('dyhrene:install-state', { detail: state }));
        }, { capture: true });
    })();
</script>
