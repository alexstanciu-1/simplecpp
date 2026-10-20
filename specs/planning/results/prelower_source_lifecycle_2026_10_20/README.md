# Source lifecycle proof migration — 2026-10-20

Doc Status: planning

Base revision: `cf3d2b78`, with the source-lifecycle migration overlay.

The former source-method, constructor and ownership-identity cases now run through
the public `prelower_templates` API setting and the ordinary mode. No retired
runtime-ABI selector or alternate source emitter is used.

## Changes required by the proof

- Concrete signature declarations can name incomplete stored types through small
  generated declaration headers. Fields still require complete storage. This fixes
  the layout cycle for a method accepting/returning its own `value<T>`.
- `pointee_modified_type: true` selects a modifier's explicit payload argument in
  the existing runtime metadata and C++ binding owners. `value<T>` uses the same
  pointer-interface emitter as other bindings; by-value facts preserve const access.
- Prepared `ownership_empty` conversions use the existing concrete conversion
  transport and native renderer, allowing a method to clear its retained owner.

No new semantic pass, registry, family-specific engine path or fallback was added.
Instrumentation adds tracked fields equally in both modes; it does not replace
source bodies or runtime algorithms. Its standard-library inspection code lives
under specialization support. Generated application units and their headers still
undergo the native builder's strict template-token validation.

## Reproduction and results

```sh
python3 compiler/my-try/tests/prelower_source_lifecycle.py --results FRESH_DIRECTORY
```

Final recording: `/tmp/prelower-source-lifecycle-20261020-accepted`.
O0, no PCH, no native debug. Both modes passed:

- Receiver replacement during arguments and clearing inside a method.
- Ordered arguments, mutable/const references, method and constructor defaults,
  property-backed construction, inline/shared/unique storage and owned results.
- Two method and three constructor copy-failure stages, with no surviving tracked
  payloads after each failure.
- Shared identity, unique move identity and empty moved-from owner, independent
  inline copies, copyability and const pointer-interface checks.
- No-op artifact replay, unsupported-boundary rejection and exact recovery.

All seven counter rows were byte-identical. Columns below are allocations, bytes,
frees, constructions, copies, moves and destructions:

```text
source-methods-constructors 31 2496 31 14 15 4 32
method-argument-failure 0 0 0 1 0 0 1
method-result-failure 0 0 0 1 1 0 2
constructor-argument-failure 0 0 0 1 0 0 1
constructor-parameter-failure 1 256 1 1 1 0 2
constructor-body-failure 1 256 1 2 2 0 4
source-identities 2 320 2 3 1 0 4
```

Warm builds compiled/linked nothing. The final method edit compiled one object,
reused eight and reused all three layout results. A nine-field edit crossed the
tracked payload's alignment padding: five objects compiled, four reused, one layout
rebuilt and two reused. Counts are observations, not a universal rebuild guarantee.
An earlier one-field edit fit padding and correctly retained the layout result.

Supporting checks:

- Runtime inventory and type bindings passed, including 24 invalid-schema cases:
  `/tmp/prelower-modifier-bindings-20261020`.
- Public CLI/API, lifecycle, strict rejection/recovery, PCH rejection and closure
  audit passed: `/tmp/prelower-source-public-20261020`.
- Output/indexing/compound boundary proofs passed after the signature-header repair,
  before the modifier metadata extension: `/tmp/prelower-source-boundaries-20261020-3`.

The shared runner used its CLI fallback because sandboxed PHP-FPM sockets were
unavailable. These are PHP-host compiler/generated-C++ proofs, not a native compiler
rebuild. No O2, sanitizer, accounting rerun or compile-speed comparison is claimed.
Historical harnesses remain archived for provenance; obsolete rejection gates and
the former source partition's exact object counts are not current expectations.
