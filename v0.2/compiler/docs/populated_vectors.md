# Typed populated vector literals

Doc Status: supporting

An explicitly typed local can initialize an available vector specialization with
ordered elements. All three flavors use square brackets:

```php
$numbers vector<int> = [1, true, 9.75];
```

```cpp
vector<int> numbers = [1, true, 9.75];
```

```javascript
let numbers: vector<int> = [1, true, 9.75];
```

A trailing comma is accepted. The destination element type authorizes the same
checked conversions as append; these examples store 1, 1 and 9. Elements are
processed in source order, each expression and its conversion completing before
the next element. A failure destroys the partially constructed vector; earlier
external effects are not rolled back. The new local is not visible in its initializer.

Direct nested literals receive the element destination recursively:

```php
$rows vector<vector<vector<int>>> = [[[1, 2]], [], [[3]]];
```

Existing record, class-owner and nested collection specializations use the same
path. No additional element family is activated. Existing empty hash/boxed
initializers remain supported; populated hash/boxed forms, inferred literals,
call/return destination context and assignment/append literal context are deferred.

## Owners and cost

Runtime vector metadata declares `initialization.mode: ordered_elements`, with an
optional `bulk` transport contract. Validation carries it into the publication plan. The generator grants `sequence_initializer_type`
and emits its element append binding using the existing validated append method.
Application-defined vectors use this same template and their resolved element
contract. Preparation does not read metadata or branch on scalar/record/owner names.

`ast_collection_expression` replaces `ast_empty_collection_expression`. It owns one
ordered list of element syntax and one list of approved uint16-intent conversion
codes, plus existing expression facts. Empty syntax retains empty lists. There are
no copied element type descriptors, new dependency indexes or preparation passes.
Collection/reset/inspection follow the owned syntax; ordinary symbol watches cover
references inside elements and the written destination type.

Preparation visits each element once and immediately checks its destination conversion.
Lowering consumes those decisions and the generated construction binding. Published
integer/string vectors use presized bulk construction; other eligible types use the
generated append binding. See [bulk transport](list_initialization.md). The existing
vector append statement delegates to that binding too. On the append path, a small immediately invoked
C++ lambda creates the temporary vector, appends elements and returns it by value;
this isolates partial construction and works in nested expressions and suspended
producer initialization. It introduces no C++ collection templates. Ordinary RAII
handles failure and existing cursor live flags are set only after initialization succeeds.

## Verification

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/populated_vectors.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/populated_vectors_native.py --output /tmp/populated-proof
```

The native proof covers all flavors, checked conversion, ordered effects, strings
with NUL/heap storage, record copy independence, class identity, three collection
levels, hash-valued elements and initialization inside a yield producer. Eight
incremental revisions per flavor compare with fresh diagnostics/artifacts, including
no-op reuse, record replacement/deletion, retry and invalid function recovery.
Rejections cover missing type context, populated non-sequence destinations, wrong
element types, self-reference and keyed syntax. Runtime probes check an element
failure after a heap-backed string was stored and a failing float-to-int conversion.

The PHP proof checks one visit per expression and pure emission. `--portable` also
compiles every successful incremental/fresh revision and both failure probes on
Clang and GCC. Nested construction exercises inner bulk and outer append together. Existing vector/hash
operation checks, compiler-generation, concrete-template and runtime vector-consumer
checks remain green. Optional `--sanitize` checks generated storage and partial
construction with ASan/UBSan/LeakSanitizer; the prebuilt runtime is uninstrumented.

## Focused timings

Sequential fresh-build observations, one per flavor (milliseconds):

| Flavor | PHP compiler | Native wall | Frontend sum | LLVM sum | Optimizer | Codegen | Link | End-to-end |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 10.85 | 537.44 | 133.99 | 275.04 | 141.03 | 126.73 | 71.00 | 658.43 |
| cpp | 12.66 | 443.80 | 132.23 | 197.49 | 100.94 | 89.81 | 61.00 | 564.43 |
| js | 9.09 | 489.36 | 129.21 | 232.81 | 130.16 | 94.22 | 69.00 | 593.95 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime, direct Ninja qualification.
Frontend/LLVM values sum parallel work; optimizer/codegen are nested within LLVM.
End-to-end includes fresh service startup, compilation/emission, native setup/build
and executable verification. These are observations for this fixture, not a flavor
performance comparison or an improvement claim. No-op runs prepare zero bodies.

Sanitizers passed for ordinary construction and partial-construction cleanup in
all flavors. The exception probe links static libc++ to avoid the host's known
dynamic libc++abi standard-exception allocator mismatch; no sanitizer category
was disabled. LeakSanitizer ran outside the sandbox's ptrace restriction.
