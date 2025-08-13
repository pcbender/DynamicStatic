import fs from 'node:fs';
import path from 'node:path';
import { marked } from 'marked';

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

function renderContent(content) {
  if (content.format === 'html') {
    return content.body;
  }
  // Default to markdown
  return marked.parse(content.body);
}

function processAssets(assets, contentType, filename) {
  if (!assets || assets.length === 0) return '';
  
  let assetHtml = '';
  
  assets.forEach(asset => {
    switch (asset.type) {
      case 'image':
        if (asset.placement === 'hero') {
          assetHtml += `<div class="hero-image">
            <img src="${asset.url}" alt="${asset.alt || ''}" />
            ${asset.caption ? `<p class="caption">${asset.caption}</p>` : ''}
          </div>\n`;
        } else if (asset.placement === 'gallery') {
          assetHtml += `<div class="gallery-item">
            <img src="${asset.url}" alt="${asset.alt || ''}" />
            ${asset.caption ? `<p class="caption">${asset.caption}</p>` : ''}
          </div>\n`;
        } else {
          // inline or default
          assetHtml += `<figure class="inline-image">
            <img src="${asset.url}" alt="${asset.alt || ''}" />
            ${asset.caption ? `<figcaption>${asset.caption}</figcaption>` : ''}
          </figure>\n`;
        }
        break;
        
      case 'video':
        assetHtml += `<div class="video-container">
          <video controls>
            <source src="${asset.url}" type="video/mp4">
            Your browser does not support the video tag.
          </video>
          ${asset.caption ? `<p class="caption">${asset.caption}</p>` : ''}
        </div>\n`;
        break;
        
      case 'document':
        assetHtml += `<div class="document-link">
          <a href="${asset.url}" target="_blank">${asset.name}</a>
          ${asset.caption ? `<p class="description">${asset.caption}</p>` : ''}
        </div>\n`;
        break;
    }
  });
  
  return assetHtml;
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
  const contentHtml = renderContent(payload.content);
  
  // Process assets (images, videos, etc.)
  const assetsHtml = processAssets(payload.assets, contentType, filename);
  
  // Generate SEO meta tags
  const seoTags = generateSEOMetaTags(payload);
  
  // Replace template placeholders
  let html = template
    .replace(/\{\{title\}\}/g, payload.metadata.title)
    .replace(/\{\{content\}\}/g, contentHtml)
    .replace(/\{\{assets\}\}/g, assetsHtml)
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
