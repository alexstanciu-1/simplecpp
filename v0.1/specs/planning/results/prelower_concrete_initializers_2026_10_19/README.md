# Concrete sequence initialization
Doc Status: planning

## Scope and ownership

This prelowering-only slice connects concrete type substitution to the existing
prepared initialization factory and entry conversions. It adds no type-family
recipes, AST, semantic facts, registry, runtime behavior or conversion rules.
The older accounting recipe path remains a separate comparison path.

`CPP_Data_Construction` retains entry evaluation order, converted snapshots and
per-entry lifetime scopes. `CPP_Initializer_Buffer` reuses the runtime's nonmovable
inline buffer, substituting concrete elements and the registered keyed aggregate.
Append receives the address of those existing physical snapshots. List append
retains its lvalue category; keyed append retains its aggregate-prvalue category.
The selected factory constructs the native result directly in final concrete storage.
Empty initialization also calls the selected factory rather than assuming default
construction is equivalent. `CPP_Callables` retains ownership of factory spelling.

Buffer layout dependencies now use the same physical header graph as concrete
owners. A nested buffer waits for its concrete element layout through the existing
probe/cache protocol. There is no extra heap box or separate buffer cache.

## Reproduce

```sh
python3 compiler/my-try/tests/prelower_substitution.py --initializers \
  --results /tmp/prelower-concrete-initializers-FRESH
python3 compiler/my-try/tests/prelower_expressions.py --initializers \
  --results /tmp/prelower-opaque-initializers-FRESH
```

The concrete mode extends the existing indexing/member/source-field proof. It
covers empty hash/vector property defaults, nonempty list/keyed literals, nested
vectors, a hash with concrete vector elements, concrete integer keys/elements and
a 40-element initializer that exercises the native buffer's heap-storage path.

The successful trace independently requires key/value order `1,2,4,2`. Throwing-copy
budgets advance from zero through successful completion, checking lifetime balance,
trace-prefix validity and matching direct/concrete counters and prefix lengths.
Existing nested-reference and indexed-write/removal proofs run in the same fixture.

Validation uses matched O0/O2 and ASan/UBSan with optional elision disabled. Debug
information and PCH are disabled. It also covers no-op fragment reuse, reverse demand
order, initializer-demand removal/restoration, source-body edits, dependent layout
changes and restored native-cache reuse. PHP execution/conversion checks do not
constitute a native compiler rebuild. The shared runner used CLI fallback because
FPM could not bind its local socket in this sandbox.

## Accounting checkpoint

The unchanged small accounting application now emits past all eleven previously
reported initializer failures. It next rejects at the known noninteger binary
adapter. Independent prepared-fragment probes now emit 250 of 285 fragments;
35 stop at their first unsupported operation: 29 binary, three cast, one indexed
compound, one strlen call and one hash iteration. Two newly exposed binary failures
were previously hidden behind initializers. This is emission-only coverage, not a
successful concrete accounting build or a performance comparison.

Remaining class-handle template exposure, iteration, operators/conversions,
provider compatibility and source/layout cycles are outside this initialization
slice. The focused initialization proof does not establish nominal source-class
payload or arbitrary runtime-provider support.

## Development evidence limits

The first native run exposed missing physical layout dependencies for initializer
buffers; those are now published through the existing layout graph. An intermediate
comparison was invalidated by adding trace-prefix instrumentation between its direct
and concrete emissions; the final matched run uses one stable fixture revision.
Raw development logs remain under `/tmp/prelower-concrete-init-*`.

## Final results

Concrete proof PASS (167.23 s); older opaque initializer regression PASS (221.10 s).
Four changed production files pass the portability checker. Every matched build
configuration has identical direct/concrete counter and trace output. The successful
initializer workload records 34 allocations / 16,516 bytes / 34 frees, 1 tracked
construction, 215 copies, 50 moves and 266 destructions. All 216 copy-budget cases
match, including the final successful budget. These are required runtime operations,
with zero additional measured allocation/copy work at the concrete boundary.

Body edit: one unit rebuilt, five reused, all three layout batches reused. Layout
edit: six units rebuilt, one batch rebuilt and two reused. Restore: no compilation
or linking, all three layout batches reused. No initializer-buffer, initializer-entry
or data-initializer template spellings occur in the tested application units/source
type headers. This is a bounded spelling check, not a whole-runtime template census.

[Summary](summary.json) records build results and hashes of full counter/trace outputs;
raw outputs remain in `/tmp/prelower-concrete-init-final/logs`.
