#!/usr/bin/env node
import process from 'node:process';

const BASE_URL = (process.env.OPUS_API_BASE_URL || 'https://webbness.net').replace(/\/$/, '');
const API_TOKEN = process.env.OPUS_API_TOKEN;
const API_KEY = process.env.WEAVER_API_KEY;
function buildHeaders(json=true){ const h={ Accept:'application/json' }; if(json) h['Content-Type']='application/json'; if(API_KEY) h['X-API-Key']=API_KEY; else if(API_TOKEN) h['Authorization']=`Bearer ${API_TOKEN}`; return h; }
export async function insertJob(job){ const res=await fetch(`${BASE_URL}/jobs`, { method:'POST', headers:buildHeaders(true), body:JSON.stringify(job)}); if(!res.ok) throw new Error(`insertJob failed: ${res.status} ${await res.text()}`); return res.json(); }
export async function getJobStatus(id){ const res=await fetch(`${BASE_URL}/jobs/${id}`, { method:'GET', headers:buildHeaders(false)}); if(!res.ok) throw new Error(`getJobStatus failed: ${res.status} ${await res.text()}`); return res.json(); }
export async function updateJobStatus(id, status, payload=null){ const updateData={ id, status }; if(payload) updateData.payload=payload; const res=await fetch(`${BASE_URL}/jobs/update`, { method:'POST', headers:buildHeaders(true), body:JSON.stringify(updateData)}); if(!res.ok) throw new Error(`updateJobStatus failed: ${res.status} ${await res.text()}`); return res.json(); }
export async function getAllJobs(status='*'){ const res=await fetch(`${BASE_URL}/jobs/list`, { method:'POST', headers:buildHeaders(true), body:JSON.stringify({ status })}); if(!res.ok) throw new Error(`getAllJobs failed: ${res.status} ${await res.text()}`); return res.json(); }
export async function getJobArtifact(id, hmacKey){ const timestamp=Math.floor(Date.now()/1000).toString(); const crypto=await import('crypto'); const signature=crypto.createHmac('sha256', hmacKey).update(timestamp).digest('hex'); const res=await fetch(`${BASE_URL}/jobs/${id}/artifact`, { method:'GET', headers:{ 'Accept':'application/json','X-Timestamp':timestamp,'X-Signature':signature }}); if(!res.ok) throw new Error(`getJobArtifact failed: ${res.status} ${await res.text()}`); return res.json(); }
