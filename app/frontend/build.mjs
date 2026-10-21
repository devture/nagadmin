import { cp, mkdir, readdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import process from 'node:process';

const source = dirname(fileURLToPath(import.meta.url));
// bin/node mounts sources at /app/src and output at /app/build.
const output = process.env.FRONTEND_OUTPUT_DIR || join(source, '../public/assets/frontend');
const modules = process.env.FRONTEND_NODE_MODULES || join(source, 'node_modules');
const { build, context } = await import(pathToFileURL(join(modules, 'esbuild/lib/main.js')));

// Keep package-owned assets together, including font and sprite paths relative to CSS.
for (const [from, to] of [
	['jquery/dist/jquery.min.js', 'vendor/jquery.min.js'],
	['bootstrap/dist/css/bootstrap.min.css', 'vendor/bootstrap/bootstrap.min.css'],
	['bootstrap/dist/js/bootstrap.bundle.min.js', 'vendor/bootstrap/bootstrap.bundle.min.js'],
	['chosen-js', 'vendor/chosen'],
	['@fortawesome/fontawesome-free/css', 'vendor/fontawesome/css'],
	['@fortawesome/fontawesome-free/webfonts', 'vendor/fontawesome/webfonts'],
]) {
	await mkdir(dirname(join(output, to)), { recursive: true });
	await cp(join(modules, from), join(output, to), { recursive: true });
}

// Build the official packages into one shared runtime for comploader and all components.
await build({
	stdin: {
		contents: "import * as React from 'react'; import { createRoot } from 'react-dom/client'; globalThis.React = React; globalThis.ReactDOM = { createRoot };",
		resolveDir: dirname(modules),
	},
	bundle: true,
	minify: true,
	define: { 'process.env.NODE_ENV': '"production"' },
	outfile: join(output, 'vendor/react.js'),
});

const options = {
	absWorkingDir: source,
	entryPoints: (await readdir(source)).filter((name) => /\.[jt]sx$/.test(name)),
	bundle: true,
	minify: true,
	sourcemap: true,
	external: ['react'],
	outdir: output,
};

if (process.argv.includes('--watch')) {
	const watcher = await context(options);
	await watcher.watch();
} else {
	await build(options);
}
