import { cpSync, existsSync, lstatSync, mkdirSync, rmSync } from 'node:fs';
import { dirname, extname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = dirname(fileURLToPath(import.meta.url));
const staticDirectory = join(projectRoot, '.vercel-static');

if (
    dirname(staticDirectory) !== projectRoot ||
    (existsSync(staticDirectory) && lstatSync(staticDirectory).isSymbolicLink())
) {
    throw new Error('Static output must be a directory inside the project.');
}

if (!existsSync(join(projectRoot, 'public/build/manifest.json'))) {
    throw new Error('Build the Vite assets before preparing the Vercel output.');
}

rmSync(staticDirectory, { recursive: true, force: true });
mkdirSync(staticDirectory, { recursive: true });

const publicExtensions = new Set([
    '.css',
    '.js',
    '.json',
    '.woff',
    '.woff2',
    '.ttf',
    '.otf',
    '.jpg',
    '.jpeg',
    '.png',
    '.webp',
    '.avif',
    '.gif',
    '.svg',
    '.ico',
    '.txt',
]);

for (const entry of ['build', 'images', 'favicon.ico', 'favicon.svg', 'robots.txt']) {
    cpSync(join(projectRoot, 'public', entry), join(staticDirectory, entry), {
        recursive: true,
        filter(source) {
            const stats = lstatSync(source);

            return (
                !stats.isSymbolicLink() &&
                (stats.isDirectory() || publicExtensions.has(extname(source)))
            );
        },
    });
}

console.log('Vercel static output prepared with frontend assets and product photos.');
