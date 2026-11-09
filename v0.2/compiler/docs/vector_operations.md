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
leaves the element values and count intact. Ordinary vector assignment/copy, function arguments
(including reference arguments), returns, populated literals,
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

Lowering emits the exported concrete C++ type and ordinary member calls. ABI argument
normalization happens after approved conversion: a source int64 literal may be emitted
as long long while the target's canonical int64 alias is long. Append and indexed reads currently normalize their arguments with casts. Replacement
uses [exact native argument lowering](../../specs/planning/native_cpp_lowering.md). String arguments retain their value-copy semantics.

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
