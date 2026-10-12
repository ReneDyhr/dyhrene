// Dependency-free DevTools probe for the mobile shell/login geometry.
// Load this file in the browser console after navigating to each route.
(() => {
    const rect = (element) => {
        if (!element) return null;
        const bounds = element.getBoundingClientRect();
        const style = getComputedStyle(element);
        return {
            x: bounds.x,
            y: bounds.y,
            width: bounds.width,
            height: bounds.height,
            position: style.position,
            heightCss: style.height,
            minHeight: style.minHeight,
            paddingBottom: style.paddingBottom,
            overflowY: style.overflowY,
        };
    };
    const navigation = document.querySelector('.mobilenav');
    const login = document.querySelector('.login-page');
    const save = [...document.querySelectorAll(
        '.login-page button[type="submit"], .register-page button[type="submit"], .sheet button[type="submit"]',
    )].map((button) => ({
        text: button.innerText.trim(),
        bounds: rect(button),
        intersectsNavigation: Boolean(navigation && button.getBoundingClientRect().bottom > navigation.getBoundingClientRect().top),
    }));
    const result = {
        route: location.pathname,
        viewport: { width: innerWidth, height: innerHeight, visualHeight: visualViewport?.height ?? null },
        document: {
            clientWidth: document.documentElement.clientWidth,
            clientHeight: document.documentElement.clientHeight,
            scrollWidth: document.documentElement.scrollWidth,
            scrollHeight: document.documentElement.scrollHeight,
        },
        safeArea: {
            top: getComputedStyle(document.documentElement).getPropertyValue('--probe-safe-top'),
            bottom: getComputedStyle(navigation ?? document.body).paddingBottom,
            viewportFit: document.querySelector('meta[name="viewport"]')?.content ?? null,
        },
        login: rect(login),
        loginBox: rect(document.querySelector('.login-box')),
        navigation: rect(navigation),
        sheet: rect(document.querySelector('.sheet')),
        save,
        hardwareKeyboardAndNativeSelect: 'not detectable by this probe; test on device',
        pinchZoom: 'must be tested manually on device',
    };
    console.table(result);
    return result;
})();
