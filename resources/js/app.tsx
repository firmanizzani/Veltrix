import '../css/app.css';
import '@fontsource-variable/inter';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AppLayout from './layouts/AppLayout';
import type { ComponentType, ReactNode } from 'react';

const pages = import.meta.glob('./pages/*.tsx');
createInertiaApp({
    title: (title) => `${title} · VELTRIX`,
    resolve: async (name) => {
        const page = (await pages[`./pages/${name}.tsx`]()) as {
            default: ComponentType<any> & { layout?: (page: ReactNode) => ReactNode };
        };
        if (name !== 'Auth' && name !== 'Error') {
            page.default.layout =
                page.default.layout ?? ((page) => <AppLayout>{page}</AppLayout>);
        }
        return page.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: false,
});
