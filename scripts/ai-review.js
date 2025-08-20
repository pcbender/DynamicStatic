#!/usr/bin/env node
// Thin wrapper to call the actual AI review script located under tools/scripts/scripts/ai-review.js
// Keeps existing workflow and npm script paths stable.
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const target = path.resolve(__dirname, '../tools/scripts/scripts/ai-review.js');

// Dynamically import the real script.
import(pathToFileURL(target).href);
