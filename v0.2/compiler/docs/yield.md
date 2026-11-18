# Synchronous yield implementation

Doc Status: supporting

The [language contract](../../../specs/generators_v0_2.md) owns semantics. This is the
bounded concrete-cursor implementation, including suspended locals, conditionals and while/do-while/for/foreach loops.

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
- Cpp_Generator owns layout/factory/resume emission. The ordinary statement emitter
  owns the shared statement/control-flow traversal. State numbers and temporary
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

147 native yield fixtures passed across the three flavors (144 in the instrumented
suite plus three owning for-header consumers); 45 ASan/UBSan/leak cases passed.
The ordinary-for native regression suite also passed 18 fixtures. Existing loop,
foreach, Iterator and function PHP proofs and writing checks passed.

Nested-loop fixture: three sequential fresh native builds per flavor, median and
range in milliseconds. O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime.
Native wall includes direct Ninja qualification overhead. PHP prepare/emission is
one earlier compiler measurement; the timing rerun uses saved emitted fixtures.
The wide native ranges are observations, not evidence of a flavor-specific cost.
Trace stages and wall time are distinct measurements.

| Flavor | PHP prepare/emit | Native median | Native range | Frontend | LLVM backend | Link |
|---|---:|---:|---:|---:|---:|---:|
| cpp | 0.739 | 273.736 | 245.1–350.1 | 21.834 | 24.158 | 100 |
| php | 1.338 | 364.725 | 321.0–519.5 | 24.588 | 26.492 | 103 |
| js | 0.740 | 367.624 | 259.2–399.4 | 34.373 | 23.152 | 105 |

Artifacts/traces: `v0.2/build/yield-loops-final/`, `v0.2/build/yield-loops-timing/`;
additional header-owner fixtures: `/tmp/yield-loops-extra.json` and
`/tmp/{cpp,php,js}_loop_header_owner.cpp`; sanitizer results:
`/tmp/scpp-yield-loops-final-safety/`. Full PHP fixture generation including
incremental/fresh checks took 617.76 ms in the instrumented run.

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
plans for unchanged producers can be considered if measurement warrants it.
Nested cursor locals and liveness-based slot reduction remain deferred.

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
The same flags are reused after loop-body cleanup; a new iteration starts a new local lifetime.

Conditional checks cover all arms, nested suspension, keyed heap-string outputs,
condition side effects (evaluated once), outer-local writes, early return, scope-exit
cleanup, sparse initialized-slot moves, abandonment and checked failures. Cross-file
branch edits, an invalid unselected arm, deletion and recovery match fresh artifacts;
unchanged callers retain their AST and no-op runs prepare zero bodies.

## Completion planning

The [synchronous yield backlog](../../specs/planning/yield_completion_backlog.md)
records ordered remaining slices and discussion gates. It is planning, not an
extension of the implemented language contract.

## Loops and shared traversal

`Cpp_Emitter::statements` now owns ordinary and producer statement traversal; the
separate generator sequence walker is removed. `cpp_resume_context` is temporary
lowering state: resume numbering, label numbering, non-owning lexical scope links
and loop cleanup boundaries/update targets. No AST node, type fact or preparation
cache was added. Existing loop preparation visits each region once.

While/do-while remain native loops. A producer for initializer executes once before
a native while; continue cleans exited lexical scopes and jumps to its update label.
The update uses the ordinary binding/snapshot emitter. Normal functions retain their
existing native for shape and RAII. Persistent for-header locals are included in the
frame inventory and destroyed on loop exit; body locals are cleaned on each iteration.
Normal lexical exit and transfers share `generator_cleanup`; return, exceptions and
abandonment use cursor close. Yield itself does not clean active locals.

Tests include empty loops, do-while's first iteration, nested loops, break/continue
after yield, for-init/update side effects, repeated owning body-local construction,
header-owner survival across continue, early exit, movement, abandonment and failure.
Cross-file for/do/while edits and invalid bodies recover to fresh emitted artifacts.

## Suspended foreach

Ordinary and suspended foreach use the same `iterable_type` emission protocol:
`lower_cpp_iteration_storage`, `lower_cpp_iteration_initialize` and
`lower_cpp_iteration_next`. Storage is separate from initialization so the same
metadata-generated traversal can use stack state or prefixed cursor-frame members.
There is no handwritten collection-family dispatch or runtime ABI change.

Each suspended foreach contributes one concrete frame resource to the existing
lexical slot inventory: optional retained receiver, required output/key storage,
and generated traversal state. Its live flag participates in ordinary cursor move,
close and reverse cleanup. `cpp_resume_context.scopes` can also reference a foreach
header resource, so continue keeps it while exit destroys it. Custom Iterator output
staging still protects an existing destination when next returns false.

Preparation reuses the normal foreach binding, conversion and method-watch path.
It adds only receiver-boundary and nested-cursor checks; no new AST node, retained
fact, copied statement list or preparation pass is introduced. Lowering recomputes
borrowed frame access rather than storing self-pointers. Source-owned layout
publication includes custom receiver types before the header join.

`tests/yield_foreach.php` verifies all four collection families, custom value/keyed
Iterator, temporaries, shared fields, mutation, nested foreach and checked failures.
Collection and custom-Iterator cross-file edits, invalid bodies/contracts, deletion,
retry without edits and recovery are compared with fresh artifacts. No-op runs
prepare zero bodies and unchanged callers retain their AST.

Verification: 117 focused native fixtures, 135 ordinary foreach regressions,
147 existing yield regressions; 48 ASan/UBSan/leak cases using the instrumented
runtime. These cover cursor relocation, abandonment, retained shared-field lifetime,
mutation, keyed boxed traversal and failure cleanup. Iterator metadata generation
and rejection, generated-output freshness and compiler style checks also pass.
Nested generated cursors, delegation and concurrent mutation remain deferred.

Focused measurements (2026-11-18, milliseconds): native columns are medians of
three fresh application builds after the regression runs; PHP is one observed
read/tokenize/parse/prepare plus emission measurement, excluding the test's
serialization purity check. O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime.
These test builds use direct Ninja qualification, not the panel's in-process path.
Native wall includes process/build orchestration and qualification overhead; it
excludes the PHP compiler. Clang traces instrument frontend/backend work and the
link column is the Ninja link-command duration. They do not sum to wall time.

| Fixture | PHP compiler | Native wall | Frontend | LLVM backend | Link | Native range |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| C++ vector | 1.286 | 216.88 | 26.80 | 20.00 | 64 | 213.58–236.47 |
| PHP vector | 0.654 | 215.73 | 26.47 | 18.71 | 65 | 214.24–228.14 |
| JS vector | 1.021 | 222.81 | 26.84 | 19.28 | 68 | 219.00–225.08 |
| C++ custom Iterator | 1.722 | 212.20 | 19.39 | 19.06 | 63 | 200.14–236.27 |
| PHP custom Iterator | 1.321 | 206.01 | 19.49 | 17.61 | 63 | 205.82–236.31 |
| JS custom Iterator | 1.830 | 205.31 | 19.10 | 17.43 | 60 | 203.96–205.58 |

Raw local results: `v0.2/build/yield-foreach-final/results.json` and
`v0.2/build/yield-foreach-timing-{0,1,2}/results.json` (generated test artifacts).
