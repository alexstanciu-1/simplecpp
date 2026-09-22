# Source structs and shared structural contracts
Doc Status: supporting

Status: implemented source structs and the first provider-record import, sharing the
same definition, location, value-copy and target-layout paths. Provider import currently
accepts complete plain native records with public integer fields, explicit zero/value-copy/
no-cleanup contracts and verified compatible layout. Opaque objects with exposed fields
and aggregate ABI passing remain separate follow-ups. Nested source fields and
metadata-eligible managed runtime fields now support automatic default construction,
copy construction and destruction; see [lifecycle composition](lifecycle_contracts.md#first-compiler-implementation-bounded-composition).

## Supported surface and contracts

```php
struct sample {
    public uint8 $tag;
    public int32 $count;
}
$value sample = new sample();
$copy sample = $value;
$other sample;
$other = $copy;
return $other->count;
```

`new sample()` constructs a struct **by value**. Class construction will produce
`shared_p`; classes are outside this slice. A typed struct local without an
initializer uses the same default construction. Scalar fields start at zero.
For plain records, same-type initialization and assignment copy values into independent storage;
field reads/writes use the containing local's lifetime. Distinct named structs
remain different types even when their field shapes are shared.

Public field eligibility is an explicit `struct_field` capability in language type
metadata, currently enabled for the available fixed-width `uint8`, `int32` and
`uint32` definitions. Consumers do not enumerate those names. Plain `int` is not
eligible under the current Simple C++ struct contract. Existing widening rules
apply to field writes; this feature does not add narrowing or literal conversions.

Zero-argument custom constructor/destructor bodies now compose with field lifecycle;
see the [prototype extension](lifecycle_contracts.md#custom-source-lifecycle-bodies).
Managed assignment, empty structs, field initializers, constructor arguments,
inheritance, packed/overlapping fields, temporary-root field access and
owned record function parameters/results remain unsupported with explicit diagnostics.
[Call-scoped record reference parameters](source_record_borrowing.md) and
[fixed-array fields/public source methods](fixed_array_list_plan.md) are implemented.
Layout support alone never authorizes aggregate ABI passing.

## Owners and common flow

Paths below are relative to `src/`.

| Owner | Contract and behavior |
|---|---|
| `03_parse/handlers/declarations.php`, `expressions.php`, `utilities/syntax_access.php` | Parse structs, ordered fields, construction and field access. Shared struct/field syntax views supply validated child roles to collection and definition workers/joins. |
| `04_analyze/type_model/data/records.php`; `resolve_types/records.php`, `data/records.php` | The shared model owns normalized `record_declaration`/`field_declaration` inputs. Resolution owns selected source/provider tasks and private results, without allocating IDs in workers. |
| `04_analyze/resolve_types/record_join.php`, `record_definitions.php` | `Record_Join` accepts selected source/provider results with current identity and complete contracts. `Record_Definitions::materialize()` owns common canonical materialization. The explicit layout policy distinguishes target-computed source layout from layout constrained by native measurements. |
| `04_analyze/resolve_types/definition_view.php` | `Definition_View` joins authoritative provider definitions with accepted source definitions. Annotation and construction lookup share this view. |
| `04_analyze/type_model/data/store.php` | `Type_Store` owns nominal IDs and flat ordered member ranges. A field is identified by its containing type and ordinal; exact names are resolved during checking. |
| `04_analyze/check_bodies/data/structures.php`, `handlers/` | `place` holds a root local ID and ordered field/index projections. Whole-local reads/writes use the same location model. `record_default` is a semantic default value; `value_copy` covers scalar and record copying. No LLVM spelling is retained in checked values. |
| `04_analyze/analyze_lifetimes/handlers/` | Validate the initialized root and projection. Fields do not create independent cleanup obligations. Existing opaque construction, borrowing, copying and destruction retain their contracts. |
| `05_generate_code/prepare_backend/layout.php`, `layout_join.php`, `data/layout.php` | Select definition/target tasks; private workers ask configured Clang/LLVM to fold size, alignment and field-offset constants. The join accepts exact selected results. Accepted layouts retain field/type/target contracts, not tool processes or task envelopes. |
| `05_generate_code/lower/handlers/`, `emit_llvm/body.php` | `storage_address` carries a root slot and optional field ordinal. Ordinary loads/stores consume the accepted layout. LLVM aggregate values support independent copies; field addresses use target-measured offsets. |

Definition preparation joins before annotation workers run. Layout preparation joins
before lowering/emission. Workers read fixed inputs and return private results; the
serial coordinator executes the same work units intended for future MT scheduling.

`src-runtime-preparation/records.php` validates the `value_record` exposure contract,
emits Clang type/trait/offset checks, and verifies the complete ordered public field
list against Clang's AST. Native field defaults, bases, bit fields, mismatched types,
and nontrivial construction/copy/cleanup are rejected. JSON can rename fields and
restrict writes without consumer-side special cases.

`load_runtime/handlers/records.php` translates verified measurements into normalized
record declarations held by `Type_Catalog`. The source/provider work selection and
join are shared; no source AST is fabricated. Both producers enter `Record_Definitions`.
Checking, lifetime analysis, lowering and field emission have no provider-specific path.

`Layout_Join` compares the generated record layout with the imported native target,
size, alignment and every offset. Mismatches fail before lowering; native over-alignment
is currently rejected rather than silently approximated. This deliberately bounded
compatibility contract leaves opaque layouts and field-accessor calls for later work.
Visible fields alone still do not authorize whole-object construction or ABI passing.

## Retention and scaling

Field rows live in the canonical type store; checked/lowered locations contain two
integers, with no copied AST, field names or physical offsets per expression. Layout
facts are retained once per nominal definition/target. Current name lookup scans the
small ordered field range; indexing and batching layout probes can be measured later.

Body edits reuse definitions and accepted layouts, replacing the affected body/module.
Definition/target edits retain the existing full-selection fallback; this slice adds
no new incremental edit category, actual threading or rollback machinery. Allocated
IDs and exact keys preserve entity identity without assuming hash uniqueness.

## Evidence and limits

`tests/integration/source_structs.php` proves real native execution with two
field types, different field orders, zero/default and explicit construction, independent
copies, whole-value assignment, reads/writes and early exits. A native Clang oracle
checks size/alignment/offsets using the actual Simple C++ integer wrappers, including padding. Reversed definition/layout/body workers,
rejected duplicate/stale/incomplete outputs and retained-input purity exercise the
preparation protocols. A full build followed by one body increment changes the result
from 51 to 68 while retaining definitions, layouts and unchanged modules/native objects.
A separate full-build/one-attempt proof changes field order and verifies full-selection
fallback, new layout, preserved nominal IDs and stale-context rejection. Debug exports
expose resolved places and accepted physical layout facts.

An additional configured Simple C++ probe confirmed zero-initialized typed struct locals
and independent copies (`0/42`). That toolchain currently misroutes `new sample()`
through its class/shared-pointer construction helper; the compiler follows the user's
explicit language contract that struct `new` is by value. No workaround was added to
the upstream compiler and no class/shared-pointer support is claimed here.

The complete prototype suite guards the coordinated migration's existing scalar and
runtime behavior. These correctness checks are not native performance benchmarks.

`tests/integration/provider_records.php` exercises two native records exported
from JSON, a source record with the same field shape, renamed/read-only fields, default
construction, value copies/assignment and actual native execution. It checks prepared
artifact reuse, source/provider worker joins, purity, one body increment, and a separate
provider-header/definition replacement through the existing full-selection fallback.
Missing native fields, wrong scalar claims, in-class defaults, unsupported aggregate
results and incompatible native alignment are rejected. These tests exercise the actual
preparation tool and package adapter, not a hand-constructed metadata substitute.

## Consolidated plain-record boundary

The [syntax proof](../../tests/03_parse/struct_parsing.php) covers ordered
field roles, declaration-change comparison, malformed-tree rejection and retained
syntax purity. Source/provider declaration collisions report a source-name span;
malformed or changed private field contracts fail before record materialization.

The native source proof also exercises whole-record self-assignment and field reads
and writes in a loop. The provider proof verifies that a read-only field restriction
applies to field writes while the separately authorized whole-value copy/assignment
remains available. These use the existing common checking, lifetime and layout paths.

The completed scope is plain source/provider records with public eligible integer
fields and zero/value-copy/no-cleanup semantics, plus call-scoped const borrowing of
provider-record locals into metadata-defined runtime calls. The later composition
slice adds nested/managed source fields and their owned temporaries; aggregate
value ABI and temporary-root field borrowing remain deferred. Linear field
lookup and per-record layout probes are tracked in the [performance watchlist](performance_watchlist.md).

## Call-scoped const record borrowing

The [native proof](../../tests/integration/runtime_record_borrows.php)
prepares two record types and ordinary free functions from local JSON. It checks
zero/initialized fields, two parameters aliasing the same local, independent copied
locals, writes after a call ends and nested calls. Lowering inspection confirms each
borrow uses an existing root slot: no temporary record slot or aggregate load is
introduced. Ordinary record copying still performs a value load.

`runtime_callable::signature` stores semantic parameters/results with
`named_type_reference` for all ordinary imported signature types. Its separate `abi`
association contains physical passing details.
Signature workers resolve those exact names after record acceptance against one
fixed `Definition_View`. Their join rejects mismatched definition identities before
materializing any signatures. Imported signatures retain no foreign field ranges.

`Body_Worker::argument_value()` owns value/address selection for ordinary and
language-bound calls. It replaces a private record-read row with a local borrow only
when the selected parameter requests borrowing. Source parameters now also support
[explicit const/mutable references](source_record_borrowing.md). Lifetime, lowering and emission
then use their common local-borrow flow. Access ends at the consuming call; the local
remains initialized and can be written or copied afterward. Existing opaque temporary
construction/borrowing/cleanup semantics are unchanged.

The proof checks reversed worker completion, rejected signature output, retained
snapshot purity and one body edit with unchanged signature/layout reuse. Wrong nominal
types, temporary records, field scalars, expired locals, mutable/retained runtime borrowing and
aggregate value parameters are rejected. Source-defined records with identical fields
remain distinct types; exposing such a type to a native function would require its
own explicit interoperability contract.

This completes the bounded record composition slice. Template families and their
ownership contracts remain a separate design discussion.

## Pre-template composition consolidation

The [record/string proof](../../tests/integration/runtime_record_strings.php)
prepares a provider function that borrows a record and returns an owned string. It
checks the hidden result-storage ABI position, native output with independent record
and string copies, source/provider field use, a loop and an early return. One body
replacement changes output while preserving backend contracts and the prior snapshot.
Existing lifecycle proofs remain responsible for precise destruction-order checks.

The same fixture checks that unused source functions with owned record or opaque-inline
parameters/results fail at their exact annotations, before body checking or LLVM
preparation. These unsupported source boundaries remain distinct from implemented
runtime owned-result contracts and explicit source record-reference parameters.
