# Bounded value foreach

Doc Status: supporting

The [array contract](../../specs/array_semantics.md#value-iteration) owns semantics;
the [native protocol](../../specs/collection_traversal.md) owns storage safety.

PHP uses `foreach ($items as $key => $value) { ... }`; C++ and JS use
`foreach (items as key => value) { ... }`. The key is optional. Braces are required.
Python uses an indented for name in source suite for typed vector elements or typed hash keys.
JS also supports for (name in source) with a braced body for typed hash keys.
Both key-only forms share the existing destination and loop scope rules and materialize only the key.
PHP writable vector and hash bindings use the [indexed write-through contract](../../specs/collection_traversal.md#php-writable-vector-and-hash-foreach).
Preparation tracks active collection storage IDs while visiting the body and checks mutable call effects against those IDs.
Vector lowering uses a private index. Hash lowering advances the generated live-key cursor, reads the current slot, and writes through the private selected key. Destructuring and nullable unwrapping are deferred.
Concrete [yield producers](yield.md) reuse this traversal path.
[Iterator<T> and Iterator<T,K> structs](iterator.md) support custom traversal. Scalar/null
boxed sources throw, including on a later advance after replacement. Statically noniterable sources are preparation errors.

## Owners and facts

`ast_foreach_statement` owns the source expression, optional key, value binding,
body and header scope. It does not inherit the condition-bearing loop node or
invent a condition AST. `ast_iteration_binding` represents the produced value at
its authored name token. Its existing expression facts provide the unknown incoming
type for conversion; its declaration identity supplies storage only when introducing
a new local. Existing value destinations are referenced, not redeclared in the scope.
The statement retains only its resolved destination and packed conversion code.
There is no prepared statement list, cursor object or per-element analysis.

`Body_Collection` uses its existing scope owner and function-local storage-ID allocator.
The key is fresh in the header scope; a visible local/parameter value is reused,
otherwise the value is inferred in that scope. New names do not escape. Zero entries
leave an existing destination unchanged. The body has its usual child scope.

Preparation resolves the source once, queries its optional `iterable_type` capability,
settles bindings and ordinary assignment conversion, then visits the body once.
It uses the current loop-depth/completion rules and conservative write-cache barriers.
There is no fixed-point computation, collection scan or additional dependency table.
Existing calls and member access establish normal watches. Reset clears the new
binding facts alongside source/body facts; incremental recovery reuses unchanged ASTs.

## Generation and lowering

The API generator consumes each runtime publication's traversal plan and emits
key/value queries and native calls. Handwritten preparation and emission do not
switch on the four collection families. Mixed/dynamic values retain their own family;
boxed keys use mixed int/string values. A publication without mixed can still use
value-only dynamic traversal; keyed traversal receives an explicit provider diagnostic.

The emitter evaluates the source once. A named wrapper is borrowed, a temporary's
lifetime is extended, and the existing retained-place helper keeps the nearest shared
field owner alive. Generated collection bindings capture the initial physical limit;
custom Iterator methods need no extent. Both use native while.
For built-in collections, identity assignments write directly into the destination
through iteration_next, avoiding a second element copy. Conversion requires a separate
selected value. Custom methods stage existing destinations until next returns true.
Boxed keyed traversal decodes native integer/text transport through generated code;
value-only traversal creates no key transport. No lambda or runtime dispatch is added.

```cpp
auto&& iteration_source = var_items_0;
const auto iteration_limit = (iteration_source).iteration_limit();
scpp::int64 iteration_position = 0;
scpp::int64 var_value_1{};
while ((iteration_source).iteration_next(iteration_position, iteration_limit, var_value_1)) {
    // Native body; break/continue/return keep normal scoped cleanup.
}
```

## Proofs and measurements

`tests/foreach.php` proves all four families in all flavors, generated-code purity,
header scope, conversions, temporary sources, nested loops, transfers, growth,
replacement, clear, holes, independent string values and retained shared field owners.
Incremental checks keep the caller AST through provider edits, invalid return types,
deleted functions, repeated failed runs and recovery, comparing artifacts with fresh
compilation and checking no-op preparation counts. `foreach_native.py` runs the same
135 emitted fixtures and validates output and exit status.

On 2026-11-18, 42 selected mutation/lifetime consumers additionally passed ASan,
UBSan and leak detection against the fully instrumented runtime (libstdc++; see the
runtime protocol's installed-libc++ sanitizer qualification caveat). Other native
proofs use libc++. Runtime layouts agree between these configurations.

Native timings below are medians across each flavor's 45 correctness fixtures, not
repeated measurements of one program or a throughput comparison. O2/g0, Ninja8,
mold, no PCH/LTO, prebuilt runtime, fresh application objects. Direct protected Ninja
includes a qualification-cache subprocess. Trace stages overlap and do not sum to wall.
PHP times cover one source's frontend/preparation/lowering; the entire PHP fixture
suite including incremental/fresh checks took 615.48 ms.

| Flavor | PHP compiler ms | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| C++ | 0.475 | 266.172 | 27.502 | 14.564 | 82 |
| PHP | 0.825 | 260.484 | 26.890 | 17.301 | 77 |
| JS | 0.820 | 271.777 | 30.775 | 17.949 | 81 |

Local raw fixtures, Ninja graphs, Clang traces and results: `build/foreach-proof/`.

## Construction-aware output storage

Application-defined vector/hash elements use a recipe-generated nested `iteration_output`
with inline value storage and an active flag. Empty construction never invokes the element's
default constructor and adds no heap allocation. Inactive bytes are initialized so GCC's
strict optimized diagnostics do not mistake guarded shared-owner storage for an uninitialized
owner. Successful advance copies into the slot; subsequent publication uses existing move
assignment. Destruction releases an active value exactly once.

The shared `iterable_type::iteration_output_type()` capability selects this strategy; null
keeps the existing runtime/custom-Iterator reference-output strategy. Normal foreach binds
its fresh local to the active slot value only inside a successful iteration. Existing
destinations are assigned only after success. Yield foreach stores the same slot in its
existing frame and uses existing close, move and exception cleanup. No new AST nodes,
preparation traversals or retained preparation facts are introduced.

Complete concrete definitions needed by cursor frame layouts are published before private
lowering. Lowering obtains their headers through a read-only publication accessor. Concrete
dependency enumeration yields a sequence, preserving multiple recipe arguments named alike.

`application_collection_iteration_native.py` checks required constructors, empty loops,
value/key outputs, copies, shared identity, receiver replacement, growth and suspended loops
in all flavors. Sixteen revisions per flavor cover edits, deletion, retry, recovery, namespace
changes and fresh-build equivalence. Additional probes check cursor moves, early close and
exception cleanup. The concrete recipe probes cover throwing copies, aligned values, hash
holes and leaks under ASan/UBSan. Cross-thread structural mutation remains deferred.

Application traversal fixture measurements (2026-11-18): four source units plus entry,
required constructors, hash/vector loops and suspended producers. Native columns are medians
of three fresh builds, O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime; direct Ninja
qualification included. PHP columns are single initial/no-op service measurements. Trace
frontend/backend totals sum work across units and overlap in wall time.

| Flavor | PHP initial ms | PHP no-op ms | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 16.86 | 2.03 | 584.29 | 291.37 | 510.75 | 57.00 |
| cpp | 15.54 | 2.21 | 564.58 | 265.96 | 497.18 | 59.00 |
| js | 15.65 | 2.14 | 573.43 | 270.02 | 512.27 | 59.00 |

Native ranges: PHP 556.6–1436.1 ms (first-run outlier), C++ 560.4–568.8 ms,
JS 557.5–593.9 ms. No attribution is made for the outlier. Raw local results and traces:
`/tmp/app-traversal-proof3/`. This fixture is not comparable to the earlier tiny struct benchmark.
