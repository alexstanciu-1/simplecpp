# Inline runtime storage: first implementation slice
Doc Status: supporting

Status: the initial no-cleanup construction/borrowing proof is implemented.
[Runtime cleanup](runtime_cleanup.md) now extends that path to types with validated
destruction operations; [copy construction](runtime_copy_construction.md) adds
explicit same-type local copying. The discussion and proof below describe the initial slice.

## Agreed scope and semantic authority

Simple C++ contracts define intended behavior. Inspect S2S output or run comparative
Clang probes when those contracts or their implementation are uncertain. Compiler
tests establish that this implementation satisfies the supported contracts.

Type names, source aliases, C++ selections, layouts and operation bindings come
from definitions through the package adapter. A fixture is an ordinary provider,
never a compiler dispatch case. Trivial destruction alone does not grant missing
semantic permission: preparation requires explicit `cleanup: "none"` and verifies
`std::is_trivially_destructible_v` against the actual C++ type.

Use existing function-call syntax for metadata-declared constructors and read-only
operations. These illustrative names are provider declarations, not builtins:

```text
$value sample_value = make_sample(7);
return sample_measure($value);
```

The constructor initializes the local directly in its destination storage. A
read-only operation borrows that object through the call and must not retain its
address. Constructed temporaries can be arguments to the same operation. Scalar
arguments/results use the existing conversion and call-checking rules.

## Ownership and representation

| Owner | Implemented contract |
|---|---|
| Runtime preparation | Generic construction from declared integer arguments, including zero arguments; explicit no-cleanup and call-scoped non-retention contracts; measured traits and verified bridge signatures. |
| `load_runtime` | Compose imported opaque definitions with the base catalog; normalize semantic argument use and result production separately from ABI positions. Own package invalidation. |
| `resolve_types` | Intern an opaque size/alignment representation through the ordinary canonical type path. Distinct named types retain distinct identities even with equal layouts. |
| `check_bodies` | Distinguish a local borrow from a copied read; mark direct local construction; check arguments against imported contracts. |
| `analyze_lifetimes` | Require live initialized locals, record local construction and call-scoped argument access, and retain scope/return boundaries. |
| `lower` | Associate constructor results with local or temporary storage slots. Prepare borrowed operands and hidden result destinations. |
| `emit_llvm` | Allocate measured, aligned byte storage; pass its address to the bridge; emit scalar loads/stores only for scalar values. |

An object's semantic type remains an object type. Its address is a way to access
that object, not its canonical representation. Similarly, a constructor's semantic
object result is separate from its LLVM `void` result and hidden destination pointer.
The adapter currently accepts ordinary default-address-space bridge pointers; the
backend rejects an incompatible stack address space.

The initial imported types have `copy: unavailable`: no copy operation was
provided. Explicit copy bindings now enable the separate copy-construction path. This is a compiler capability boundary, not a claim that Simple C++
makes every underlying type noncopyable. Unbound copies, assignments and owned aggregate
parameters/returns in source-defined functions fail explicitly.

Lifetime analysis remains a separate stage; added execution instructions do not
replace it. An `argument_borrow` end records the end of argument access. It must
not become a destructor location for an owned temporary: future cleanup must
respect full-expression lifetimes. Local scope/return ends remain separate facts.
The initial slice emitted no destruction. The follow-up [cleanup implementation](runtime_cleanup.md)
now records separate obligations and consumes them as explicit ABI calls.

## Compact data, future MT and incremental use

- Shared type and callable definitions are retained once per accepted context;
  body rows reference canonical type IDs and shared declarations.
- Checked rows add a value-use tag, argument passing mode and local-construction
  mode (now an enum distinguishing direct construction and copying). Lowered values reference a storage slot only when addressed; locals and
  temporaries share one slot dataset. Value IDs and slot IDs are distinct and
  scoped to their owning body. Constructor destinations are a worker-local map.
- The intended native representation is typed linear containers with small enum
  tags, inline records and tagged payloads. PHP object allocation is not a memory
  target, and no native memory usage or threading performance is claimed.
- Workers read fixed phase inputs, produce separate results and use the existing
  joins. Preparation/tool verification remains coordinator work; no shared mutable
  catalog or ID allocation is added inside body workers. Execution stays serial.
- An unchanged package reuses its base/composed catalogs and normalized contracts.
  A package or base-catalog change requests full selection through the same pipeline.
  Body edits retain valid caller plans and native objects. Fine-grained provider
  invalidation and persistent caches remain deferred.

## Executable proof

[`runtime_inline.php`](../../tests/integration/runtime_inline.php)
prepares two unrelated types through the same JSON-driven path. One is over-aligned
and noncopyable and verifies its own address, so an accidental byte copy or wrong
alignment fails at execution. The other has a different layout and constructor arity.
The test covers:

- Local and temporary construction, nested borrows and scalar widening.
- Repeated construction in a loop, branches and an early return.
- No aggregate loads/stores; explicit construction/borrow facts in plans and exports.
- Reversed lowering worker completion with deterministic joins and unchanged inputs.
- One full build followed by one body edit, retaining the unchanged caller plan,
  native object and provider snapshot.
- Rejection of copying, assignment, source aggregate passing/returns, an unavailable
  local, missing non-retention metadata and a false no-cleanup declaration.
  Rejected compiler/preparation work preserves accepted publication.

The scalar regression suite and standalone preparation tests remain applicable.
The existing scalar full/ThinLTO experiment still owns LTO flow feasibility;
this slice adds no production LTO mode.

## Next boundary

Cleanup now connects semantic lifetime analysis to explicit destruction actions,
including temporaries, branches, loops and early returns. The next step is the
small string surface, beginning with literal construction and echo. Containers and
smart pointers need their own ownership/copy/move contracts. Mutable/escaping borrows,
source reference syntax, copy/move operations, field access, general record syntax,
source aggregate passing and exception unwinding also remain outside this slice.
