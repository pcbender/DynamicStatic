import process from 'node:process';

/**
 * Minimal client for the Opus Publisher API endpoints used by GitHub workflows.
 *
 * Implements the endpoints documented in openapi.json:
 * - POST /api/insertJob.php
 * - GET  /api/getJobStatus.php
 * - POST /api/updateJob.php
 *
 * The publish.php endpoint is intentionally omitted.
 *
 * Usage:
 *   import { insertJob, getJobStatus, updateJobStatus } from './scripts/opusPublisherClient.js';
 *
 *   const job = await insertJob({ id: '123', status: 'queued', created_at: new Date().toISOString(), updated_at: new Date().toISOString(), payload: { article: { title: 'Example', url: 'https://example.com' } } });
 *   const status = await getJobStatus(job.job_id);
 *   await updateJobStatus(job.job_id, 'done');
 */

const BASE_URL = (process.env.OPUS_API_BASE_URL || 'https://webbness.net').replace(/\/$/, '');
const API_TOKEN = process.env.OPUS_API_TOKEN;

function buildHeaders(json = true) {
  const headers = { Accept: 'application/json' };
  if (json) headers['Content-Type'] = 'application/json';
  if (API_TOKEN) headers['Authorization'] = `Bearer ${API_TOKEN}`;
  return headers;
}

/**
 * Insert a new publishing job.
 * @param {object} job - Job payload as defined by the API schema.
 * @returns {Promise<object>} API response containing status, message and job_id.
 */
export async function insertJob(job) {
  const res = await fetch(`${BASE_URL}/api/insertJob.php`, {
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
 * @returns {Promise<object>} Job data.
 */
export async function getJobStatus(id) {
  const url = new URL('/api/getJobStatus.php', BASE_URL);
  url.searchParams.set('id', id);
  const res = await fetch(url, {
    method: 'GET',
    headers: buildHeaders(false)
  });
  if (!res.ok) {
    throw new Error(`getJobStatus failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Update the status of an existing job.
 * @param {string} id - Job identifier.
 * @param {string} status - New status string.
 * @returns {Promise<object>} API response containing status.
 */
export async function updateJobStatus(id, status) {
  const res = await fetch(`${BASE_URL}/api/updateJob.php`, {
    method: 'POST',
    headers: buildHeaders(true),
    body: JSON.stringify({ id, status })
  });
  if (!res.ok) {
    throw new Error(`updateJobStatus failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

export default { insertJob, getJobStatus, updateJobStatus };
