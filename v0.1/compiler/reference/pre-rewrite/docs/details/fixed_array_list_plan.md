# Fixed-array source-list proof: model assessment
Doc Status: supporting

Status: implemented after the user's coordinated-migration approval. The fixed-array
and method proofs run through the ordinary compiler pipeline. Dynamic allocation
and the growing-list scenario remain separate work.

The [default generic contract](generic_type_contract.md) is now enforced. Current
proofs use concrete element types and templated capacities. Generic `T data[N]`
remains [parked review debt](generic_type_contract.md#deferred-review-debt): bare
`T` does not promise initialization. No fixture exemption is used. Ordinary methods,
array layout/indexing, copy isolation and incremental proofs remain covered.

## Implemented source surface

```php
const STEP: int32 = 1;
template<int N>
struct list_i32 {
    public int32 $data[N];
    public int32 $size;
    public function append($value int32): void {
        $this->data[$this->size] = $value;
        $this->size = $this->size + STEP;
    }
    public const function get($index int): int32 {
        return $this->data[$index];
    }
}
```

`public const function` gives the implicit `$this` parameter a call-scoped const
borrow; `public function` gives it a mutable borrow. Both use ordinary signatures,
argument validation, lifetime checking and pointer ABI preparation. Methods are
scoped by the owning record's allocated declaration ID. Concrete method instances
retain that definition and the receiver's template arguments; no AST is copied.
Ordinary method contracts and bodies are checked independently of use. Dependent
template methods are instantiated only when demanded.

One-dimensional fields accept a positive literal extent, global integer constant,
or integer template parameter. Element eligibility derives from the named field
contract: value copying, zero storage and no cleanup. Nested/managed elements,
standalone array annotations and multidimensional declarators remain unsupported.
Array capacity is a type property; it is not the source list's logical length.
`get()` above checks physical array capacity. A logical-length check is source list
behavior and is deliberately absent from this minimal fixture.

Global constants may name an integer type, such as `int32`; literal range checking
uses that type. Unannotated constants and integer template parameters retain the
language `int` contract. This is literal preparation, with no expression evaluation
or implicit narrowing. The counter uses ordinary exact-type addition.

## Implementation owners

- `resolve_types/Record_Preparation` creates producer-neutral `array_type_definition`
  recipes. `Type_Cache` materializes their exact element/count identities in the
  candidate lineage. Source/provider record fields still share `Record_Definitions`.
- `resolve_types/Concrete_Preparation` prepares literal constants, then schedules
  ordinary/specialized records, applications and members using indexed prerequisites.
  `Member_Worker` resolves concrete receiver contracts; `Member_Join` validates
  selected private outputs. `Instance_Identities` owns allocation for both joins.
- `check_bodies/Place_Checking` builds root plus ordered field/index projections.
  `Expression_Order` exposes index dependencies. Assignment indices execute once,
  then the address is prepared before the right-hand side. Lifetime analysis
  consumes index values while preserving the owning local's lifetime.
- `prepare_backend/LLVM_Types::compound()` supplies target layout probes with array
  storage. LLVM owns element stride and record padding. Lowering emits explicit
  address preparation; `emit_llvm/Location_Emission` expands checked indices into
  an unsigned bounds comparison and a fail-stop `llvm.trap` branch before access.
  Signed negative indices and narrow unsigned indices retain their meaning.
- Method bodies compare separately from record storage and method signatures.
  A body-only replacement retains concrete IDs and layouts; changed template
  definition snapshots invalidate retained application bindings before use.

## Proofs

[Fixed arrays](../../tests/integration/fixed_arrays.php) proves distinct
capacities/types, zero initialization, independent copies, unsigned and negative
indices, target-before-value order, const/type/count diagnostics, bounds failures
and a pure body increment.
[Source list](../../tests/integration/fixed_array_list.php) proves source
methods, ordinary and templated receivers, calls through explicit receiver types,
method-to-method demand, shared receiver signatures, reversed worker completion,
join rejection, overflow and one selective method-body replacement.

## Original migration assessment

The table below records the limits assessed before the approved migration.

## Scope

The list is a source test, not a compiler intrinsic or a production container.
Prove a fixed capacity of 16 in one source declaration and a second capacity in
another instance/declaration, with at least two eligible scalar element types.
All capacities and element types flow from source/type contracts.

The object contains an inline array and a size field. Append checks capacity,
writes one existing element slot and increments size. Indexed access must check
its bounds; no allocation or buffer replacement occurs. Initially use element
contracts that permit zero initialization, value copying and no cleanup. Ordinary
copyable inline records retain their value-copy behavior. The separately tracked
noncopyable owning-list scope and copying debt concern the later dynamic case.

## Existing limits and required owners

| Owner | Current limit | Required shared model |
|---|---|---|
| Parsing and symbol collection/resolution | Struct children are fields; callable definitions are free functions. | Array declarators/index expressions and member declarations/calls; methods have allocated declaration identities and an owning type. Preserve the original AST. |
| Type preparation and instantiation | `intern_array()` stores a representation, but source arrays have no semantic materialization path. Field permission explicitly requires integer representation. | Canonical element/count array types; field eligibility and zero/copy/cleanup capabilities derived from constituent types. Method instances bind the containing template arguments through explicit contexts. |
| Body checking and lifetime analysis | `place` is a local ID plus at most one field ordinal. | A compact root plus ordered field/index projections. Existing direct fields become one projection. Preserve evaluation order, single evaluation of indices, root lifetime and const permissions. |
| Call preparation | Source calls have ordered explicit parameters; no method receiver. | Resolve methods by owner type; normalize their implicit receiver to the shared borrowed-parameter contract. Use the same checked calls, signatures and backend preparation as free functions. |
| Target layout, lowering and emission | Record layout preparation spells every field using `LLVM_Types::scalar()`; `storage_address` has one field ordinal. | Compound storage layout and element stride from the target; common projected addresses, checked indexing and explicit failure control flow. No host-size guesses or sample-specific offsets. |
| Numeric operations | Exact-type addition exists; an ordinary integer literal cannot silently narrow to a fixed-width size field. | Implement the bounded general counter operation needed for append in the ordinary numeric owner. Preserve conversion rules; do not add a test-only constant provider or enable `int` struct fields to bypass them. |

A field/index projection is a location, not an independently owned value. Source
methods must retain concrete callable identities and signature dependencies;
spelling a method as a generated free-function name is not a substitute for
member binding. Do not synthesize a second source AST for specializations.

## Source-contract decision

The checked-in Simple C++ [compact-layout contract](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/compact_layout_types.md#22-first-slice-struct-members)
explicitly rejects struct methods. Its existing
[fixed-array surface](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/array_semantics.md#10-fixed_arrayt-n-first-slice-semantics)
uses `fixed_array<T, N>`. The requested C++-shaped `T data[N]` example and methods
therefore need to be identified as prototype language extensions when choosing
the precise PHP++ spelling. The prototype already has its own agreed template
syntax; extending it must not claim compatibility with the older S2S surface.

Approved and implemented: public instance methods and fixed-array
fields in the prototype use the common models above. Defer overloads,
inheritance, static methods, custom constructors/destructors, dynamic allocation,
managed elements and provider template families.

Smallest alternative: implement the same array/location migration first and use
existing free functions with borrowed record parameters for the proof. That
reduces the immediate declaration/receiver work but defers the requested methods;
it is a scope change, not an equivalent claim of completion.

## Validation and risks

- Native source-to-output proof: empty state, several appends, element order,
  full capacity, rejected overflow and out-of-range reads/writes.
- Two element types and two capacities through the same generic paths.
- Independent inline copies; const receiver/index write rejection; wrong element
  types and unsupported array counts rejected at source locations.
- Index evaluation order and single evaluation, including side-effecting calls.
- Target-derived size/alignment/offsets/stride, checked against independent native
  layout facts where the contract is unclear.
- Fixed work inputs, private results, reversed completion and join rejection;
  one full build plus one body-edit increment with retained-snapshot purity.
- Existing full suite: scalar fields/copies, runtime borrowing, source references,
  template instances, cleanup and backend reuse must continue working.

The main risks are stale member-instance identities, duplicated index evaluation,
lost const restrictions and mixing element layout with host assumptions. This
crossed several owners; the user explicitly approved the coordinated refactor
required by [AGENTS.md](../../reference/source-repository/working_rules.md#design-and-scope).
