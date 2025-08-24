#!/usr/bin/env node
const fs = require('fs'), path = require('path'), crypto = require('crypto');
const distDir = path.join(process.cwd(), 'dist'), dataDir = path.join(distDir, 'data');
if (!fs.existsSync(distDir)) { console.error('[gen-manifest] dist/ missing'); process.exit(1); }
if (!fs.existsSync(dataDir)) fs.mkdirSync(dataDir, { recursive: true });
const files = fs.readdirSync(dataDir).filter(f => f.endsWith('.json') && f !== 'manifest.json').sort();
const hash = buf => crypto.createHash('sha1').update(buf).digest('hex').slice(0,12);
const manifest = { version: Date.now(), files: {} };
for (const f of files) manifest.files[`/data/${f}`] = hash(fs.readFileSync(path.join(dataDir,f)));
fs.writeFileSync(path.join(dataDir, 'manifest.json'), JSON.stringify(manifest));
const swSrc = path.join(process.cwd(),'scripts','sw.js'), swDest = path.join(distDir,'sw.js');
if (fs.existsSync(swSrc)) fs.copyFileSync(swSrc, swDest);
console.log('[gen-manifest] wrote data/manifest.json and copied sw.js');
