#!/usr/bin/env node
// Relocated OpusProcessor
import fs from 'node:fs';
import path from 'node:path';
import { marked } from 'marked';
import simpleGit from 'simple-git';
import { Octokit } from '@octokit/rest';

export const DEFAULT_STORE = process.env.TRACKING_ID_FILE || 'tracking-id.txt';
export function storeTrackingId(id, store=DEFAULT_STORE){ fs.writeFileSync(store,String(id),'utf8'); }
export function readTrackingId(store=DEFAULT_STORE){ try { return fs.readFileSync(store,'utf8').trim(); } catch { return null; } }

export async function processContentPayload(payload, templatePath, { distDir='dist', articlesData='dist/data/articles.json', repo='.' }={}) {
  if(!payload.metadata || !payload.content || !payload.deployment) throw new Error('Invalid payload: missing required fields (metadata, content, deployment)');
  let htmlBody = payload.content.format==='html' ? payload.content.body : marked.parse(payload.content.body);
  const template = fs.readFileSync(templatePath,'utf8');
  let merged = template
    .replace(/\{\{title\}\}/g,payload.metadata.title)
    .replace(/\{\{content\}\}/g,htmlBody)
    .replace(/\{\{description\}\}/g,payload.metadata.description||'')
    .replace(/\{\{author\}\}/g,payload.metadata.author||'');
  if(merged.includes('<!-- Write your article content here')) merged = merged.replace(/<!-- Write your article content here[^>]*-->/, htmlBody);
  const contentType = payload.type || 'articles';
  const filename = payload.deployment.filename;
  const outputDir = path.join(distDir, contentType); if(!fs.existsSync(outputDir)) fs.mkdirSync(outputDir,{recursive:true});
  const outputPath = path.join(outputDir, filename); fs.writeFileSync(outputPath, merged,'utf8');
  let articles=[]; try { if(fs.existsSync(articlesData)) articles=JSON.parse(fs.readFileSync(articlesData,'utf8')); } catch {}
  const url = `/${contentType}/${filename}`;
  const articleData = { title:payload.metadata.title, url, description:payload.metadata.description||payload.content.excerpt||'', tags:payload.metadata.tags||[], category:payload.metadata.category||contentType, author:payload.metadata.author||'', publishDate:payload.metadata.publishDate||new Date().toISOString() };
  const existing = articles.findIndex(a=>a.url===url); if(existing>=0) articles[existing]={ ...articles[existing], ...articleData }; else articles.push(articleData);
  try { fs.writeFileSync(articlesData, JSON.stringify(articles,null,2)); } catch {}
  const git = simpleGit(repo); await git.add([outputPath, articlesData]); await git.commit(`Publish ${filename} - ${payload.metadata.title}`);
  if(process.env.WEAVER_CREATE_PR==='1'||process.env.WEAVER_CREATE_PR==='true') {
    const baseBranch = process.env.BASE_BRANCH || 'main';
    const jobId = payload.job_id || payload.deployment.job_id || Date.now().toString();
    let branchNameBase = `dynstatic/${jobId}`.replace(/[^A-Za-z0-9_\-\/]/g,'-').slice(0,60);
    let branchName = branchNameBase;
    try { await git.fetch(); await git.checkout(baseBranch); await git.pull('origin', baseBranch); await git.checkoutLocalBranch(branchName); } catch { branchName = `${branchNameBase}-${Math.random().toString(36).slice(2,6)}`; await git.checkout(baseBranch); await git.checkoutLocalBranch(branchName); }
    await git.add([outputPath, articlesData]); await git.commit(`Content publish for job ${jobId}`); await git.push('origin', branchName, { '--set-upstream': null });
    const token = process.env.GITHUB_TOKEN; if(token){ const octokit=new Octokit({ auth:token }); const repoSlug=payload.deployment.repository || process.env.GITHUB_REPOSITORY || ''; const [owner, repository] = repoSlug.split('/'); if(owner && repository){ try { await octokit.rest.pulls.create({ owner, repo:repository, title:`Content publish: ${payload.metadata.title}`, head:branchName, base:baseBranch, body:`Automated content publish for job ${jobId}.\n\nContains file: ${outputPath}` }); } catch(prErr){ console.warn('PR creation failed:', prErr.message); } } }
  }
  return outputPath;
}

export async function processMarkdownFile(markdownPath, templatePath, { distDir='dist', articlesData='dist/data/articles.json', repo='.' }={}) {
  const md = fs.readFileSync(markdownPath,'utf8');
  const titleMatch = md.match(/^#\s+(.*)/); const title = titleMatch ? titleMatch[1].trim() : path.basename(markdownPath,'.md');
  const outputName = `${path.basename(markdownPath).replace(/\.md$/, '')}.html`;
  const payload = { type:'articles', metadata:{ title, description:'', tags:[], template:'article-template' }, content:{ format:'markdown', body:md }, deployment:{ repository:'legacy/conversion', filename:outputName } };
  return processContentPayload(payload, templatePath, { distDir, articlesData, repo });
}

if (import.meta.url === `file://${process.argv[1]}`){ const arg=process.argv[2]||''; if(arg.startsWith('--store=')){ storeTrackingId(arg.split('=')[1]); } else if(arg==='--read'){ const id=readTrackingId(); if(id) console.log(id); } }
