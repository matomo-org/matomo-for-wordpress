# Execution environments

Commands below illustrate an installed checkout at `wp-content/plugins/matomo`. Adapt paths to the discovered plugin and WordPress roots. Use DDEV commands only when that installation has a DDEV configuration; standalone plugin checkouts can use the repository's disposable Compose test environment.

## Inspect DDEV

Run these from the WordPress root as appropriate:

```bash
git -C wp-content/plugins/matomo status --short
git -C wp-content/plugins/matomo diff --stat
ddev describe
ddev exec php -v
ddev wp core version
ddev wp plugin list --format=json
ddev wp option get home
ddev wp option get siteurl
ddev wp cron event list --fields=hook,next_run_gmt,recurrence --format=json
```

These are discovery checks, not functional test passes. Inspect staged changes with `git diff --cached` as well. Avoid dumping configuration, cookies, tokens or entire options tables into reports.

At skill creation, DDEV specified PHP 8.4 and MariaDB 10.11; plugin Compose defaults specified PHP 8.1. Record actual execution versions; host PHP and DDEV PHP may differ. Plugin development `vendor/bin` and `node_modules/.bin` were absent. Bundled `app/vendor` does not establish plugin test dependencies.

## PHPUnit integration tests

Configuration: plugin-root `phpunit.xml.dist`; bootstrap: `tests/phpunit/bootstrap.php`; suite: `integration`.

The bootstrap reads `WP_TESTS_DIR` or defaults to `/tmp/wordpress-tests-lib`. It requires WordPress test includes, configuration and a dedicated database. A normal WordPress install alone is insufficient. Do not assume the bootstrap's suggested `bin/install-wp-tests.sh` exists: this checkout provisions the library in `scripts/local-dev-entrypoint.sh`.

For an already configured, disposable repository Compose environment, run from the plugin root:

```bash
npm run phpunit
npm run phpunit -- tests/phpunit/wpmatomo/test-tracking.php
```

The second command illustrates a focused run. The package script invokes Compose's `exec` service rather than host PHPUnit. Its entrypoint drops/recreates the selected `_test` database on every run. Verify `WP_DB_HOST`, generated database name, mounts and `WP_TESTS_DIR` first.

Direct `./vendor/bin/phpunit -c phpunit.xml.dist` is suitable only where the test library, dependencies and dedicated test database are already verified. Never point it at the DDEV working database. A core `app/vendor/bin/phpunit` executable is not automatically the correct plugin runner.

Use `composer install` with the lockfile when compatible with the selected PHP version. Resolve incompatibilities in isolation; do not copy CI's dependency updates, manifest rewrites or broad permission changes into the working checkout merely to launch tests. The XML schema declaration and Composer PHPUnit constraint differ: verify the installed executable version rather than guessing from the schema URL.

## Full WebdriverIO pipeline

Read current `.github/workflows/tests.yml`, `.env.default`, `docker-compose.yml`, `scripts/local-dev-entrypoint.sh` and `scripts/wdio.sh` before provisioning. Use an isolated copy containing the actual working-tree changes, relevant untracked files and submodules. A clone of `HEAD` alone misses local changes. Keep mounts within that copy, use a unique Compose project and free port, and pass consistent settings to setup and browser execution. Copying plugin files does not isolate a database.

Prepare dependencies from lockfiles as supported by the checkout, verify Docker Compose and Chrome, and create the required local `.env` using CI as a guide. Preserve the user's existing `.env`. Required integration packages, generated marketplace artifacts and license secrets may limit tests; report that coverage explicitly.

From the isolated plugin root, the complete entry point is:

```bash
npm run wdio
```

It builds `matomo.zip`, provisions fresh `test` and related sites and runs:

1. `wdio.conf.tracking.ts`: serialized state-changing specs from the main configuration's exclusion list, including tracking, update, marketplace, language and archiving.
2. `wdio.conf.ts`: remaining specs with parallel workers and shared global setup.
3. `wdio.conf.uninstall.ts`: uninstallation specs in `tests/e2e-uninstall/`.

Preserve ordering. The main suite is gated on tracking command success: enumerate suites actually executed after failure. Global setup creates visits, goals and Tag Manager entities. A focused rerun requires inspecting setup dependencies and checking the disposable environment still contains Matomo after uninstall.

Do not simply set WebdriverIO `baseUrl` to DDEV. `tests/e2e/website.ts` builds URLs using `WORDPRESS_URL`, `PORT` and folder settings; API/CLI helpers also depend on the repository environment. Inspect these before adapting focused runs.

## Hazards and artifacts

- The inspected entrypoint tests whether `RESET_DATABASE` is **nonempty**: even `0` requests a reset. Do not treat it as a boolean safety switch.
- `scripts/wdio.sh` sets `RESET_DATABASE=1`, writes `.env.script`, builds release archives, starts/stops Compose services and attempts the uninstall suite. Verify resolved targets first.
- Screenshot baselines: `tests/e2e/baseline/`; actual/diff images: `tests/e2e/actual/` and `tests/e2e/diff/`. Some baseline names depend on PHP/WordPress versions. Keep baselines intact.
- The main config reads `docker/wordpress/<folder>/wp-content/debug.log`, appends `debug.concat.log` and removes the processed log. Save concatenated logs, runner output and `.e2e-docker-out` in artifacts.
- Debug checks filter some third-party warnings. Inspect raw logs when diagnosing missing/misattributed errors.

## Compare runs

Use matching fixture start states. Reusing populated sites can alter global setup and totals. Do not compare DDEV report totals against fresh Compose fixtures. Pin concrete PHP/WordPress versions where practical; `latest` can change between runs. Record environment differences as gaps until there is evidence they represent code regressions.

Preserve original failure output, reproduce the smallest relevant case once if useful, and compare the same case against the reference revision in isolation. Preserve local edits. Separate likely cause from observed facts, and repair application code only when requested.
