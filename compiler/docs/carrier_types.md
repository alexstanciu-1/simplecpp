# Shared carrier type resolution

Doc Status: supporting

## Scope

One carrier family represents a value payload or a metadata-defined alternate state.
This slice connects existing runtime exports and compiler type resolution for:

| Source family | States | Default |
| --- | --- | --- |
| nullable<T> | value, empty | empty |
| result<T> | value, error | successful T{} |
| result_or_false<T> | value, false sentinel | false sentinel |
| result_or_bool<T> | value, false sentinel, true sentinel | false sentinel |

The value state owns T. Only the error alternate owns an error_t payload; empty and
boolean sentinels have no alternate payload. A successful false boolean payload remains
distinct from a false sentinel. No defaults or extraction semantics changed.

Source value operations now use [packed carrier conversions](carrier_values.md): construction,
copying, typed payload conversions, one-layer extraction, payload operators and presence queries.
The original declaration-only tests remain a separate export/header proof.

## Metadata, exports and generation

`carriers.families` now contains default_state and states records. The validated runtime
plan retains one carrier_families policy map; concrete specializations consume that plan.
Validation preserves the existing runtime contracts rather than admitting new state models.
`carriers.provider` owns declaration placement. Published concrete carrier identities and
error_t register through the existing type-export index; no fixed compiler C++ names or
header paths are added. A supplied legacy publication header must agree with the provider.

Carrier declarations join provider assembly. Required scalar/string/vector providers are
derived from actual payloads and the error contract. Minimal nullable publications do not
import string/error APIs. Existing boxed payload headers retain their current direct include
path; migrating boxed declarations is outside this slice. Runtime implementation C++ and
carrier ABI stay unchanged. Public headers remain template-free; private variant storage
and existing lifetime algorithms remain in use.

The compiler generator emits one concrete singleton per published specialization whose
payload is already an active compiler type. Default payloads are int64 (also int), bool,
string and vector<string>, for all four families. Runtime-only optional widths or boxed
payloads do not activate additional compiler types. Nested carriers and null payloads remain
unavailable. A vector payload must itself be a published, compiler-supported specialization.

## Compiler structures

- New `carrier_type_definition`: shared base with one non-owning canonical payload_type link;
  current transport and payload-operation behavior is documented in [carrier values](carrier_values.md).
- Generated `types/carriers.php`: concrete identities with export-selected C++ types and headers.
- `type_registry`: generated one-argument family registration and canonical payload dispatch,
  shared with vector registration. It does not allocate a type per use or read runtime metadata.
- Existing `ast_type_reference` and `Type_Resolution`: reused without additional AST nodes.

Payload aliases resolve before specialization lookup. Thus nullable<int> and nullable<int64>
share one object; nullable<vector<string>> points to the existing vector definition.
Compiler inventory version 22 includes carriers.php; CMake tracks its templates, PHP renderer
and publication dependencies. Runtime header byproducts come from provider inventory.

Prepared declaration facts retain the canonical type as before. Full written type spellings
remain in incremental contract fingerprints; existing stable symbols, watches and queues own
invalidation. No new shared mutable state, cleanup owner or serialization format is introduced.

## Verification

79 selected compiler CTests and 56 selected runtime tests pass. Type tests cover all flavors,
all four families, payload aliases, published nested vector payloads, arity/unavailable-type
errors, and same-identity value returns. Incremental tests cover family/payload changes,
retry without edits, deletion/re-addition, no-op reuse and fresh-output equivalence while
retaining the unchanged consumer AST. A synthetic observer exercises signature invalidation
without implying source carrier value calls. All 18 checkpoint native executables pass.

Provider tests cover relocated/shared vector placement, invalid policies, invalid providers,
legacy header conflicts, preservation after rejected generation, and retirement/re-addition.
Reviewed parity capture counts remain 130/247 runtime, 19/537 scalar and 17/37 CLI accepted/rejected.
Runtime implementation artifact hashes are unchanged; carrier header changes are includes,
and prepared plans now contain carrier policies, exports and provider information.

Representative initial-signature fixture timings (ms):

| Flavor | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| cpp | 118.91 | 20.71 | 14.43 | 59 |
| php | 179.71 | 39.38 | 26.99 | 85 |
| js | 374.92 | 55.35 | 54.20 | 196 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented app objects.
These single verification samples overlapped runtime rebuilding/tests; they are not flavor
performance comparisons. Trace work and parallel wall time are distinct. PHP fixture generation,
including incremental/fresh checks, took 87.42 ms. Results: `/tmp/scpp-carrier-types-native/results.json`.
