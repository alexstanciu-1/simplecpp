# Changelog
Doc Status: supporting

This file is the authoritative checked-in source for release notes referenced by:

- `specs/git_workflow_release_procedure.md`

## Unreleased

### Changes

## 0.1.49 - 2026-05-19

### Additions

- Added a planning note under `specs/planning/` that defines the contract-first helper unification policy for shared strict and legacy helper names.
- Added focused strict regression coverage for `shell_exec(...)` so the shared helper contract stays pinned in strict mode.

### Fixes

- Fixed strict shared helper resolution so plain names that also exist in legacy now preserve the same visible branching model instead of silently exposing stricter wrapper-only contracts.
- Fixed strict `strpos(...)` and `strrpos(...)` exposure so shared plain-name calls preserve the expected `int|false` contract instead of leaking internal nullable search semantics into author-facing control flow.
- Fixed strict `hex2bin(...)` exposure so the shared plain-name call preserves the expected `string|false` contract instead of exposing internal structured-error helper behavior.
- Fixed strict `explode(...)` and `implode(...)` shared-name composition so the plain PHP-like helper pair continues to work naturally under the restored contract-first surface.
- Fixed strict onboarding, profile docs, builtin contracts, examples, and checked-in tests so they now teach and enforce the contract-first rule consistently across shared helper names.

### Breaking Changes

- None

### Migration Notes

- Shared helper names such as `strpos`, `strrpos`, `hex2bin`, `explode`, `implode`, and `shell_exec` should now be read with the same visible branch contract in both `legacy` and `strict`.
- In strict projects, if a helper keeps a plain shared name, prefer the same control-flow habit you would use for the legacy PHP-shaped contract.
- Subsystem/domain helper families remain explicitly prefixed in strict mode, including `fs_*`, `io_*`, `regex_*`, `curl_*`, and `dt_*`.
- Agent Skill review completed: no `.agents/skills/*` updates were required for this hotfix because the release changes policy/docs/tests/runtime helper routing without changing repo-local skill instructions.
## 0.1.48 - 2026-05-18

### Additions

- Added exact regression coverage for `isset($object->typedHash[$missingKey])` on typed-hash object properties and for literal strict ternary branches that mix `"text"` with `null`.

### Fixes

- Fixed keyed `isset(...)` lowering for typed-hash property paths so forms like `isset($b->items[$missing])` now stay on the keyed runtime helper path and return `false` for missing keys instead of lowering through `hash_t::at(...)` and throwing.
- Fixed strict ternary branch normalization so value-like branch pairs such as `"ok" : null`, `null : "ok"`, and matching `nullable<T>` with `null` normalize through `nullable<T>` in the runtime helper instead of failing with an incomplete `condition_ternary_result<..., null_t>` instantiation.

### Breaking Changes

- None

### Migration Notes

- No source migration is required.
- Agent Skill review completed: no `.agents/skills/*` updates were required for this hotfix because the fixes only adjust runtime/helper and generator lowering behavior without changing the authored strict surface.

## 0.1.47 - 2026-05-18

### Additions

- Added a planning note under `specs/planning/` that records the v1 helper-naming simplification direction for strict projects.

### Fixes

- Fixed strict PHP helper resolution so common language-adjacent helpers such as `trim`, `ltrim`, `rtrim`, `substr`, `substr_compare`, `substr_replace`, `explode`, `implode`, `hex2bin`, `bin2hex`, `number_format`, `strlen`, `strpos`, `strrpos`, `strtolower`, `strtoupper`, `lcfirst`, and `ucfirst` resolve by their plain PHP-like names again in strict projects.
- Fixed strict onboarding, profile docs, examples, and checked-in PHP tests so they teach and exercise the restored plain helper surface consistently instead of the old `str_*` variants for those general helpers.
- Fixed repo-local strict Agent Skill guidance so the skill and its references now match the current strict helper naming surface.

## 0.1.46 - 2026-05-18

### Additions

- Added focused regression coverage for `string|null` ternary lowering, indexed `unset()` on `vector<>`, keyed `unset()` on typed `hash<>`, and friendly `.phs` `<?php` source-header diagnostics.

### Fixes

- Fixed `string|null` union lowering so strict typed paths normalize nullable string unions consistently instead of degrading to non-null `string_t` handling.
- Fixed indexed `unset($vector[$i])` so typed `vector<>` values now lower through keyed unset support and compact remaining elements safely.
- Fixed keyed `unset()` coverage for typed `hash<>` containers so the supported keyed-unset path is pinned by regression tests alongside the vector change.
- Fixed `.phs` source diagnostics so files that begin with `<?php` now fail early with a friendly unsupported-source message instead of surfacing an internal parser error.

### Breaking Changes

- None

### Migration Notes

- In strict projects, prefer the restored plain helper names for general language-adjacent helpers such as `trim`, `substr`, `explode`, `implode`, `hex2bin`, `strlen`, and `strtolower`.
- Subsystem/domain helper families remain prefixed in strict mode, including `fs_*`, `io_*`, `json_*`, `dt_*`, and `regex_*`.
- Agent Skill review completed: `.agents/skills/simple-cpp-php-strict/*` was updated for this hotfix to reflect the restored strict helper naming surface.
- No source migration is required.
- Agent Skill review completed: no `.agents/skills/*` updates were required for this hotfix because the existing strict-skill guidance already forbids leading `<?php` in `.phs` files and the other fixes do not change authoring workflow guidance.

## 0.1.45 - 2026-05-17

### Additions

- Expanded `scpp explain-build` with focused views for `files-transpiled`, `files-reused`, `outputs-rebuilt`, `entrypoint`, `final-output`, `generated-files`, and `ninja-target`.
- Added direct Ninja target guidance to `scpp explain-build`, including a ready-to-run `ninja -C ... -d explain ...` hint for the current executable target.

### Fixes

- Fixed build-report visibility so `scpp explain-build` now surfaces concrete source-to-generated/object/output mappings instead of only a high-level rebuild summary.
- Fixed explain-build usability so it now warns that path-shaped executable outputs such as `.prism/build/main` are not valid Ninja target names.
- Fixed documentation gaps after `scpp update` by documenting a one-time `scpp clean` plus rebuild troubleshooting step for stale per-project `.prism/` state.

### Breaking Changes

- None

### Migration Notes

- Use the focused `scpp explain-build` views when you need one exact answer instead of the full summary, especially for transpile decisions, final output paths, generated files, or direct Ninja debugging.
- After `scpp update`, if an existing project behaves oddly and the same problem does not reproduce in a fresh project, try `scpp clean` followed by `scpp build` once to refresh stale `.prism/` state.
- Agent Skill review completed: no `.agents/skills/*` updates were required for this hotfix; the release updates `specs/simple_cpp_php_strict_quick_learn.md` and related build/docs guidance only.

## 0.1.44 - 2026-05-17

### Additions

- Added a runtime-chain-safety planning note under `specs/planning/` plus a v2 planning note for future guarded nullable-path evaluation ideas.

### Fixes

- Fixed plain nullable/object-handle chain access so the covered `shared_p`, `unique_p`, and `nullable` operator paths raise a controlled runtime exception instead of crashing natively on null access.
- Fixed general chained `isset(...)` probing so forms like `isset($root->child->name)` lower through a safe runtime probe path instead of forcing ordinary eager chain evaluation.

### Breaking Changes

- None

### Migration Notes

- Prefer `isset($node->child->name)` or `!isset($node->child->name)` for nullable path guards instead of long manual `=== null || ...` chains.
- A direct nullable/object-handle chain like `$root->child->name` now fails with a project-shaped runtime exception where the covered wrappers previously could crash the process.
- Agent Skill review completed: `.agents/skills/simple-cpp-php-strict/*` and `specs/simple_cpp_php_strict_quick_learn.md` were updated for this hotfix.
## 0.1.43 - 2026-05-16

### Additions

- Added an opt-in `curl` runtime module backed by libcurl, with explicit build-time detection and clear failure messages when the module is enabled but libcurl support is unavailable.
- Added strict `curl_*` authoring support for handle-oriented request flows, including local file transport, local HTTP server flows, and opt-in external HTTPS coverage.
- Added legacy PHP-curl wrapper coverage over the first-pass runtime surface so legacy-profile projects can exercise matching `curl_*` flows for file, HTTP GET, POST, and JSON round-trips.
- Added curl-focused native runtime tests, strict sample projects, strict and legacy PHP test suites, and harness support for per-test runtime modules, local PHP test servers, and opt-in external-network runs.

### Fixes

- Fixed runtime-module propagation so app compilation inherits curl module compile definitions consistently when a project enables `runtime.modules = ["curl"]`.
- Fixed profile-specific runtime symbol loading so legacy PHP generation no longer gets strict runtime symbol mappings merged over legacy entries.
- Fixed PHP test harness project materialization so reruns do not keep stale per-test files that can interfere with HTTP fixture-backed curl tests.

### Breaking Changes

- None

### Migration Notes

- Projects that want curl support must enable the opt-in `curl` runtime module in `prism.json` and have libcurl development headers available at build time.
- Network-backed curl regression coverage is intentionally opt-in; pass `--include-network` to `tests/tools/run_tests.php` when you want the GitHub HTTPS integration test to run.
- Agent Skill review completed: `.agents/skills/simple-cpp-php-strict/*` was updated to reflect curl-capable strict module guidance for this release.

## 0.1.42 - 2026-05-16

### Additions

- Added a planning catalog for typed `hash<>` `foreach` expectation-gap, control, and regression-guard examples under `specs/planning/`.
- Added a generator audit note for the typed `hash<>` `foreach` stabilization work, documenting current causes and possible generator-side solution directions.

### Fixes

- None

### Breaking Changes

- None

### Migration Notes

- These planning notes do not change runtime, generator, or user-facing language semantics; they are working documents intended to support future implementation passes.
- Agent Skill review completed: no `.agents/skills/*` updates are required for this release because the release adds planning documentation only.

## 0.1.41 - 2026-05-16

### Additions

- Added regression coverage for typed `hash<>` `foreach` flows over non-`this` method-call sources and locals initialized from those method calls.

### Fixes

- Fixed typed `hash<>` `foreach` lowering so known typed method-call sources no longer lose their declared return type merely because the source is not `this`.
- Fixed typed `hash<>` `foreach` loop-local binding so unknown non-dynamic sources no longer silently degrade into `mixed_t`, which previously pushed downstream lowering toward dynamic `.get(...)`, keyed field-write fallbacks, and synthetic repair casts.
- Fixed typed `hash<>` `foreach` flows so nullable-string helper boundaries, typed object field access, and typed reindexing continue to lower through the concrete runtime iterator key/value contract.

### Breaking Changes

- None

### Migration Notes

- Re-run typed `hash<>` `foreach` cases that previously needed defensive key/value stabilization or produced dynamic-object fallback lowerings; direct key-helper usage and typed property access should now stay on the typed path for the covered generator flows.
- Agent Skill review completed: no `.agents/skills/*` updates are required for this hotfix because the existing strict authoring guidance already matches the user-facing typed-hash `foreach` behavior after this generator fix.

## 0.1.40 - 2026-05-12

### Additions

- Added a safer browser-test-ui subprocess environment so PHP, Ninja, and compiler runs no longer inherit unrelated Apache or PHP-FPM request variables.

### Fixes

- Fixed the browser test UI so headerless `.phs` manual input is executed through the same PHP-compatibility wrapper used by the real Prism++ loader, instead of being echoed back as raw source text.
- Fixed the browser test UI AST and token debug fixture path so pre-tokenized manual input is parsed through the shared PHP-compatibility adapter instead of a raw headerless parse path.
- Fixed the browser test UI manual-input sandbox flow so a stale legacy `sandbox/src/alt.php` scaffold file no longer poisons unrelated one-file `scpp build` runs.
- Fixed the browser test UI compile/run path so the stable `/simple-cpp/stable/test/` harness successfully builds and executes the generated C++ sample again.

### Breaking Changes

- None

### Migration Notes

- The browser test UI now forwards only a small safe allowlist of environment variables into child PHP, Ninja, and compiler processes; ad hoc CGI-style request variables are no longer part of that subprocess contract.
- Agent Skill review completed: no `.agents/skills/*` updates are required for this hotfix because the existing guidance already matches the current authoring and diagnostics workflow.

## 0.1.39 - 2026-05-12

### Additions

- Added explicit strict-mode philosophy guidance to the strict quick-learn, the strict-mode guidance profile, and the strict Agent Skill.

### Fixes

- Clarified that strict mode is aimed at explicit, durable code for long-lived projects rather than the shortest possible code.
- Clarified that most strict code should stay typed after a small number of well-chosen entry boundaries, instead of framing strict mode as constant stabilization work.
- Clarified the role of `take(...)` as an explicit boundary helper for wrapper-shaped results, absence, and failure states.

### Breaking Changes

- None

### Migration Notes

- Strict docs and Agent Skill guidance now describe strict mode more explicitly as a long-term readability and maintenance posture, with special attention to typed boundaries and explicit wrapper handling.
- Agent Skill review completed: `.agents/skills/simple-cpp-php-strict/*` was updated for this hotfix.

## 0.1.38 - 2026-05-12

### Additions

- Added regression coverage that locks in the reuse-mode dependency hotfix contract for stale dependency artifacts.

### Fixes

- Fixed the build reuse integration regression test so it now expects `scpp build` reuse mode to fail early and clearly when dependency artifacts are stale, instead of asserting the old silent-success behavior.

### Breaking Changes

- None

### Migration Notes

- If dependency sources changed and reusable dependency artifacts are stale, default reuse mode should now be expected to stop early and direct the user to rerun with `--build-dependencies`.
- Agent Skill review completed: no `.agents/skills/*` updates are required for this hotfix because the existing guidance already matches the current reuse-mode behavior.

## 0.1.37 - 2026-05-11

### Additions

- Expanded the strict PHP++ quick-learn with clearer authoring, diagnostics, project composition, and dependency workflow guidance for current strict-profile projects.
- Added a dedicated strict-skill diagnostics reference covering build, run, saved reports, and common validation flows.

### Fixes

- Fixed dependency reuse mode so `scpp build` and `scpp run` now fail clearly when reused dependency artifacts are missing instead of falling through to a later Ninja error.
- Fixed dependency reuse mode so changed dependency sources no longer allow stale dependency objects to be reused silently during `scpp build` and `scpp run`.
- Tightened strict skill and quick-learn guidance to steer users toward current `.phs`, strict typing, dependency, and diagnostics workflows.

### Breaking Changes

- None

### Migration Notes

- If a consumer project reuses dependency artifacts, `scpp build` and `scpp run` now stop early when those artifacts are missing or stale; rerun with `--build-dependencies` to refresh them.
- Strict projects should prefer the updated quick-learn and strict skill guidance for project composition, diagnostics, and typed authoring patterns.
- Agent Skill review completed: `.agents/skills/simple-cpp-php-strict/*` was updated for this hotfix.

## 0.1.35 - 2026-05-11

### Additions

- Added relation-aware generated line maps so generated `.line.tsv` data can describe whether a generated line maps `exact`, `above`, `below`, or `around` the originating source line.
- Added helper-free runtime diagnostics recovery that records generated locations from runtime error traces and remaps them back to original `.phs` lines through `.line.tsv`.
- Added source-facing strict aliases `error`, `resource_handle`, `nullable_resource_handle`, and `falseable_resource_handle` for authoring and examples while preserving the existing runtime lowerings.

### Fixes

- Fixed strict runtime diagnostics so generated cast failures no longer depend on generated helper wrappers and still resolve back to the original authoring line.
- Fixed debug builds used for runtime diagnostics to emit line information needed for generated-frame recovery.
- Fixed strict typed-local shorthand scanning so source-facing alias declarations work for both initialized and uninitialized typed locals.
- Normalized strict docs, examples, and tests to the source-facing `error` and `resource_handle` aliases.

### Breaking Changes

- None

### Migration Notes

- Rebuild strict projects if you rely on runtime source remapping; debug builds now emit relation-aware `.line.tsv` files and runtime reports resolve generated failures through the catch-time trace path.
- Source-facing strict code should prefer `error` and `resource_handle` aliases over the runtime-shaped `error_t` and `resource_handle_t` names.
- Agent Skill review completed: the strict diagnostics reference was updated and no additional `.agents/skills/*` changes are required for this release.

## 0.1.33 - 2026-05-10

### Additions

- None

### Fixes

- Fixed strict `dbg(...)` and `dbg_if(...)` release lowering so generated C++ carries the original `.phs` source path and compiles cleanly against the runtime `dbg_at(const char *, ...)` API.

### Breaking Changes

- None

### Migration Notes

- Retry strict projects that started failing in `v0.1.32` with `Call to undefined method Scpp\\S2S\\Generator\\Generator::cppStringLiteral()`, `Undefined property ... $currentSourcePath`, or a generated `dbg_at(string_t(...), ...)` compile error.
- Agent Skill review completed: no `.agents/skills/*` updates are required for this hotfix because the existing strict debug-helper guidance remains correct.

## 0.1.32 - 2026-05-10

### Additions

- Added regression coverage for strict runtime cast failures remapping back to the original `.phs` file and line through generated `.line.tsv` artifacts.

### Fixes

- Fixed strict runtime cast diagnostics so generated `mixed_t -> typed` cast failures remap back to `original_file` / `original_line` in saved reports when generated location data is available.
- Fixed strict runtime diagnostic summaries and `scpp error` output to prefer the remapped original source location for these failures instead of stopping at generated/runtime context.
- Updated the repo-local strict Agent Skill guidance to reflect that saved runtime reports may now expose remapped `original_file` / `original_line` attribution directly.

### Breaking Changes

- None

### Migration Notes

- Re-run strict projects that previously stopped at `Runtime error while running the built program.` for `cast<...>(mixed_t)` failures; the saved report and short summary should now point back to the original authoring line when generated line maps are available.
- Agent Skill review completed: strict diagnostics guidance was updated for this hotfix.

## 0.1.31 - 2026-05-09

### Additions

- Added strict-safe PHP++ debug helpers: `dbg`, `dbg_if`, `dbg_set`, `dbg_unset`, and `dbg_enabled`.
- Added composable `DBG_*` inspection flags for type, value, shape, fields, keys, length, source, caller, JSON, raw text, pointer identity, compact output, and explicit depth controls.
- Added best-effort runtime inspection for scalars, `mixed_t`, hashes, vectors, wrappers, nullable-like values, pointers, and handles with recursion protection and compact hex pointer IDs.
- Added source-aware lowering for `dbg(...)` and `dbg_if(...)` so debug output can report the originating `.phs` file and line.
- Added strict debug helper guidance to the PHP++ strict quick-learn and repo-local Agent Skill docs.

### Fixes

- None

### Breaking Changes

- None

### Migration Notes

- Strict projects can use `dbg("label", $value, DBG_SHAPE | DBG_DEPTH_2 | DBG_PTR)` for focused runtime shape inspection without ad hoc generated-C++ probes.
- Use `dbg_set("gate", $condition)`, lower-call `dbg_if("gate", ...)`, and `dbg_unset("gate", $condition)` for scoped debugging. Duplicate gate sets and missing unsets intentionally fail loudly.
- Object field reflection remains best-effort and may report unsupported details instead of crashing.

## 0.1.30 - 2026-05-09

### Additions

- Added generated-line `CodeBlock` records so source and header buffers carry source line/column metadata until final render.
- Added regression coverage that verifies generated `.line.tsv` output maps a lowered `php::echo_one(...)` call back to the original `.phs` source line.

### Fixes

- Fixed the PHP flow test harness so `.phs` oracle runs execute a temporary pre-tokenized PHP-compatible copy instead of the raw PHP++ source.
- Fixed runtime config normalization so an already-normalized `runtime.language_profiles.php.profile` value is preserved on later config passes instead of falling back to `legacy`.
- Removed the expression-level `with_runtime_context` runtime diagnostic wrapper from generated C++ and the runtime support header so runtime source attribution can move onto the generated-location plus `.line.tsv` remap path.

### Breaking Changes

- None

### Migration Notes

- Rebuild projects that rely on generated source maps so `.line.tsv` files are regenerated with statement-level source line metadata.
- Agent Skill review completed: strict diagnostics guidance now points agents at saved runtime reports first, then generated C++ and `.line.tsv` artifacts when source attribution is not yet present.

## 0.1.29 - 2026-05-09

### Additions

- Added the default `datetime` runtime module with reusable `scpp::dt` APIs for Unix timestamps, monotonic timing, sleeps, UTC ISO helpers, common local datetime formatting, and common local datetime parsing.
- Added strict PHP++ `dt_*` datetime helpers backed by the shared runtime API, including `dt_now`, `dt_now_ms`, `dt_monotonic_ms`, `dt_sleep_ms`, `dt_format`, `dt_format_now`, `dt_parse`, `dt_format_iso_utc`, and `dt_parse_iso_utc`.
- Added legacy PHP wrapper support for `time()`, `date()`, and `strtotime()` on top of the same runtime implementation.
- Added datetime docs, strict quick-learn guidance, Agent Skill guidance, native runtime tests, and PHP surface regression fixtures.

### Fixes

- None

### Breaking Changes

- None

### Migration Notes

- Strict projects should prefer the `dt_*` helpers over legacy PHP-shaped `date()` and `strtotime()` names.
- Phase 1 intentionally supports common numeric datetime forms only: `YYYY-MM-DD`, `YYYY-MM-DD HH:MM:SS`, `YYYY-MM-DDTHH:MM:SS`, `YYYY-MM-DDTHH:MM:SSZ`, and core format tokens such as `Y-m-d H:i:s`.
- Named timezones, locale names, calendar arithmetic, and natural-language `strtotime` expressions remain deferred.

## 0.1.28 - 2026-05-09

### Additions

- Added regression coverage for strict runtime type failures reporting source-level diagnostics through `scpp run` and `scpp error`.
- Added regression coverage for strict dependency project exports composing before local generated headers after a dependency was previously built standalone.

### Fixes

- Fixed strict runtime cast failures so generated code can attach the original source file, source line, expression, expected type, operation, and actual runtime kind to structured diagnostics.
- Fixed `scpp run` so structured runtime failures are saved to `.prism/last_error.json` and summarized through source-level context instead of leaking only generated/runtime stderr.
- Fixed runtime profile normalization so legacy `runtime.language_profiles.php.profile` config and object-style `runtime.languages.php.profile` config are both resolved before build/run diagnostics record project mode.
- Fixed the remaining strict same-project composition issue from #58 by including dependency project export headers before local generated headers, sorting exported dependency headers with the same base-before-derived rule, and regenerating dependency entrypoints when a standalone project is reused as a dependency.

### Breaking Changes

- None

### Migration Notes

- Re-run failing strict projects and inspect `scpp error` / `scpp full-error`; supported runtime type failures now prefer source `.phs` context when available.
- Rebuild larger strict multi-project graphs that removed same-project generated `.hpp` includes; dependency export headers now participate in the same generated composition ordering used by project unit headers.
- Agent Skill review completed: strict diagnostics and composition guidance were updated for this hotfix.

## 0.1.27 - 2026-05-09

### Additions

- Added the opt-in PCRE2-backed regex runtime module with typed `regex_*` APIs, legacy PHP `preg_*` wrappers, opportunistic JIT use, UTF-8 `/u` coverage, match offsets, named captures, replacement backreferences, practical split/match-all flags, and explicit runtime errors for deferred offset-capture and unmatched-as-null output forms

### Fixes

- Stabilized strict project sample validation so checked runnable samples rebuild required runtime artifacts instead of depending on a warm local runtime cache.
- Normalized the regex sample source files to the current Prism++ `.phs` source format without PHP opening tags or `declare(strict_types=1)`.

### Breaking Changes

- None

### Migration Notes

- Enable the `regex` runtime module explicitly and install PCRE2 development files manually for now; full PHP parity for `PREG_OFFSET_CAPTURE`, `PREG_UNMATCHED_AS_NULL`, and dynamic callable-array handling remains deferred

## 0.1.26 - 2026-05-09

### Additions

- Added regression coverage for strict same-project namespaced units whose generated header order would otherwise reference a later namespace/class.
- Added regression coverage for same-project derived-class headers needing base-class headers before derived headers.

### Fixes

- Fixed generated same-project composition so `__project_units.hpp` includes a project-wide `__project_fwd.hpp` before generated unit headers.
- Fixed same-project generated header ordering so base-class headers are emitted before derived-class headers when both are discovered in the project unit set.
- Updated the repo-local strict Agent Skill guidance to reflect that same-project `.phs` files are composed by `scpp build` and must not include generated `.hpp` files.

### Breaking Changes

- None

### Migration Notes

- Retry removing same-project generated `.hpp` `require` / `include` lines after updating; nested namespaces and common base/derived ordering cases are now covered by generated project composition.
- Keep `/** @lib-export */` for dependency-visible cross-project declarations.

## 0.1.25 - 2026-05-09

### Additions

- Added regression coverage for strict same-project `.phs` files composing without source-level generated `.hpp` includes.

### Fixes

- Fixed strict project builds so same-project generated headers are force-included through an internal `.prism/generated/__project_units.hpp` build artifact.
- Fixed project composition guidance to make generated `.hpp` names an internal build detail rather than a PHP++ source authoring surface.

### Breaking Changes

- None

### Migration Notes

- Remove source-level `require` or `include` statements that point at generated `.hpp` files; same-project `.phs` composition is handled by `scpp build`.
- Agent Skill review completed: strict project composition guidance remains aligned with the existing repo-local skill; no `.agents/skills/*` updates are required.

## 0.1.24 - 2026-05-09

### Additions

- Added focused regression coverage for `scpp run` runtime-library launch environment handling and Linux shared-runtime SONAME emission.

### Fixes

- Fixed `scpp run` for strict projects that built successfully but failed to launch from the project root because `libruntime.so` could not be resolved.
- Fixed Linux reusable runtime linking so `libruntime.so` declares a stable SONAME instead of allowing executables to record a project-relative shared-library path.
- Fixed the `scpp run` launch environment so Unix-like systems prepend the runtime directory through the platform loader path (`LD_LIBRARY_PATH` on Linux/Unix, `DYLD_LIBRARY_PATH` on macOS) while Windows continues to use `PATH`.

### Breaking Changes

- None

### Migration Notes

- Rebuild the reusable runtime or run `scpp run --force` once after updating if an existing project still has a binary linked against the old slash-containing runtime dependency path.
- Agent Skill review completed: no `.agents/skills/*` updates are required because existing `scpp run` validation guidance remains correct.

## 0.1.23 - 2026-05-09

### Additions

- Added regression coverage for top-level PHP++ constants with array initializers so the generator cannot regress to missing constant source-line metadata.

### Fixes

- Fixed PHP++ constant IR construction so top-level constants, class constants, and enum cases carry source-line metadata before header emission.
- Fixed the generator crash on top-level `const` declarations where `ConstantDecl::$line` was missing during header line-map emission.

### Breaking Changes

- None

### Migration Notes

- No migration is required.
- Agent Skill review completed: no `.agents/skills/*` updates are required because this hotfix restores existing documented constant lowering behavior and does not change authoring guidance.

## 0.1.22 - 2026-05-09

### Additions

- Added a repo-local Agent Skill at `.agents/skills/simple-cpp-php-strict/` for strict PHP++ / PHS app authoring, validation, diagnostics, project composition, and common PHP-habit pitfalls
- Added `scpp docs <name>` to print curated local Markdown documentation without requiring web access
- Added local documentation entries for strict authoring, diagnostics, build workflow, profiles, examples, Agent Skill guidance, and AI onboarding
- Added focused regression coverage for the `scpp docs` registry, successful doc lookup, and unknown-doc failure path

### Fixes

- Updated release workflow rules so `.agents/skills/*` must be reviewed and kept current before release publication
- Updated strict PHP++ quick-learn, getting-started, README, and project build docs to surface the new local docs workflow

### Breaking Changes

- None

### Migration Notes

- Use `scpp docs` to list local documentation names.
- Use `scpp docs strict` or `scpp docs diagnostics` when an agent or user needs local strict PHP++ guidance without browsing the web.

## 0.1.18 - 2026-05-08

### Additions

- Added `--entry=<path>` support to `scpp build` and `scpp run` so one invocation can target a project-local source file without editing `prism.json`
- Added persistent `tests/.run-tests/` PHP test workspaces so repeated flow-test runs can reuse warm `.prism` build state instead of recompiling from scratch

### Fixes

- Fixed echo lowering so adjacent exported echo nodes now emit direct sequential `php::echo_one(...)` calls instead of large batched `php::echo_eval(...)` lambda packs
- Fixed the PHP flow test harness to build through `scpp` project mode rather than one-off direct compiler invocations, while preserving per-test result files and cwd-sensitive fixture behavior
- Fixed PHP runtime symbol qualification so generated `echo_one` calls resolve through the runtime symbol registry just like other known PHP helpers

### Breaking Changes

- None

### Migration Notes

- `scpp build --entry=...` and `scpp run --entry=...` override the configured `prism.json` entrypoint for that invocation only
- `tests/.run-tests/` is generated state and should remain ignored; warm reruns of individual PHP flow tests now become much faster after the first build

## 0.1.17 - 2026-05-08

### Additions

- Added scanner regression coverage for allowed inline typed comment slots, including leading/trailing property comments, inline parameter comments, and inline function-like return comments

### Fixes

- Fixed type metadata ownership so params, properties, locals, and function/method/closure return comments are now sourced from pre-tokenizer scanner annotations instead of raw `php-ast` doc-comment attachment
- Fixed the accepted inline type-comment surface so only recognized typed slots are honored, while detached forms such as `/** @var T */` remain non-authoritative
- Fixed closure and arrow-function typed comment handling so it follows the same scanner-owned slot rules as ordinary function-like sites

### Breaking Changes

- None

### Migration Notes

- Inline type comments remain supported, but only in recognized typed slots such as `$x /** T */ = ...`, `function (/** T */ $x)`, `public /** T */ $x`, `public $x /** T */ = ...`, and immediate post-signature return slots
- `TypeCommentExtractor` has been removed from the active pipeline; scanner-owned annotation metadata is now the single type-comment source of truth

## 0.1.16 - 2026-05-08

### Additions

- Added explicit runtime maintenance commands and flags: `scpp runtime-build`, `--build-runtime`, `--build-dependencies`, and `--force`

### Fixes

- Fixed the public `scpp build` and `scpp run` defaults so they now reuse existing runtime and Prism project dependency artifacts unless explicit rebuild flags are requested
- Fixed `scpp update` so a real fast-forward rebuilds the default reusable runtime cache automatically, and `scpp update --force` rebuilds that cache even when the checkout is already current

### Breaking Changes

- `--reuse-runtime` and `--reuse-dependencies` are replaced by `--build-runtime` and `--build-dependencies`

### Migration Notes

- `scpp build` and `scpp run` now default to warm-build reuse for runtime and dependency artifacts
- Use `--build-runtime` and `--build-dependencies` when you explicitly want those heavier rebuild steps
- Use `scpp runtime-build [--debug|--release] [--force]` to rebuild the reusable runtime cache directly
- Use `scpp update --force` to rebuild the default reusable runtime cache even when no Git update is pulled
## 0.1.15 - 2026-05-08

### Additions

- Added a scanner-owned pre-tokenizer path for PHP++ shorthand type syntax across locals, properties, parameters, and function-like return sites
- Added shorthand-type regression fixtures and regression scripts for rewritten-source and annotation-ownership validation
- Added build option coverage and integration coverage for runtime/dependency warm-build reuse behavior
- Added `public_html/test` pre-tokenized AST/PHP execution support so browser-driven test runs can exercise shorthand typed syntax

### Fixes

- Fixed nested closure and arrow return-type ownership so callable annotations no longer depend on raw `php-ast` doc-comment attachment quirks
- Fixed the project build graph so lower-level build-service calls can reuse existing runtime and dependency artifacts without recompiling them unless explicitly requested
- Fixed the `public_html/test` harness so shorthand typed syntax is parsed and executed through the pre-tokenized source path instead of failing in raw PHP parsing

### Breaking Changes

- None

### Migration Notes

- Typed doc-comment forms such as `$count /** int */ = 0;` remain supported, but shorthand surface forms such as `$count int = 0;` are now first-class and normalize through the pre-tokenizer

## 0.1.14 - 2026-05-06

### Additions

- None

### Fixes

- None

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.13 - 2026-05-06

### Additions

- Added targeted PHP regression coverage for typed-hash foreach over a fresh local initialized from a method returning a nullable typed object, preserving direct key usage at a `?string` helper boundary

### Fixes

- Fixed fresh-local generator type capture to reuse namespace-aware return-type recovery, preserving typed hash property shapes across method-return locals
- Fixed the remaining reproduced Open M3 typed-hash foreach-key to nullable-string helper boundary by preventing `assembled->properties` loops from degrading foreach keys to `mixed_t`

### Breaking Changes

- None

### Migration Notes

- Explicit `(string)` stabilization should no longer be required in the reproduced `assemble_path(...): ?model` then `foreach ($assembled->properties as $property_name => ...)` Open M3 flow

## 0.1.12 - 2026-05-05

### Additions

- Added targeted PHP regression coverage for typed `hash<shared_p<T>>` foreach keys passed into method helpers expecting `?string`

### Fixes

- Fixed generator argument wrapping so concrete `T` values passed to `nullable<T>` parameters no longer degrade into synthetic `cast<nullable<T>>(...)` bridges
- Fixed the reproduced typed-hash foreach key to nullable-string helper boundary seen in real Open M3 traversal flows

### Breaking Changes

- None

### Migration Notes

- Explicit `(string)` stabilization should no longer be required at typed `hash<T>` foreach-key to `?string` helper boundaries in the reproduced flow

## 0.1.10 - 2026-05-05

### Additions

- Added regression coverage for `scpp --doctor` best-effort subprocess timeout handling
- Added `foreach` planning/checklist notes for the long-term generator cleanup
- Added targeted PHP regression coverage for typed `hash<shared_p<T>>` `foreach` key/value flows, direct helper-key usage, and direct reindexing into typed hashes

### Fixes

- Fixed `scpp --doctor` so best-effort Git remote and compiler-launcher probes time out instead of hanging indefinitely
- Fixed the PHP test harness `--jobs=1` path so focused debugging runs execute sequentially without the flaky worker subprocess layer
- Fixed typed `hash<shared_p<T>>` generator lowering across direct `foreach` key/helper flows by qualifying declared hash/object types with the active PHP namespace before emission
- Fixed generator forward-declaration collection so imported object types referenced through doc-typed hash params/returns do not degrade into bogus local forward declarations

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.8 - 2026-05-04

### Additions

- Added first-class PHP-surface `hash<T>` support that lowers to runtime `hash_t<T>`
- Added typed hash coverage for keyed literals, keyed access, properties, mixed keyed-plus-append literals, and `foreach` including by-reference forms

### Fixes

- Fixed typed hash lowering across `isset`, `empty`, dim access, append, and typed `foreach` value preservation
- Fixed runtime `foreach` bridge support so typed `hash_t<T>` containers iterate correctly without falling back to mixed-only handling

### Breaking Changes

- None

### Migration Notes

- Replace PHP-surface `hash_t<T>` examples with `hash<T>` in authored code and docs

## 0.1.7 - 2026-05-04

### Additions

- Added project-level Prism dependencies through `dependencies` in `prism.json`
- Added native library configuration through `libraries` in `prism.json`
- Added `/** @lib-export */` support for dependency-visible top-level functions, classes, interfaces, and constants
- Added generated per-project export manifests and composed project export headers for dependency builds
- Added documentation for project dependencies, project exports, and `@lib-export` usage in the build and AI onboarding docs

### Fixes

- Fixed multi-project build planning so dependency projects are scanned, generated, and linked as part of one build graph
- Fixed transitive dependency resolution with deduplication and cycle detection
- Fixed constant export detection for doc-comment based `@lib-export`
- Removed the generated PCH warning caused by emitting `#pragma once` in PCH main files

### Breaking Changes

- None

### Migration Notes

- Projects that want cross-project declaration visibility should mark public dependency surfaces explicitly with `/** @lib-export */`

## 0.1.4 - 2026-04-26

### Additions

- Clarified that the PHP S2S generator is type-blind and not a semantic compiler
- Strengthened AI onboarding guidance to prevent fallback to standard PHP semantics
- Added explicit documentation for generator responsibility boundaries
- Introduced `docs/future_thoughts/` for non-authoritative future ideas

### Fixes

- Removed implicit future promises from active documentation by relocating them to planning docs

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.3 - 2026-04-26

### Additions

- Completed semantic-matrix coverage across the active operator families, including conditional selection, arithmetic, logical, comparison, bitwise, unary, probes, and compound-assignment surfaces
- Added runtime-supported iterable and `take(...)` extraction behavior for `result<T>`, `result_or_false<T>`, and `result_or_bool<T>` wrapper flows used by filesystem-style results

### Fixes

- Fixed wrapper-lifted runtime/operator behavior across arithmetic, logical, bitwise, ordering, equality, identity, and conditional-selection lowering paths
- Fixed matrix semantics and enablement boundaries for mixed-condition truthiness, `unset_*` handling, and remaining elvis / ternary consistency gaps

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.2 - 2026-04-23

### Additions

- None

### Fixes

- Fixed `scpp --doctor` so compiler launcher detection no longer fatals when no launcher command has been resolved yet

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.1 - 2026-04-23

### Additions

- Added AI onboarding documentation under `docs/ai_onboarding/`
- Added repository guidance for coding style, workflows, examples, repo structure, and language-model usage
- Added `AGENTS.md` and linked onboarding guidance from existing docs

### Fixes

- No user-facing fixes recorded in this release beyond documentation/process updates

### Breaking Changes

- None

### Migration Notes

- None
