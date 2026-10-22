---
name: matomo-wordpress-regression
description: Test the Matomo for WordPress plugin in wp-content/plugins/matomo, establish a functionality baseline, and compare behavior after changes using existing PHPUnit and WebdriverIO suites plus browser checks. Use for Matomo plugin regression testing and release verification.
---

# Matomo WordPress regression testing

Produce evidence of what works, what fails, and what changed. Reuse the plugin's tests and exercise observable behavior; source inspection or linting alone cannot establish functional correctness.

## Locate and inspect

This skill is stored in the plugin repository at `.agents/skills/matomo-wordpress-regression`. Resolve the plugin root from this location, or use the user's specified checkout. The plugin may be installed at `<wordpress-root>/wp-content/plugins/matomo` or checked out independently. Locate the WordPress installation and available local environment before executing commands. Run plugin Git commands from the plugin root; the containing WordPress installation may not be a Git repository.

Read applicable `AGENTS.md` files, then inspect the plugin's Git status, diff, `package.json`, `composer.json`, `phpunit.xml.dist`, `.github/workflows/tests.yml`, and relevant test configuration. Preserve existing changes. Read `app/AGENTS.md` before working inside bundled Matomo core. Use [test-map.md](references/test-map.md) to select coverage and [execution.md](references/execution.md) for commands and runner hazards. Recheck these against the checkout; references describe the inspected version, not permanent upstream guarantees.

## Choose the run

- **Baseline:** when asked to record current functionality, execute available broad coverage and save an immutable report for future comparisons. Existing failures remain failures, not accepted behavior.
- **Regression (default):** inspect changes, run directly affected tests and core smoke checks, then compare against a compatible saved baseline. With no baseline, save the current result as an initial observation and say that before/after comparison was unavailable.
- **Full:** when requested, execute the complete integration suite and browser pipeline in a disposable environment. Include single-site/multisite and PHP/WordPress versions justified by the change and current CI configuration. State the tested matrix explicitly.

Start independent read-only checks while resolving missing prerequisites. Execute available functional checks; do not stop at a plan or merely print suggested commands. If a runner is blocked, continue independent checks and mark the missing coverage blocked. Prefer existing frameworks over adding a new one.

## Protect the working installation

Use DDEV for inspection and browser smoke checks appropriate to the site's current state. Settings saves, synthetic tracking, archiving and test entities change persistent state: prefer a disposable clone for these. On a retained site, use only changes covered by the user's authorization, record original settings and created entities, and restore them afterward. Do not restore a whole database over concurrent work. Do not deactivate/uninstall the retained plugin or delete analytics data during ordinary smoke checks.

Run PHPUnit only against a verified dedicated test database. Use the repository Compose environment for full browser tests: its runner resets databases, builds releases, changes settings, installs plugins and finally uninstalls Matomo. Verify resolved mounts, database destinations, port and Compose project do not target or collide with DDEV. A hostname override does not make repository browser specs safe for the retained site.

Invoking this skill for testing authorizes test execution within the requested scope. Ask only when genuinely missing information or additional authorization prevents the next action. A testing request does not authorize plugin code repairs. Preserve failure evidence before any authorized fix.

## Verify behavior

Use the test map to cover the changed feature and shared dependencies. Core smoke coverage includes admin pages, tracking, reports/archiving, settings persistence, privacy/exclusions, permissions and new runtime errors. Use isolated fixtures for mutating cases. Identify absent optional integration coverage explicitly.

Discover available browser tools or use WebdriverIO. Use authenticated sessions or locally provided credentials without printing secrets. Base assertions on page elements, network requests, API results and persisted state. HTTP 200 or a screenshot alone is insufficient. Tracking requires a fresh visitor context, an identifiable synthetic action and confirmation it reached Matomo; reports require a suitable date range and archiving. Check direct protected URLs/API with limited roles and anonymous users as well as visible menus.

Capture new console/network errors and PHP/WordPress logs, retaining earlier logs. Distinguish Matomo errors from other plugins and pre-existing entries. Do not suppress errors or regenerate screenshot baselines to make tests pass. Explain legitimate visual differences with artifacts.

## Save and compare evidence

Default artifacts: `<wordpress-root>/.matomo-regression/<UTC-timestamp>/`, outside the plugin checkout. Honor an established destination instead. Write a Markdown report containing:

- Run mode/time; plugin commit and working-tree changes; PHP, WordPress and relevant integration versions; site mode, locale, browser, fixtures/date range and tested URL without credentials. Save a diff or changed-file hashes where necessary to identify the tested state, including staged and untracked changes.
- Exact commands, exit codes, suite/test counts, browser assertions and links to sanitized logs/screenshots/diffs.
- Each case marked **PASS**, **FAIL**, **BLOCKED**, **SKIPPED** or **NOT APPLICABLE**, expected/actual behavior and reproduction details for failures. Record cleanup and state that could not be restored.
- Baseline path, new/existing/resolved failures and coverage/environment differences. Match test identifiers and assertions, not just total counts. Prefer the same cases, fixtures and versions; disclose incompatible comparisons.

Keep old runs intact. Select the most recent compatible baseline by default; do not silently promote a candidate or failing run to an approved reference. Normalize timestamps, changing IDs and dynamic data only where irrelevant to the assertion.

Conclude with observed results, regressions, material gaps and the report path. Say “no regressions detected in the executed checks” only when supported; blocked/skipped suites limit that conclusion. Never claim a run proves nothing can break.
