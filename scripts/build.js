#!/usr/bin/env node
// Wrapper to call actual build script in tools/scripts/scripts/build.js
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const target = path.resolve(__dirname, '../tools/scripts/scripts/build.js');
import(pathToFileURL(target).href);
