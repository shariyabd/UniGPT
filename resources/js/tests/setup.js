// Global stubs for the Ziggy `route()` helper used throughout the components.
// In the app this is injected globally; under test we provide a deterministic
// stand-in so components can render without the real Ziggy runtime.

const route = (name, params) => {
    if (name === undefined) {
        // route().current('x') style calls
        return { current: () => false };
    }

    return params !== undefined && params !== null ? `/${name}/${params}` : `/${name}`;
};

route.current = () => false;

globalThis.route = route;
