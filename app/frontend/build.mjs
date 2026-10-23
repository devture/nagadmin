import { cp, mkdir, readdir, readFile, writeFile } from 'node:fs/promises';
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

// Chosen 1.8.7 uses $.trim, which jQuery 4 removed. Patch only its string-valued
// search field and regenerate the minified entry point loaded by comploader.
const chosenPath = join(output, 'vendor/chosen/chosen.jquery.js');
const chosenSource = await readFile(chosenPath, 'utf8');
const chosenSearch = '$.trim(this.get_search_field_value())';
if (chosenSource.split(chosenSearch).length !== 2) {
	throw new Error('Review the Chosen jQuery 4 compatibility patch for this version');
}
const chosenPatched = chosenSource.replace(chosenSearch, 'this.get_search_field_value().trim()');
await writeFile(chosenPath, chosenPatched);
await build({
	stdin: { contents: chosenPatched },
	minify: true,
	outfile: join(output, 'vendor/chosen/chosen.jquery.min.js'),
});

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
