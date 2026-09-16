# InfinityFree deployment

Only the contents of `modernized/` are deployed. Do not upload the repository root because it contains the audited legacy application for comparison.

## 1. Create a separate site and database

Create a new InfinityFree subdomain and a separate MySQL database for Work 03. Import `database/schema.mysql.sql` with phpMyAdmin.

## 2. Create the protected configuration

Copy `config.infinityfree.example.php` to `config.php` on the hosting server and replace every `CHANGE_ME` value. Use the exact host, database name and user shown by InfinityFree.

Use a unique install key of at least 24 random characters. Never commit `config.php`.

## 3. Upload only the modernized application

The contents must be directly under the new site's `htdocs` directory:

    htdocs/.htaccess
    htdocs/config.php
    htdocs/public/
    htdocs/src/
    htdocs/database/

Do not upload the original root-level PHP files, `tests/`, `.github/`, `.devcontainer/` or a wrapping repository directory.

## 4. Create the first administrator

Open `https://YOUR_DOMAIN/setup.php`, enter the install key and choose the login details. Once a user exists, the endpoint returns HTTP 410. After successful setup, delete `public/setup.php` from the hosting server as an additional safeguard.

## 5. Verify

Check login, prefix search, item registration, receive, issue, over-issue rejection, protected deletion and logout. Confirm that `/config.php`, `/src/` and `/database/` are inaccessible and `/setup.php` returns 404 after removal.

