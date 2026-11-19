# Collection initialization — private consuming inputs

Doc Status: planning

Recorded 2026-11-19. Flat bulk transport and remote populated-vector coverage are
integrated under one generated contract; see the integration results below.
This note supports bootstrap 2.4 and records the bounded transport decision and
verification. The [data initialization standard](../../../specs/data_initialization.md)
remains authoritative and now includes the narrow private runtime transport amendment.
The source API for user factories remains read-only; enabling user factories is deferred.

## Existing capability, optional consuming transport

Keep `list_initializable` and `key_value_initializable` as the source contracts.
Construction is explicit capability opt-in, not detection of a specially named
method or of a vector/hash representation. A factory returns the completed target
under its normal class/struct representation rules.

| Binding entry point | Input rights | Eligible providers |
| --- | --- | --- |
| Existing read-only factory | Inspect ordered input and copy element values; never retain its backing | Registered runtime providers and, when independently enabled, user factories |
| Optional consuming factory | Move from exclusive compiler-owned materialized input; never retain its backing | Explicitly registered runtime providers only |

The consuming callable is an implementation alternative of the same initialization
binding, not another source interface. Missing eligibility selects the read-only
path. User factories remain read-only; matching a signature/name never grants
consumption. Enabling user factories and their non-escape enforcement is separate.

Materialize entries using existing prepared conversions, exactly once and in source
order. Copy source lvalues into the private input; construct temporary results there.
Never move directly from a source local, property, argument or collection slot.
Copies preserve each type's ordinary value/identity semantics; a shared object copy
does not clone the object. Invoke the factory only after every input succeeds.

Initially permit consumption only for registered element transfers established to
be nonthrowing. Every input slot, including a moved-from slot, remains destructible.
Allocation/validation failure destroys the partial result and all constructed input
slots. Replace an existing assignment destination only after successful construction.
Previously executed source effects are not rolled back. No consumed-slot bitmap,
source move syntax, arbitrary borrow facility or runtime type-name test is needed.

## Presizing and storage

The factory receives the exact entry count. For vectors, allocate capacity for that
count before transfer and perform no growth allocations while filling. Empty input
requires no element backing allocation. This is not a promise of one total allocation
or exact public capacity: a representation can also allocate a storage record.
Future keyed factories reserve for the input entry count without discarding ordered
entries or bypassing duplicate-key validation. Nested source construction is now supported. Only eligible bulk levels promise
presizing; application/outer levels currently retain append construction.

Reserve/populate algorithms belong to the runtime factory, not emitted per-element
reserve/append calls. The current metadata uses a 4096-byte inline budget with
conservative element limits: 512 for integer families and 128 for strings. Generated
native assertions require `sizeof(element) * inline_limit <= stack_bytes`. This
avoids native layout computation in preparation and prevents a representation change
from silently exceeding the bound. Native typed arrays provide alignment.

Within the limit, lowering uses an ordinary local typed array. Above it, it uses
`new T[n]{...}` and a concrete runtime guard whose destructor calls `delete[]`.
Native array construction cleans up partial inputs if an entry throws before the guard
is created. Both paths evaluate entries once, in order, before the same factory call.
There are no per-element consumed flags, raw-placement input buffers or duplicated
initializer expressions. The initial placement-array prototype was rejected after
measurement exposed a large Clang compilation regression.

## Ownership and lightweight preparation

| Owner | Responsibility |
| --- | --- |
| Syntax/AST | One source-ordered entry inventory and target context; no consuming-specific AST node or parallel expression list |
| Runtime metadata/publication | Declare input/result types, existing read-only callable, optional consuming callable, nonthrowing transfer eligibility and presizing contract; publish concrete names/includes |
| Shared preparation | Resolve one initialization binding and existing entry conversions; validate admissibility and record dependencies |
| Generated compiler bindings/lowering | Consume settled facts, select eligible transport, preserve order/lifetime, and emit one factory call |
| Runtime factory | Allocate destination storage, transfer/copy entries, validate and clean up |

Keep optional callable information on the resolved binding/provider, not repeated
booleans on every AST node. Generate dependency and eligibility queries from metadata;
do not add handwritten scalar/string/vector name switches or rediscover signatures
during emission. Reuse an honest active-v0.2 owner where available; the my-try model
is reference material, not authorization to copy its structures. If implementation
needs a wide cross-owner refactor, report that boundary before expanding the slice.

## First implementation slice: flat published typed vectors

Support explicitly typed, flat populated literals for published integer and string
vectors through the common initialization binding. Preserve current empty-literal
behavior. Exercise both read-only and consuming runtime bindings, including a
read-only-only provider proving fallback. No new source spelling is introduced.
Use concrete runtime adapters; do not require public variadic C++ templates.

Start by amending the normative transport contract and publishing the bounded runtime
bindings. Then connect existing literal preparation/conversions to materialized input
and direct factory emission. Integers prove the inexpensive bulk path; strings prove
private-input consumption, independent source values and embedded-NUL preservation.
Actual compiler-generated units must confirm the isolated research conclusions.

### Verification checklist — completed

- [x] Contract/publication: consuming eligibility is explicit, parameter/result types
  and includes are validated, and read-only-only providers select the copy path.
  Source-authored factories cannot acquire consuming rights by name or signature.
- [x] cpp/php/js on Clang and GCC: empty/single/multiple entries, integer boundaries,
  prepared checked conversions, short/heap strings and full embedded-NUL bytes.
- [x] Distinct entry identities prove source order and single evaluation. Throwing
  entry evaluation/conversion suppresses later entries and the factory call.
- [x] Repeated source string lvalues remain unchanged; independently mutate each
  result element and the source, then destroy sources/input backing and read results.
- [x] Inject allocation failures during input and destination construction. Verify
  partial cleanup, balanced ownership and unchanged existing assignment destination;
  test successful replacement as well. Run ASan/UBSan lifetime checks separately.
- [x] Assert sufficient presized destination capacity and zero growth during fill,
  including 0/1 and size boundaries. Distinguish input, destination element-block,
  storage-record and string payload allocations; do not infer presizing from timing.
- [x] Exercise both sides of the temporary-storage byte threshold, with correct
  alignment, identical results and cleanup. Empty input uses no element backing.
- [x] Incremental versus fresh output: change source entries/count/type/conversion, remove a referenced function,
  and recover. Verify affected callers rebuild and unrelated callers reuse preparation
  and emission. Provider callable/include/eligibility changes and consuming-binding
  removal/restoration use regenerated exports and fresh compiler sessions. Verify
  retired literal calls and old provider headers disappear; rejected edits recover.
- [x] Validate reduced/relocated provider publication, generated bindings and native
  includes end to end; update the lowering catalog from actual emitted C++.
- [x] On dev002, compare generated read-only/consuming/bulk versus presized append
  units. Record source bytes, compile/link wall and execution samples. Keep allocation
  instrumentation and compiler tracing out of clean timing runs; profile separately.
  Report preparation cost/reuse separately and investigate material regressions before
  adopting the implementation. No winner claim based on small noisy differences.

## Deferred work and research evidence

### Fixed bindings per compiler session

Generated PHP bindings are published completely before loading. `boot.php` loads
those classes once; their contracts stay fixed throughout the session. Application
source edits retain incremental AST/fact/emission reuse. Regenerating provider classes
requires a fresh PHP compiler process; no OPcache-based class replacement or live
provider-reload mechanism was added. Tests distinguish these boundaries explicitly.

### Implementation and verification evidence

- Parser, reference collection and reset use one `ast_collection_expression` with ordered
  expressions and compact conversion codes; no per-entry wrapper objects. Existing empty `[]` behavior
  is preserved. Only explicitly typed local initializers are admitted in this slice.
- A generated `sequence_initializer_type` capability owns construction selection.
  Its optional shared `list_initialization_binding` selects the element type, concrete factory,
  private heap guard and inline limit. Handwritten preparation queries this capability;
  it does not branch on vector/scalar/string names. Application types cannot gain it
  by declaring a method named `from_owned`.
- Runtime `from_list` copies read-only input; optional `from_owned` consumes private
  input. Both allocate destination element capacity once, before population. Returning
  a constructed vector and assigning it use existing value/ownership behavior. Direct
  populated-literal assignment syntax is not added.
- `compiler/tests/list_initialization.php`: 75 cpp/php/js fixtures, including source
  failures, checked conversions, type/count changes, declaration deletion/recovery,
  no-op reuse and retirement of literal calls. Incremental output matches fresh output;
  an unrelated unit reuses both preparation and emitted text.
- On dev002, all 75 fixtures passed with Clang 18 and GCC 13. Separate ASan/UBSan proofs
  passed under both compilers, with leak and stack-use-after-return detection enabled.
  Allocation sweeps cover read-only/consuming factories, partial heap-string input
  construction, old-destination preservation and both storage paths. Presizing and
  alignment are asserted directly, not inferred from timings.
- Fresh-session provider tests pass for consuming/read-only selection, renamed callable
  and guard, forced heap input, relocated includes, reduced publication, removal and
  restoration. Invalid contracts preserve the last complete export. Source-written
  factories remain unavailable as initialization providers.
- 18 focused CTests passed, including vector/copy/native regressions, provider exports,
  generator contracts and the lowering catalog. A final affected subset was rerun after
  consolidation. The catalog checks both generated columns for all 72 examples.
- Two stale existing test expectations were corrected: hash append is already supported,
  and collection method signatures now belong to generated type handlers rather than
  registry literals. Two older boxed catalog rows were synchronized with their existing
  order argument. No corresponding hash/boxed implementation change was made.
- The generator parity baseline was explicitly refreshed: target facts and acceptance
  outcomes remain unchanged (17 accepted, 37 rejected). New metadata changes input hashes;
  artifact/model changes are restricted to the five vector-bearing accepted cases.
- [dev002 report](benchmarks/flat_vector_initialization/report.md): 162 clean native
  timing trials, separate Clang phase profiles, PHP prepare/emit samples and all three
  flavors. These measurements belong to the pre-integration commit; no new timing claim is
  made for the merged application/nested paths. Execution
  and compile-time improvements are reported with the string/small-case tradeoffs.

Deferred: bulk transport for application/nested element types, hashes/keyed duplicate handling, mixed/dynamic, user-written factories/non-escape enforcement,
move-only or throwing-transfer elements, general moves/borrows, inference expansion,
spread input and production representation changes. Constructor spelling versus
static factory spelling is an internal binding choice, not a second semantic path.

The [dev002 comparison](benchmarks/collection_literals/report.md) supports presized
bulk input and avoiding redundant owned-element copies. It compares isolated models,
not implemented source features. It does not establish a universal constructor/static
factory winner or authorize consuming arbitrary user input. The first flat slice now uses the verified typed-array transport; the remaining
research candidates are not production implementations.

### Implementation footprint

Working-tree line comparison against HEAD, before this accounting section; paired
replacement lines count as changed. Excludes benchmark artifacts/scripts and the
mechanically refreshed parity JSON baseline. Includes new implementation/test files.

| Area | Added | Changed | Removed |
| --- | ---: | ---: | ---: |
| Compiler | 134 | 2 | 0 |
| Runtime/generator/build | 87 | 3 | 0 |
| Tests | 561 | 2 | 2 |
| Documentation | 301 | 8 | 0 |

The C++ emitter consumes the generated capability and the already converted entry
expressions; the native compiler handles array construction and partial cleanup:

```php
$count = count($elements);
$type = $binding->element->cpp_type();
$values = implode(', ', $elements);
if ($count <= $binding->inline_entries) {
    return "([&]() {\n    $type input[]{{$values}};\n"
        . "    return $binding->factory(input, $count);\n}())";
}
```

## Integrated contract — 2026-11-19

Remote populated-vector coverage and the optimized flat slice share one syntax and
preparation path. Existing nested vectors, records, shared owners, hash-valued elements
and yield-producer initialization remain supported. Metadata mode `ordered_elements`
grants the capability; optional bulk metadata selects private input transport for
eligible published types. Other admitted specializations use their generated append
binding. Removing bulk does not remove source capability. Source factories remain
read-only; a method name cannot grant consuming rights.

The AST uses one collection node and compact per-element conversion codes, with no
entry wrapper objects, extra preparation passes or dependency lists. Bulk bindings
are built with canonical generated type identities, never by the emitter. Bindings
remain fixed per session; provider edits are verified in fresh sessions.

Evaluation guarantees cover each expression and conversion in source order, single
evaluation, suppression after evaluation/conversion failure, and cleanup. Internal
allocation/transfer timing is unspecified; we do not require an append between source
expressions. Both implementations preserve value copies and shared identity.

Integration verification — completed:
- [x] Both source suites and unchanged flat catalog output (72 examples, both columns).
- [x] 75 flat fixtures per compiler on Clang/GCC; allocation/presizing/lifetime proofs
  passed with ASan/UBSan/LeakSanitizer on both. Leak checks ran outside the sandbox
  because its ptrace restriction prevents LeakSanitizer execution.
- [x] Nested/application incremental edits, deletion/recovery and fresh equivalence:
  five successful revisions plus two failure probes per flavor compiled and ran on
  both Clang/GCC. Additional nested/application and partial-cleanup sanitizer checks
  passed for cpp/php/js (generated storage instrumented, prebuilt runtime reused).
- [x] Inner bulk plus outer append in the same expression, including yield producers.
- [x] Provider bulk/read-only/append/disabled/restored transitions, relocation and reduction.
- [x] Generator contracts and reviewed version-5 parity export: unchanged 17 accepted /
  37 rejected cases and target facts. Remote bumped the export format to version 5;
  the stale version-4 assertion and baseline were explicitly updated.
- [x] Initial 15-test CTest selection: 14 passed; stale parity expectation repaired.
  Final affected selection: 8/8 passed, including parity, input contracts, concrete
  recipes, vector publication, source checks, style and catalog.

### Integrated footprint relative to fetched remote

Includes the local bulk implementation plus integration changes; excludes benchmark
artifacts/scripts and the parity JSON baseline. Paired replacement lines count as
changed; counts precede this accounting section.

| Area | Added | Changed | Removed |
| --- | ---: | ---: | ---: |
| Compiler | 49 | 4 | 0 |
| Runtime/generator/build | 101 | 6 | 2 |
| Tests | 585 | 5 | 0 |
| Documentation | 385 | 14 | 0 |

The shared capability is generated; one nullable binding selects bulk versus append
without a handwritten element-family test:

```php
interface sequence_initializer_type extends indexed_collection_type
{
    /** Null selects ordered append; a binding selects private bulk transport. */
    public function list_initialization(): ?list_initialization_binding;
    public function write_type(): type_definition;
    public function lower_cpp_sequence_append(ast_expression $element, int $conversion /** uint16 */, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string;
}
```
