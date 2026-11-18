# Straight-line yield implementation

Doc Status: supporting

The [language contract](../../../specs/generators_v0_2.md) owns semantics. This is the
first concrete-cursor slice, not a general coroutine or suspended-local implementation.

```php
function numbers(int $start): Iterator<int> {
    echo "started";
    yield $start;
    yield 2;
}
$cursor = numbers(1); // Captures arguments, does not print yet.
foreach ($cursor as $value) { echo $value; break; }
foreach ($cursor as $value) { echo $value; }
```

C++ spelling: `Iterator<int> numbers(int start) { yield start; yield 2; }`.
JS spelling: `function numbers(start:int):Iterator<int> { yield start; yield 2; }`.
Keyed spelling: `Iterator<int,string>` with `yield "first" => value;` in all flavors.

## Owners and retained facts

- `ast_yield_statement` owns its value and optional key. Two nullable packed conversion
  codes are prepared facts, reset with the ordinary expression facts.
- `generator_type_definition` is the function-specific return identity. It holds a
  non-owning function link and shared canonical value/key identities. The existing
  function return-type slot owns it; no second registry or generated source AST exists.
- Signature resolution settles output identities before caller/body preparation. It
  uses existing function signature watches for application types. Calls watch the
  existing function symbol, including failures and later recovery.
- Body preparation visits each statement once, verifies this bounded statement set,
  and calls existing generated typed-destination conversions. No liveness solver,
  copied prepared body or per-loop descriptor is retained.
- Cpp_Generator owns layout/factory/resume emission. State numbers and temporary
  statement selections are local to lowering. It reuses the emitter's expression,
  echo/call and signature paths, without synthetic AST parents.

The function's ordinary publication header includes its cursor layout. Body-only
edits change next's definition; output/capture signature changes also change the
published layout. Existing incremental publication/emission handles both. Keyedness
edits, failure/deletion and restoration are compared with fresh builds in the tests.

## Native storage and cleanup

A cursor contains a uint32-intended state and a union holding its concrete argument
record. No separate heap allocation is introduced for the frame. Captured string,
collection and shared values retain their own existing allocation/copy contracts.

The constructor initializes captures; next accesses them through local aliases and
uses switch cases for straight-line segments. Outputs are converted into exact-type
native temporaries before being written. Each yield records the next state and returns
true. Completion and exception handling call close, which marks the cursor closed
before destroying its active argument record. The destructor also calls close.

Move construction uses placement new for active captured arguments, then closes the
source. Moving a closed cursor does not touch inactive storage. `<new>` supplies
standard placement construction; generated cursor code uses no C++ templates or
co_yield. Native copy construction/assignment are deleted. Source-level named moves
and reassignment remain deferred; initial producer results use native copy elision.

This concrete union pattern is only argument storage. It does not yet implement
arbitrary locals becoming live at different source positions or nested child cursors.

## Verification

`tests/yield.php` and `yield_native.py` cover all flavors, lazy execution, pause/resume,
empty producers, keyed/discarded-key traversal, heap-backed strings, shared and inline
struct capture, and cleanup of unstarted/partially consumed cursors. Native checks
move active/closed cursors and retry after a checked byte conversion throws. Invalid
copy/reference/control-flow/output forms reject before C++ compilation.

Cross-file edits change yield values and value-only/keyed signatures; errors, deletion,
retry without edits and recovery match fresh emitted artifacts/executables. Unchanged
caller AST identity is retained and no-op runs prepare zero bodies.

`tests/yield_safety.py` accepts the fixture JSON plus an instrumented runtime archive.
It verifies string storage, captured shared owners, active/closed moves, failed conversion
and started/unstarted abandonment with ASan/UBSan/leak detection. The sanitizer runtime
uses libstdc++, while ordinary native proofs use libc++.

Existing function, Iterator and collection foreach checks also pass. No runtime ABI,
metadata generator or user-authored Iterator copy contract changed.

## Native and compiler observations

48 native fixtures passed across the three flavors; 15 ASan/UBSan/leak cases passed.
Representative lazy/resume fixture, single runs, milliseconds. O2/g0, libc++, Ninja8,
mold, no PCH/LTO, prebuilt runtime; native wall includes direct Ninja qualification
cache overhead. PHP prepare/emission timings are from a separate fixture run excluding
serialization assertions. These are correctness-run observations, not a performance
comparison. Total PHP fixture generation including incremental/fresh checks: 264.68 ms.

| Flavor | PHP prepare/emit | Native wall | Clang frontend | LLVM backend | Link |
|---|---:|---:|---:|---:|---:|
| C++ | 1.988 | 256.006 | 25.750 | 23.832 | 73 |
| PHP | 1.232 | 240.380 | 25.303 | 18.489 | 73 |
| JS | 4.821 | 253.287 | 23.688 | 19.297 | 75 |

Artifacts/traces: `v0.2/build/yield-final/`; sanitizer results: `/tmp/scpp-yield-implementation-safety/`.
