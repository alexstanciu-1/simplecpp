# Strict v0.1 compatibility: grouped causes
Doc Status: planning

Later [scalar admission follow-up](v0_1_compatibility_scalar_admission.md): all 48
ordinary strict operator fixtures now compile, resolving S03; 60 affected matrix
rows pass their existing oracles. Counts below retain their dated audit meaning.

The separate [full generated matrix audit](v0_1_compatibility_operator_matrix.md)
records 22,816 cases on dev002. Its counts do not change this earlier fixture ledger.

Active scope for this follow-up: **strict-profile source tests only**. Legacy-profile
tests, native C++ runtime tests and generation-only probes are excluded from this
view. Operator matrices remain excluded; ordinary strict operator fixtures remain.
The broader [audit](v0_1_compatibility_expanded.md) is retained as background evidence.

The retained original selection uses the v0.1 strict **library profile**. That does
not make every source spelling strict syntax: S02 comment declarations are
**deferred legacy syntax**, excluded from the current implementation queue. Their
original results remain in the ledger; strict-declaration companions are reported
separately and do not count as unchanged-source passes.

## Result and evidence boundary

The retained strict subset contains **126 cases**: 125 metadata-backed fixtures
and the original async language nowdoc. Evidence after the S01 follow-up yields:

| Disposition | Cases |
| --- | ---: |
| Build/run oracle matched | 14 |
| Compile-only oracle matched | 78 |
| Missing strict implementation, grouped below | 17 |
| Deferred legacy syntax (S02) | 6 |
| Intentional v0.2 change | 3 |
| Contract decision required | 1 |
| Negative rejection controls needing review | 2 |
| Curl provider/project adapter blocked | 5 |
| **Total** | **126** |

The 27 remaining positive differences therefore split into **17 strict implementation blockers +
6 deferred legacy-syntax cases + 3 intentional changes + 1 unresolved contract**. None of the selected baseline
positive oracles failed. These are affected-case counts, not independent feature
counts or a language-completion percentage.

This extraction reuses the dated audit at v0.2 `d675aca8251374555c9191067793175b43d8a79c`
against v0.1 `75de94c71e37060c564b6ade19850b7686bd457a`; it is not a new full strict
suite run. Async alone was rechecked after correcting harness isolation: the
expanded run had copied unrelated tool fixture sources alongside its extracted
nowdoc. The isolated original still passes v0.1 (`4242`) and fails v0.2 at `async`
(`main.phs`, byte 6). The corrected result and old setup result are both retained.
On 2026-10-17, S01 was repaired by adding the direct `<cstdint>` include to
`runtime/include/scpp/runtime_error.hpp`, which owns the `std::uintptr_t` use.
**All 76 original S01 cases now pass both versions' compile-only oracles**, including
every emitted translation unit. A standalone include of the actual header also
passes Clang C++23 syntax checking. No additional blockers surfaced in this cohort;
these checks do not prove runtime behavior. No other compiler/runtime fixes were made.

The [S01 follow-up evidence](v0_1_compatibility_strict_s01_results.json) retains the
76 fresh results, source hashes and manifest. Its 382 commands are in
`/tmp/compat-strict-s01-fixed`; the shared 12-slot runner used CLI fallback because
FPM was unavailable. Neither compiler was rebuilt natively. The remaining 50 cases
retain earlier original-source evidence: **17 strict implementation failures across
10 open cause groups**, six deferred legacy-syntax cases, three intentional changes,
one contract decision, two rejection controls and five adapter blockers, plus the
prior 16 passes. They were not rerun in the S01 follow-up.

The [operator checkpoint](v0_1_compatibility_operators.md) freshly replays all 48
ordinary strict operator fixtures: 43 compile-only passes and the same five S03
failures. It separately records behavioral suites and a stale boxed-suite rejection
expectation; no new original-source cause group was found.

## Implementation causes

Each case belongs to its **first observed blocker**. Counts are disjoint; later
unsupported operations can be masked. Grouping by an owner/cause does not claim
that one patch will make every affected program pass.

| ID | Cause | Cases | Owner and required proof |
| --- | --- | ---: | --- |
| S01 | Resolved: standalone runtime header dependency | 0 | Direct `<cstdint>` include added in the owning header. All 76 affected strict compile-only cases and the standalone-header check pass. |
| S02 | Deferred legacy syntax | 6 deferred | Comment type declarations are outside strict-first work, even in strict-profile fixtures. See the separate migration probe below. |
| S03 | Bool equality/inequality reaches integer-only preparation | 5 | Shared operator preparation. Prove bool `==` / `!=` in direct return, local and assignment contexts. These are ordinary tests, not the matrix pass. |
| S04 | Falseable string result identity reaches integer-only preparation | 1 | Wrapper comparison preparation. Preserve `shell_exec(...) === false` and the subsequent successful string path. Do not merge this with the bool/bool contract merely because diagnostics resemble S03. |
| S05 | Reference capture fails an explicit callable signature | 1 | Callable/capture preparation. `function<int()>` with `use (&$a)` must preserve the reference capture and its exact declared return contract. |
| S06 | Brace-free if/else bodies rejected | 1 | Control-flow parser. Admit the original bodies without rewriting the test with braces. The later `strpos` call is another likely blocker, counted separately only where reached. |
| S07 | Mixed predicate API missing | 1 | Core library exposure. First unresolved call is `is_bool`; the fixture also exercises further predicates. Expose the agreed contracts and prove the complete fixture, not just name lookup. |
| S08 | Debug API missing | 1 | Core library exposure. `dbg` is unresolved. Preserve its declared output oracle; do not substitute a different logging call. |
| S09 | String search/replacement APIs not admitted | 2 | Strings contract and module exposure. First failures are `substr_replace` and `strpos`. Reconcile the held string-unit contracts before adding signatures; a missing registration alone is not a complete diagnosis. |
| S10 | Borrowed source spans absent | 3 | Source types, lifetime and API exposure. Two cases fail at `source_buffer_span`, one at `byte_span`, with the `source` module selected. Owning buffers/line indexes are not the missing concept. Span-dependent text building and stable hashing remain masked. |
| S11 | Typed tokenizer facade absent | 1 | Tokenizer library exposure. First unresolved call is `phs_tokenize_buffer`; prove typed token-buffer access through the original source. |
| S12 | Async source syntax absent | 1 | Async callable/suspension model and frontend. Original `async function` is not parsed. This needs its semantic owner and bounded contract before lowering work. |
| **Remaining strict blockers** | **10 open cause groups; S01 resolved; S02 deferred** | **17** | |

S01 was one shared build blocker, not evidence of 76 unrelated missing constructs.
All its cases are compile-only: 41 operator, 19 control-flow, nine function, three
namespace, two bool and two import fixtures. Their behavior is not yet proved.

S02 consists of `strict_datetime_001/002`, `strict_filesystem_001/002`, and
`strict_io_001/002`. Their library profile is strict, but their comment type syntax is legacy. The
[strict guide](../../../../specs/simple_cpp_php_strict_quick_learn.md#typed-shorthand-note)
and [catalog](../catalog/01_literals_locals.md#type-var-005) defer that syntax.
Missing comment admission is not a strict implementation blocker.

S10 consists of `strict_source_001_buffer_span`, `strict_source_003_stable_hash`,
and `strict_strings_009_text_builder`. `strict_source_002_line_index` already passes
when its module is selected. This separates missing span support from setup errors.

The [cause ledger](v0_1_compatibility_strict_causes.json) lists every source path
under its cause ID, owner, raw result, diagnostic, original disposition and evidence
directory. It also lists passes, negative controls, intentional changes and blocked
cases, so they cannot silently fall into the implementation queue.

## S02 strict-declaration probes — 2026-10-17

The six [retained companions](../../tests/legacy_compatibility_strict_s02.json)
replace only `$name /** Type */` with `$name Type`. All 25 replacements were
checked against the pinned original bytes; calls, control flow and expected output
are unchanged. Original fixtures and their six deferred S02 results are preserved.

All six companions pass the v0.1 build/run oracle. On v0.2:

| Companion | Result | Actual blocker |
| --- | --- | --- |
| `strict_datetime_001_datetime_surface` | Build/run passes | None observed |
| `strict_datetime_002_common_format` | Build/run passes | None observed |
| `strict_filesystem_001_fs_surface` | Build/run passes | None observed |
| `strict_filesystem_002_fs_error_paths` | Build/run passes | None observed |
| `strict_io_001_io_surface` | Generation fails | S13: unresolved `resource_handle` type |
| `strict_io_002_io_open_failure` | Generation fails | S13: unresolved `resource_handle` type |

The four passes match the original exit/stdout/stderr oracles on both versions.
They demonstrate the exercised datetime/filesystem operations after syntax migration,
not unchanged-source compatibility or complete module coverage.

**S13 — IO handle type admission** is a real strict blocker, owned by the runtime
IO type/API catalog and handle contract. Both failures report
`S2S needs one resolved type for resource_handle`. Current runtime metadata has no
`resource_handle` declaration or IO module catalog; this is not an omitted selection
of an available IO metadata module. The failure masks later `io_open`, `take`, IO
operations, handle lifetime and output behavior; no claim is made that type
registration alone would make either program pass.

The original-source queue remains **17 affected cases in 10 open groups**. The
migration lane adds **two blocked companions in one new group (S13)**: 19 failing
test programs across 11 groups when both lanes are considered, with their distinct
source-compatibility boundaries retained. S02 itself remains deferred legacy syntax.

[Retained probe evidence](v0_1_compatibility_strict_s02_results.json) includes both
versions' results, original/variant hashes, compiler/runtime hashes and manifest.
The run recorded 38 commands under `/tmp/compat-strict-s02-declarations`, using the
shared 12-slot runner with CLI fallback. No compiler/runtime fixes or native compiler
rebuilds were performed for this probe. The runner gained explicit `source_variant`
manifest support; it does not silently rewrite original compatibility fixtures.

Replay only these companions:

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --manifest compiler/my-try/tests/legacy_compatibility_strict_s02.json --results /tmp/compat-strict-s02-FRESH
```

## Intentional changes: migration tests, not restoration work

| Case | Existing v0.2 decision | Follow-up |
| --- | --- | --- |
| `strict_control_flow_001_php_helpers` | [Data initialization](../../../../specs/data_initialization.md) requires a known concrete literal target. | Add a separately identified explicitly typed companion; preserve the original failure record. Later helper operations remain untested. |
| `strict_datetime_003_monotonic_precision` | [Integer arithmetic/comparisons §7](../../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons) require matching signedness; `uint64 > 0` crosses signedness. | Explicitly convert the literal in a migration companion. Do not report the monotonic-clock API missing. |
| `strict_json_001_json_surface` | [Dynamic families](../../../../specs/dynamic_types.md) make default `json_decode` return dynamic. The original output is mixed. | Select `json_decode_mixed` where mixed trees are intended, then prove the rest of the fixture separately. |

`strict_strings_005_php_microtime_static_call` is the one **contract decision**:
keep the original `php::microtime` alias or approve a precise migration. A missing
resolved `php` type does not establish permission to remove that source spelling.

## Controls and blocked cases

Two closure negatives are not positive compatibility failures:

- `closures_008_missing_return_type_negative`: both versions reject missing
  callable return intent; review the full intended rejection contract.
- `closures_009_container_storage_negative`: v0.2 first rejects untyped data
  construction. That masks whether callable-container storage is rejected for the
  intended reason; merely observing a rejection is insufficient.

The five `strict_curl_001` through `strict_curl_005` cases remain adapter-blocked.
They require optional curl provider/project resources; some also need HTTP/network
setup. They are not five demonstrated compiler defects. Additional unadapted
strict project examples remain in the full inventory, outside these 126 cases.

## Proposed order and strict-only replay

S01 is complete; S02 legacy syntax is deferred. S03–S05 isolate smaller preparation contracts;
S06–S09 cover parser and API admission. S10–S12 need their deferred span, tokenizer
and async contracts handled through the owning models. S13 separately needs the IO
handle/type/API contract before implementation. Keep migrations, the alias
decision, negative controls and curl setup as separate work streams. This is a
suggested sequence, not authorization to implement compiler changes in this audit.

Replay exactly the resolved S01 cohort with the
[76-case manifest](../../tests/legacy_compatibility_strict_s01.json):

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --manifest compiler/my-try/tests/legacy_compatibility_strict_s01.json --results /tmp/compat-strict-s01-FRESH
```

The runner currently returns exit code 1 for an all-`compile-compatible` run because
its exit-zero policy requires every case to be `compatible-proved` (build/run).
For this cohort, inspect the summary: the successful result is 76
`compile-compatible` cases, with both versions passing. Runner policy is unchanged.

The [strict-only manifest](../../tests/legacy_compatibility_strict.json) contains
all 126 selected cases and no legacy-profile or native-runtime entries:

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --manifest compiler/my-try/tests/legacy_compatibility_strict.json --results /tmp/compat-strict-FRESH
```

Add `--filter PATH_SUBSTRING` for a focused replay. Do not add `--all`: that option
expands discovery back to both profiles. Result meanings and toolchain prerequisites
remain in the [runner guide](v0_1_compatibility_testing.md).


## S04 resolved — 2026-10-20

The [V-L06/S04 replay](float_identity.md) compiles and executes
`strict_process_001_shell_exec_contract.phs` unchanged, with exact expected stdout.
The fixture is byte-identical to pinned v0.1; deterministic false-sentinel/string
identity controls also pass. Earlier V-L07 admission work resolves this original
S04 blocker. No process/runtime/output implementation change was needed. The table
and original failure evidence above remain the dated strict-04 classification.
