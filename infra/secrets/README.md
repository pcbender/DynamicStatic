This directory holds secret material for local/dev operations.

DO NOT COMMIT REAL SECRETS.

Files expected (create manually):
  app_key.txt
  google_client_secret.txt
  microsoft_client_secret.txt
  db_password.txt
  mysql_root_password.txt
  ngrok_authtoken.txt
  github_app_private_key.pem   (GitHub App private key)

All are referenced by docker-compose via Docker secrets.

Add any new secret filenames to .gitignore patterns if needed.
