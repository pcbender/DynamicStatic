import fs from 'node:fs';
import path from 'node:path';
import { marked } from 'marked';
import crypto from 'node:crypto';

/**
 * Renders content from a structured ContentJobPayload into HTML files
 * and updates site metadata (navigation, articles index, etc.)
 * 
 * Expected payload structure matches ContentJobPayload schema from openapi.json
 * 
 * Usage from GitHub Actions:
 *   node scripts/render.js /tmp/article.json
 */

const DIST_DIR = 'dist';
const TEMPLATES_DIR = 'templates';
const DATA_DIR = path.join(DIST_DIR, 'data');

function ensureDirectories() {
  [DIST_DIR, DATA_DIR, path.join(DIST_DIR, 'articles'), path.join(DIST_DIR, 'posts')].forEach(dir => {
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
  });
}

function loadTemplate(templateName) {
  const templatePath = path.join(TEMPLATES_DIR, `${templateName}.html`);
  if (fs.existsSync(templatePath)) {
    return fs.readFileSync(templatePath, 'utf8');
  }
  // Fallback to article template
  const fallbackPath = path.join(TEMPLATES_DIR, 'article-template.html');
  if (fs.existsSync(fallbackPath)) {
    return fs.readFileSync(fallbackPath, 'utf8');
  }
  throw new Error(`Template not found: ${templateName} (and no article-template.html fallback)`);
}

// Decode and persist base64 data URI assets (images) to dist/assets and rewrite URL
function materializeAssets(assets) {
  if (!assets) return;
  const assetsDir = path.join(DIST_DIR, 'assets');
  if (!fs.existsSync(assetsDir)) fs.mkdirSync(assetsDir, { recursive: true });
  assets.forEach(a => {
    if (!a || typeof a !== 'object') return;
    if (!a.url || typeof a.url !== 'string') return;
    if (a.url.startsWith('data:')) {
      // data:[<mediatype>][;base64],<data>
      const match = a.url.match(/^data:([^;]+);base64,(.+)$/);
      if (!match) return;
      const mime = match[1];
      const b64 = match[2];
      let ext = 'bin';
      if (mime === 'image/png') ext = 'png';
      else if (mime === 'image/jpeg') ext = 'jpg';
      else if (mime === 'image/gif') ext = 'gif';
      else if (mime === 'image/webp') ext = 'webp';
      const safeName = (a.name || ('asset-' + crypto.randomBytes(4).toString('hex'))) // base name
        .replace(/[^A-Za-z0-9_.-]/g, '_')
        .replace(/\.+$/, '');
      const fileName = safeName + '.' + ext;
      const filePath = path.join(assetsDir, fileName);
      try {
        fs.writeFileSync(filePath, Buffer.from(b64, 'base64'));
        a.url = '/assets/' + fileName; // relative public path
      } catch (e) {
        console.warn('Failed to write asset', fileName, e.message);
      }
    }
  });
}

// Replace markdown placeholders ![alt](asset:name) with actual markdown/image or HTML
function replaceAssetPlaceholders(markdown, assets) {
  if (!assets || assets.length === 0) return markdown;
  return markdown.replace(/!\[(.*?)\]\(asset:([^\)]+)\)/g, (m, alt, name) => {
    const asset = assets.find(a => a.name === name || a.name === decodeURIComponent(name));
    if (!asset) return m; // leave placeholder if not found
    asset._usedInline = true;
    if (asset.type === 'image') {
      return `![${alt || asset.alt || ''}](${asset.url})`;
    }
    // Non-image: insert a link
    return `[${alt || asset.name}](${asset.url})`;
  });
}

function renderContent(content, assets) {
  if (content.format === 'html') {
    return content.body;
  }
  // preprocess markdown for asset placeholders
  let md = content.body;
  md = replaceAssetPlaceholders(md, assets);
  return marked.parse(md);
}

// Build HTML blocks for assets not used inline.
function buildRemainingAssetsHtml(assets) {
  if (!assets || assets.length === 0) return { hero: '', gallery: '', attachments: '' };
  let hero = '';
  let galleryItems = '';
  let attachments = '';
  assets.forEach(a => {
    if (a._usedInline) return;
    switch (a.type) {
      case 'image':
        if (a.placement === 'hero') {
          hero += `<div class="hero-image"><img src="${a.url}" alt="${a.alt || ''}" />${a.caption ? `<p class=\"caption\">${a.caption}</p>` : ''}</div>\n`;
        } else if (a.placement === 'gallery') {
          galleryItems += `<div class="gallery-item"><img src="${a.url}" alt="${a.alt || ''}" />${a.caption ? `<p class=\"caption\">${a.caption}</p>` : ''}</div>\n`;
        } else if (a.placement === 'attachment') {
          attachments += `<div class="attachment"><a href="${a.url}" download>${a.name}</a>${a.caption ? `<p class=\"caption\">${a.caption}</p>` : ''}</div>`;
        }
        break;
      case 'video':
        galleryItems += `<div class="video-container"><video controls><source src="${a.url}" type="video/mp4"></video>${a.caption ? `<p class=\"caption\">${a.caption}</p>` : ''}</div>`;
        break;
      case 'document':
        attachments += `<div class="document-link"><a href="${a.url}" target="_blank">${a.name}</a>${a.caption ? `<p class=\"description\">${a.caption}</p>` : ''}</div>`;
        break;
      case 'audio':
        attachments += `<div class="audio-item"><audio controls src="${a.url}"></audio>${a.caption ? `<p class=\"caption\">${a.caption}</p>` : ''}</div>`;
        break;
    }
  });
  const gallery = galleryItems ? `<div class="asset-gallery">${galleryItems}</div>` : '';
  return { hero, gallery, attachments };
}

function updateMenuJson(metadata, contentType, filename) {
  const menuPath = path.join(DATA_DIR, 'menu.json');
  let menu = [];
  
  try {
    if (fs.existsSync(menuPath)) {
      menu = JSON.parse(fs.readFileSync(menuPath, 'utf8'));
    }
  } catch (err) {
    console.warn('Could not read menu.json, starting fresh:', err.message);
  }
  
  const url = `/${contentType}/${filename}`;
  const menuItem = {
    name: metadata.title,
    href: url,
    active: false
  };
  
  // Add or update menu item
  const existingIndex = menu.findIndex(item => item.href === url);
  if (existingIndex >= 0) {
    menu[existingIndex] = { ...menu[existingIndex], ...menuItem };
  } else {
    menu.push(menuItem);
  }
  
  fs.writeFileSync(menuPath, JSON.stringify(menu, null, 2));
}

function updateArticlesJson(payload, contentType, filename) {
  const articlesPath = path.join(DATA_DIR, 'articles.json');
  let articles = [];
  
  try {
    if (fs.existsSync(articlesPath)) {
      articles = JSON.parse(fs.readFileSync(articlesPath, 'utf8'));
    }
  } catch (err) {
    console.warn('Could not read articles.json, starting fresh:', err.message);
  }
  
  const url = `/${contentType}/${filename}`;
  const articleData = {
    title: payload.metadata.title,
    url,
    description: payload.metadata.description || payload.content.excerpt || '',
    tags: payload.metadata.tags || [],
    category: payload.metadata.category || contentType,
    author: payload.metadata.author || '',
    publishDate: payload.metadata.publishDate || new Date().toISOString(),
    template: payload.metadata.template || 'article-template'
  };
  
  // Add SEO data if present
  if (payload.seo) {
    articleData.seo = payload.seo;
  }
  
  const existingIndex = articles.findIndex(article => article.url === url);
  if (existingIndex >= 0) {
    articles[existingIndex] = { ...articles[existingIndex], ...articleData };
  } else {
    articles.push(articleData);
  }
  
  fs.writeFileSync(articlesPath, JSON.stringify(articles, null, 2));
}

function buildSearchIndex(payload, contentType, filename) {
  const searchPath = path.join(DATA_DIR, 'search.json');
  let searchIndex = [];
  
  try {
    if (fs.existsSync(searchPath)) {
      searchIndex = JSON.parse(fs.readFileSync(searchPath, 'utf8'));
    }
  } catch (err) {
    console.warn('Could not read search.json, starting fresh:', err.message);
  }
  
  const url = `/${contentType}/${filename}`;
  const searchEntry = {
    title: payload.metadata.title,
    url,
    content: payload.content.body.substring(0, 500), // First 500 chars for search
    tags: payload.metadata.tags || [],
    category: payload.metadata.category || contentType
  };
  
  const existingIndex = searchIndex.findIndex(entry => entry.url === url);
  if (existingIndex >= 0) {
    searchIndex[existingIndex] = searchEntry;
  } else {
    searchIndex.push(searchEntry);
  }
  
  fs.writeFileSync(searchPath, JSON.stringify(searchIndex, null, 2));
}

function generateSEOMetaTags(payload) {
  const seo = payload.seo || {};
  const metadata = payload.metadata;
  
  return `
    <meta name="description" content="${seo.metaDescription || metadata.description || ''}">
    <meta name="keywords" content="${(seo.keywords || metadata.tags || []).join(', ')}">
    <meta name="author" content="${metadata.author || ''}">
    <meta property="og:title" content="${metadata.title}">
    <meta property="og:description" content="${seo.metaDescription || metadata.description || ''}">
    <meta property="og:type" content="article">
    ${seo.canonicalUrl ? `<link rel="canonical" href="${seo.canonicalUrl}">` : ''}
  `.trim();
}

async function renderArticle(payload) {
  ensureDirectories();
  
  const contentType = payload.type || 'articles';
  const filename = payload.deployment.filename;
  const template = loadTemplate(payload.metadata.template || 'article-template');
  
  // Render the main content
  // Prepare assets (decode base64, etc.)
  materializeAssets(payload.assets);

  const contentHtml = renderContent(payload.content, payload.assets);

  const { hero, gallery, attachments } = buildRemainingAssetsHtml(payload.assets);
  
  // Generate SEO meta tags
  const seoTags = generateSEOMetaTags(payload);
  
  // Replace template placeholders
  let html = template
    .replace(/\{\{title\}\}/g, payload.metadata.title)
    .replace(/\{\{content\}\}/g, contentHtml)
  .replace(/\{\{assets\}\}/g, hero + gallery + attachments)
    .replace(/\{\{seo-meta\}\}/g, seoTags)
    .replace(/\{\{description\}\}/g, payload.metadata.description || '')
    .replace(/\{\{author\}\}/g, payload.metadata.author || '')
    .replace(/\{\{publishDate\}\}/g, payload.metadata.publishDate || '');
  
  // Handle legacy template format for backward compatibility
  if (html.includes('<!-- Write your article content here')) {
    html = html.replace(
      /<!-- Write your article content here[^>]*-->/,
      contentHtml
    );
  }
  
  // Write HTML file
  const outputDir = path.join(DIST_DIR, contentType);
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }
  
  const outputPath = path.join(outputDir, filename);
  fs.writeFileSync(outputPath, html);
  
  // Update metadata files
  updateMenuJson(payload.metadata, contentType, filename);
  updateArticlesJson(payload, contentType, filename);
  buildSearchIndex(payload, contentType, filename);
  
  // Output metadata for GitHub Actions (GitHub Actions can read this)
  console.log(`::set-output name=title::${payload.metadata.title}`);
  console.log(`::set-output name=filename::${filename}`);
  console.log(`::set-output name=type::${contentType}`);
  
  return {
    outputPath,
    title: payload.metadata.title,
    filename,
    type: contentType
  };
}

// CLI usage
if (import.meta.url === `file://${process.argv[1]}`) {
  const payloadFile = process.argv[2];
  
  if (!payloadFile) {
    console.error('Usage: node scripts/render.js <payload-file>');
    process.exit(1);
  }
  
  if (!fs.existsSync(payloadFile)) {
    console.error(`Payload file not found: ${payloadFile}`);
    process.exit(1);
  }
  
  try {
    const payload = JSON.parse(fs.readFileSync(payloadFile, 'utf8'));
    
    // Validate basic structure
    if (!payload.metadata || !payload.content || !payload.deployment) {
      throw new Error('Invalid payload structure: missing required fields (metadata, content, deployment)');
    }
    
    console.log('Rendering article:', payload.metadata.title);
    const result = await renderArticle(payload);
    console.log('Successfully rendered to:', result.outputPath);
    
  } catch (error) {
    console.error('Error rendering article:', error.message);
    process.exit(1);
  }
}

export { renderArticle, processAssets, updateMenuJson, updateArticlesJson };
