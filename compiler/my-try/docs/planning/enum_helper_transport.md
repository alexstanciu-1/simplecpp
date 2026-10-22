# VD08-H01 / VD08-H02 concrete enum helpers and presence
Doc Status: planning

## Outcome — 2026-10-22

The approved transport slice implements `enum_name`, `enum_value`,
`enum_from_value` and plain-value `isset` on `nullable<E>` / `?E` and `result<E>`
under default prelowering. It changes no runtime implementation or language
semantics. V-D08 remains partial; overall closure stays **58/64 (90.625%)**, with
two partial and four other open issues. The earlier
[enum-wrapper report](enum_wrappers.md) retains its original failure evidence.

## Owners and preserved contracts

`CPP_Compiler_Functions` owns one enum helper body for both ordinary artifacts and
compiled implementations. A bounded `enum_helper` request in the existing runtime
operation bridge retains settled `prepared_enum_call` facts. Existing selected-call
sequencing snapshots arguments; no source-name dispatch or new registry was added.
Specialization identity includes the helper, qualified enum, ordered case names and
values, and physical input/result identities. Metadata edits and retirement therefore
rebuild or remove demand through the existing publication/specialization owners.

`Value_Probe_Preparation` selects the existing value-presence decision for plain
nullable/structured Result enum probes. This grants no enum truthiness contract.
The existing wrapper-presence bridge borrows its operand for the full expression,
including temporaries, evaluates it once and does not consume or mutate its state.
Zero-valued cases remain present; nullable absence and Result errors remain absent.
Indexed probes and `empty` are outside this slice.

The existing helper body preserves exact fixed backing types, original case spelling,
unit ordinals and signedness-safe membership checks before narrowing. Full-width
uint64 values remain intact. Reverse conversions throw for unmatched values,
overflow and negative/unsigned mismatches. Lookup/import shadowing stays ordinary.
The semantic authorities remain `specs/compact_layout_types.md` sections 3.4/3.5
and `specs/count_empty_isset_contract.md`; no normative change was necessary.

## Verification

All execution is on dev002 in `/home/alex-ai/curl/20261020-work`, with the shared
12-slot runner and CLI fallback because CLI/FPM versions differ. Default prelowering
is used for production source programs; application manifests forbid templates.
No full matrix, runtime changes, portability conversion or native compiler rebuild.

- `20261022-vd08h-first`: the updated enum-wrapper harness passes 18 positive
  programs, two checked-extraction failures, 12 preparation rejections, retained
  cross-file recovery and six existing regression suites. The two former deferred
  probes are now positive cases. This checkpoint preceded the explicit indexed-probe
  exclusion guard; the final focused run verifies the resulting plain-value path.
- `20261022-vd08h-final`: 15 positive programs cover unit/plain-int and every fixed
  integer backing width, exact result types through reference parameters, independent
  printed numeric values, spelling, presence states, cross-width reverse conversion,
  once-only evaluation, left-to-right snapshots, lexical and imported shadowing.
- Four runtime-failure programs check overflow, negative-to-unsigned mismatch,
  uint64-max-to-negative mismatch and unmatched membership.
- Thirteen preparation rejections preserve arity/type/type-marker rules, exact enum
  identity, plain-enum probe rejection, wrapper `empty` rejection and enum-condition
  rejection. Dynamic `empty` remains an explicitly deferred concrete transport probe.
- All 19 executable cases run cold and warm; warm builds compile/link nothing.
- Retained cross-file alias/import tests reject invalid backing and removed cases,
  invalidate readiness, recover exact artifacts, change int8 to uint64 backing,
  retire and restore helper demand, and match fresh output. Final output builds/runs.
- Existing `enums.php` and `value_probes.php` suites pass. The former verifies ordinary
  helper artifacts and semantic/incremental behavior; it is not a fresh native
  execution claim for the old ordinary-mode fixtures.

`20261022-vd08h-helpers-01` retains fixture failures caused by existing fixed-width
binary-comparison and large-expression-literal restrictions. The fixtures were
corrected to compare printed numeric bytes, require exact reference types and obtain
the uint64 maximum from a declared enum. Those compiler restrictions were not changed.
`20261022-vd08h-helpers-02` passes the corrected set before the final extra controls.
The three obsolete helper rejection rows were removed from
`prelower_unsupported_boundaries.php`; unrelated historical rows were not migrated
or used as current acceptance claims.

## Remaining V-D08 scope

VD08-H01 and VD08-H02 are repaired within the above boundary. Sentinel Result enum
carriers, lifted operators, wrapper conditions, broader selection and same-wrapper
fallback remain separate. String backing, reflection, enum methods/interfaces and
other extensions require individual compatibility/design decisions. General value
probes, indexed probes and wider prelowering gaps are not claimed repaired.

## Evidence archive

Dev002 archive: `/home/alex-ai/curl/vd08h-evidence.tar.gz`. SHA-256:
`6cbd45b6aabdf2d53728e73e19b7bd83110ef1604089bd8a3ff217aeeded8f74`.
The source manifest pins base `6d75e8180` and verifies all nine changed compiler/test
files against the dev002 workspace. The archive includes final and earlier results,
command logs, source fixtures, generated artifacts and publication manifests. Final
review confirms 19 zero-work warm builds and 20 template-forbidden publications
(including retained output).
