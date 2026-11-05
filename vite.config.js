import fs from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

const adminRoot = path.resolve('resources/admin');

/**
 * O painel (código vindo do Able) importa a partir de resources/admin, como no
 * tsconfig baseUrl: 'components/MainCard', 'config'... Só se aplica a ficheiros
 * do próprio painel, para não interferir com pacotes do node_modules.
 */
const adminImports = () => {
    const roots = new Set(fs.readdirSync(adminRoot).map((entry) => entry.replace(/\.tsx?$/, '')));

    return {
        name: 'acb:admin-imports',
        enforce: 'pre',
        async resolveId(source, importer, options) {
            if (!importer || !path.resolve(importer).startsWith(adminRoot) || !roots.has(source.split('/')[0])) {
                return null;
            }

            return this.resolve(path.join(adminRoot, source), importer, { ...options, skipSelf: true });
        },
    };
};

/**
 * Os ícones iconsax-react (usados pelo Able) definem a cor e o tamanho por
 * omissão com defaultProps, que o React 19 deixou de aplicar a componentes
 * de função: sem isto os ícones ficam sem traço nem preenchimento.
 */
const iconsaxDefaults = () => ({
    name: 'acb:iconsax-defaults',
    transform(code, id) {
        if (!/iconsax-react[\\/]dist[\\/]esm[\\/]/.test(id)) {
            return null;
        }

        return code
            .replace(/(\bcolor = )(_ref\d+)\.color\b/g, "$1$2.color === undefined ? 'currentColor' : $2.color")
            .replace(/(\bsize = )(_ref\d+)\.size\b/g, "$1$2.size === undefined ? '24' : $2.size");
    },
});

/**
 * O plugin de fontes gera um @font-face por formato (woff2 e woff) e o
 * browser acaba por descarregar os dois. Todos os browsers actuais lêem
 * woff2, por isso as regras e os ficheiros .woff são retirados do build.
 */
const withoutWoff = (css) => css.replace(/@font-face\s*\{[^}]*format\("woff"\)[^}]*\}\s*/g, '');

const woff2Only = () => ({
    name: 'acb:woff2-only',
    apply: 'build',
    enforce: 'post',
    generateBundle(_options, bundle) {
        for (const fileName of Object.keys(bundle)) {
            if (fileName.endsWith('.woff')) {
                delete bundle[fileName];
            }
        }
    },
    // O CSS das fontes e o manifesto usado por @fonts são escritos no fim do build.
    closeBundle() {
        const dir = path.resolve('public/build');
        const manifestPath = path.join(dir, 'fonts-manifest.json');

        if (!fs.existsSync(manifestPath)) {
            return;
        }

        const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
        const stylesheet = path.join(dir, manifest.style.file);
        fs.writeFileSync(stylesheet, withoutWoff(fs.readFileSync(stylesheet, 'utf8')));

        for (const key of Object.keys(manifest.style.familyStyles ?? {})) {
            manifest.style.familyStyles[key] = withoutWoff(manifest.style.familyStyles[key]);
        }
        fs.writeFileSync(manifestPath, JSON.stringify(manifest));
    },
});

export default defineConfig({
    plugins: [
        woff2Only(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/admin/main.tsx'],
            refresh: true,
            fonts: [
                bunny('Plus Jakarta Sans', {
                    alias: 'body',
                    variable: '--font-jakarta',
                    weights: [400, 500, 600, 700, 800],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400, style: 'normal' }],
                }),
            ],
        }),
        tailwindcss(),
        adminImports(),
        iconsaxDefaults(),
        react(),
    ],
    optimizeDeps: {
        // Em desenvolvimento, sem pré-empacotamento para a correcção iconsaxDefaults se aplicar.
        exclude: ['iconsax-react'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
