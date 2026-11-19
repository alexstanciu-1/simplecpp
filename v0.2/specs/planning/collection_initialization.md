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
  Its optional shared `initialization_transport` selects the element type, concrete factory,
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
$type = $binding->element;
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
    public function list_initialization(): ?initialization_transport;
    public function write_type(): type_definition;
    public function lower_cpp_sequence_append(ast_expression $element, int $conversion /** uint16 */, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string;
}
```

## First typed keyed local initialization slice — implemented

Implemented 2026-11-19. This section records the agreed scope and verification;
the top-level data initialization standard remains semantic authority.

### Scope and source spelling

Start with the four already published hash combinations: integer/string values with
integer/string keys (`hash<Value,Key>`). Admit explicitly typed local initializers
using bracket-keyed entries in all three flavors, with an optional trailing comma:

```php
$codes hash<int,string> = ["identifier" => 1, "integer" => 2,];
```

```cpp
hash<int,string> codes = ["identifier" => 1, "integer" => 2,];
```

```javascript
let codes: hash<int,string> = ["identifier" => 1, "integer" => 2,];
```

Preserve existing empty `[]` construction. This is a bounded implementation of the
standard's keyed protocol, not implicit PHP array construction. Use existing key
admission/equality and typed-destination value conversions. Do not coerce numeric-looking
string keys into integers or introduce another key normalization policy.

### Evaluation, duplicate rejection and ownership

Implemented evaluation contract for this slice:

1. Process entries in source order. Evaluate and convert the key once, then evaluate
   and convert its value once, before starting the next entry.
2. Key evaluation/conversion failure suppresses that value and all later entries.
   Value evaluation/conversion failure suppresses later entries. Keep already performed
   external effects; do not roll them back.
3. Materialize private typed entries, preserving duplicates, then invoke one published
   runtime factory. The factory rejects equal keys using the destination's existing
   key equality, including duplicates produced by calls or equivalent expressions.
   It must never silently overwrite an earlier value.
4. Duplicate validation occurs in the factory after input materialization. A duplicate
   is therefore not a promise to skip its value or later source expressions. Verify
   this explicitly; do not confuse it with key-conversion failure. Do not add constant
   duplicate folding or an extra compile-time duplicate map in this first slice.
5. On failure, destroy constructed input entries and partial destination storage.
   On success, publish only the complete hash. Source lvalues remain unchanged;
   consuming bindings may move only private copies/materialized temporaries. User
   factories remain read-only and are not enabled here.

Internal allocation timing remains unspecified, consistent with the integrated
sequence contract. Allocation failure may interrupt input or destination construction;
no stable trace of allocation points is promised. The receiver must enforce duplicate
rejection even when no compiler analysis can prove key equality. Existing ordinary
hash `set` retains replacement semantics; construction must not redefine that API.

### Metadata, presizing and lightweight owners

Extend the existing initialization family with a keyed capability, keeping positional
and keyed shapes explicit. Metadata/generated contracts own key/value identities,
factory names, provider includes, read-only/consuming eligibility, duplicate-rejection
policy and bounded private-input storage. Generate capability queries; handwritten
preparation must not contain a hash-name or scalar-family whitelist.

- **Syntax:** retain one ordered inventory of key/value expressions. Prefer a dedicated
  keyed syntax/entry shape so positional vectors do not gain unused key fields. An entry
  owns its key/value syntax and only the required prepared conversion facts; no parallel
  prepared tree, normalized-key cache or persistent duplicate index. Duplicate keys are
  valid input occurrences retained until runtime validation, not erroneous duplicate
  declarations to link through the symbol duplicate mechanism.
- **Preparation:** supply the explicit destination context; validate the keyed capability
  and prepare each key/value once using existing conversions and symbol watches. Retain
  no per-literal copy of the provider contract and add no preparation pass.
- **Lowering:** reuse the private typed-input lifetime/storage approach and generated
  argument conversions. Emit one factory call; do not emit a chain of replacing `set`
  calls, a temporary source hash, or public C++ container templates.
- **Runtime/generator:** own concrete entry/input transport and the duplicate-checking
  factory. Reuse destination key hashing/equality and storage lifecycle. If the existing
  runtime insertion helper replaces values, factor its bounded construction insertion
  path rather than duplicate a hash algorithm in the emitter.

Presize for the supplied entry count before filling. Cover entry storage, live-slot
bookkeeping and the hash index/buckets, accounting for load factor and physical-index
limits. Reserving entries alone is insufficient if insertion still grows/rehashes the
index. Verify zero capacity growth/rehash during successful filling. Do not promise one
allocation or exact public capacity; empty input requires no entry/index backing.
Reject size/byte arithmetic overflow before allocation. Private input uses an explicit
byte budget, generated native size assertions and aligned inline/owned-heap storage;
keys and values must both be counted. Reuse nonthrowing-transfer eligibility and retain
a read-only factory path. No public reserve API or general storage redesign is implied.

### Keyed verification checklist — completed

- [x] All four published key/value combinations in cpp/php/js on Clang and GCC:
      empty, one/many entries, trailing comma, integer boundaries, empty/heap strings,
      embedded NUL and numeric-looking string keys.
- [x] Duplicate literals, equal strings with different spellings, computed equal keys
      and hash collisions: equality rejects duplicates; collisions alone do not.
      Existing ordinary `set` still replaces. Failed construction exposes no result.
- [x] Trace distinct effectful key/value calls to prove key-before-value, entry order
      and single evaluation. Checked key/value failures suppress the required suffix.
      Duplicate detection follows completed materialization, including later effects.
- [x] Source lvalue independence, copied string bytes, destroyed source/input backing,
      partial construction cleanup and allocation-failure sweeps. Check both read-only
      and consuming factories; run ASan/UBSan and leak checks separately from timings.
- [x] Direct presizing instrumentation for entries, live slots and index: no growth or
      rehash during fill, including threshold boundaries. Exercise private inline/heap
      boundaries, alignment, zero input and overflow/size rejection.
- [x] Reject missing target context, positional/keyed mixtures, invalid keys/values,
      unsupported destinations and deferred nested/context forms with source diagnostics.
- [x] Source incremental edits to keys, values, entry count and destination type;
      referenced constant/function changes, deletion, repeated failure and recovery.
      Compare diagnostics and emitted artifacts with fresh compilation in every flavor;
      execute successful revisions on Clang/GCC. Retire obsolete provider includes/calls
      and preserve unrelated preparation, emission and native-object reuse.
- [x] Provider contracts stay fixed per session. Regenerate and start fresh sessions
      for callable/include relocation, reduced publication, consuming removal/restoration
      and keyed-capability removal/restoration. Validate native includes end to end;
      rejected metadata preserves the last complete export.
- [x] One preparation per expression and pure emission; existing sequence bulk/append,
      nested vectors, ordinary hash mutation and lookup suites remain green. Update both
      lowering-catalog columns from actual output.
- [x] Record emitted size and focused PHP preparation/emission plus native compile/link
      costs for representative tables. Use dev002 for comparative timing claims, with
      clean execution measurements separated from sanitizers/allocation instrumentation.

### Keyed implementation and verification evidence

The capability is generated from hash initialization metadata. Handwritten preparation
retains only each entry's value-conversion ID and uses the existing strict key contract.
The shared `initialization_transport` and private-input emitter now serve both positional
vectors and keyed hashes. Ordinary `set` and bulk construction share insertion ownership;
the known-new insertion helper takes rvalue references, avoiding an extra move in `set`.

Completed verification on 2026-11-19:

- 138 source/native fixtures across cpp/php/js passed on dev002 with Clang 18.1.3
  and GCC 13.3. Both compiler runs passed ASan/UBSan ownership proofs with leak and
  stack-use-after-return checking, including emitted heap-input allocation failures.
- Presizing proofs cover counts 1–4, 63–65, 255–257, 1024, 65,536 and 65,537. Integer construction
  performs five destination allocations, covering storage, entries, live slots,
  controls and buckets, with no insertion-time growth. Read-only and consuming factories
  use one-byte positions at 256 entries, two at 257 and 65,536, and four at 65,537. Empty construction allocates none.
- Fresh-session provider tests cover read-only fallback, renamed/relocated exports,
  reduced publication, capability removal/restoration and forced full-hash collisions.
  Rejected name conflicts preserve the last complete export. Tests exercise published
  methods, the private storage pointer, visible type names and members of the input guard.
- Retained-session tests compare fresh output after edits, errors, deletion and recovery.
  Real Ninja builds verify caller/unrelated native object reuse across all flavors on
  both compilers. With cross-unit declarations held constant, editing a key, adding an
  entry, switching key type and removing/restoring the initializer each rebuild exactly
  the caller object. Each edit matches fresh emitted C++ and runs on both compilers.
  Removing the final external function use can also change its declaring header and
  legitimately rebuild that owner. Removing/restoring keyed input retires/restores
  factory requirements.
- Final local CTest runs passed 12/12 checks: source/native/ownership/incremental,
  provider exports, ordinary hash operations/types, generator contracts/concrete
  templates/parity, style and catalog. Earlier focused vector and populated-initializer
  regression suites also passed. The catalog checks both C++ columns (73 examples).
- Frozen generator parity retains 17 accepted and 37 rejected cases, with unchanged
  artifact/model fingerprints; only generator-input hashes changed.

[dev002 report](benchmarks/keyed_initialization/report.md) records 72 clean timing
samples and 12 separate Clang phase profiles. It includes PHP preparation/emission,
native compile/link, staged wall and executable checks. This is an acceptance-cost
record, not evidence of a before/after speedup or per-operation execution throughput.
Measured runtime source hashes match the final local generated sources.

A representative preparation excerpt shows the single existing traversal and facts:

```php
foreach ($declaration->initializer->entries as $entry) {
    $this->value($entry->key, $name);
    $type->prepare_key($entry->key);
    $this->value($entry->value, $name);
    $entry->conversion = $type->write_type()->prepare_conversion($entry->value, conversion_context::typed_destination);
}
```

### Keyed implementation footprint

Initial implementation before review fixes, relative to `f8e371fa9`, including the previously drafted keyed plan and new files.
Changed lines pair removed/added lines within each edit; added/removed are the remainder.

| Area | Added | Changed | Removed |
| --- | ---: | ---: | ---: |
| Compiler | 107 | 9 | 0 |
| Tests | 528 | 3 | 0 |
| Documentation | 342 | 8 | 0 |
| Runtime/generator/build | 88 | 12 | 0 |

Excludes benchmark harness/results and mechanical parity-input hashes. The two shared
input-template renames preserve their bytes and are counted as moves, not additions.

### Explicit deferrals

Brace-keyed spelling, nested keyed literals, application/enum/shared-owner key or value
families, boxed mixed/dynamic, inferred targets, constants/static tables, property/default/
call/return/assignment destination contexts, integer-hash positional initialization,
user-defined factories and non-escape enforcement, move-only/throwing-transfer elements,
source borrows/moves, provider hot reload and compile-time duplicate evaluation.
The native tokenizer port is a separate milestone-2 slice, not part of this implementation.
