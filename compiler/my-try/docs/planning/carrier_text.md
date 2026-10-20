# V-L11 and V-D14 bounded text conversion
Doc Status: supporting

The approved scope admits ready plain integer text widths and canonical scalar
carriers for `.` and string-destination `.=`. V-L11 and V-D14 close within that
bounded scope. uint64 and wider domains remain explicitly separate. No runtime
implementation changed.

## Contract and ownership

Plain signed 8/16/32/64-bit and unsigned 8/16/32-bit integers join canonical
int/float/bool/string and literal null. Each of the four carrier families may
contain canonical int, float, bool or string. Every pair in that text domain
admits `.` and returns string. `.=` destinations remain plain string or one of the
four string carriers, with any RHS in the bounded domain. Counts below distinguish
source spellings, including int/int64, rather than unique semantic identities.

Present payloads use the same text as their plain scalar. Nullable empty, false
sentinels and both result_or_bool sentinel states become empty text. Present bool
true remains `"1"`, distinct from the empty text of a true sentinel. Structured
Result error throws without changing its stored message, line or file. String
conversion preserves every byte. See the [normative contract](../../../../specs/expression_evaluation.md#bounded-scalar-and-carrier-text-contexts--2026-10-20)
and `coercions.text.source_concatenation_contract` in runtime configuration. The
latter is source-contract metadata, not a change to display-helper behavior.

The runtime-first probe found that plain float text uses precision six whereas
explicit carrier-to-string casts use precision fourteen: `1.23456789012345`
renders `1.23457` versus `1.2345678901235`. Reusing those casts for numeric carrier
text would violate scalar/carrier equivalence. Existing `has_value`,
`require_value` and scalar text helpers compose the agreed behavior without a
runtime implementation prerequisite. Existing string-carrier casts remain an
equivalent path and preserve their prior failure behavior.

`String_Conversions` owns domain and conversion selection. Two operations in the
existing conversion decision describe optional and required payload text; no new
retained structure or general conversion mechanism is introduced. The C++ consumer
implements those settled operations using existing runtime helpers. Integer
admission uses registered width/signedness facts. Operator preparation preserves
the old boxed operand domain; mutation preparation preserves the old indexed
pair domain. These gates prevent the text extension from silently broadening
separately deferred paths.

The existing operand renderer converts a saved left value before evaluating and
converting the RHS. Left failure suppresses the RHS. Right failure preserves
prior RHS effects without compound write-back. Successful `.=` commits one string
payload to the original captured local/property/static destination and returns a
value snapshot. Receiver rebinding during the RHS cannot redirect that commit.

## Verification on dev002

Host: `alex-ai@dev002.travelfuse.ro`; snapshot
`/home/alex-ai/carrier-text/20261020-01`; starting commit `ee3dceb4`. Source hashes,
not HEAD alone, identify the tested work. PHP 8.5.11 uses CLI fallback because FPM
has a different version; native fixtures use Clang 18.1.3 and Python 3.12.3.
Shared runners use twelve continuously refilled slots; independent suites overlap.
No full operator matrix or native compiler rebuild ran.

- Runtime first: 188 carrier state/payload compositions pass. The precision
  discrepancy and true-sentinel display-versus-cast difference are recorded.
- Exact inventory: **924 source rows**, comprising **729 named-type `.` pairs**,
  **135 named-type `.=` pairs** and **60 literal-null rows**. These include existing
  positive controls and aliases; they are not 924 newly introduced semantic signatures.
- Each of the 864 named-type rows passes preparation/cleanup, invalid-edit artifact
  retirement, repaired/fresh equivalence and no-op recovery, then native behavior.
  Native checks cover independent value/text expectations, all carrier states,
  distinct left/right source errors, local/property/static snapshots and receiver
  rebinding. Compound checks preserve earlier RHS effects on failure and verify
  successful write-back and returned snapshots.
- Values include signed/unsigned width boundaries, bool payloads, complete binary
  strings, signed zeros, float rounding, large exponents, infinities and NaN. The
  text oracle does not reuse runtime casts or text helpers to generate expectations.
- Literal-null rows execute natively; the four original V-L11/V-D14 source
  findings prepare and emit successfully. Deferred-domain and unchanged value-boundary
  controls (71 rows) reject before artifact publication.
- Eight regression families cover existing V-L07 controls and indexed string
  carriers, eager evaluation, compound effects, indexed compounds, boxed/mixed text,
  runtime strings, explicit casts and interpolation. The original V-L07 inventory
  remains its dated canonical proof boundary of 3,376 carrier signatures.

Acceptance comprises **11 suites and 5,906 expected command exits**, with 520,271
recorded case iterations. These iterations are not unique signatures or individual
assertion counts. All 6,269 remote source hashes match; 217 runtime implementation
files are unchanged. Only runtime contract metadata changed.

## Evidence and corrections

[Exact results and signatures](carrier_text_results.json),
[all attempt logs](carrier_text_evidence.json.gz),
[initial source manifest](carrier_text_source_initial.json),
[final tested source manifest](carrier_text_source_final.json) and the
[closeout audit](carrier_text_audit.json) preserve provenance. The archive includes
candidate manifests and launchers. Documentation closeout follows the tested snapshot.

The first smoke attempt exposed an invalid initializer in the replacement
wrapped-int16 rejection fixture; using its default empty value makes the text
operation itself the rejected boundary. All 864 rows then passed. Review tightened
boxed and indexed exclusions, after which all 864 rows were repeated against the
final compiler code. Four regression suites shared an obsolete `string .= ?int`
rejection; it became a positive check while wrapped-int16 remains negative. Only
those affected regression suites needed rerunning. Failed attempts remain evidence.

## Closeout and boundaries

Overall original-issue closure becomes **24/64 (37.5%)** by issue count, not an
engineering-effort estimate. Historical findings are retained in the issue ledger.
No newly exposed implementation blocker remains in this approved scope.

The following remain separate: uint64 text and its configured unsigned-safe bridge
prerequisite; wrapped fixed-width/nested/object/container payloads; new boxed
combinations; new indexed text capabilities; other conversion contexts. Assignment,
argument passing, equality, explicit casts, echo and interpolation do not inherit
these text-context conversions. Existing display helpers still have their own
error/sentinel behavior. The float cast formatting difference is documented rather
than silently changing that independent contract.
