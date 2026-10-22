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

These are discovery checks, not functional test passes. The working-tree diff above is not the branch comparison: follow [comparison-baselines.md](comparison-baselines.md) to inspect committed feature changes, base advancement, staged/unstaged changes and relevant untracked files. Avoid dumping configuration, cookies, tokens or entire options tables into reports.

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

Use matching fixture start states and the approval/compatibility rules in [comparison-baselines.md](comparison-baselines.md). Reusing populated sites can alter global setup and totals. Do not compare DDEV report totals against fresh Compose fixtures. Pin concrete PHP/WordPress versions where practical; `latest` can change between runs. Record versions before and after execution so automatic updates cannot silently change a run's identity. Record environment differences as gaps until there is evidence they represent code regressions.

Preserve original failure output, reproduce the smallest relevant case once if useful, and compare the same case against the reference revision in isolation. Preserve local edits. Separate likely cause from observed facts, and repair application code only when requested.

## First practical validation: TRACK-001

Complete the entire journey below on a user-identified known-working commit, then on the feature candidate. Keep the reference source free of candidate edits, but include committed and local changes in the candidate snapshot. Use the same runtime versions, enabled integrations, fixture recipe, site mode, site timezone, report date/period, case contract and assertions. Run against independent equivalent fixture states; avoid unrelated visits that alter totals. Use a unique marker on each side and compare the same outcome rather than requiring identical generated IDs.

1. **Prepare:** record the source/skill identities, versions, fixture fingerprint, effective site ID and site timezone. Select a concrete visit/report date that stays fixed through the case; do not mix UTC and site-local dates. Prepare a unique page URL or title that survives configured query-parameter exclusions. Record the expected count (one page action for the marked page in the clean fixture) before navigating.
2. **Generate the visit:** open a fresh anonymous Chrome context through available MCP tools or the existing browser framework and visit the identifiable page once. Capture tracker loading, the request and response, and the run marker. Wait on observable completion; do not pass from HTTP 200 alone or generate extra visits through automatic navigation retries.
3. **Confirm raw storage:** verify exactly one matching page action for the intended site and URL/title via Live API or a read-only query using the actual table prefix. Preserve the matching record and count. A response from the tracker without a stored matching action is insufficient.
4. **Archive:** inspect the current `tests/e2e/manual-archiving.e2e.ts` and CLI helper before choosing the supported invocation. In the disposable repository Compose environment, an example is:

   ```bash
   npm run matomo:console -- core:archive --force-idsites=<site-id> --force-date-range=<YYYY-MM-DD>,<YYYY-MM-DD> --force-periods=day
   ```

   Replace placeholders with the verified site ID and concrete action date; never run them literally. Ensure the CLI targets the same WordPress/Matomo installation as the browser. Record the exact command, exit code and archive completion/output. If stale archives need invalidating, limit the existing supported API to the fixture's site/date/period in the disposable environment. Do not copy the test suite's archive-table deletion workaround onto a retained installation. A registered cron event or a request to start archiving without confirmed completion is not completion of this step.
5. **Confirm the archived report:** request the page URL/title report for the same site, concrete date and `period=day`, using the authenticated reporting API (for example `Actions.getPageUrls`) or rendered report. Expand hierarchical rows or inspect the appropriate subtable as necessary; a default top-row limit must not hide the marker. Assert the exact marker appears with the expected action count, and retain that report row and parameters. A Live visit or unrelated aggregate count is not an archived report assertion.
6. **Finish and compare:** revoke temporary sessions, restore permitted settings and record retained synthetic data. Fill [report-template.md](report-template.md) for both runs. `TRACK-001` passes only when all stages pass. If a prerequisite is unavailable, mark the case BLOCKED and retain completed subassertions; if an executed assertion fails, mark it FAIL, even if later stages are blocked by it. Missing archiving/report checks cannot be called a successful journey. Compare both runs only after compatibility and approval checks.

Useful invocations:

```text
Use $matomo-wordpress-regression to establish a baseline on known-working commit <full-sha>. Complete TRACK-001 and available coverage, preserve evidence, and report blocked or untested functionality. Do not fix application code or update screenshot baselines.

Use $matomo-wordpress-regression to compare this branch against <base-branch> and baseline <report-path>. Include committed branch changes and local changes, execute affected cases and core smoke checks, and report new, existing and resolved failures.
```
