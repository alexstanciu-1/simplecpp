# First vector value operations

Doc Status: supporting

## Source boundary

Published vector specializations support explicitly typed empty local construction,
append and indexed replacement on writable locals, and bounds-checked indexed reads. Examples:

```text
// cpp
vector<int> values = [];
values[] = 8;
values[0] = 9;
echo values[0];

// php
$values vector<int> = [];
$values[] = 8;
$values[0] = 9;
echo $values[0];

// js
let values: vector<int> = [];
values[] = 8;
values[0] = 9;
echo values[0];
```

The default publication provides int/int64 and string elements. Empty [] requires
an explicit local type. Append and replacement use the element type’s existing
typed-destination conversion.
There is no new conversion matrix or reliance on C++ implicit conversions.

Indices require the canonical runtime count type, currently int/int64. Negative,
empty and beyond-end accesses use the runtime bounds check. Reads return values,
including owned string copies preserving embedded NUL bytes. An indexed read cannot
bind a mutable reference. Both writes require existing writable local storage.
Replacement emits each argument once in a direct native call. C++ chooses their evaluation order.
The published `set` member checks bounds before replacing the slot. Failure
leaves the element values and count intact. Local vector initialization/copy and assignment
accept the same canonical specialization, including vector<int>/vector<int64> aliases.
Copies have independent elements/cardinality; string elements retain their existing
value semantics. Self-assignment is harmless. By-value arguments and returns use the common value path.
Exact-type named locals/parameters also support writable reference arguments. Populated literals,
iteration and other methods remain deferred.

Source `count(values)` returns the published count type and `empty(values)` returns bool.
The generated specialization validates and lowers the zero-argument const members
`size()` and `empty()` from the runtime plan. Both observe current cardinality.

## Ownership and generated handlers

`ast_empty_collection_expression` retains only inherited expression facts; its explicit
local declaration supplies the resolved vector type. `ast_index_expression` owns
receiver/index syntax and retains its inherited result type. `ast_append_statement`
owns receiver/value syntax. The shared `ast_index_assignment` owns receiver,
index and RHS syntax. Each write retains one approved conversion enum. There is no
copied prepared expression tree or additional vector instance type record.

`compilerVectorTypes()` consumes the validated runtime specialization member plan.
It selects the canonical append, at and set signatures, validates receiver/result forms,
and generates preparation/lowering methods on each existing concrete type definition.
Scalar element conversion remains with the element's generated handler. No runtime
metadata is interpreted during application preparation. Each operand is prepared once;
reads are not constant-folded and no element-content cache or alias graph is added.

Append preparation queries the receiver’s append capability for its value type. Append,
indexed read and replacement use the same exact-argument renderer. It uses the prepared
operand and conversion to select the target literal suffix, pass an exact local directly,
or normalize arithmetic for the canonical overload. Conversion headers are collected
before the call. String arguments retain their value-copy semantics.

Generated application code and public vector headers remain template-free. The existing
runtime's private storage implementation continues using std::vector. No runtime ABI,
publication format or metadata schema changes are needed for these already planned members.

Collection, preparation reset, inspection and lowering traverse the new syntax nodes.
Existing symbol watches, grouped preparation queues and generation cleanup remain the
owners of incremental lifecycle; no additional shared mutable state is introduced.

## Verification

78 selected compiler CTests pass, including generator and style checks. Focused tests
cover both element types in all flavors, typed append conversions, expression reads,
loop appends, string copy isolation/NUL bytes, unsupported operations and lowering purity.
The initial 33 native fixtures executed successfully, including negative/empty/beyond-end reads and
a dynamic float-to-int append conversion overflow observed by a test-only C++ catcher.

Incremental tests change a called function's return type, retain the consumer AST,
reprepare its append and replacement conversions, retry failures without edits, delete/re-add the
function, and compare emitted output with fresh compilation at successful checkpoints.
Additional edits change the local vector element type, fail and recover while preserving
the unchanged library. Successful checkpoint executables verify the expected output.

Representative single-run main-fixture timings (ms):

| Flavor | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| cpp | 137.19 | 18.27 | 12.87 | 62 |
| php | 222.36 | 28.37 | 24.47 | 104 |
| js | 204.76 | 25.16 | 19.11 | 92 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented application
objects. Other validation ran concurrently; these are correctness-check timings, not
flavor performance comparisons. Trace work and parallel wall time are distinct measures.
The complete PHP fixture generation, including incremental/fresh checks, took 112.53 ms.
Raw results: `/tmp/scpp-vector-operations-native-final/results.json`.

The vector provider proof also passes relocated/shared headers, specialization
retirement/re-addition, strict filtering and template-free public closure checks.

## Local copying and assignment

Explicitly typed or inferred local declarations may copy an existing vector of the
same specialization. Ordinary assignment replaces the destination value with a copy.
Empty vectors and empty-vector copies follow the same rule. Runtime-produced vectors
remain valid local initializers and can now also be assigned to an existing local.
Empty `[]` construction still requires an explicitly typed initializer; this slice
does not introduce `values = []` or populated vector literals.

The canonical definition inherits common same-type admission: one type-identity test
returns the existing identity conversion, without syntax-parent checks. There
is no element walk, alias graph, copied layout or content cache during preparation.
No AST properties, preparation records, generated types or runtime ABI changes were
needed. The common emitter already emits native copy construction/assignment; the
existing runtime implements independent vector storage and exception-safe copy/swap
assignment. C++ templates remain private to the prebuilt runtime.

By-value arguments and returns are supported through the common binder and conversion path.
Instance fields now default to empty and use existing value copying/mutation; see
[collection fields](collection_fields.md). Cross-element conversions remain deferred. Writable references use the
[common reference binder](collection_references.md).
Application-defined element types still need their own specialization path.

Verification: `tests/vector_copies.php` and `tests/vector_copies_native.py` cover all
three flavors, int/int64 aliases, inferred/typed copies, overwritten destinations,
self-assignment, empty copying/assignment, independent append/replacement and
heap-backed strings containing NUL. Incremental tests retain caller ASTs through
provider edits, wrong return types, deletion, retries without edits and recovery;
successful emitted artifacts match fresh compilation and are built and executed.
No-op runs prepare zero bodies. Prepared facts are unchanged by emission.

2026-11-14 local-copy verification: 24 native fixtures, nine regression suites
(including native vector operations/type proofs and the runtime consumer) and style
checks passed. Base-fixture timings, milliseconds:

| Element/flavor | Compiler | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: | ---: |
| int/php | 1.15 | 212.82 | 18.23 | 12.60 | 61.00 |
| int/cpp | 1.83 | 233.80 | 22.55 | 20.45 | 67.00 |
| int/js | 1.30 | 211.30 | 17.52 | 12.72 | 72.00 |
| string/php | 1.10 | 179.99 | 13.84 | 13.99 | 56.00 |
| string/cpp | 1.08 | 182.78 | 14.13 | 14.10 | 54.00 |
| string/js | 1.10 | 342.10 | 23.07 | 20.66 | 77.00 |

O2/g0, libc++, mold, Ninja8, no PCH/LTO, prebuilt runtime; fresh instrumented app
objects. Native wall includes cached toolchain qualification. Compiler timings were
measured separately for identical generated base fixtures, excluding PHP startup,
serialization/purity assertions and native work. LLVM includes optimization and code
generation; trace work is not additive wall time. These are single-run fixture
measurements, not flavor performance comparisons. Artifacts and traces:
`/tmp/scpp-vector-copies-verified/`; compiler measurement run:
`/tmp/scpp-vector-copy-timings.json`.
