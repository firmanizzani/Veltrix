import '../css/app.css';
import '../css/workspace.css';
import '@fontsource-variable/inter';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AppLayout from './layouts/AppLayout';
import { PwaProvider } from './components/pwa';
import type { ComponentType, ReactNode } from 'react';

const pages = import.meta.glob('./pages/*.tsx');
createInertiaApp({
    title: () => 'VELTRIX',
    resolve: async (name) => {
        const page = (await pages[`./pages/${name}.tsx`]()) as {
            default: ComponentType<any> & { layout?: (page: ReactNode) => ReactNode };
        };
        if (name !== 'Auth' && name !== 'Error' && name !== 'Landing') {
            page.default.layout =
                page.default.layout ?? ((page) => <AppLayout>{page}</AppLayout>);
        }
        return page.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <PwaProvider>
                <App {...props} />
            </PwaProvider>,
        );
    },
    progress: {
        delay: 700,
        color: '#166348',
        includeCSS: false,
        showSpinner: false,
        popover: false,
    },
});
