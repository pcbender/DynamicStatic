import fs from 'node:fs';
import path from 'node:path';
import { marked } from 'marked';
import simpleGit from 'simple-git';

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

/**
 * Convert a markdown file into HTML using a template and commit the result.
 *
 * @param {string} markdownPath - Path to the markdown source file.
 * @param {string} templatePath - Path to the HTML template file.
 * @param {object} [options]
 * @param {string} [options.distDir='dist'] - Directory to write the output HTML.
 * @param {string} [options.articlesData='dist/data/articles.json'] - Articles metadata file to update.
 * @param {string} [options.repo='.'] - Path to git repository root.
 * @returns {Promise<string>} Resolved with the path to the generated HTML file.
 */
export async function processMarkdownFile(
  markdownPath,
  templatePath,
  { distDir = 'dist', articlesData = 'dist/data/articles.json', repo = '.' } = {}
) {
  const md = fs.readFileSync(markdownPath, 'utf8');
  const htmlBody = marked.parse(md);

  const template = fs.readFileSync(templatePath, 'utf8');
  const merged = template.replace(
    '<!-- Write your article content here. Use IDs on headings to link them to the table of contents. -->',
    htmlBody
  );

  const outputName = `${path.basename(markdownPath).replace(/\.md$/, '')}.html`;
  const outputPath = path.join(distDir, outputName);
  fs.writeFileSync(outputPath, merged, 'utf8');

  // Update articles metadata
  let articles = [];
  try {
    if (fs.existsSync(articlesData)) {
      articles = JSON.parse(fs.readFileSync(articlesData, 'utf8'));
    }
  } catch (err) {
    console.warn('Could not read articles data file', err);
  }

  const titleMatch = md.match(/^#\s+(.*)/);
  const title = titleMatch ? titleMatch[1].trim() : outputName;
  const url = `/${outputName}`;
  const existing = articles.findIndex(a => a.url === url);
  const entry = { title, url };
  if (existing >= 0) {
    articles[existing] = { ...articles[existing], ...entry };
  } else {
    articles.push(entry);
  }
  try {
    fs.writeFileSync(articlesData, JSON.stringify(articles, null, 2));
  } catch (err) {
    console.warn('Could not update articles data file', err);
  }

  const git = simpleGit(repo);
  await git.add([outputPath, articlesData]);
  await git.commit(`Publish ${outputName} from markdown`);
  return outputPath;
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
