const state = {
  redirect_uri: 'https://chat.openai.com/aip/g-285d3d3631e73e4b56bf647eaafab15ac5c255c0/oauth/callback',
  client_id: 'dsb-gpt',
  scope: 'jobs:read',
  orig_state: 'test123abc',
};

const json = JSON.stringify(state);
const base64url = Buffer.from(json, 'utf8')
  .toString('base64')
  .replace(/\+/g, '-')
  .replace(/\//g, '_')
  .replace(/=+$/, '');

console.log(base64url);