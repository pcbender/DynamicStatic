import process from 'node:process';

/**
 * Minimal client for the Opus Publisher API endpoints used by GitHub workflows.
 *
 * Implements the endpoints documented in openapi.json:
 * - POST /jobs (insertJob)
 * - POST /jobs/list (getAllJobs)
 * - GET  /jobs/{id} (getJobStatus)
 * - POST /jobs/update (updateJobStatus)
 * - GET  /jobs/{id}/artifact (getJobArtifact)
 *
 * Usage:
 *   import { insertJob, getJobStatus, updateJobStatus } from './scripts/opusPublisherClient.js';
 *
 *   const payload = {
 *     type: 'article',
 *     metadata: { title: 'Example Article', description: 'An example', tags: ['example'] },
 *     content: { format: 'markdown', body: '# Hello World\n\nThis is content.' },
 *     deployment: { repository: 'owner/repo', filename: 'example.html' }
 *   };
 *   const job = await insertJob({
 *     id: '123',
 *     status: 'queued',
 *     created_at: new Date().toISOString(),
 *     updated_at: new Date().toISOString(),
 *     payload
 *   });
 *   const status = await getJobStatus(job.job_id);
 *   await updateJobStatus(job.job_id, 'done');
 */

const BASE_URL = (process.env.OPUS_API_BASE_URL || 'https://webbness.net').replace(/\/$/, '');
const API_TOKEN = process.env.OPUS_API_TOKEN; // legacy bearer
const API_KEY = process.env.WEAVER_API_KEY; // new service key

function buildHeaders(json = true) {
  const headers = { Accept: 'application/json' };
  if (json) headers['Content-Type'] = 'application/json';
  if (API_KEY) headers['X-API-Key'] = API_KEY; // new auth
  else if (API_TOKEN) headers['Authorization'] = `Bearer ${API_TOKEN}`; // fallback
  return headers;
}

/**
 * Insert a new publishing job.
 * @param {object} job - Job with structured ContentJobPayload as defined by the API schema.
 * @returns {Promise<object>} API response containing status, job_id and dispatched flag.
 */
export async function insertJob(job) {
  const res = await fetch(`${BASE_URL}/jobs`, {
    method: 'POST',
    headers: buildHeaders(true),
    body: JSON.stringify(job)
  });
  if (!res.ok) {
    throw new Error(`insertJob failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Retrieve the status of a publishing job.
 * @param {string} id - Job identifier.
 * @returns {Promise<object>} Job object with structured ContentJobPayload.
 */
export async function getJobStatus(id) {
  const res = await fetch(`${BASE_URL}/jobs/${id}`, {
    method: 'GET',
    headers: buildHeaders(false)
  });
  if (!res.ok) {
    throw new Error(`getJobStatus failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Update the status of a publishing job.
 * @param {string} id - Job identifier.
 * @param {string} status - New status value.
 * @param {object} [payload] - Optional updated payload data.
 * @returns {Promise<object>} API response.
 */
export async function updateJobStatus(id, status, payload = null) {
  const updateData = { id, status };
  if (payload) updateData.payload = payload;
  
  const res = await fetch(`${BASE_URL}/jobs/update`, {
    method: 'POST',
    headers: buildHeaders(true),
    body: JSON.stringify(updateData)
  });
  if (!res.ok) {
    throw new Error(`updateJobStatus failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Get all jobs with status filter.
 * @param {string} status - Status filter (comma-separated or '*' for all).
 * @returns {Promise<Array>} Array of job objects.
 */
export async function getAllJobs(status = '*') {
  const res = await fetch(`${BASE_URL}/jobs/list`, {
    method: 'POST',
    headers: buildHeaders(true),
    body: JSON.stringify({ status })
  });
  if (!res.ok) {
    throw new Error(`getAllJobs failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Get job artifact (payload) with HMAC authentication.
 * @param {string} id - Job identifier.
 * @param {string} hmacKey - HMAC secret key.
 * @returns {Promise<object>} ContentJobPayload object.
 */
export async function getJobArtifact(id, hmacKey) {
  const timestamp = Math.floor(Date.now() / 1000).toString();
  const crypto = await import('crypto');
  const signature = crypto.createHmac('sha256', hmacKey).update(timestamp).digest('hex');
  
  const res = await fetch(`${BASE_URL}/jobs/${id}/artifact`, {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'X-Timestamp': timestamp,
      'X-Signature': signature
    }
  });
  if (!res.ok) {
    throw new Error(`getJobArtifact failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}
