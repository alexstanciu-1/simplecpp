# V-D08 nullable and structured Result enum payloads
Doc Status: planning

## Bounded outcome — 2026-10-22

The approved slice admits `nullable<E>` / `?E` and `result<E>` for unit,
int-backed and fixed-integer-backed enums. The two nullable spellings share the
same canonical application. Exact enum identity, absence/error states, source
snapshots and take write-back use existing conversion and concrete transport
owners. No runtime implementation or C++ backend operation was added.

Prepared enum declarations publish `result_payload`. Result admission now reads
settled source/provider capabilities through `Capability_Facts`, instead of only
checking a nominal registry declaration. Type-application preparation already
observes the source declaration; conversion queries do not schedule work.
Nullable value boundaries and take admit exact enums without admitting enum
truthiness. Other Result carriers remain explicitly excluded. Existing exact
nullable joins and structured Result coalescing support an enum fallback without
broadening same-wrapper fallback or mixed joins.

The normative contract is in
[compact layout types](../../../../specs/compact_layout_types.md#321-v02-nullable-and-result-enum-payloads)
and [error/take](../../../../specs/error_values_and_take.md). A zero-backed case is
a successful value. Nullable default initialization is empty. Existing bare-enum,
Result-local and object-field initialization restrictions prevent invented zero
payloads. Required extraction rejects absence/failure; failed take preserves the
value destination; successful structured take clears the error output.

V-D08 is **partial**, not closed. Other enum extensions and the separate helper/
value-probe transport gaps below remain visible. Overall: 58/64 resolved
(90.625%), two partial (V-R08 and V-D08), four other open. No full matrix or native
compiler rebuild was run.

## v0.1 compatibility evidence

An isolated snapshot of the original frozen baseline
`75de94c71e37060c564b6ade19850b7686bd457a` was uploaded with explicit authorization.
Its Generator, TypeMapper and rules catalog match local release/0.1.78 for these
paths. Snapshot SHA-256:
`702df5b1e7e4c6702ade53067c85d2339668a711c6b322ba77d0229eb4a25194`.

`20261022-vd08-baseline-01` runs the real pinned strict generator and compiles/runs
its output on dev002. Int-backed enum cases A=0 and B=7 give:

| Probe | Stage/result | Observed stdout |
| --- | --- | --- |
| Plain enum control | Executed successfully | `B` |
| Explicit `nullable<E>` | Success, then absence; failed extraction preserves A | `yesAemptyA` |
| `result<E>` | Success, then error; failed extraction preserves A | `yesAerrorA` |
| `?E` | Generator rejects take because the inferred source is bare E | No executable |

Thus explicit wrappers are a demonstrated compatibility requirement. v0.2 fixes
the shorthand's lost absence semantics rather than copying that historical defect.
This source baseline proof covers int-backed enums; the wider v0.2 and direct
current-runtime backing proofs are separately described below. The baseline
harness records a rejected probe as a result, not as a feature pass.

## Current-runtime and v0.2 proofs

`20261022-vd08-runtime-01` compiles and executes the existing runtime with
ASan/UBSan: unit enums, int64 endpoints and uint64 endpoints; nullable and Result
states, copies, take output preservation/error clearing and checked extraction.
No runtime repair was required.

The consolidated `20261022-vd08-final` harness uses the shared runner's 12 slots,
CLI fallback because PHP CLI/FPM versions differ, and default prelowering:

- 16 positive source programs: both nullable spellings; Result construction,
  assignment and extraction; unit/int/int8/uint64 backing; zero and full-width
  values; by-value argument snapshots before a later reference mutation;
  source evaluated once; error clearing/preservation; empty defaults; lazy exact
  fallback and checked extraction.
- Two programs verify runtime rejection of required extraction from absence/error.
- Twelve preparation rejections cover integers/other enums, mismatched take and
  fallback, default Result locals/fields, enum conditions, sentinel Result carriers
  and lifted payload equality.
- All executable source cases run cold and warm; warm builds compile/link nothing.
  Published application units forbid templates.
- A retained cross-file/imported enum proof rejects a backing overflow and removed
  case, marks failed publication unready, recovers exactly, changes int8 backing to
  full-range uint64, and compares fresh versus retained output. The final published
  program builds and runs with default prelowering.
- Existing Results, take, nullable, enum-model, type-capability and Result-container
  PHP preparation/regression suites pass. These controls do not claim a native
  compiler rebuild or full legacy suite execution.

Earlier attempts are retained: source-01 had missing whitespace between generic
closing brackets and assignment; source-02 exposed the helper gap below;
expanded-01 exposed the wrapper value-probe gap. The first standalone container
regression invocation omitted its required output directory; corrected execution
passes in `20261022-vd08-container-control` and the consolidated harness.

## Separate remaining gaps

- **VD08-H01:** `enum_name(E::A)` rejects under default prelowering with
  `compiler helper requires concrete transport`. Ordinary enum metadata helper
  support does not establish default-prelowered support. No helper repair here.
- **VD08-H02:** generic `isset` on these wrappers rejects under default prelowering
  with `value probe operation requires concrete transport`. State proofs use take,
  checked extraction and coalescing. The deferred probe records the actual failure;
  it is not counted as successful source support.
- `result_or_false<E>`, `result_or_bool<E>`, wrapper conditions, lifted operators,
  same-wrapper fallback and broader selection remain outside this bounded slice.
- String-backed enums, reflection helpers, enum methods/interfaces and other V-D08
  extensions still need separate decisions. S02 remains deferred.

## Evidence archive

Dev002: `/home/alex-ai/curl/vd08-enum-wrapper-evidence.tar.gz`
(local review copy: `/tmp/vd08-enum-wrapper-evidence.tar.gz`). SHA-256:
`4390dfd8875b3066e876407e187c2ce717616c0cf15c2b589c7b6d005760b68b`.
The final source manifest verifies all 11 compiler/test files against the executed
dev002 workspace. The archive retains final, baseline, sanitizer and earlier
helper/value-probe failure evidence; it does not bundle the full baseline runtime.
