# Expanded v0.1 compatibility audit — 2026-10-17
Doc Status: planning

The expanded audit identifies **171 baseline-proved positive source failures needing
implementation**, **38 first blockers explained by explicit v0.2 changes**, and
**8 source cases needing a contract decision**. One additional negative source test
loses its required rejection because its enum comment annotation is ignored.
These are fixture counts, not counts of independent missing features.

This is the historical pre-S01 snapshot; its counts and raw results are retained.
The [strict follow-up](v0_1_compatibility_strict_causes.md) closes S01 after the
header fix and 76 passing strict compile-only reruns. Other profiles were not rerun.
No compiler or runtime fixes were made during the original expanded audit. The milestone remains unchanged-source
compatibility except for documented changes; identical C++ lowering is not required.
The [main report](v0_1_compatibility_report.md) owns that milestone and static API
inventory. This report owns the expanded execution evidence and its dispositions.

## Corpus and reproducibility

Baseline: newest previously verified v0.1 development head
`75de94c71e37060c564b6ade19850b7686bd457a`. v0.2:
`d675aca8251374555c9191067793175b43d8a79c` plus the recorded working tree.
The baseline/tag/GitHub comparison is in the main report.

The [997-entry manifest](../../tests/legacy_compatibility_all.json) includes every
one of the **375 PHP and 105 runtime metadata tests**, the three original extra
source tests, 37 further standalone native assertions, and 477 generation probes.
Fifteen entries are explicitly blocked/disabled; **982 entries were attempted**.
This is complete metadata discovery, not a claim that every legacy integration
harness has been ported or every discovered source has a behavioral oracle.

| Lane | Selected | Attempted | Evidence boundary |
| --- | ---: | ---: | --- |
| PHS/PHP source, including compile-only and negative tests | 378 | 366 | Original source, original profile, metadata or original extra-test oracle |
| Runtime C++ assertions and compile-fail tests | 142 | 139 | Original C++ test/support code against each runtime; separate from PHS exposure |
| Standalone generation probes | 477 | 477 | No compilation/run claim without an oracle |
| **Total** | **997** | **982** | Operator matrices excluded |

The [file inventory](v0_1_compatibility_expanded_inventory.json) also records 2,076
operator-matrix file entries, 196 generated artifacts, 65 unadapted tool/driver
files, 36 project/platform/resource files and three parity/benchmark files.
Metadata files are paired with their selected source; they are not extra tests.
Ordinary `tests/php/operators/` fixtures are included. Matrix-like array/sample
names are not excluded merely because their names contain “matrix”.

Reproduce discovery or replay the retained selection:

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --all --results /tmp/compat-FRESH
python3 -B compiler/my-try/tests/legacy_compatibility.py --manifest compiler/my-try/tests/legacy_compatibility_all.json --results /tmp/compat-replay-FRESH
```

See the [runner guide](v0_1_compatibility_testing.md) for filtering, result meanings
and prerequisites. Ubuntu Clang 18.1.3 / C++23 and PHP 8.5.7 were used. The shared
12-slot runner fell back to CLI when FPM could not bind its local socket. Both
compilers are PHP-hosted; generated programs and runtime tests were compiled,
not the compiler itself. Runtime metadata optimization flags were retained;
source compilation uses the adapter's flags, not the old harness's `-O3` profile.

The [retained results](v0_1_compatibility_expanded_results.json) contain every
source, source hash, raw result, first-stage diagnostic, reviewed disposition,
manifest and run-specific compiler/runtime/adapter hashes. Full local artifacts:
`/tmp/compat-expanded-full`, `/tmp/compat-expanded-supplement`,
`/tmp/compat-process-recheck`, `/tmp/compat-regex-recheck`, and
`/tmp/compat-strict-async-isolated`.
Those five runs recorded 3,423 commands. Corrected adapter results supersede seven
initial results, which remain in the JSON for auditability.

## Raw execution results

| Result | Source | Runtime C++ | Probes |
| --- | ---: | ---: | ---: |
| Build/run oracle matched | 119 | 122 | — |
| Compile-only oracle matched | 2 | — | — |
| Baseline passed, v0.2 failed | 217 | 10 | — |
| Both rejected; semantic reason still needs review | 24 | 2 | — |
| Baseline rejection not preserved at generation | 1 | — | — |
| Baseline oracle unproven in this toolchain/run | 3 | 5 | — |
| Blocked or baseline-disabled | 12 | 3 | — |
| Both generated; later stages unverified | — | — | 140 |
| Only baseline generated | — | — | 101 |
| Baseline generation failed | — | — | 236 |

The 236 failed baseline probes include deliberately rejected/unannotated examples;
they are not 236 regressions. The 101 generation differences need behavioral or
rejection oracles before entering the confirmed implementation queue. A negative
test rejected earlier for an unrelated unsupported construct does not establish
that v0.2 enforces the intended rule.

## Actual source implementation work

These first blockers have successful baseline evidence and no established semantic
exception. The counts include the one lost negative rejection in the annotation row.
A row can hide later missing operations; no source was rewritten to bypass it.

| First blocker / owner | Cases | Concrete obligation |
| --- | ---: | --- |
| Runtime header dependency / emitted unit integration | 79 | `runtime_error.hpp` uses `std::uintptr_t` without sufficient include coverage. Standalone emitted units fail C++ compilation. Many simple compile-only tests share this one blocker; this is not 79 missing language features. |
| Legacy type annotation admission | 45 | Preserve original comment-based local, property and parameter types. Forty-four positive cases fail; enum raw-int assignment also loses its required generation rejection. Typed literals losing their comment target are **not** the approved untyped-literal break. |
| Library/API exposure | 14 | Missing first calls include `var_dump`, `is_bool`, `strtotime`, `dbg`, `substr_replace`, `strpos`, `file_get_contents`, `source_buffer_span` and `phs_tokenize_buffer`. Strict names replacing legacy-profile names are not source compatibility without an approved migration. |
| Scalar/wrapper operation preparation | 10 | Bool equality/inequality, float arithmetic/comparison, string comparison and `shell_exec` falseable-result identity cases reach an integer-only admission path. These are ordinary fixtures, not matrix coverage. |
| Reference foreach | 7 | Original reference bindings are rejected as deferred. A safe reference-iteration contract remains milestone work. |
| `fixed_array<T,N>` source type | 3 | Size arguments are rejected by the type parser. Other fixed-array tests are masked by annotation handling. |
| Property/index interpolation | 2 | Original `{$object->field}` / indexed interpolation is rejected; simple variable interpolation passes. |
| Brace-free control flow | 1 | Original if/else bodies require admission without adding braces to the corpus. |
| Nullable union spelling | 1 | Original `string|null` return syntax is rejected; accepting `?string` alone does not preserve this spelling. |
| Scalar/null conditional inference | 2 | Original `flag ? "ok" : null` local inference is rejected. The later nullable return does not currently rescue it. No approved removal was established. |
| Receiver reference-return origin | 1 | Original method reference return falls outside the one-reference-parameter model. |
| Cyclic class declarations in partitioned output | 1 | Mutually referring class headers emit an unresolved generated type name. |
| Reference-capture callable signature | 1 | An explicitly typed `function<int()>` with reference capture fails exact-signature preparation. |
| Non-scalar constants | 1 | Original top-level array constant hits scalar-only constant preparation. |
| Typed `hash<mixed>` property | 1 | Preparation succeeds but native instantiation fails in the specialized hash implementation. |
| Root CLI arguments | 1 | Original `$argc/$argv` spelling is unresolved; helper APIs do not alone preserve it. |
| Borrowed source span type | 1 | Text-builder fixture still lacks `byte_span` with the `source` module selected. Two other fixtures stop at `source_buffer_span` in the API row. |
| Async syntax | 1 | The original async language nowdoc passes v0.1 but fails v0.2 parsing. Native async runtime assertions pass separately. |
| **Total** | **172** | **171 positive failures + one lost negative rejection** |

Representative exact source paths, diagnostics and owners are recoverable by
`cases[].disposition.group` in the retained JSON. The broad architectural owners
and static gaps remain COMP-01 through COMP-13 in the main report. In particular,
passing native async, process, regex, locks or Storage assertions does not prove
the corresponding complete PHS frontend/API/project contract.

## Intentional v0.2 source changes

| First blocker | Cases | Authority and migration boundary |
| --- | ---: | --- |
| Untyped data construction no longer defaults to mixed | 35 | [Data initialization](../../../../specs/data_initialization.md): supply a concrete target. These originals genuinely lack a target; comment-typed constructions were classified separately above. Later array operations remain untested. |
| Default JSON decode produces dynamic | 1 | [Dynamic families](../../../../specs/dynamic_types.md): `strict_json_001` expects mixed output. Select `json_decode_mixed` when that is the intended family. |
| Integer comparisons require matching signedness | 1 | [Integer amendment, §7](../../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons): `strict_datetime_003` compares `uint64` with signed literal zero. Explicitly convert the literal; this is not missing monotonic-clock support. |
| Nonvirtual instance-method hiding is rejected | 1 | [Inheritance contract](../../../../specs/inheritance_v0_2.md): `classes_027` redeclares a nonvirtual instance method. A virtual redesign changes dispatch, so migration must preserve the application's intended behavior rather than mechanically adding an abstract method. |
| **Total** | **38** | First blockers explained by existing normative contracts, not newly invented exclusions |

A successful migrated companion test would be useful, but would not turn any of
these original-source failures into an unchanged-source pass.

## Contract decisions still required

Eight baseline-proved positive cases remain separate from both implementation
failures and accepted migrations:

- Three standalone untyped-null cases: decide the intended source contract rather
  than extending the untyped-container exception to null automatically.
- Two `(object) [...]` foreach cases: reconcile the original construction spelling
  with mixed/dynamic family changes; no blanket spelling exclusion was established.
- One inherited static-property/constant redeclaration case (`classes_023`): the
  normative inheritance document describes a bounded first-slice restriction.
  Decide whether it is a milestone exclusion or deferred compatibility work.
- One `php::microtime` runtime alias and one bare `error` sentinel case: decide
  exposure versus an explicitly approved migration.

Deferred support, “strict first”, or an available replacement spelling alone does
not authorize dropping a compatibility obligation.

## Runtime differences: separate from PHS support

Of ten baseline-proved native failures, eight have an identified intentional first
blocker:

| Native first blocker | Cases | Authority |
| --- | ---: | --- |
| `empty()` rejects scalars/null/missing or unsupported leaves | 5 | [Count/empty/isset](../../../../specs/count_empty_isset_contract.md), [array semantics](../../../../specs/array_semantics.md): only existing strings/collections qualify. |
| Successful structured `take` clears the prior error | 1 | [Error and take contract](../../../../specs/error_values_and_take.md): the old assertion expects the seed error to survive. |
| Default JSON decode changes family | 1 | [Dynamic families](../../../../specs/dynamic_types.md): old native tests compare decoded values against mixed kind enums. |
| Implicit dynamic-to-mixed boxing is removed | 1 | [Dynamic families](../../../../specs/dynamic_types.md): explicit scalar/null bridges; no implicit cross-family container conversion. |

The remaining two are `test_collections.cpp` and `test_collection_adapters.cpp`:
the current runtime lacks `scpp/collections.hpp`. They establish an absent old C++
collection surface, not automatically a PHS lowering requirement. Reconcile them
with the collection-transform source/API gap (COMP-06), without requiring identical
old runtime representation or headers.

## Baseline failures, adapter corrections and remaining coverage

Eight raw baseline oracles remain unproven:

- Three source cases: `test_07_finally_return` (exception construction),
  `int_000_top_level_return_zero` (wrapper return from native `int main`) and
  `use_002_use_namespace_alias_basic` (namespace alias emitted as a type alias).
- Three native cases: `test_mixed_t_dynamic` (expected exception not thrown),
  `test_nullable` (nullable increment unavailable), and `test_or_wrappers`
  (result/error-sentinel comparison does not compile).
- Two native unique-pointer compile-fail cases reject correctly under Clang but
  miss GCC-specific metadata wording. A focused GCC recheck passed both original
  diagnostic oracles on **both** runtimes; its results are retained in the JSON.
  The raw Clang results remain visible rather than silently loosening the oracle.

Seven setup results were corrected and rerun: three source/text-buffer module
selections, repository-relative resources for `take_007`, and process/regex provider
linkage, plus isolation of the async nowdoc from unrelated tool fixtures.
The isolated async test confirms the same missing syntax as the original first pass.
`strict_source_002`, native process and native regex now pass. The other
three expose actual missing APIs/types. The final runner includes these adapters;
initial setup failures are not counted as missing support.

The fifteen blocked entries are three baseline-disabled interpolation cases, nine
curl PHP tests and three curl/MySQL native tests. Optional provider/project/network
adapters remain explicit work. Unadapted tool harnesses and project/platform inputs
are listed individually in the file inventory. Many invoke the old compiler,
assert old C++ text, need multi-file/project setup or external services; running
those scripts unchanged would not prove v0.2 compatibility. The three remaining
runtime files are the PHP/native Storage parity pair and a benchmark.

The runner's six oracle tests and both PHP adapter syntax checks passed through
the shared runner. No full operator matrix, native compiler rebuild, project CLI
parity suite, or compiler fix was performed. Next work can be selected from the
implementation groups above, while keeping migration fixtures, unresolved contracts
and unadapted harnesses visible. There is no defensible whole-language completion
percentage yet.
