# Concrete fields and recursive physical composition
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `274d9b39`.

This native experiment tests the user's proposed representation:

```cpp
struct Invoice {
    Vector_Int amounts;
    Vector_Tracked payloads;
    Tracked marker;
    long total() const;
};

invoice.amounts.append(Integer(30));
mutate_amounts(invoice.amounts); // Actual Vector_Int&; no borrowed facade.
```

One source-defined record and the same application/method source compile in both
variants. Direct types are aliases to original runtime templates. Concrete types
own layout-probed native objects and expose out-of-line non-template methods.
One fixture emitter handles lifecycle/storage for all types, preserving native
availability and noexcept traits like the production opaque representation worker.
Fixture method bindings are test inputs, not a production registry or family engine.
Runtime container/ownership algorithms are unchanged.

## The important composition rule

The native implementation of a container uses the selected **physical element
type**, consistently:

```cpp
// Concrete build's separately compiled support:
using Native_Vector_Int = scpp::vector_t<Integer>;
using Native_Vector_Invoice = scpp::vector_t<Invoice>;
using Native_Vector_Vector_Int = scpp::vector_t<Vector_Int>;
```

Thus `Vector_Int::at()` returns an actual `Integer&`, the outer vector returns an
actual `Invoice&`, and the nested vector returns an actual `Vector_Int&`. No native
object is reinterpreted as a facade. Unwrapping accesses only a native object that
was placement-constructed inside its owning facade's byte storage.

Existing providers requiring exactly `vector_t<int_t<>>` cannot automatically
accept `vector_t<Integer>`. Matching layouts do not make C++ types interchangeable.
Production integration must select compatible physical bindings or deliberately
retain an existing native boundary. This experiment does not supply conversion
copies or assume ABI equivalence to the old physical types.

## Validation

```bash
python3 compiler/my-try/tests/prelower_composition.py \
  --results FRESH_DIRECTORY --timing-repeats 5
```

Separate compilation, no LTO or PCH, debug disabled. O0, O2, optional copy elision
disabled, and ASan/UBSan configurations compare exact allocation/byte/free and
tracked construction/copy/move/destruction counts. LeakSanitizer is disabled;
tracked live-payload assertions remain enabled. The checks cover:

- direct field calls and real mutable/const field and element references;
- source methods and a separately compiled function taking `Vector_Int&`;
- independent record copies, moves and assignments;
- copy failure after an earlier field has completed, with partial-record cleanup;
- a vector of records, aliasing append/growth and mutation through nested fields;
- nested vectors containing concrete vector objects, identity and growth;
- shared receiver retention and unique transfer, pointer identity and moved-from state;
- matching sizes, alignments, special-member availability and noexcept traits;
- template-free application exposure for the tested runtime types: preprocessed
  application input contains no `scpp::` references or runtime template definitions.

The negative control deliberately inserts a native vector copy into a readonly
element access. The previously zero-cost reference phase then records one allocation,
one payload copy and their cleanup, demonstrating counter sensitivity.

The layout probe is layered: integer, field containers, source record, then containers
and ownership wrappers of that record. This is a fixture bootstrap, not a new cache.
Production's currently grouped probe assumes native source payloads do not depend
on facade layouts. Integration needs dependency-aware layout resolution in the
existing layout/build owners; it cannot simply substitute field spellings.

## Conclusion and limits

The tested composition supports concrete source fields without additional boundary
allocations or payload copies. Ordinary C++ references suffice for those fields.
This supports preferring concrete field composition before general native-field
borrow machinery. Preserve physical argument selection and lifecycle traits through
the existing backend bindings; do not add semantic types or per-family engines.

This is a standalone native representation proof, not compiler-emitted application
integration. It does not prove incremental layout ordering, recursive source-type
cycles, hash/key protocols, all provider interfaces or whole-accounting coverage.
The matched traits do not include native triviality or establish binary compatibility.
Forwarding methods remain out-of-line calls without LTO; runtime throughput was not
measured. Native storage inside support still uses runtime templates.

Raw evidence: `/tmp/prelower-composition-final`. Compiler version, per-phase counters,
layouts/traits and negative control are recorded in [summary.json](summary.json).
Optional [unit timings](timings.json) are five sequential paired samples with
alternating variant order, warmed filesystem and fresh object compilation. They
separate support, source-method and application units; they exclude layout probing,
linking and compiler frontend/lowering work. They are fixture measurements, not a
production speed prediction.

## Measured unit compilation

Median milliseconds over five paired samples:

| Unit | Direct O0 | Concrete O0 | Direct O2 | Concrete O2 |
|---|---:|---:|---:|---:|
| support | 1006.8 | 1407.9 | 1155.0 | 3202.2 |
| invoice | 1073.9 | 81.9 | 1522.8 | 118.2 |
| scenario | 1330.6 | 90.2 | 2479.7 | 166.4 |

The application and source-method units compile much faster in this fixture;
the concrete support unit costs more, particularly at O2. That work has moved
into reusable support, not disappeared. No PCH was used; this is not a comparison
against an optimized production PCH baseline.

All four behavioral configurations passed with exact direct/concrete counter parity.
The three field/nested-reference phases each recorded zero allocations, copies,
moves or destruction. Sizes, alignments and the listed lifecycle/noexcept traits
also matched across both variants.
