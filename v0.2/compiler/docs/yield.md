# Synchronous yield implementation

Doc Status: supporting

The [language contract](../../../specs/generators_v0_2.md) owns semantics. This is the
bounded concrete-cursor implementation, including suspended locals and conditionals.

```php
function numbers(int $start): Iterator<int> {
    echo "started";
    $next = $start;
    yield $next;
    $next += 1;
    yield $next;
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
  declaration/write, echo/call and signature paths, without synthetic AST parents.
- `cpp_generator_layout` is an ephemeral backend publication record: emitted layout
  text, required public headers and non-owning application-type dependencies. Each
  source produces it before header publication. Header planning never reads foreign
  executable bodies. There are no new AST nodes or retained preparation facts.

The function's ordinary publication header includes its cursor layout. Body-only
edits change next's definition; output/capture signature and local-type changes
can also change the published layout. Consumers include the published cursor header
rather than duplicating its private local-type dependencies. Existing incremental publication/emission handles both. Keyedness
edits, failure/deletion and restoration are compared with fresh builds in the tests.

## Native storage and cleanup

A cursor contains a uint32-intended state and a union holding its concrete argument
record. No separate heap allocation is introduced for the frame. Captured string,
collection and shared values retain their own existing allocation/copy contracts.

The constructor initializes captures; next accesses them through local aliases and
dispatches resume states to labels inside ordinary native conditional blocks. Outputs are converted into exact-type
native temporaries before being written. Each yield records the next state and returns
true. Completion and exception handling call close, which marks the cursor closed
before destroying its initialized locals in reverse order and then its argument record. The destructor also calls close.

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

Cross-file edits change yield values, value-only/keyed signatures, and local layouts
(int/string/shared/bool/addition/removal); errors, deletion,
retry without edits and recovery match fresh emitted artifacts/executables. Unchanged
caller AST identity is retained and no-op runs prepare zero bodies.

`tests/yield_safety.py` accepts the fixture JSON plus an instrumented runtime archive.
It verifies string storage, captured shared owners, active/closed moves, failed conversion
and started/unstarted abandonment with ASan/UBSan/leak detection. Suspended-local
checks cover string reassignment, shared lifetime, active moves, abandonment before
later declarations and initialization failure after an earlier suspension. The sanitizer runtime
uses libstdc++, while ordinary native proofs use libc++.

Existing function, Iterator and collection foreach checks also pass. No runtime ABI,
metadata generator or user-authored Iterator copy contract changed.

## Native and compiler observations

102 native fixtures passed across the three flavors; 33 ASan/UBSan/leak cases passed.
Representative nested-conditional fixture, single runs, milliseconds. O2/g0, libc++,
Ninja8, mold, no PCH/LTO, prebuilt runtime; fresh app objects. Native wall includes
direct Ninja qualification overhead. PHP prepare/emission is measured in the same
fixture-generation process. These are correctness-run observations, not a repeated
performance comparison; frontend/backend/link timings do not sum to native wall.

| Flavor | PHP prepare/emit | Native wall | Clang frontend | LLVM backend | Link |
|---|---:|---:|---:|---:|---:|
| cpp | 0.898 | 232.111 | 24.248 | 21.357 | 62 |
| php | 0.779 | 231.646 | 20.551 | 19.921 | 64 |
| js | 0.683 | 251.481 | 25.919 | 25.347 | 59 |

Total PHP fixture generation, including incremental/fresh checks: 344.20 ms.
Artifacts/traces: `v0.2/build/yield-branches/`; sanitizer results: `/tmp/scpp-yield-branches-safety/`.

## Suspended local storage

Every admitted local has a concrete union slot, initially inactive.
The existing binding emitter performs placement construction using its already-approved
initializer and generated conversion. Only successful construction marks its active flag. One native bool per local
replaces the old initialized-prefix counter: branch selection leaves holes in the
local inventory. Flags are grouped before the slots, rather than padding each slot.
No liveness solver, heap frame or additional C++ template is used. Cursors without
locals have no local-lifetime flags.

The ordinary expression emitter uses a private storage-name map while emitting the
resume body; reads/writes target the active slot directly. Moves transfer captures
and initialized locals, close the source on success, and clean partially initialized
destination locals if moving fails. Existing value-type move/copy contracts apply.

Layout planning visits declarations in lexical order, including conditional arms;
it does not add preparation work or retained caches. Reusing these transient layout
plans for unchanged producers can be considered if measurement warrants it. Loops,
nested cursor locals and liveness-based slot reduction remain deferred.

## Conditional suspension

The ordinary preparation conditional worker owns truthiness, lexical scopes and
completion; yield adds no AST node, prepared state-machine record or retained fact.
Private lowering recursively visits arms and emits native if/else blocks. A resume
switch jumps to the label following the suspended yield, inside the selected arm.
It never repeats an earlier condition. Each ordinary statement and yield output has
its own native temporary scope, so a resume jump crosses no automatic initialization.

Normal block exit destroys only that block's active locals, in reverse order. Close
uses the same flag-clearing destruction helper across the complete lexical inventory;
mutually exclusive arms contribute inactive slots. Moves transfer only active slots.
This is sufficient for the admitted acyclic control flow; loops require their own slice.

Conditional checks cover all arms, nested suspension, keyed heap-string outputs,
condition side effects (evaluated once), outer-local writes, early return, scope-exit
cleanup, sparse initialized-slot moves, abandonment and checked failures. Cross-file
branch edits, an invalid unselected arm, deletion and recovery match fresh artifacts;
unchanged callers retain their AST and no-op runs prepare zero bodies.
