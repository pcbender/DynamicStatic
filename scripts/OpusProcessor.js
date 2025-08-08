import fs from 'node:fs';

/**
 * Persists the Opus tracking ID across workflow runs.
 *
 * GitHub Actions runners have an ephemeral filesystem, meaning files
 * written during a job vanish once the job completes.  To keep the
 * tracking ID between jobs or separate workflow runs, workflows should
 * upload the store file as an artifact using `actions/upload-artifact`
 * and download it later with `actions/download-artifact`.
 */
export const DEFAULT_STORE = process.env.TRACKING_ID_FILE || 'tracking-id.txt';

export function storeTrackingId(id, store = DEFAULT_STORE) {
  fs.writeFileSync(store, String(id), 'utf8');
}

export function readTrackingId(store = DEFAULT_STORE) {
  try {
    return fs.readFileSync(store, 'utf8').trim();
  } catch {
    return null;
  }
}

// Allow running this module directly for simple CLI usage.
//   node scripts/OpusProcessor.js --store=123
//   node scripts/OpusProcessor.js --read
if (import.meta.url === `file://${process.argv[1]}`) {
  const arg = process.argv[2] || '';
  if (arg.startsWith('--store=')) {
    const id = arg.split('=')[1];
    storeTrackingId(id);
  } else if (arg === '--read') {
    const id = readTrackingId();
    if (id) console.log(id);
  }
}
