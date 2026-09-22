# Native templates using compiler-owned source operations
Doc Status: supporting

Status: isolated investigation passed, 2026-09-19. Follows the
[ownership decision](clang_lifecycle_composition.md#ownership-decision).
Production compiler and preparation code are unchanged. The adapter described
here is a tested candidate, not an adopted source representation or implemented
compiler feature.

## Answer

A concrete native adapter can satisfy a template's element-operation requirements
by forwarding its C++ special members to compiler-owned LLVM functions. Those
functions own the **complete source operation**, including field composition.
The adapter contains aligned inline storage, not automatically managed C++ fields.

For the tested `scpp::vector_t` path:

```text
native vector insertion / relocation / assignment / destruction
  -> native element adapter's selected C++ special member
     -> compiler-owned complete source operation in LLVM
        -> imported runtime operation for each managed source field
```

There is one definition of each source operation. The C++ adapter contains calls,
not another implementation of source construction/copying/destruction. LTO may
inline those calls, but correctness does not depend on it.

## Why a normal C++ field declaration is insufficient

Declaring real managed members and also calling complete source operations would
give both C++ and our compiler responsibility for member lifetime. C++ constructs
members before the constructor body and destroys them after the destructor body;
an empty or forwarding body does not suppress this behavior. See
[construction](https://eel.is/c++draft/class.base.init) and
[destruction](https://eel.is/c++draft/class.dtor).

The candidate adapter instead owns `alignas(...) std::byte payload[...]`.
Its C++ lifecycle manages the adapter object; compiler-owned operations manage the
payload's fields. Byte arrays can provide storage for explicitly constructed native
objects; imported managed fields are placement-constructed through their prepared
operations. See the [C++ storage rules](https://eel.is/c++draft/intro.object).
The adapter does not copy that byte array to copy a managed source value.

The fixture uses a C++ layout witness solely for `sizeof`, alignment and offsets.
No object of that witness type is constructed or accessed. It is not another
implementation of the source operation. The eventual compiler must export and
validate its authoritative layout through the target/layout owner; the witness
does not let preparation invent a second source field model.

## Source storage and native adapter storage are distinct

A source value in LLVM-owned storage is not automatically a live C++ adapter.
The test never casts it to an adapter reference. Instead:

1. LLVM allocates correctly aligned source storage and calls its complete constructor.
2. An explicit payload bridge passes a borrowed source address to a native adapter
   constructor; that constructor invokes the same compiler-owned copy operation.
3. The real vector owns the resulting adapter and its independently owned payload.
4. Native code borrows a stored payload back into an LLVM inspection function.
5. Copy-out calls the compiler-owned copy operation into uninitialized caller storage.
6. LLVM independently destroys the original and copied-out source values. Native
   vector cleanup destroys its adapters, which invoke the same source destructor.

The explicit payload bridge is a fixture C ABI function, with its physical
signature measured by the existing metadata extractor. Current free-function
metadata maps object borrows to native C++ references; it does not yet express
this independent source-payload/native-adapter relationship. Do not reuse that
reference contract to conceal a cast between the two representations.

## Reproducible evidence

Run the [focused fixture](../../src-runtime-preparation/tests/source_operations/README.md)
from the repository root:

```sh
python3 src-runtime-preparation/tests/source_operations/run.py
```

Clang 18.1.3, C++23, x86-64 Linux/WSL2 and the configured Simple C++ runtime.
The source payload has two managed fields, each containing a real runtime vector,
and one scalar. The measured test layout is 64 bytes with alignment 32; the native
adapter has exactly that size/alignment, with payload at offset zero. The alignment
is a native test stress case, not a newly supported source alignment annotation.

Passed O0, O1, O1 with full LTO, and O1 with ThinLTO. Each configuration ran an
original source-operation module followed by one replacement that changed a
default scalar value. The same native bitcode bytes and modification time were
retained across that edit; linking was repeated. This proves a reusable boundary,
not implemented compiler incremental selection or retention.

Each of the eight executions observed:

| Complete source operation | Calls |
|---|---:|
| Construction | 2 |
| Copy construction | 6 |
| Move construction | 1 |
| Copy assignment | 2 |
| Move assignment | 1 |
| Destruction | 9 |

The observer enforces exactly two managed-field transitions per source operation,
in declaration order and reverse order for destruction. Field construction cannot
reuse a live field address; destruction cannot run twice. Copies/moves require a
live source, assignments require a live destination, and every field is dead at
completion. Construct/copy/move totals equal destruction totals. Content checks
prove independent vector copies, assignment, self-assignment, forced relocation,
erase, and independent copy-out after the original/source container has died.

The native module declares, but does not define, all six source operations. The
LLVM fixture is their sole implementation. Negative checks reject copy insertion
when adapter copying is explicitly deleted, linking with absent source operations,
and linking duplicate source-operation definitions. Unsupported operations are
not replaced with byte copying or dummy implementations.

The final focused run took about 12 seconds with four independent mode build jobs.
This is not a generated-program performance measurement. PHP/Python syntax and
diff checks passed. No broad compiler suite was needed for isolated fixtures/docs.

## Capabilities and representation limits

- **Concrete and typed:** each native specialization uses a concrete adapter type.
  No runtime type ID lookup, per-element function-pointer table, or extra payload
  heap allocation is introduced by this adapter. Payload fields retain their own
  runtime allocations. This does not establish execution-performance parity.
- **Exact identity:** production needs an exact source-type/specialization identity
  for the adapter and its imported operations. Equal size/layout is not sufficient.
- **Capability fidelity:** copy/move availability, effects and non-unwinding behavior
  must match the source contract. The fixture explicitly deletes copying for its
  negative variant. A native trait is not by itself proof that every demanded
  container operation is available; compile the actual demanded operations.
- **Traits can differ:** forwarding special members make the adapter nontrivial.
  For a trivial source record, that could affect native concepts and optimizations.
  The current test uses a genuinely managed payload. It does not select a universal
  representation for trivial source types.
- **Native template requirements matter:** templates using the declared lifecycle
  interface can work with this candidate. Direct source-field access, inheritance,
  nominal C++ overload matching, standard-layout/triviality constraints, equality,
  hashing and custom methods need their own explicit mappings. The adapter is not
  transparently interchangeable with an existing handwritten C++ source type.
- **Fatal failure only:** hooks use the current non-unwinding/fatal-boundary policy.
  Recoverable exceptions and partial source-construction cleanup remain deferred.
- **Source support unchanged:** these are native declarations and hand-authored LLVM
  test operations. They do not enable custom source structs, general source moves,
  classes, source-type export or provider-family consumption in the compiler.

An alternative is a runtime family with explicit element-operation policies and
raw storage management. That changes the runtime container integration and has not
been tested here. It is not necessary for this vector feasibility result.

## Next design boundary

The [source/native contract](source_native_contract.md) now defines this boundary
before production integration. It retains the accepted ownership split and covers:

1. Exact source type identity, layout, and selected complete-operation capabilities.
2. Required compiler function imports with ABI, object-state and failure contracts.
3. The explicit source-payload/native-adapter mapping and supported family demands.
4. Private preparation outputs and join validation of layout, imports and exports.
5. Invalidation: layout/capability/ABI/adapter changes rebuild dependent native
   specializations; operation-body-only edits recompile the source module and relink.

Source analysis and cleanup scheduling remain compiler-owned. Native template
instantiation determines where a runtime container invokes those operations.
Ordinary runtime packages retain their self-contained-link guarantee; project
modules need declared imports and a final join that proves they are satisfied.
The design and investigation do not implement that protocol or authorize a wide
refactor. The next step is a concrete implementation plan under that contract.
