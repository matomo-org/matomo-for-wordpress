# Functionality and existing coverage

Paths are relative to `wp-content/plugins/matomo`. Re-enumerate current tests; filenames/integrations can change. Select by behavior and shared dependencies, not filename alone.

| Area | Existing test anchors | Observable checks |
| --- | --- | --- |
| Admin/onboarding | `tests/e2e/mwp-admin.get-started.e2e.ts`, `mwp-admin.summary.e2e.ts`, `mwp-admin.about.e2e.ts`; `tests/phpunit/wpmatomo/admin/` | Authorized pages show expected content; links and embedded Matomo UI work without new errors. |
| Settings | `tests/e2e/mwp-admin.settings.e2e.ts`; `tests/phpunit/wpmatomo/admin/test-trackingsettings*.php`, `test-accesssettings.php`, `test-advancedsettings.php` | Existing values display; a supported change saves, survives reload and affects behavior. Restore original values on retained sites. |
| JS tracking | `tests/e2e/tracking.e2e.ts`; `tests/phpunit/wpmatomo/test-tracking.php`, `test-trackingcode.php`, `trackingcode/` | Fresh anonymous visit loads tracker, emits expected requests and stores the unique action in Live data. Check duplicates/exclusions. |
| AJAX/bot tracking | `tests/phpunit/wpmatomo/test-ajax-tracker.php`, `test-ai-bot-tracking.php`, `db/test-wordpresstracker.php` | Legitimate events are stored with correct exclusions; invalid requests follow expected error behavior. |
| Reports/archiving | `tests/e2e/manual-archiving.e2e.ts`, `matomo-reporting.*.e2e.ts`; `tests/phpunit/wpmatomo/report/`, `test-scheduled-tasks*.php` | Seed a recognizable action, verify raw data, archive its date and verify report/API output. An empty unrelated date range is not a failure. |
| Privacy/opt-out | `tests/e2e/mwp-opt-out.e2e.ts`, `matomo-admin.privacy.e2e.ts`; `tests/phpunit/wpmatomo/admin/test-privacysettings.php`, `test-exclusionsettings.php` | Opt-out UI works; tracking disablement, consent and exclusions suppress appropriate requests/cookies. Separate visitor contexts for positive/negative cases. |
| Permissions/auth | `tests/e2e/mwp-roles.e2e.ts`, `matomo-api.e2e.ts`; `tests/phpunit/wpmatomo/test-access.php`, `test-capabilities.php`, `test-roles.php`, `test-api.php`, `plugins/WordPress/test-*auth.php` | Admin access works; limited roles and anonymous users cannot access protected routes/mutations. Test direct URLs/API, not only menus. |
| Tag Manager | `tests/e2e/tracking.tag-manager.e2e.ts`, `matomo.tag-manager.e2e.ts`; `tests/phpunit/plugins/WordPress/Overrides/TagManager/` | Disposable container/tag/trigger and published event work; template restrictions hold. Publish only on disposable sites. |
| WooCommerce | `tests/e2e/tracking.ecommerce.e2e.ts`, `matomo-reporting.ecommerce.e2e.ts`; `tests/phpunit/wpmatomo/ecommerce/` | Product/cart/test order produce correct items/revenue without duplicate orders; report/API matches. Use test checkout without live payment. |
| Multisite/sync | `tests/e2e/mwp-admin.multisite.*.e2e.ts`; `tests/phpunit/wpmatomo/site/`, `user/`, `test-site.php`, `test-user.php` | Network/site controls follow activation mode; sites/users synchronize and analytics remain scoped. Requires actual multisite fixtures. |
| Updates/lifecycle | `tests/e2e/update.e2e.ts`, `mwp-admin.update-block.e2e.ts`, `wp-plugins-admin.e2e.ts`, `tests/e2e-uninstall/`; `tests/phpunit/wpmatomo/test-install.php`, `test-updater.php`, `test-uninstall.php`, `test-minimum-requirements*.php` | Installation/update preserves expected settings/data and checks compatibility; uninstall follows retention configuration. Disposable environment only. |
| Diagnostics/integrations | `tests/e2e/mwp-admin.diagnostics.e2e.ts`, `matomo-admin.diagnostic.e2e.ts`, `mwp-admin.marketplace.e2e.ts`, `mwp-admin.suggestions.e2e.ts`, `matomo-plugin.*.e2e.ts`; `tests/phpunit/wpmatomo/wpstatistics/` | Diagnostics expose status; configured integrations render and imports/marketplace behave correctly. External services/licenses can block coverage. |

## Stable case contracts

Use the IDs below in every report. Every listed contract starts at version `1`. An ID always denotes the same observable behavior; do not rename or reuse it for a different assertion. If an expectation materially changes, increment its contract version and explain the change; if it is a different behavior, allocate a new ID. Different contract versions are uncomparable until the same contract is rerun on both revisions. Keep deprecated IDs in historical reports. Link suite-native identifiers and evidence to these IDs instead of replacing the IDs with session-specific labels.

| Case ID | Contract | Fixed assertion / pass condition |
| --- | --- | --- |
| TRACK-001 | 1 | One uniquely marked browser page action is stored once for the intended site; explicit archiving completes; the matching marker and expected count appear in that site's archived page report for the same concrete date/period. All stages are required. See [execution procedure](execution.md#first-practical-validation-track-001). |
| TRACK-002 | 1 | A fresh eligible anonymous page visit loads the configured tracker and emits the intended tracking request without duplicate page actions. This alone does not pass TRACK-001. |
| ADMIN-001 | 1 | Authorized Summary navigation renders its expected report headings and date controls without a login redirect or fatal error. Runtime warnings are evaluated separately by RUNTIME-001. |
| SETTINGS-001 | 1 | A supported setting change saves, survives a fresh reload, produces its intended behavior, and is restored when required. Display-only checks are partial evidence, not a pass. Record the same chosen setting and original/new values on both sides. |
| PRIVACY-001 | 1 | Visitor opt-out is enabled and persists across navigation; a subsequent marked visit emits no tracking request and creates no matching stored action. Clear test opt-out state afterward. |
| PRIVACY-002 | 1 | With the same configured consent requirement, a fresh context emits no disallowed tracking before consent; granting consent permits the intended action. Record the consent mode tested. |
| PRIVACY-003 | 1 | The selected configured exclusion suppresses a matching visitor/action while a matched positive control is tracked. Use the same exclusion category/fixture on both revisions. |
| AUTH-001 | 1 | An anonymous direct request to protected Matomo WordPress settings requires login or is denied without exposing settings. Record the actual redirect/status. |
| AUTH-002 | 1 | An anonymous request to the same protected reporting API is denied without exposing visit data. Record HTTP status and error code separately; denial alone does not establish correct HTTP semantics. |
| AUTH-003 | 1 | A fixture user in the same limited role can perform the permitted action and cannot perform the protected action by direct URL/API. Record role/capability setup and both actions. |
| REPORT-001 | 1 | Authorized full Reporting navigation reaches the intended site and renders report navigation/widgets, without authentication failure or fatal error. Empty unrelated date ranges are not tracking failures. |
| CRON-001 | 1 | Expected Matomo archive/sync events are registered at the configured intervals. Event registration alone does not establish execution or satisfy TRACK-001. |
| DIAG-001 | 1 | Diagnostics renders its expected sections and exposes configuration findings. Record health warnings separately; rendering success does not establish a healthy configuration. |
| RUNTIME-001 | 1 | No new Matomo PHP warnings/deprecations/fatal errors or uncaught Matomo browser errors occur during the executed cases. Capture before/after logs and visible output. Missing required logging leaves that subcoverage blocked; unrelated-plugin and earlier entries are identified separately. |
| TAG-001 | 1 | A disposable Tag Manager fixture can be configured/published and its marked event reaches the intended site; preserve matching container/tag/trigger recipe. |
| ECOM-001 | 1 | A test order produces the expected items/revenue once in stored/report data without live payment; preserve the same checkout fixture. |
| MULTI-001 | 1 | Under the same multisite activation mode, fixture site/user synchronization works and analytics remain scoped to the intended site. |
| LIFE-001 | 1 | Installing/updating a disposable fixture preserves the documented settings/data and compatibility checks. Record exact start/end versions and retention expectations. |
| LIFE-002 | 1 | Uninstalling a disposable fixture follows the selected retention policy, with the expected data/settings preserved or removed. |

For a selected case that expands into multiple settings, integrations or fixture variants, keep its ID and contract but add a stable variant key (for example `SETTINGS-001/download-extensions`). Compare identical variants only. Add a new fixed case where no contract above describes the changed behavior; do not hide extra assertions in a loosely named pass.

Core smoke coverage uses ADMIN-001, SETTINGS-001, TRACK-001, TRACK-002, PRIVACY-001, PRIVACY-003, AUTH-001, AUTH-002, REPORT-001 and RUNTIME-001, plus AUTH-003 when a limited-role fixture is available. Configured consent behavior adds PRIVACY-002. Record inapplicable cases with reasons. With read-only access, execute the available subset and mark unavailable mutating steps blocked; do not label the complete smoke set passed.

Broad baseline/full runs should include every applicable area and identify skipped premium features, multisite, import fixtures and integrations. Syntax checks and PHPCS supplement the matrix but do not establish functionality. For changed behavior without coverage, add a focused assertion to the existing framework when test changes are authorized, or execute and document a reproducible browser/API case.
