import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const root = new URL('../../', import.meta.url);
const bootstrapPath = new URL('resources/views/components/layouts/install-bootstrap.blade.php', root);
const controllerPath = new URL('resources/js/mobile-app.js', root);

async function createHarness(options = {}) {
    const bootstrap = await readFile(bootstrapPath, 'utf8');
    const controllerSource = await readFile(controllerPath, 'utf8');
    const inlineScript = bootstrap.match(/<script>([\s\S]*?)<\/script>/)?.[1];
    assert.ok(inlineScript, 'the actual inline install bootstrap must exist');

    const listeners = new Map();
    const documentListeners = new Map();
    const controls = {
        install: { hidden: true, disabled: false },
        help: { hidden: false },
        wrapper: { hidden: false },
    };
    const document = {
        querySelector(selector) {
            return ({
                '[data-app-install]': controls.install,
                '[data-app-install-help]': controls.help,
                '[data-app-install-controls]': controls.wrapper,
            })[selector] ?? null;
        },
        addEventListener(type, listener) {
            const registered = documentListeners.get(type) ?? [];
            registered.push(listener);
            documentListeners.set(type, registered);
        },
        dispatchEvent(event) {
            for (const listener of documentListeners.get(event.type) ?? []) listener(event);
        },
    };
    const window = {
        matchMedia: () => ({ matches: Boolean(options.standalone) }),
        addEventListener(type, listener) {
            const registered = listeners.get(type) ?? [];
            registered.push(listener);
            listeners.set(type, registered);
        },
        dispatchEvent(event) {
            for (const listener of listeners.get(event.type) ?? []) listener(event);
        },
    };
    const context = {
        window,
        document,
        navigator: { standalone: Boolean(options.legacyStandalone) },
        CustomEvent: class CustomEvent {
            constructor(type, init = {}) { this.type = type; this.detail = init.detail; }
        },
        Event: class Event { constructor(type) { this.type = type; } },
        console,
        Promise,
        setTimeout,
        clearTimeout,
    };
    vm.runInNewContext(inlineScript, context, { filename: bootstrapPath.pathname });
    const earlyEvent = options.earlyEvent ? installEvent() : null;
    if (earlyEvent) window.dispatchEvent(Object.assign(earlyEvent, { type: 'beforeinstallprompt' }));
    const moduleUrl = `data:text/javascript,${encodeURIComponent(controllerSource)}`;
    const controller = await import(moduleUrl);
    const dispose = controller.mountMobileApp({ window, document, navigator: context.navigator });

    return {
        controls,
        document,
        dispose,
        documentListeners,
        earlyEvent,
        listeners,
        state: window.__dyhreneMobileApp,
        window,
    };
}

function installEvent() {
    return {
        prevented: false,
        promptCalls: 0,
        preventDefault() { this.prevented = true; },
        async prompt() { this.promptCalls += 1; return this.result; },
        result: { outcome: 'accepted' },
    };
}

test('captures an early event without suppressing browser promotion', async () => {
    const harness = await createHarness({ earlyEvent: true });
    assert.equal(harness.earlyEvent.prevented, false);
    assert.equal(harness.state.pendingInstallEvent, harness.earlyEvent);
});

test('prevents a late event only after custom controls are ready', async () => {
    const harness = await createHarness();
    assert.equal(harness.state.customUiReady, true);
    const event = installEvent();
    harness.window.dispatchEvent(Object.assign(event, { type: 'beforeinstallprompt' }));
    assert.equal(event.prevented, true);
    assert.equal(harness.controls.install.hidden, false);
});

test('consumes accepted and dismissed prompts; duplicate clicks cannot prompt twice', async () => {
    for (const outcome of ['accepted', 'dismissed']) {
        const harness = await createHarness();
        const event = installEvent();
        event.result = { outcome };
        harness.window.dispatchEvent(Object.assign(event, { type: 'beforeinstallprompt' }));
        const click = { target: { closest: () => ({ matches: () => true }) } };
        await Promise.all([
            harness.documentListeners.get('click')[0](click),
            harness.documentListeners.get('click')[0](click),
        ]);
        assert.equal(event.promptCalls, 1);
        assert.equal(harness.state.pendingInstallEvent, null);
        assert.equal(harness.state.prompting, false);
    }
});

test('clears a rejected prompt safely and keeps manual help reachable', async () => {
    const harness = await createHarness();
    const event = installEvent();
    event.prompt = async () => { throw new Error('prompt rejected'); };
    harness.window.dispatchEvent(Object.assign(event, { type: 'beforeinstallprompt' }));
    await harness.documentListeners.get('click')[0]({ target: { closest: () => ({ matches: () => true }) } });
    assert.equal(harness.state.pendingInstallEvent, null);
    assert.equal(harness.controls.install.hidden, true);
    assert.equal(harness.controls.help.hidden, false);
});

test('appinstalled clears prompt state and hides all installation actions', async () => {
    const harness = await createHarness();
    const event = installEvent();
    harness.window.dispatchEvent(Object.assign(event, { type: 'beforeinstallprompt' }));
    harness.window.dispatchEvent({ type: 'appinstalled' });
    assert.equal(harness.state.pendingInstallEvent, null);
    assert.equal(harness.state.installed, true);
    assert.equal(harness.state.customUiReady, false);
    assert.equal(harness.controls.wrapper.hidden, true);
});

test('standalone matchMedia and legacy iOS standalone hide install UI', async () => {
    for (const options of [{ standalone: true }, { legacyStandalone: true }]) {
        const harness = await createHarness(options);
        assert.equal(harness.state.customUiReady, false);
        assert.equal(harness.controls.wrapper.hidden, true);
    }
});

test('pageshow and repeated Livewire navigation do not multiply global listeners', async () => {
    const harness = await createHarness();
    harness.window.dispatchEvent({ type: 'livewire:navigating' });
    assert.equal(harness.state.customUiReady, false);
    harness.window.dispatchEvent({ type: 'livewire:navigated' });
    assert.equal(harness.state.customUiReady, true);
    const eventTypes = ['pageshow', 'livewire:navigating', 'livewire:navigated'];
    for (let index = 0; index < 4; index += 1) {
        for (const type of eventTypes) harness.window.dispatchEvent({ type });
        harness.document.dispatchEvent({ type: 'livewire:navigated' });
    }
    assert.equal(harness.listeners.get('beforeinstallprompt').length, 1);
    assert.equal(harness.listeners.get('appinstalled').length, 1);
    assert.equal(harness.documentListeners.get('click').length, 1);
    assert.equal(harness.documentListeners.get('livewire:navigating').length, 1);
    assert.equal(harness.documentListeners.get('livewire:navigated').length, 1);
});

test('full-document navigation starts with no retained browser prompt event', async () => {
    const firstDocument = await createHarness();
    const event = installEvent();
    firstDocument.window.dispatchEvent(Object.assign(event, { type: 'beforeinstallprompt' }));
    const nextDocument = await createHarness();
    assert.equal(nextDocument.state.pendingInstallEvent, null);
    assert.notEqual(nextDocument.state, firstDocument.state);
});
