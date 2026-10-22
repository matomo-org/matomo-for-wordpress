# Comparison scope and baseline lifecycle

## Resolve Git scope before choosing tests

Record four separate identities: the base ref and resolved commit, the merge base with the candidate, candidate `HEAD`, and the candidate's local modifications. A Git base describes source differences; a test baseline describes observed behavior. Neither substitutes for the other.

Use the user's explicit `base-branch` or commit first. If omitted, use an established project comparison target or a locally configured remote default branch when unambiguous, stating that assumption. Do not treat the feature branch's own tracking branch as its comparison base. If no target can be established, ask for it while continuing independent environment checks. Do not guess a known-working commit from recency, a clean tree, or the presence of the skill in a commit.

Resolve refs to full SHAs at the start and record whether remote refs were refreshed. Use these inspection commands from the plugin root after setting `comparison_base` to the selected ref:

```bash
git status --short
git rev-parse --verify "${comparison_base}^{commit}"
git rev-parse HEAD
```

Set `comparison_base_sha` to the resolved full SHA and `comparison_merge_base` to the result of `git merge-base "$comparison_base_sha" HEAD`. Then inspect:

```bash
git log --oneline "$comparison_merge_base..HEAD"
git diff --name-status "$comparison_merge_base" HEAD
git diff --stat "$comparison_merge_base" "$comparison_base_sha"
git diff --name-status "$comparison_base_sha" HEAD
git diff --cached --name-status
git diff --name-status
git ls-files --others --exclude-standard
```

- Merge-base-to-candidate changes identify committed feature work, including on a clean tree.
- Merge-base-to-base changes identify base advancement. Baseline-commit-to-candidate differences also include upstream differences: account for them rather than attributing every change to the feature branch.
- Staged, unstaged and relevant untracked files contribute additional candidate changes. Select tests from the union of all relevant source differences, including differences from the baseline's source commit if it differs from the Git base.

Save relevant binary-capable patches and untracked-file hashes/content as appropriate to reproduce the candidate. Exclude credentials and unrelated untracked files. Record submodule revisions/dirty state where they affect tests. A SHA plus “dirty” is insufficient to reproduce local behavior. Inspect a stable snapshot if editing continues during testing.

If a ref is missing or no merge base exists, report the comparison unavailable; do not silently substitute `HEAD~1`. An exact explicit baseline-to-candidate tree comparison may still be useful when disclosed as such.

## Establish a reference

1. Resolve the user-identified known-working ref to a full SHA. Test that exact source in isolation without changing the active checkout or importing candidate changes into the reference. The skill instructions may come from the feature branch even when the reference commit predates the skill; record the skill revision separately.
2. Record concrete runtime versions and fixture setup. Complete `TRACK-001` on the reference and candidate as the first validation of the workflow. Use clean equivalent fixture states, the same case contract and assertions, and different unique run markers.
3. Save an immutable report from `report-template.md`. Its initial state is **observation** if not based on an identified known-working ref, or **candidate** if awaiting baseline approval. “Known working” supplied by a user is provenance, not evidence that all tests passed.
4. An explicit user instruction designating a particular report/run as the approved reference authorizes approval; do not request the same approval again. A generic request to establish a baseline authorizes capture, not accepting unknown failures. If designation is still needed, finish the report first and present its concrete coverage/failures for approval.
5. Store approval in a separate `baseline-approval.json` beside the report so the original evidence stays unchanged. Record report relative path and SHA-256, tested source SHA/local snapshot identity, approval time, user instruction provenance, approved case IDs/contract versions, and any explicitly accepted known failures/coverage limits. Do not put accepted failures into the pass count. Never fabricate approval metadata.

Approval is scoped to the recorded cases and environment. A reference containing failures can be used only with those known failures made explicit and accepted in its approval scope; it is not a clean baseline. Incomplete or failed `TRACK-001` means the complete tracking-to-report journey is not established, even if other cases are approved independently.

## Select and compare

Use the explicit baseline report path first. Check its approval provenance, hash and coverage. If the user explicitly designates an unapproved report as the reference in the current instruction, record that designation and its stated scope. Otherwise retain an unapproved report as an informational comparison only. With no explicit path, choose the latest approved compatible baseline for the relevant project/cases; do not choose the newest arbitrary report directory.

Compare concrete PHP, WordPress, database and integration versions; activation/site mode; relevant configuration; locale/timezone/browser where material; fixture recipe/data fingerprint; site/date parameters; case contract version; and executed assertion coverage. Allow intended plugin source changes between runs. Unique markers and generated IDs can differ while fixture recipes remain equivalent. Record environment before and after both runs; automatic updates or configuration drift can invalidate affected comparisons.

For incompatible dimensions, describe the mismatch and either recreate the reference environment or rerun reference and candidate in a new matching environment. The latter creates a new candidate baseline requiring approval within its scope. If alignment is blocked, report affected cases **uncomparable** while retaining compatible cases; do not conceal mismatches by normalization.

Classification for comparable assertions:

| Baseline | Candidate | Classification |
| --- | --- | --- |
| PASS | FAIL | New failure |
| FAIL | Same failure | Existing failure |
| FAIL | Different failure | Changed failure; retain both signatures |
| FAIL | PASS | Resolved failure |
| PASS | PASS | Unchanged pass |
| Missing, blocked, skipped, incompatible or different contract | Any result | Uncomparable / coverage change |

A candidate that was skipped or blocked cannot resolve a prior failure. Keep actual execution status separate from comparison classification. Screenshot reference updates and application fixes require their own requested scope; comparison never silently accepts them.
