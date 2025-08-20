#!/usr/bin/env node
// Generate a content hash manifest for JSON data files and copy the service worker.
// Produces dist/data/manifest.json with structure:
// {
//   generatedAt: ISO string,
//   version: sha256 of concatenated file hashes,
//   files: { "fileName.json": "<sha256>" }
// }
// Also copies scripts/sw.js -> dist/sw.js (does not transform).

import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

function sha256(buf) {
  return crypto.createHash('sha256').update(buf).digest('hex');
}

function ensureDir(p) {
  if (!fs.existsSync(p)) fs.mkdirSync(p, { recursive: true });
}

function main() {
  const root = process.cwd();
  const dataDir = path.join(root, 'dist', 'data');
  if (!fs.existsSync(dataDir)) {
    console.error('[gen-manifest] data directory not found:', dataDir);
    process.exit(1);
  }
  const manifestPath = path.join(dataDir, 'manifest.json');

  const entries = fs.readdirSync(dataDir)
    .filter(f => f.endsWith('.json') && f !== 'manifest.json');

  if (entries.length === 0) {
    console.warn('[gen-manifest] No JSON data files found in', dataDir);
  }

  const files = {};
  const concatenated = [];
  for (const file of entries.sort()) {
    const full = path.join(dataDir, file);
    const content = fs.readFileSync(full);
    const hash = sha256(content);
    files[file] = hash;
    concatenated.push(file + ':' + hash);
  }
  const version = sha256(concatenated.join('\n'));

  const manifest = {
    generatedAt: new Date().toISOString(),
    version,
    files
  };
  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
  console.log('[gen-manifest] Wrote', manifestPath, 'version', version);

  // Copy service worker
  const swSrc = path.join(root, 'scripts', 'sw.js');
  if (!fs.existsSync(swSrc)) {
    console.error('[gen-manifest] Service worker source missing at', swSrc);
  } else {
    const swDest = path.join(root, 'dist', 'sw.js');
    ensureDir(path.dirname(swDest));
    fs.copyFileSync(swSrc, swDest);
    console.log('[gen-manifest] Copied service worker to', swDest);
  }
}

if (import.meta.url === `file://${process.argv[1]}`) {
  try {
    main();
  } catch (err) {
    console.error('[gen-manifest] Failed:', err);
    process.exit(1);
  }
}
