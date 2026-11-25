# Native build caching and artifact invalidation review
Doc Status: supporting

2026-10-17. Review only, no fixes. PHP-hosted compiler, Clang 18.1.3, Linux. Shared CommandRunner/run_tasks with 12 slots; dependent build sequences ordered. All fixtures and runtime copies are under specs/planning/results/compiler_diagnostic_review_2026_10_18/native-review. No native compiler rebuild.

## Confirmed: upstream missed source edits reuse a native executable marked ready

Baseline main.phs:

```php
$x = 1; return $x;
```

Edit:

```php
$x = 1; return $y;
```

Keep the byte length and modification time equal, retain the Compiler instance, call exec_cpp(publication) without a file notification, then Native_CPP_Build::build(publication_directory) with the previous cache.

Observed:

| Attempt | Objects compiled | Objects reused | Links | Native status |
| --- | ---: | ---: | ---: | --- |
| Baseline | 4 | 0 | 1 | ready |
| Invalid unnotified edit | 0 | 4 | 0 | ready |
| Same invalid edit, explicitly notified | — | — | — | pending |
| Repaired source | 0 | 4 | 0 | ready |

The invalid unnotified edit returns the identical executable path and digest, and executing it returns 1 (the valid baseline result). Both source-manifest association and native status are identical to baseline. The fixture fixes mtime to 1700000000 and clears PHP's stat cache after each edit.

This extends the previously confirmed mtime/size source-detection limitation through actual native compilation and execution. It is NOT an independent defect in native object keys: the native builder receives the old ready publication. Native build owns generated artifacts, not PHS source discovery.

Root: compiler/my-try/01_prepare_inputs/module.php:106 and compiler/my-try/compiler/compile.php:343. Existing documented debt: docs/planning/incremental_strategy.md:115. Native association is published in 06_native/cpp_build.php after link success.

Reproduction driver: native-review/phs-sequence.php. Evidence: native-review/phs/results.json and phs/logs/execute-retained.*. Run with `php specs/planning/results/compiler_diagnostic_review_2026_10_18/native-review/phs-sequence.php FRESH_EXISTING_DIRECTORY`. It runs baseline, missed edit, notified rejection, repair, and the hash checks below; creates only its own source/publication/cache directories.

## Actual preparation gap does not bypass native invalidation

Edit from `$h hash<int, string>; return 0;` to `$h hash<int, float>; return 0;`, using explicit source notification.

Source preparation/emission/publication succeed (known hash-key gap). The native builder invokes Clang, which rejects `hash_t<T, T_KEY> requires a supported typed key family`. Native status is `pending\n`, no successful build result is returned, and repair reuses four correct objects and the earlier matching executable. The C++ publication remains ready because that status means its artifact set was published, not that Clang accepted it.

Evidence: native-review/phs/results.json, hash_invalid and hash_repair.

## Native-layer negative probes: no new stale-cache failure found

Native consumer fixtures use small C++ artifact sets published through Output_Publication, not hand-edited cache metadata. These are native-stage boundary tests, not PHS syntax reproductions.

1. Compile failure: `int answer(){return 7;}` -> `int answer(){return missing;}` with unchanged calling entry. Both initial failure and retry leave native status pending.
2. Link failure: remove answer.cpp from the published artifact set while entry.cpp still calls answer(). The old cached answer.o remains on disk but is not linked. GNU ld rejects undefined reference to answer(); native status pending.
3. Edited published file without republication: rejected by manifest content validation before object reuse; native status pending.
4. Ordinary runtime header edit preserving size and mtime: `inline int value(){return 7;}` -> `inline int value(){return x;}`. Clang rejects the updated header; native status pending. This distinguishes native content hashing from the weaker PHS discovery heuristic.
5. Corrupted cache objects: required object rebuilt. Relink may correctly be skipped when the rebuilt object has identical bytes.
6. Corrupted executable: relinked; repaired program executes with exit 7.
7. Pending source publication: native build rejects and marks native status pending.
8. Newly appearing header tested with `__has_include("optional.hpp")` selects `static_assert(false, "newly selected invalid code");`. Both PCH and no-PCH builds reject the edit, as do fresh caches. This candidate did not produce stale reuse.
9. Corrupted PCH: rebuilt; no fallback to stale object acceptance.
10. Invalid runtime header used by the PCH: adding `static_assert(false, "broken PCH input");` fails PCH compilation and leaves native status pending. Repair succeeds.

Main safeguards: 06_native/cpp_build.php:48 sets pending before admitted build validation; :59 authenticates every published artifact; :158 performs fresh dependency scanning; :163 hashes actual dependency contents; :174 keys links by exact ordered object membership; cached() validates binary digests and permissions.

## Evidence and scope

35 build attempts in the completed sequences: 15 native failure/recovery builds, 5 no-PCH include-discovery builds, 8 PCH include/corruption/recovery builds, 7 end-to-end PHS builds. All 13 rejected attempts leave native status pending.

Result files:
- native-review/results/failures/results.json
- native-review/include-results/has_include/results.json
- native-review/include-results/has_include_pch/results.json
- native-review/phs/results.json

Scripts: native-review/driver.php, run.py, run-includes.py, phs-sequence.php, run-phs.py. Initial has_include hypotheses in results/has_include* expected stale reuse and the harness stopped when the builder correctly rejected; the completed reruns are in include-results/. Those aborted initial hypotheses are excluded from the 35 attempts.

No independent native-cache key/invalidation bug or admitted failed build retaining native readiness was demonstrated. The confirmed ready/stale executable case requires the upstream source edit to be missed before publication. Existing documented toolchain shared-library/default-search assumptions, concurrent external mutations, process-kill/power-loss behavior, and hostile cache metadata were not expanded into new claims. Lock contention/pre-admission errors deliberately have different readiness semantics and are not reported as failed admitted builds.
