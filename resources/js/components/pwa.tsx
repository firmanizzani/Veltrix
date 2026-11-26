import {
    createContext,
    useContext,
    useEffect,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { Download } from 'lucide-react';
import { Button } from './ui/button';
import { Dialog } from './ui/dialog';
import '../../css/pwa.css';

type InstallPrompt = Event & {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

const InstallContext = createContext<{
    installed: boolean;
    install: () => Promise<void>;
} | null>(null);

export function PwaProvider({ children }: { children: ReactNode }) {
    const [prompt, setPrompt] = useState<InstallPrompt | null>(null);
    const [installed, setInstalled] = useState(false);
    const [instructions, setInstructions] = useState(false);
    const [offline, setOffline] = useState(!navigator.onLine);
    const [update, setUpdate] = useState<ServiceWorkerRegistration | null>(null);
    const [updating, setUpdating] = useState(false);
    const reloadAfterUpdate = useRef(false);
    const isAppleMobile =
        /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    useEffect(() => {
        const display = window.matchMedia('(display-mode: standalone)');
        const syncDisplay = () =>
            setInstalled(
                display.matches ||
                    Boolean(
                        (navigator as Navigator & { standalone?: boolean }).standalone,
                    ),
            );
        const capturePrompt = (event: Event) => {
            event.preventDefault();
            setPrompt(event as InstallPrompt);
        };
        const markInstalled = () => {
            setInstalled(true);
            setPrompt(null);
            setInstructions(false);
        };
        const syncConnection = () => setOffline(!navigator.onLine);
        syncDisplay();
        display.addEventListener('change', syncDisplay);
        window.addEventListener('beforeinstallprompt', capturePrompt);
        window.addEventListener('appinstalled', markInstalled);
        window.addEventListener('online', syncConnection);
        window.addEventListener('offline', syncConnection);

        return () => {
            display.removeEventListener('change', syncDisplay);
            window.removeEventListener('beforeinstallprompt', capturePrompt);
            window.removeEventListener('appinstalled', markInstalled);
            window.removeEventListener('online', syncConnection);
            window.removeEventListener('offline', syncConnection);
        };
    }, []);

    useEffect(() => {
        if (!('serviceWorker' in navigator) || !window.isSecureContext) {
            return;
        }
        let active = true;
        let registration: ServiceWorkerRegistration | undefined;
        let installing: ServiceWorker | null = null;
        const checkWaiting = () => {
            if (active && registration?.waiting && navigator.serviceWorker.controller) {
                setUpdate(registration);
            }
        };
        const watchUpdate = () => {
            installing?.removeEventListener('statechange', checkWaiting);
            installing = registration?.installing ?? null;
            installing?.addEventListener('statechange', checkWaiting);
        };
        const register = () => {
            navigator.serviceWorker
                .register('/sw.js', { updateViaCache: 'none' })
                .then((result) => {
                    if (!active) {
                        return;
                    }
                    registration = result;
                    checkWaiting();
                    watchUpdate();
                    result.addEventListener('updatefound', watchUpdate);
                })
                .catch((error: unknown) => {
                    console.warn('VELTRIX: PWA registration is unavailable.', error);
                });
        };
        const controllerChanged = () => {
            if (reloadAfterUpdate.current) {
                window.location.reload();
            }
        };
        navigator.serviceWorker.addEventListener('controllerchange', controllerChanged);
        if (document.readyState === 'complete') {
            register();
        } else {
            window.addEventListener('load', register, { once: true });
        }
        return () => {
            active = false;
            window.removeEventListener('load', register);
            registration?.removeEventListener('updatefound', watchUpdate);
            installing?.removeEventListener('statechange', checkWaiting);
            navigator.serviceWorker.removeEventListener(
                'controllerchange',
                controllerChanged,
            );
        };
    }, []);

    async function install() {
        if (!prompt) {
            setInstructions(true);
            return;
        }
        try {
            await prompt.prompt();
            await prompt.userChoice;
        } catch {
            setInstructions(true);
        } finally {
            setPrompt(null);
        }
    }

    return (
        <InstallContext.Provider value={{ installed, install }}>
            {children}
            <div className="pwa-notices">
                {offline && (
                    <div
                        className="pwa-notice"
                        role="status"
                    >
                        Koneksi terputus. Transaksi dan data terbaru memerlukan internet.
                    </div>
                )}
                {update && (
                    <div className="pwa-notice">
                        <p role="status">
                            Versi baru tersedia. Selesaikan pekerjaan sebelum memperbarui.
                        </p>
                        <Button
                            size="sm"
                            disabled={updating}
                            onClick={() => {
                                if (update.waiting) {
                                    reloadAfterUpdate.current = true;
                                    setUpdating(true);
                                    update.waiting.postMessage({ type: 'SKIP_WAITING' });
                                }
                            }}
                        >
                            {updating ? 'Memperbarui…' : 'Perbarui aplikasi'}
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setUpdate(null)}
                        >
                            Nanti
                        </Button>
                    </div>
                )}
            </div>
            <Dialog
                open={instructions}
                onOpenChange={setInstructions}
                title="Instal VELTRIX"
                description="Buka ruang kerja langsung dari layar utama atau desktop Anda."
            >
                <div className="pwa-install-instructions">
                    {isAppleMobile ? (
                        <p>
                            Buka halaman ini di Safari, ketuk Bagikan, lalu pilih
                            Tambahkan ke Layar Utama dan Tambah.
                        </p>
                    ) : (
                        <p>
                            Di Chrome atau Edge, buka menu browser dan pilih Instal
                            VELTRIX atau Instal halaman sebagai aplikasi. Pada Android,
                            pilihan ini juga dapat muncul sebagai Tambahkan ke layar
                            utama.
                        </p>
                    )}
                    <p>
                        Jika pilihan instal belum muncul, muat ulang halaman setelah
                        terhubung ke internet. Untuk browser lain, gunakan Chrome, Edge,
                        atau Safari pada iPhone/iPad.
                    </p>
                    <p className="muted">
                        Transaksi, stok, dan laporan tetap memerlukan koneksi internet.
                    </p>
                </div>
                <div className="dialog-footer">
                    <Button onClick={() => setInstructions(false)}>Mengerti</Button>
                </div>
            </Dialog>
        </InstallContext.Provider>
    );
}

export function PwaInstallButton({ className }: { className?: string }) {
    const context = useContext(InstallContext);
    if (!context || context.installed) {
        return null;
    }
    return (
        <Button
            type="button"
            variant="outline"
            className={className}
            onClick={context.install}
        >
            <Download
                size={17}
                aria-hidden="true"
            />
            Instal VELTRIX
        </Button>
    );
}
