# Type storage and action descriptors
Doc Status: supporting

Status: implemented data foundation in the PHP prototype, now consumed by
[declared return-type resolution](return_type_resolution.md). `compile()` stops
before `build_native`. Broader type forms, executable actions and backend support
remain future work.

## Identity, representation and lists

[`Type_Store`](../../src/04_analyze/type_model/data/store.php) owns flat type,
representation and member datasets, plus private name and representation indexes.
Consumers use lookup methods. Type/representation IDs are one-based and belong
to a store lineage; independent stores' IDs are not interchangeable. Member
ranges use zero-based `first` and `count`; a contiguous range needs no per-member
`next`. Debug JSON exposes these datasets in row order (ID = row index + 1).

`reference_type(name, namespace)` reserves an identity without a declaration.
`declare_type` completes that pending identity, or creates a declared identity
if no reference exists. A second declaration fails even before representation
completion. `is_declared` distinguishes these cases. `set_representation` requires
a declaration and attaches an interned representation. Separate steps allow references
such as a structure containing a pointer to itself. Representation ID zero means
unresolved, never void. Accessing an unresolved representation fails explicitly.
Alias resolution, generic instances, removal and source-symbol associations are
later work. Source return annotations now resolve through the named provider
catalog; the store itself does not interpret syntax or infer type meanings.

Each named type row also holds a reference to its immutable provider definition.
`bind_definition` connects the declaration to that shared record, checking its
qualified identity and any existing representation. `definition_for_type(id)`
returns the meaning and declared shape directly; unbound definitions fail
explicitly. Integers require signedness; non-integer definitions omit it.
Representation assignment checks consistency with an attached definition.
Distinct signed/unsigned definitions can share a representation without sharing
meaning. This costs one reference per canonical row, not a definition copy per
AST annotation. Debug JSON includes the referenced definition in each type row;
serialization is a debug view, not the in-memory layout.

## Reuse on encounter

For a fully named type with no variable frontend arguments, the coordinator uses
`reference_type(name, namespace)` to retrieve or establish its ID. This is a
reference operation; explicit declaration registration retains its duplicate
checks. Scope/name interpretation must select the authoritative qualified name
before this lookup. An unknown source name must still be diagnosed by resolution;
creating a pending ID is not evidence that a definition exists.

`needs_representation(id)` selects missing representation work before computing
it. This is not a semantic, operation, layout or ABI readiness check. After a
declaration is registered and computation succeeds, `set_representation` attaches
the representation. Further encounters reuse the same ID and representation.
Repeated pending references share one ID, but do not establish a declaration. Primitive/parameter-free types
use the ordinary ID space; there is no name switch or reserved numeric range.
Scalar, pointer and array representation cache hits return before allocating
payloads; aggregate cache hits retain their existing member ranges.

Each store requires an immutable `type_context`: configuration, provider and
target keys. Keys identify the complete authoritative inputs (canonical content
or reliable content/version identities), not display names or timestamps. No
empty/default context is accepted. The session supplies manifest/catalog content keys and an explicit
language-value representation scope; no target layout is assumed.

[`Type_Cache::requires_rebuild`](../../src/04_analyze/resolve_types/utilities/type_cache.php)
reports a missing cache or changed configuration/provider/target keys without
changing control state. `Compiler_Session` uses that report to set `full_rebuild`
before frontend selection. After incremental admission fixes the decision,
`Type_Cache::prepare` starts an empty cache on full selection or clones the
compatible previous store, sharing immutable rows. This also covers a rebuild
requested by the admission gate. Selective preparation with an incompatible
cache is rejected. Context comparison uses key values, not object identity;
preparation never mutates the previous store or the coordinator's decision.

The empty cache selects all encountered types for recomputation through the same
future resolution path; there is no separate full-build resolver or per-row
marking sweep. A fresh cache starts a new ID lineage: numeric IDs may be reused,
so old typed bindings/results must all be rebuilt rather than interpreted in the
new cache. Previously returned results retain their previous store. No deleted
provider declarations survive in the fresh name index.

Within one candidate/context, `invalidate_definition(id)` preserves identity
and declaration state while clearing both its definition binding and representation.
It replaces the former representation-only invalidation hook so stale semantic
facts cannot remain attached to a recomputed shape. This storage hook is
not a dependency propagation engine or permission to reuse dependent results.
Individual source-defined type changes still need future explicit impact rules;
unsupported changes require full selection. Discarded-candidate IDs must never
leak into accepted results. The compiler queries cache validity before frontend
selection and prepares the candidate after admission, before type workers;
inspection stops before native building. See [native builds](native_executable.md)
for the optional executable path.

Signature workers read fixed snapshots and return definition requests. The
coordinator deduplicates those requests, establishes IDs and joins definitions
before the next reading phase. No worker mutates the global type table. This
store provides the lookup/pending/completion boundary; the signature phase owns
its concrete selection and join without a general lazy-provider framework. Parameterized instance caching remains deferred.

## Representation and snapshots

The [records](../../src/04_analyze/type_model/data/representations.php) use immutable
kind-specific payloads. Integer width has no assumed default. Floating format
determines width; equal-width formats remain distinct. Pointers retain their
referenced language type and address space, with no assumed pointer width.
Arrays retain element type and count, without duplicating a computed bit size.
Structures have ordered named fields; signatures have ordered parameter types
and a separate return type. Parameter names belong to declarations, not signature
representation identity. There is no parameter list allocation in a signature
row; fields and parameters use ranges in the shared member dataset.

Interning compares complete descriptor keys, preserving component order and type
identities. Equal representations reuse existing rows/ranges. Distinct named
types may share one representation. These are source-aware shape descriptors,
not final LLVM types: field names and referenced language identities remain
meaningful here even when lowered storage would be equal.

The coordinator builds or clones a candidate store. PHP arrays copy on write;
unchanged immutable rows and payloads remain shared. Redefining a type replaces
its row in the candidate without mutating an older snapshot. Workers will read
a fixed candidate, not concurrently allocate IDs or intern representations.
The signature phase executes serial work units; broader downstream incremental
admission remains unimplemented.
Selected layout preparation validates supported plain records before lowering;
nested records and illegal by-value cycle analysis remain future extensions. See
[structured values](structured_values_layout.md) for the current boundary.

## Actions and later boundaries

Every shared named value definition requires an explicit `lifetime_contract`.
The current catalog describes scalar `copy: value` and `cleanup: none`, with
explicit `null` only for void (no value). Missing contracts, unknown policies
and contracts on void are rejected. These facts are accessed by canonical type
ID, shared across values/signatures and exported with the bound definition.
They do not record an individual value's scope, ownership or cleanup location.
[Scalar lifetime analysis](lifetime_analysis.md) consumes these contracts;
[instruction lowering](lowering.md) implements direct scalar returns and
no-cleanup discards. [Runtime copy](runtime_copy_construction.md) and [cleanup](runtime_cleanup.md)
extend these shared contracts for managed inline values. Catalog edits
already invalidate the type context; invalid metadata cannot replace accepted
session outputs.

[`operation_contract`](../../src/04_analyze/type_model/data/definitions.php) records
an operation, ordered canonical operand-type IDs, result type, and an
`implementation_binding`. A binding identifies a provider entry and whether it
denotes a callable or native operation. These records are descriptions only.
Future catalog loading must validate referenced types and actual implementations;
constructing a record must never advertise unsupported execution as a capability.
Provider entry keys must be resolved to owned implementation contracts before
lowering, not used for helper-name dispatch in LLVM emission.

The [body-checking conversion resolver](body_checking.md) implements identity and
[same-family, same-signed integer widening](integer_conversions.md). General
operation catalogs, richer conversions and lifetime contracts remain later work.
Target memory layout and signature/ABI passing plans remain separate future
outputs. No layouts, offsets, alignments, passing modes or LLVM spellings are
guessed or persisted on these type records.

## Verification

[`type_storage.php`](../../tests/04_analyze/type_model/type_storage.php) checks distinct
identities sharing a representation, namespace lookup, floating formats of equal
width, empty lists, ordered fields/parameters, representation deduplication,
recursive pointer/array/signature composition, snapshot sharing and replacement,
invalid references, failure purity, action descriptors and JSON inspection.
These prove the storage contract, not semantic validity or native memory costs.
[`type_reuse.php`](../../tests/04_analyze/type_model/type_reuse.php) feeds real parsed return
annotations through test-owned definition suppliers: 400 occurrences share two
IDs and each definition is computed once. It also checks repeated warm lookups,
pending identity reuse, namespace isolation, candidate invalidation/redefinition,
failure isolation and preserved duplicate declaration errors. Suppliers are test
fixtures; the integrated signature phase is verified separately.
[`type_context.php`](../../tests/04_analyze/type_model/type_context.php) verifies pending
reference completion, rejection of undeclared representation attachment, duplicate
declarations before representation completion, independent context-key changes,
initial/full/warm preparation, immutable context, candidate isolation and exports.
The [porting gate](../../reference/source-repository/working_rules.md) still applies before any Simple C++ port;
kind-specific PHP objects should become inline union payloads there.

[`type_definitions.php`](../../tests/04_analyze/type_model/type_definitions.php) proves direct
semantic lookup by ID, equal-width signed/unsigned representation sharing,
unchanged record reuse, coherent invalidation, rejected conflicting definitions,
and prior-snapshot preservation. Its real compiler runs change only catalog
signedness with unchanged source/width: full selection refreshes signatures and
exports the new meaning while the old result remains intact.
