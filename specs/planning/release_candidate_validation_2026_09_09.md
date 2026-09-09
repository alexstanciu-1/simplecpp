# Release candidate validation — 2026-09-09
Doc Status: planning

Original validation baseline: `codex/compiler-vector-runtime` at `fc20d73d040c4e69758bcec0b1caf40c26755f72`, plus the Windows header, documentation/skill, and test-fixture changes described below. The stabilization commit containing this report follows managed-struct commit `881fb1e4` and includes the subsequent fixes and evidence.

This is release preparation evidence, not a semantic specification or a release approval. No branches were merged, deleted, or pushed during this validation pass. Final version/date bookkeeping remains for the release branch.

## Current stabilization status — 2026-09-09

The two remaining workflow/diagnostic gates are repaired in this stabilization commit:

- GNU-like debug builds now use full debug information. Clang PCH template frames
  retain their actual header paths, allowing runtime error recovery to select the
  generated application frame. The complete runtime-diagnostics suite passes with
  both Clang and GCC.
  Linux retains Clang as its default compiler. Debug artifacts can be larger;
  release optimization is unchanged.
- Runtime placement is resolved by one shared policy for selection and compilation.
  `runtime-build` now builds missing optional module artifacts even with an existing
  shared base. The new `test_scpp_runtime_build_bundle.php` uses an isolated shared
  cache and the public CLI to build the base, add curl, repeat the build, and link/run
  a strict project without rebuilding a project-local runtime. It passes.
- Diagnostic tests accept GCC and Clang's different typed-argument presentation
  while retaining the failing call-site location, source-level type explanation,
  and native type evidence; the full source-diagnostics suite passes with both
  compilers. Namespace-alias rejection remains a known-gap test and
  passes with both compilers after updating its source-located expectation.
- CMake registers mysqli-dependent tests only when `SCPP_WITH_MYSQLI` is enabled.
  The disabled configuration builds the runtime/wrappers and registers no mysqli
  tests even when live smoke tests are requested. The enabled configuration builds
  and passes the fetch-exhaustion test without requiring a live database.
- The full build-options suite passes, including shared/local artifact placement
  and project build behavior. Both authoring skills now describe the repaired
  reusable runtime maintenance path and compiler diagnostic notes.

A focused Clang PCH/application rebuild probe measured 8.23 seconds with `-g1`
and 8.24 seconds with `-g`; object size increased from 776,928 to 1,349,944 bytes,
while PCH size stayed at 29,661,260 bytes. This is one small local probe, not a
whole-project performance guarantee (`debug-info-cost.json` in the evidence
directory). Some diagnostic-suite cleanup logs contain transient directory-not-empty
warnings from background analysis; the assertions and process exit statuses pass.

The older entrypoint and three native defects are **explicitly deferred**, with
owners, alternatives, and completion criteria in
[release_debt_disposition_2026_09_09.md](release_debt_disposition_2026_09_09.md).
Their failing tests remain enabled and unchanged. No all-green native-suite claim
is made. Current fix evidence is under `/tmp/scpp-release-debt/`.

Remaining release work: validate the final combined
candidate and hosted CI with the recorded baseline exceptions, finalize version
and date bookkeeping, and follow the required PR/release workflow. Earlier hold
recommendations and failing counts below are historical, not the current status.

## Follow-up fixes after the reassessment — 2026-09-09

Managed struct fields are committed separately as `881fb1e4` (`Support managed
fields in value structs`). Subsequent release fixes are included in this stabilization commit.

- JSON: `Generator::wrapExprForExpectedType` now sends mixed payloads directly to
  the required typed-boundary conversion instead of coercing them first. Existing
  direct-initializer wrapper handling and explicit source casts are preserved.
  The complete strict-safety suite passes with both default Clang and explicit GCC,
  including missing fields, invalid integer shapes, recursive decoded vectors,
  typed JSON encoding, explicit casts, and recursion diagnostics.
- Module gating: unavailable runtime modules receive a semantic frontend
  diagnostic code. The fast build gate and CLI bucket classification share the
  same blocking-diagnostic policy. The unchanged inactive-curl reproduction now
  passes, and its durable test also checks a repeated build using cached analysis.
- The complete JSS project build/run suite passes, including scenarios that the
  original curl failure prevented from running. The complete STAN
  strict-discipline and build-worker integration suites also pass.

Focused logs: `/tmp/scpp-json-boundary-fix.log`,
`/tmp/scpp-json-boundary-fix-gcc.log`, `/tmp/scpp-curl-gate-fix.log`,
`/tmp/scpp-stan-release-fixes.log`, `/tmp/scpp-jss-release-fixes.log`, and
`/tmp/scpp-stan-worker-release-fixes.log`. These fixes do not resolve the Linux
default-compiler/source-location decision or the other deferred findings below.
The historical results and baseline comparisons below describe the earlier
candidate and must not be read as failures reproduced after these fixes.

## Reassessment after struct support — 2026-09-09

The original blanket blocker wording below was too broad. A failed check is not
necessarily a new candidate regression. This reassessment distinguishes behavior,
baseline status, and the recommended release decision; it does not mark any failed
check as passed or approve publication.

Baseline: isolated `main` archive at
`ff2ca601dc9ab69f7f391c7d56b752a68c4af15d`. Candidate: the original candidate commit
plus current working-tree changes, including managed struct support. The original
suite counts remain historical; the later struct checks are recorded separately
in [managed_struct_fields_validation_2026_09_09.md](managed_struct_fields_validation_2026_09_09.md).
This was a focused reassessment, not another full release-candidate run.

| Finding | Evidence / classification | Recommended disposition |
|---|---|---|
| JSON required numeric conversion and decoded vectors | Fresh focused tests pass on main and fail on the migrated candidate; the candidate failures also reproduce with GCC, matching the main baseline compiler. Main rejects non-integer JSON payloads at a required int boundary and accepts decoded vectors; candidate accepts 2.5 as int and fails vector compilation. The earlier missing-required-string failure is another observed symptom of the same boundary path. | **Fix before release. Confirmed functional regression affecting the mandatory JSON migration.** Excluding mixed from structs does not remove JSON's mixed payload or this issue. |
| Inactive curl module in JSS | The unchanged focused test passes on main (build rejected with the expected module diagnostic) and fails on current candidate (build accepted). | **Fix before release. Confirmed configuration-contract regression.** This establishes missing enforcement, not a security exploit or proof that curl executes successfully with the module disabled. |
| Top-level `return 0;` | Copied the candidate's new positive fixture unchanged into the isolated main test tree. It also fails there: `int_t<>` cannot be returned from native `int` entrypoint. | **Pre-existing compiler defect, newly exposed by a test.** Worth fixing, but not evidence this branch regressed the feature. If deferred, explicitly record the known failure rather than treating the positive test as passing. |
| Clang source diagnostic wording/location arrangement | Main passes with GCC and fails with explicitly selected Clang in the same way as the candidate: error at the call site, useful typed-argument note at the declaration. Invalid input is still rejected. | **Compiler-specific test expectation / presentation issue.** Make assertions recognize the valid error-plus-note arrangement; do not require GCC's exact wording on Clang. Not a functional release blocker by itself. |
| Clang runtime bounds source location | Main passes with GCC but also loses the source location with explicitly selected Clang. The bounds error itself is detected and the index/size/operation remain correct. | **Pre-existing Clang diagnostic defect with new default-path exposure.** Candidate changes Linux compiler preference from GCC to Clang. Before releasing that default change, restore location reporting or retain the prior default; this is more than a wording mismatch. |
| Three native failures: nullable increment, result/error comparison, mixed bool/int comparison | Earlier isolated-main compile/run reproductions remain valid; no candidate-only failure established. These mix absent operator support with a semantic/test expectation disagreement. | **Pre-existing native contract/test debt.** Reconcile against specs/config separately; do not assume all are stale tests, change runtime semantics merely to green them, or label them candidate regressions. |
| Namespace-alias known-gap fixture | The fixture explicitly expects compilation failure. Compilation still fails, but at a different diagnostic stage/message. | **Known unsupported feature plus diagnostic expectation drift.** Review its expected diagnostic, not a new positive-program failure. |
| mysqli-disabled CMake target | The unconditional `test_mysqli_fetch_exhaustion` registration is also present on main. Module-enabled test passes; disabled configuration attempts to link an unavailable symbol. | **Pre-existing test-build configuration defect.** Correct the target condition; does not establish broken mysqli behavior in a supported enabled build. |
| Shared `runtime-build` missing curl side artifact | Prior local reproduction remains unresolved. Main also has the `reuse` caller versus `shared` bundle branch; this pass did not reproduce the complete artifact workflow on main. | **Workflow defect; regression status unproven.** Verify the documented fresh-user rebuild path. Fix or document a tested public workaround before claiming that path works; do not promote source similarity alone to a baseline reproduction. |

**Revised recommendation:** hold for the two confirmed functional regressions
(JSON typed boundaries and inactive-curl enforcement), and resolve the Linux
default-compiler diagnostic decision. Treat the other findings as explicit
pre-existing defects, expectation updates, or remaining workflow validation—not
as an undifferentiated list of new release blockers. Passing affected checks and
final hosted CI are still required before publication.

Fresh evidence is under `/tmp/scpp-release-reassessment/`: focused JSON and curl
logs for main/candidate, `main-top-return.log`, and source/runtime diagnostic logs
with default GCC and explicit Clang. Focused scripts invoke original test methods
with their assertions preserved; the candidate JSON fixture includes the already
prepared `take(...)` migration. The top-level return fixture was copied only to
the temporary main archive. No production code or branch refs changed in this
reassessment.

## Changes prepared

- Guard `sys/resource.h` with the same Unix/macOS condition as the existing `getrusage` implementation. Windows keeps its existing fallback behavior.
- Document the checked JSON decode/encode migration for both PHP++ profiles and JSS, including `take`, successful null/false values, error recovery, and runtime rebuilding.
- Update the stale `explain-build` expected report shape to include the existing resolution-surface hash; the full test now passes.
- Make the condition-visibility fixture call a supported typed helper instead of an unrelated unavailable mixed method. The intended assignment-in-condition visibility diagnostics now pass.
- Update scoped-pack expectations for the recognized runtime `error` type, retaining the check that runtime types/helpers introduce no project-header dependency; the complete scoped-pack suite passes.
- Refresh async default-integer notation to `int_t<>` and remove a literal-spelling snapshot; both complete async suites pass, with runtime/output assertions preserved.
- Migrate strict-safety JSON fixtures to checked `take` extraction and assert successful decode/encode before typed-boundary checks. The migrated test exposes a real typed-boundary failure described below; its expectations remain strict.
- Review both repo-local authoring skills and their references; replace direct JSON extraction examples and fix adjacent stale error declarations/helper names.
- Expand `CHANGELOG.md` Unreleased to cover compiler-support runtime APIs, tokenizers, metadata/ABI work, scoped builds/grouping/cache/planner changes, STAN, tasks, const parameters, JSON recovery, fixes, and hotfix #214 since v0.1.74.

## Validation scope

- Enabled curated runtime tests, baseline and address/undefined/leak sanitizers.
- Enabled curated PHP tests in strict and legacy profiles, excluding opt-in external integrations.
- All 48 `tests/tools/test_*.php` scripts, including the candidate's changed workflows, async/JSS flows, UI/WebView checks, and the #214 concatenation regression.
- Fresh CMake Debug builds and CTest for native runtime tests, first with mysqli disabled and then with mysqli, regex, and curl enabled.
- Actual Windows MSVC compilation of a translation unit including `scpp/lang/php.hpp` and calling both memory-usage helpers.
- Compile/run JSON migration snippets in strict PHS, legacy PHS, and JSS; skill structure validation; whitespace review.

The earlier 30-check retired-branch audit is separate evidence and is not substituted for these checks. Generated operator matrices and opt-in external service integrations are not covered by this pass.

## Results

- Windows MSVC 14.44 (Visual Studio 2022): full PHP runtime umbrella and memory helper calls compile successfully with Windows SDK 10.0.26100.
- Curated runtime: 102/102 baseline and 102/102 address/undefined/leak sanitizer checks passed.
- Strict PHP++: 124/124 passed after building optional curl artifacts and rerunning four local-HTTP/file checks with TCP binding permitted. Initial missing-artifact and sandbox port-binding failures are setup failures, not passing results.
- JSON migration examples: strict PHS, legacy PHS, and JSS all build and print `{"name":"Ada"}`.
- Native optional-module build: 26/29 CTest entries pass; the two wrapper compile failures and mixed comparison assertion also reproduce on main. Regex, curl, JSON, async core, and mysqli fetch exhaustion tests pass.
- Legacy PHP++: 245/247 pass after rerunning local curl checks with TCP binding permitted. The remaining failures are listed below.
- Full task module regression script passes, including worker-pool configuration/reuse, ordered publication, caps/metrics, background errors, timeouts, result-index handling, and disabled-module diagnostics.
- Independent remaining strict-safety cases: typed-collection JSON encoding, explicit null/string casts, explicit integer casts, debug recursion diagnostics, and release opt-in recursion diagnostics pass. Numeric required-boundary and decoded-vector cases fail as described below.
- All 7 pre-tokenizer regressions pass, including const parameters.
- Both repo-local skills pass `quick_validate.py`; JSON examples and adjacent workflow guidance were reviewed.

CLI/tool scripts: **44/48 pass** after fixture corrections. Every script was attempted; failed scripts stop at their first assertion, so later scenarios remain unverified unless independently checked below. The table below records every outcome.

**Original validation disposition: hold; refined by the reassessment above.** The requested Windows, JSON documentation, skill, and release-note changes are prepared. Failed checks below remain recorded, but their existence alone does not establish a new release regression. Hosted CI still needs a fresh run after these edits are committed/pushed; the local MSVC check does not replace the complete Windows WebView job.

## Findings from the original validation (see follow-up status above)

- After migrating the strict-safety fixture to `take`, successful decoding followed by assigning a missing field to a required `string` local unexpectedly exits successfully. Independent remaining safety-case invocations also show decoded `2.5` being accepted as an `int`, and JSON-array-to-vector initialization failing compilation. Generated code nests a coercing `cast<T>` inside `required_cast<T>` (for example `required_cast<int_t<>>(cast<int_t<>>(row.get(...)))`); for vectors the inner cast is unsupported. The relevant owner is `Generator::wrapExprForExpectedType` and required-boundary emission. The prior direct-wrapper fixtures did not exercise the migrated value path correctly. Keep the required-value assertions strict. Checked encoding of all three typed collection fixtures passes.
- `int_000_top_level_return_zero.phs:2`: the candidate adds this positive legacy fixture, but `return 0;` lowers to returning `int_t<>` from native `int main()`. The emitted wrapped value cannot convert to the native return type.
- JSS project/module validation: the inactive-curl fixture unexpectedly builds successfully with only `filesystem` enabled while calling `curl_init()`. The test expects a source-mapped missing-module diagnostic. Earlier JSS JSON success/error scenarios in that script completed before this failure; later scenarios were not reached.
- `scpp runtime-build` with shared-runtime reuse reports the base artifact up to date without creating the requested curl side artifact. The explicit shared-bundle build service was used to prepare curl test artifacts. This workflow discrepancy remains separate from the JSON base-runtime migration.
- The mysqli-disabled CMake configuration still registers `test_mysqli_fetch_exhaustion`, which links with an unresolved MySQL connection symbol. The mysqli-enabled build links and passes that test. The disabled-module build configuration still needs correction.
- `test_nullable.cpp:108,110`: prefix/postfix increment on `nullable<int_t<>>` has no overload. Reproduced by compiling the fixture against an isolated `main` snapshot; the fixture and generated operator owner are unchanged between main and candidate.
- `test_or_wrappers.cpp:82`: `result<sample_box> == error` has no matching overload. Also reproduced against isolated main.
- Native mixed comparison fixture expects mixed bool/int equality to throw, but no exception occurs. The same assertion failure reproduces in a binary built entirely from the isolated main snapshot.
- `use_002_use_namespace_alias_basic`: this documented known-gap fixture still fails compilation, but compilation now reaches C++ diagnostics instead of the expected STAN unresolved-receiver text. Its diagnostic expectation needs review.
- Runtime bounds diagnostics loses the original `main.phs:2` location and source excerpt under the default Clang build, while retaining the correct index/size and operation. The complete runtime-diagnostics script passes with `SCPP_CXX=g++`; the default Clang path remains a failed check.
- Source diagnostics fixture expects GCC-style typed-argument wording at the call site; Clang emits a primary overload error and a typed-argument note at the declaration. The same script passes with `SCPP_CXX=g++`; the default Clang path remains a failed check.

## Recommended release path

1. Fix the confirmed JSON typed-boundary and inactive-curl regressions. Resolve the Linux default-compiler decision around source-located runtime diagnostics. Separately disposition the pre-existing entrypoint/native/CMake defects, update compiler-specific and known-gap diagnostic expectations, and verify the public runtime-build artifact workflow. See the reassessment table for the distinction between release gates and existing debt.
2. Rerun affected checks and hosted CI on the final committed candidate. Finalize version/date markers and checked-in release notes on the release branch.
3. Follow the required PR path: candidate into `develop`, then `release/<version>` into `main`; tag/publish from main with the checked-in notes. Synchronize develop to the same release commit and retire the candidate only after release verification.

## Reproduction artifacts

Core commands:

```bash
php tests/tools/run_tests.php gate --suite=runtime --jobs=4
php tests/tools/run_tests.php run --suite=php --profile=strict --jobs=4
php tests/tools/run_tests.php run --suite=php --profile=legacy --jobs=4
php tests/tools/run_tests.php run --suite=php --profile=strict --test=strict_curl --jobs=2
php tests/tools/run_tests.php run --suite=php --profile=legacy --test=curl --jobs=2
php generators/php/bin/check_pre_tokenizer_regressions.php
cmake -S runtime -B /tmp/scpp-release-native -G Ninja -DCMAKE_BUILD_TYPE=Debug -DSCPP_WITH_MYSQLI=ON -DSCPP_WITH_REGEX=ON -DSCPP_WITH_CURL=ON
cmake --build /tmp/scpp-release-native --parallel 2 -- -k 0
ctest --test-dir /tmp/scpp-release-native --output-on-failure
```

Run each `tests/tools/test_*.php` with PHP. Curl fixture reruns require local TCP binding; shared runtime artifacts for both profiles and the optional curl module must exist before curated tests run.

Raw command logs and result inventories are under `/tmp/simplecpp-release-validation/`. Test-runner per-case reports also live beside fixtures as `.test-results.json` files. The temporary artifact paths are local evidence, not durable CI artifacts.

## CLI/tool script inventory

| Script under `tests/tools/` | Final outcome |
|---|---|
| `test_scpp_async_core_language.php` | Passed after fixture correction |
| `test_scpp_async_surface_projects.php` | Passed after fixture correction |
| `test_scpp_build_benchmark.php` | Passed |
| `test_scpp_build_grouping_auto.php` | Passed |
| `test_scpp_build_grouping_manual.php` | Passed |
| `test_scpp_build_grouping_module.php` | Passed |
| `test_scpp_build_grouping_release.php` | Passed |
| `test_scpp_build_options.php` | Passed |
| `test_scpp_build_planner_state.php` | Passed |
| `test_scpp_build_reuse_integration.php` | Passed |
| `test_scpp_clean.php` | Passed |
| `test_scpp_compact_layout_acceptance.php` | Passed |
| `test_scpp_concat_transpile_performance.php` | Passed |
| `test_scpp_condition_visibility_diagnostics.php` | Passed after fixture correction |
| `test_scpp_const_params.php` | Passed |
| `test_scpp_docs.php` | Passed |
| `test_scpp_doctor.php` | Passed |
| `test_scpp_dynamic_alias_hotfix.php` | Passed |
| `test_scpp_dynamic_phase1.php` | Passed |
| `test_scpp_explain_build.php` | Passed after fixture correction |
| `test_scpp_jss_frontend_first_slice.php` | Passed |
| `test_scpp_jss_project_build_run.php` | Failed; see findings |
| `test_scpp_jss_samples.php` | Passed |
| `test_scpp_member_visibility.php` | Passed |
| `test_scpp_mysqli_strict_runtime_link.php` | Passed |
| `test_scpp_null_chain_runtime_and_isset.php` | Passed |
| `test_scpp_object_action_cache.php` | Passed |
| `test_scpp_object_action_identity.php` | Passed |
| `test_scpp_project_module_dependency_validation.php` | Passed |
| `test_scpp_project_module_public_api_validation.php` | Passed |
| `test_scpp_project_module_stan_summary_cache.php` | Passed |
| `test_scpp_project_unit_scoped_packs.php` | Passed after fixture correction |
| `test_scpp_runtime_diagnostics.php` | Failed; see findings |
| `test_scpp_source_diagnostics.php` | Failed; see findings |
| `test_scpp_source_inventory.php` | Passed |
| `test_scpp_stan_build_worker_integration.php` | Passed |
| `test_scpp_stan_diagnostics_session.php` | Passed |
| `test_scpp_stan_strict_discipline.php` | Passed |
| `test_scpp_strict_getenv_wrapper.php` | Passed |
| `test_scpp_strict_prefix_generic_ref_param.php` | Passed |
| `test_scpp_strict_runtime_catalog.php` | Passed |
| `test_scpp_strict_safety_edges.php` | Failed; see findings |
| `test_scpp_tasks_module.php` | Passed |
| `test_scpp_transpile_freshness.php` | Passed |
| `test_scpp_ui_module.php` | Passed |
| `test_scpp_union_compile_probe.php` | Passed |
| `test_scpp_update.php` | Passed |
| `test_scpp_webview_module.php` | Passed |
