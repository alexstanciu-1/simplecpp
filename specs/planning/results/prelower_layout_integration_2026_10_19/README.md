# Prepared runtime ABI and native layout-cache integration
Doc Status: supporting

Date: 2026-10-19. Baseline `dee955af` plus the changes committed with this report.
This is the first **API integration** slice. The ordinary expression emitter and CLI
`--prelower-templates` still select the original integer/vector recipes. They do not
yet select this generic opaque protocol automatically.

## Implemented ownership

- `CPP_Specializations` keeps its existing compact references, entry index, request
  deduplication and stable output ordering. Legacy family selection moved into the
  existing recipe worker; there are no family branches in the engine.
- `CPP_Opaque_Representation` reads canonical types, completed capability queries and
  existing C++ mappings. It emits one lifecycle protocol for every admitted runtime
  object. No parallel registry, new AST or retained semantic facts were introduced.
- A bounded observation bridge consumes a real `prepared_call`, including its argument
  boundary and concrete selected signature. Existing callable bindings supply the
  runtime symbol/header. Only one readonly observation with an owned storable result
  is currently admitted. Copy/reference-return/variadic call shapes are not guessed.
- The generated owner contains the original runtime object. Copy permission comes
  from the semantic capability owner; physical availability/noexcept comes from the
  target's native traits. Result construction goes directly into destination storage.
- `layout_probe` is a source artifact published by the existing coordinator. The
  native builder compiles/links it through its existing dependency/action cache,
  executes it and caches its generated layout header through the same ownership and
  readiness protocol. Clang never runs from preparation/emission. No published source
  is rewritten by the builder.

Layout results include size/alignment and lifecycle/noexcept traits, guarded again
by support-side assertions. Their identity includes protocol, toolchain, native flags
and probe executable digest. Header dependencies participate in fresh dependency
scanning; consumers include the resulting header in their normal object keys.
A grouped layout header currently invalidates consumers conservatively.

## Proof and counters

```bash
python3 compiler/my-try/tests/prelower_layout.py --results /tmp/FRESH_LAYOUT_RESULTS
```

The fixture prepares real source calls to runtime `count` and to the existing
metadata-only `parcel<T>` provider's `unpack`. It passes their settled facts into the
production engine, publishes those artifacts through `Output_Publication`, and builds
with `Native_CPP_Build`. The C++ test entry/projection/seed helpers are deliberately
fixture code: this proves the APIs, not ordinary expression or indexed-place adoption.

Covered representations include multiple integer widths, nested vectors, hash,
vector of unique ownership, and unfamiliar `parcel<int>` / `parcel<receipt>` types.
An instrumented provider payload verifies copies, moves, destruction and final live
count. The same generated lifecycle path serves all these types. The noncopyable
container's copy members are disabled by semantic capability even where a native
container trait alone could appear copyable.

Direct and opaque paths match:

| Scenario | Allocations | Allocated bytes | Payload copies |
|---|---:|---:|---:|
| Nested container construction/copy/move/observation/cleanup, direct | 4 | 80 | 0 instrumented |
| Same scenario through generated opaque owner/bridge | 4 | 80 | 0 instrumented |
| Provider observation returning an instrumented value, direct | 0 | 0 | 1 |
| Same provider call through generated bridge | 0 | 0 | 1 |

The provider's one copy is the existing by-value return behavior; the bridge adds no
second copy. Nested mutable/readonly addresses agree, mutation preserves identity,
and container copies stay independent. Native integer payload copies themselves are
not instrumented; their container allocation behavior is measured. Ordinary/aligned
C++ new/delete counters are not a process-wide malloc profiler.

Generated artifacts execute at O0 and O2, and under ASan/UBSan. Leak detection is off
for sandbox compatibility; live payload counts and deallocation counters are checked.
The earlier [mechanism proof](../prelower_boundary_2026_10_19/README.md) additionally
covers throwing partial-copy cleanup, optional copy elision disabled and a negative
control with deliberately added allocation/copy.

## Cache/recovery evidence

The committed [summary](summary.json) contains build reports and exact counters.

- Cold: one layout header resolved; five application/support objects compiled.
- Warm: layout reused, zero object compilations, no relink; artifact inode/mtime
  values unchanged.
- Body-only edit: layout reused, one object compiled and relinked.
- Corrupt cache-owned layout header: regenerated; unchanged objects/link reused.
- Native optimization switch: isolated layout/object configuration; returning to O0
  reused its previous artifacts.
- Published provider header changes `parcel<int>` alignment/size from 8 to 64: layout
  invalidated and regenerated from the real changed dependency.
- Broken provider header: native status remains pending. Repair returns to the prior
  valid cached layout and objects.

Existing native-cache regression coverage passed, including ordinary PCH/debug,
object/link reuse and recovery. Existing prelower emission/replay coverage passed
following recipe relocation. Scoped portability checking and conversion passed for
all six touched portable backend/native implementation files. These are PHP and
conversion proofs, **not a native compiler rebuild**. PHP-FPM could not bind its
sandbox socket; the shared runner used its normal CLI fallback.

Raw evidence: `/tmp/prelower-integration-verified` (integration/portability),
`/tmp/prelower-integration-final` (emission regression), and
`/tmp/prelower-layout-checks/native` (existing native-cache regression).

## Remaining boundary

The next slice must select these APIs from ordinary C++ expression emission, retain
canonical operation requests for fragment replay, and map prepared nested places and
reference returns. This work must preserve the existing sequencing/lifetime owner.
Current nested projection helpers do not establish that compiler integration.

Generated-source type dependencies reject explicitly because source layout bootstrap
is not solved. The layout executable is host-only; cross-compilation is unsupported.
One generic observation mechanism is not a claim that every callable ABI, constructor,
conversion, ownership form or runtime template has been integrated. No compile-time
speedup is claimed from this behavioral/cache proof.
