# S07 exact-mixed type predicates
Doc Status: supporting

S07 is implemented and verified on dev002. `is_bool(mixed)`, `is_int(mixed)` and
`is_float(mixed)` now resolve through core metadata. The unchanged original strict
fixture passes end to end with exact stdout, empty stderr and exit zero. No
compiler or runtime implementation changed.

## Contract and owner

The [normative predicate contract](../../../../specs/builtins/type_predicates.md)
defines stored-kind checks without coercion, extraction or flow-sensitive narrowing.
Each predicate uses the existing non-retaining `observe` parameter contract and
returns a bool value. Core metadata owns the three source signatures and C++
bindings to the existing type-predicate header. No special compiler dispatch or
new retained model is needed.

False is a bool, zero is an int, and integral-valued floats, signed zero, infinities
and NaN remain floats. Null, strings and owned/shared/weak tables return false for
all three tests, including expired weak handles. Inputs are neither consumed nor
mutated; readonly values, temporaries and temporary-owner properties use the existing
observation lifetime path. Evaluating an argument happens once and may propagate
an error. The returned bool remains stable after the original storage changes.

## Verification

Host: `alex-ai@dev002.travelfuse.ro`; snapshot
`/home/alex-ai/mixed-predicates/20261020-01`; starting commit `71f3d607`.
PHP 8.5.11 uses CLI fallback because FPM differs; Clang 18.1.3 compiles the native
fixtures. Shared runners use twelve refilled slots. No full matrix or native
compiler rebuild ran.

- The existing runtime passed **69 checks across 23 mixed states**, before metadata
  admission. Stored kind/payload fingerprints and subsequent kind-changing writes
  verify preservation; shared table contents remain intact.
- The pre-admission original source reproduces unresolved `is_bool`.
- The original strict fixture then passes without edits, with output
  `not-bool`, `not-int`, `not-float`, `bool`, `int`, `float`, each on its own line.
  Its bytes match the pinned v0.1 source, not a rewritten substitute.
- Source wrappers repeat the 69 state checks. Dedicated observers prove readonly
  inputs, once-only producers, stable and temporary-owner properties, subsequent
  mutation, saved bool results, conditional use and propagation of division_by_zero.
  Both source programs run under AddressSanitizer.
- **47 rejected calls** cover wider types, wrong arity, borrowed results, an
  unsupported additional predicate and absence of flow-sensitive narrowing.
- **Six recovery cycles** verify invalid updates and retries with withheld output,
  repaired/fresh equivalence and no-op stability.
- Existing observation/catalog/callable/reference tests pass, including **5 native
  AddressSanitizer programs**. Mixed regressions pass **15 PHP suites and 324 native
  programs**; conditional regressions pass **46 native programs** and their scope,
  rejection, span and recovery checks.

There are **378 native executions** including the direct runtime probe and two
source programs, plus 375 regression programs. All **1,448 accepted command exits**
match expectations. All **6,277 remote source hashes** match; **217 runtime files**
are unchanged, with only `runtime/metadata/modules/core.json` excluded from that
unchanged count. Its diff contains exactly three signatures and three bindings.

## Evidence and closure

[Results](s07_results.json), [all attempt logs](s07_evidence.json.gz),
[initial manifest](s07_source_initial.json), [final manifest](s07_source_final.json)
and [static audit](s07_audit.json) retain provenance. The first source run passed
preparation and the original native program but the behavior observer misspelled
the emitted name of `saved_result`. Correcting that test-only name fixed the build;
the failed attempt is retained. Documentation closeout follows the tested snapshot.

S07 has no remaining blocker in its approved scope. Overall original-issue closure
is **26/64 (40.625%)**, an issue count rather than an effort estimate. The original
ledger finding remains historical evidence.

Scalar overloads, fixed widths, dynamic, wrappers, additional predicates and
flow-sensitive narrowing remain separate capabilities. The runtime's generic C++
fallbacks do not authorize wider source overloads. This closure does not classify
those wider domains as permanent language exclusions or close any other issue.
