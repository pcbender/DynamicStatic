#!/usr/bin/env node
// AI Review script (relocated from tools/scripts/scripts/ai-review.js)
import { setFailed, info } from '@actions/core';
import { context, getOctokit } from '@actions/github';
import { OpenAI } from 'openai';
import simpleGit from 'simple-git';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { execSync } from 'child_process';
import { encoding_for_model } from '@dqbd/tiktoken';

// Configuration
const MODE = process.argv.includes('--mode=deep') ? 'deep' : 'light';
const SITE_DIR = (() => {
	const f = process.argv.find(a => a.startsWith('--siteDir='));
	return f ? f.split('=')[1] : '.';
})();
const CREATE_PR = process.argv.includes('--create-pr');
const isLocal = !process.env.GITHUB_ACTIONS;

// Model configuration
const model = 'gpt-4-turbo';
const encoding = encoding_for_model(model);
const maxTokens = 32768; // Max tokens for gpt-4-turbo
const reserveForResponse = 1024;
const promptBudget = maxTokens - reserveForResponse;

// System prompt
const SYSTEM_PROMPT = `
You are an expert reviewer for static sites built with HTML, JSON data files, and Alpine.js.
Return a concise, actionable report with bullet points and clear headings.
When citing, include file and approximate line numbers if present.

Prioritize (in order):
1) Correctness & Safety (broken markup, Alpine directives/x-data, event handling, security: unsafe HTML injection).
2) Accessibility (semantic HTML, labels, contrast cues, keyboard nav, aria-* sanity).
3) Performance for static sites (critical CSS, image sizes, caching hints, script weight, render-blocking, LCP/CLS risk).
4) SEO basics for static (unique titles, meta, canonical, headings structure, alt text).
5) JSON integrity (structure, null/undefined handling; propose schemas; call out content pitfalls).
6) Authoring quality in visible text (spelling/grammar/style) — be brief, suggest edits.
7) Release checklist (for deep mode only): sitemaps/robots, link integrity, 404s, hreflang (if any), Lighthouse/LCP notes.

Output sections (always in this order):
- Summary
- Critical Issues
- Accessibility
- Performance
- SEO
- JSON/Data
- Content (Spelling/Grammar)
- Release Checklist (only include in deep mode)
- Suggested Fixes (short, concrete patch suggestions)
`;

if (isLocal) {
	try { const dotenv = await import('dotenv'); dotenv.config(); } catch {}
	if (!process.env.GITHUB_TOKEN) {
		console.error('ERROR: GITHUB_TOKEN environment variable is required');
		process.exit(1);
	}
}

function readIfExists(p) { try { return fs.readFileSync(p, 'utf8'); } catch { return ''; } }
function countTokens(str) { return encoding.encode(str).length; }

function getContext() {
	if (isLocal) {
		const repoArg = process.argv.find(arg => arg.startsWith('--repo='));
		const repository = repoArg ? repoArg.split('=')[1] : process.env.GITHUB_REPOSITORY;
		if (!repository || !repository.includes('/')) {
			console.error('ERROR: Repository must be specified as owner/repo');
			process.exit(1);
		}
		const [owner, repo] = repository.split('/');
		const prArg = process.argv.find(arg => arg.startsWith('--pr='));
		const prNumber = prArg ? parseInt(prArg.split('=')[1]) : null;
		return { isLocal: true, owner, repo, prNumber, repository: { owner: { login: owner }, name: repo }, payload: prNumber ? { pull_request: { number: prNumber } } : {} };
	}
	return { isLocal: false, owner: context.repo.owner, repo: context.repo.repo, prNumber: context.payload.pull_request?.number, repository: context.payload.repository, payload: context.payload };
}

async function gatherPrDiff(octokit, owner, repo, prNumber, maxChars = 45000) {
	const { data: files } = await octokit.rest.pulls.listFiles({ owner, repo, pull_number: prNumber });
	let bundle = '';
	for (const f of files) {
		if (!['added', 'modified', 'renamed'].includes(f.status)) continue;
		if (!f.patch) continue;
		if (!/\.(html?|json|md|js|css)$/i.test(f.filename)) continue;
		const header = `\n\n--- FILE: ${f.filename} (${f.status}) ---\n`;
		if ((bundle + header + f.patch).length > maxChars) break;
		bundle += header + f.patch;
	}
	return bundle;
}

function collectContextFiles(root = '.', maxFiles = 10) {
	const extensionsToInclude = ['.html', '.json', '.js', '.css', '.yml', '.yaml', '.md', '.txt', '.xml'];
	const foldersToIgnore = ['node_modules', '.git', 'build'];
	const specificFiles = ['package.json', 'README.md', 'robots.txt', 'sitemap.xml', 'index.html'];
	const historyFileName = isLocal ? 'ai-review-history-local.json' : 'ai-review-history.json';
	const reviewDataFile = path.join(root, '.github', historyFileName);
	let currentTokens = 0;
	let reviewHistory = {};
	try { reviewHistory = JSON.parse(fs.readFileSync(reviewDataFile, 'utf8')); } catch { reviewHistory = {}; }
	const files = [];
	function walkDir(dir) {
		try {
			const entries = fs.readdirSync(dir, { withFileTypes: true });
			for (const entry of entries) {
				const fullPath = path.join(dir, entry.name);
				const relativePath = path.relative(root, fullPath);
				if (entry.isDirectory()) { if (!foldersToIgnore.includes(entry.name)) walkDir(fullPath); }
				else {
					const fileName = path.basename(fullPath);
					const ext = path.extname(fullPath).toLowerCase();
					const excludeFiles = ['package-lock.json','yarn.lock','pnpm-lock.yaml'];
						const shouldExclude = excludeFiles.some(pattern => {
							if (pattern.includes('*')) { const regex = new RegExp(pattern.replace(/\*/g, '.*')); return regex.test(fileName); }
							return fileName === pattern;
						});
					if (!shouldExclude && (specificFiles.includes(fileName) || extensionsToInclude.includes(ext))) {
						const stats = fs.statSync(fullPath);
						files.push({ path: relativePath, modifiedTime: stats.mtime.getTime(), lastReviewed: reviewHistory[relativePath] || 0 });
					}
				}
			}
		} catch (err) { console.warn(`Error reading directory ${dir}:`, err.message); }
	}
	walkDir(root);
	files.sort((a,b)=>{ if(a.lastReviewed===0&&b.lastReviewed!==0) return -1; if(a.lastReviewed!==0&&b.lastReviewed===0) return 1; return a.lastReviewed-b.lastReviewed; });
	const parts=[]; const reviewedFiles=[];
	for (const fileInfo of files.slice(0, maxFiles)) {
		const fullPath = path.join(root, fileInfo.path);
		try { const content = fs.readFileSync(fullPath,'utf8'); if (content) { const fileText = `\n--- CONTEXT FILE: ${fileInfo.path.replace(/\\/g,'/')} ---\n${content}`; const fileTokens = countTokens(fileText); if (currentTokens + fileTokens > promptBudget) break; parts.push(fileText); reviewedFiles.push(fileInfo.path); currentTokens += fileTokens; } } catch {}
	}
	const now = Date.now(); reviewedFiles.forEach(f=>{ reviewHistory[f]=now; });
	try { const githubDir = path.join(root,'.github'); if(!fs.existsSync(githubDir)) fs.mkdirSync(githubDir,{recursive:true}); fs.writeFileSync(reviewDataFile, JSON.stringify(reviewHistory,null,2)); } catch {}
	return parts.join('\n');
}

function collectAllRepoFiles(root='.', maxFiles=100){
	const extensionsToInclude=['.html','.js','.css','.json','.md','.yml','.yaml'];
	const foldersToIgnore=['node_modules','.git','dist','build','.next','coverage'];
	const excludeFiles=['package-lock.json','yarn.lock','pnpm-lock.yaml','.env','.env.local','*.min.js','*.min.css','*.map','ai-review-history.json','ai-review-history-local.json'];
	const files=[];
	function walkDir(dir){ try { const entries=fs.readdirSync(dir,{withFileTypes:true}); for(const entry of entries){ const fullPath=path.join(dir,entry.name); const relativePath=path.relative(root,fullPath); if(entry.isDirectory()){ if(!foldersToIgnore.includes(entry.name)) walkDir(fullPath);} else { const ext=path.extname(fullPath).toLowerCase(); const fileName=path.basename(fullPath); const shouldExclude=excludeFiles.some(pattern=>{ if(pattern.includes('*')){ const regex=new RegExp(pattern.replace(/\*/g,'.*')); return regex.test(fileName);} return fileName===pattern;}); if(!shouldExclude&&extensionsToInclude.includes(ext)) files.push(relativePath);} if(files.length>=maxFiles) return;} } catch{} }
	walkDir(root); return files.slice(0,maxFiles);
}

async function createBranch(octokit, branchName, baseBranch='main'){ const ctx=getContext(); try { const { data: refData } = await octokit.rest.git.getRef({ owner:ctx.owner, repo:ctx.repo, ref:`heads/${baseBranch}`}); const baseSha=refData.object.sha; await octokit.rest.git.createRef({ owner:ctx.owner, repo:ctx.repo, ref:`refs/heads/${branchName}`, sha:baseSha }); } catch(error){ if(error.status!==422) throw error; }}

async function commitAndPushChanges(branchName, fixSummaries){ const git=simpleGit(); try { await git.checkout(branchName); await git.add('.'); const commitMessage=`🤖 AI Deep Review Fixes\n\nFixed ${fixSummaries.length} files:\n${fixSummaries.join('\n')}`; await git.commit(commitMessage); await git.push('origin', branchName); } catch(error){ console.error('Error committing changes:', error); throw error; }}

function extractFixedContent(response){ const match=response.match(/FIXED_CONTENT:\s*\n([\s\S]*?)(?=\nSUMMARY:|$)/); return match?match[1].trim():null; }
function extractSummary(response){ const match=response.match(/SUMMARY:\s*\n(.*?)(?:\n|$)/); return match?match[1].trim():'Fixed issues'; }

async function runLightPRReview(){ const ctx=getContext(); const { Octokit } = await import('@octokit/rest'); const octokit=new Octokit({ auth:process.env.GITHUB_TOKEN }); const openai=new OpenAI({ apiKey:process.env.OPENAI_API_KEY }); if(!ctx.prNumber){ console.log('Not a PR, skipping review'); return;} const { data: files } = await octokit.rest.pulls.listFiles({ owner:ctx.owner, repo:ctx.repo, pull_number:ctx.prNumber }); const extensionsToReview=['.js','.html','.css','.json','.md']; const filesToReview=files.filter(file=>extensionsToReview.some(ext=>file.filename.endsWith(ext))); if(filesToReview.length===0){ console.log('No relevant files to review'); return;} const diff=await gatherPrDiff(octokit, ctx.owner, ctx.repo, ctx.prNumber); const prompt=`Review these PR changes and provide actionable feedback:\n\n${diff}\n\nFocus on:\n1. Any bugs or issues\n2. Code quality improvements\n3. Security concerns\n4. Performance suggestions\n\nKeep feedback constructive and specific to the changes made.`; console.log('Calling OpenAI API for PR review...'); const response=await openai.chat.completions.create({ model:model, temperature:0.1, messages:[ { role:'system', content:SYSTEM_PROMPT }, { role:'user', content:prompt } ]}); const reviewBody=response.choices[0].message.content?.trim() || '(no output)'; await octokit.rest.issues.createComment({ owner:ctx.owner, repo:ctx.repo, issue_number:ctx.prNumber, body:`## 🤖 AI Light Review\n\n${reviewBody}`}); console.log('PR review posted successfully'); }

async function runDeepReview(){ const ctx=getContext(); const { Octokit } = await import('@octokit/rest'); const octokit=new Octokit({ auth:process.env.GITHUB_TOKEN }); const openai=new OpenAI({ apiKey:process.env.OPENAI_API_KEY }); console.log('Starting deep review of entire repository...'); const baseBranch=process.env.BASE_BRANCH || 'main'; const branchName=`feature/ai-fixes-${new Date().toISOString().split('T')[0]}`; await createBranch(octokit, branchName, baseBranch); const files=collectAllRepoFiles(SITE_DIR,100); let fixCount=0; const fixSummaries=[]; for(const filePath of files){ console.log(`Reviewing ${filePath}...`); const content=fs.readFileSync(path.join(SITE_DIR,filePath),'utf8'); const prompt=`Review this file and provide the complete fixed version if there are any issues.\n    \nFile: ${filePath}\nContent:\n${content}\n\nIf there are issues, respond with:\nISSUES_FOUND: true\nFIXED_CONTENT:\n[the complete fixed file content]\nSUMMARY:\n[brief description of what was fixed]\n\nIf no issues, respond with:\nISSUES_FOUND: false`; const response=await openai.chat.completions.create({ model:model, temperature:0.1, messages:[ { role:'system', content:SYSTEM_PROMPT }, { role:'user', content:prompt } ]}); const aiResponse=response.choices[0].message.content; if(aiResponse.includes('ISSUES_FOUND: true')){ const fixedContent=extractFixedContent(aiResponse); const summary=extractSummary(aiResponse); if(fixedContent){ fs.writeFileSync(path.join(SITE_DIR,filePath), fixedContent); fixCount++; fixSummaries.push(`- ${filePath}: ${summary}`); console.log(`  ✓ Fixed: ${summary}`); } } else { console.log(`  ✓ No issues found`);} await new Promise(r=>setTimeout(r,1000)); }
	if(fixCount>0){ await commitAndPushChanges(branchName, fixSummaries); const { data: pr } = await octokit.rest.pulls.create({ owner:ctx.owner, repo:ctx.repo, title:`🤖 AI Code Improvements - ${fixCount} files fixed`, head:branchName, base:baseBranch, body:`## AI Deep Review Results\n\nThis PR contains automated fixes for ${fixCount} files.\n\n### Files Fixed:\n${fixSummaries.join('\n')}\n\n### Review Process:\n- Each file was analyzed for code quality, security, and best practices\n- Fixes were applied automatically\n- Please review changes before merging\n\n_Generated by AI Deep Review_`}); console.log(`\nCreated PR #${pr.number} with ${fixCount} fixes`);} else { console.log('\nNo issues found in repository! 🎉'); } }

async function main(){ if(MODE==='deep' && CREATE_PR){ await runDeepReview(); return;} const ctx=getContext(); const isPR=!!ctx.prNumber; if(MODE==='light' && isPR && !isLocal){ await runLightPRReview(); return;} const { Octokit } = await import('@octokit/rest'); const octokit=isLocal ? new Octokit({ auth:process.env.GITHUB_TOKEN }) : getOctokit(process.env.GITHUB_TOKEN); const openai=new OpenAI({ apiKey:process.env.OPENAI_API_KEY }); console.log(`Running AI review in ${MODE} mode`); console.log(`Repository: ${ctx.owner}/${ctx.repo}`); console.log(`Mode: ${isPR ? 'PR' : 'release'} review`); console.log(`Environment: ${isLocal ? 'local' : 'GitHub Actions'}`); let userPrompt=''; if(isPR){ const diff=await gatherPrDiff(octokit, ctx.owner, ctx.repo, ctx.prNumber, MODE==='light'?45000:120000); if(!diff){ console.log('No relevant diff found.'); return;} const contextFiles = MODE==='deep'?collectContextFiles(SITE_DIR):''; userPrompt=`Mode: ${MODE.toUpperCase()}\nReview this PR diff (focus on HTML/JSON/Alpine.js). ${MODE==='light'?'Be brief.':'Be thorough.'}\n\nDIFF:\n${diff}\n\n${contextFiles?`\nPROJECT CONTEXT (snippets):\n${contextFiles}`:''}\n\nIf you flag issues, propose concrete, minimal patches.\nFor Alpine.js, check x-data/x-bind/x-on for reactivity and event safety.`; } else { const contextFiles=collectContextFiles(SITE_DIR); const linkReport=readIfExists('link-report.json'); const htmlValidate=readIfExists('htmlvalidate.json'); const lhSummary=readIfExists('.lighthouseci/lhr-*.json') || ''; userPrompt=`Mode: ${MODE.toUpperCase()}\nThis is a release deep review for a static site (HTML + JSON + Alpine.js).\n\nPROJECT CONTEXT:\n${contextFiles}\n\nLINK CHECK (JSON):\n${linkReport.slice(0,20000)}\n\nHTML VALIDATE (JSON):\n${htmlValidate.slice(0,20000)}\n\nLIGHTHOUSE (if present):\n${lhSummary.slice(0,15000)}`; }
	console.log('\nCalling OpenAI API...'); const resp=await openai.chat.completions.create({ model:model, temperature:0.1, messages:[ { role:'system', content:SYSTEM_PROMPT }, { role:'user', content:userPrompt } ]}); const reviewBody=resp.choices[0].message.content?.trim() || '(no output)'; const title=MODE==='light' ? '💡 AI Light Review' : '🛠️ AI Deep Release Review'; if(isLocal){ console.log('\n'+'='.repeat(80)); console.log(title); console.log('='.repeat(80)); console.log(reviewBody); console.log('='.repeat(80)+'\n'); if(isPR){ console.log('\nTo post this review to GitHub, add --post flag'); if(process.argv.includes('--post')){ await octokit.rest.issues.createComment({ owner:ctx.owner, repo:ctx.repo, issue_number:ctx.prNumber, body:`### ${title}\n${reviewBody}`}); console.log(`✓ Review posted to PR #${ctx.prNumber}`);} } else { console.log('\nTo create an issue with this review, add --post flag'); if(process.argv.includes('--post')){ const issue=await octokit.rest.issues.create({ owner:ctx.owner, repo:ctx.repo, title:`${title} · ${new Date().toISOString()}`, body:reviewBody }); console.log(`✓ Issue created: #${issue.data.number}`);} } } else { if(ctx.prNumber){ await octokit.rest.issues.createComment({ owner:ctx.owner, repo:ctx.repo, issue_number:ctx.prNumber, body:`### ${title}\n${reviewBody}`}); } else { await octokit.rest.issues.create({ owner:ctx.owner, repo:ctx.repo, title:`${title} · ${new Date().toISOString()}`, body:reviewBody }); } } console.log(`${MODE} review completed.`); }

if (isLocal) { main().catch(err => { console.error('ERROR:', err.message || String(err)); process.exit(1); }); } else { main().catch(err => setFailed(err.message || String(err))); }

try { const blocking = (globalThis.summary?.blockingIssues?.length ?? 0) > 0 || (globalThis.summary?.errors?.length ?? 0) > 0 || (globalThis.report?.blocking === true) || (globalThis.result?.clean === false); if (blocking) { console.log('STATUS: FAIL'); process.exit(1); } else { console.log('STATUS: OK'); process.exit(0); } } catch (e) { console.error('AI review status error:', e); console.log('STATUS: FAIL'); process.exit(1); }
