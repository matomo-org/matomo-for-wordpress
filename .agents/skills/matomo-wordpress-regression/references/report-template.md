# Matomo regression report

Use this template for new runs. Replace bracketed fields with facts or `unknown` plus a reason. Remove instructional notes after filling it. Retain case IDs/contract versions and explicit coverage gaps; never invent results for an unexecuted case.

## Run and source identity

- Run ID / UTC start and end: [values]
- Mode: [observation / baseline candidate / regression / full]
- Known-working ref and provenance: [user-supplied ref and instruction, or unavailable]
- Git comparison base ref / full SHA: [values]
- Merge-base full SHA / candidate full SHA: [values]
- Committed branch changes and base advancement: [summary and patch links]
- Staged / unstaged / relevant untracked changes: [patch links and file hashes]
- Submodule state / tested snapshot identity: [values]
- Skill revision or content hashes: [values, separately from tested plugin commit]

## Environment and fixtures

| Dimension | Reference | Candidate | Compatible? / evidence |
| --- | --- | --- | --- |
| PHP / WordPress / database versions | [values] | [values] | [result] |
| Plugins, integration versions and activation | [values] | [values] | [result] |
| Single-site/multisite, configuration fingerprint | [values] | [values] | [result] |
| Locale / site timezone / browser / viewport | [values] | [values] | [result] |
| Fixture recipe, initial data fingerprint | [values] | [values] | [result] |
| Site ID / action date / reporting period | [values] | [values] | [result] |
| Runtime versions/configuration after run | [values] | [values] | [drift or none] |

Record tested URL without credentials, synthetic run marker and effective execution environment. Distinguish equivalent recipes from intentionally different generated IDs. Explain absent reference columns when no reference exists.

## Baseline selection

- Report path / SHA-256: [values or none]
- Approval record / user designation / approved case scope: [values or unapproved]
- Accepted known failures and limits: [explicit scope or none]
- Selection rationale / incompatible dimensions: [details]

## Case results

Add one row per executed or planned case from `test-map.md`, using the exact ID and contract version. Include suite-native test identifiers for automated checks and subassertion coverage for partial runs. Execution status is PASS, FAIL, BLOCKED, SKIPPED or NOT APPLICABLE. Comparison classification is separate: new, existing, changed, resolved, unchanged or uncomparable.

| Case ID | Contract | Expected behavior | Actual behavior / execution status | Reference status | Comparison | Evidence / reproduction |
| --- | --- | --- | --- | --- | --- | --- |
| TRACK-001 | 1 | Unique visit → raw action → explicit archiving → matching archived page report | [each stage and overall status] | [status] | [classification] | [marker, site/date/period, command/API result, report row] |

## Commands and browser evidence

| Command or MCP tool / arguments | Environment / source snapshot | Exit code or tool outcome | Suite/test counts and executed assertions | Sanitized artifact |
| --- | --- | --- | --- | --- |
| [exact invocation without secrets] | [value] | [value] | [count/coverage, not inferred] | [link] |

For TRACK-001, preserve the tracker request, stored action/Live result, archive invocation and completion, and archived page report containing the unique marker. HTTP 200, a scheduled cron event or Live data alone cannot pass the complete case.

## Findings and missing coverage

List observed failures with reproducible triggers and expected/actual results. Separate likely cause from evidence. Record unrelated-plugin errors and diagnostics configuration issues separately. Explicitly list blocked/skipped cases, unavailable integrations, absent logging and comparison limitations. An observed failure without a comparable baseline is not established as a new regression.

## Cleanup and conclusion

- Restored settings / removed fixtures / revoked temporary sessions: [evidence]
- Retained synthetic analytics or un-restored state: [details]
- Executed case totals by status, plus independent suite counts: [values]
- Comparable new / existing / changed / resolved failures; uncomparable cases: [values]
- Observed conclusion and material limits: [statement supported by executed checks]
- Baseline state: [observation / candidate / approved with separate approval-record link]
