# Data-model relationship audit

Doc Status: planning

Incremental migration update (2026-09-28): the candidate-replacement observations
below are historical. Combined `sync()` now calls the retained tokenizer/parser
phases; `publish_update`, `replace_collection` and `Declaration_Changes` were removed.
Deletion cleanup and dependency notification belong to shared preparation. See
[the current lifecycle](../lifecycle/incremental.md).


Review date: 2026-09-27

## Purpose

This note records places where the current compiler reconstructs an existing
relationship indirectly instead of representing that relationship at its truthful
owner. Typical symptoms include recovering an occurrence through a token index,
rescanning retained roots by path, or rebuilding structural identity from syntax.

This is an architectural review, not a semantic specification. Sections 1–5 now
record agreed implementation directions; the remaining directions are proposals
requiring review at their owning layers. The user authorized implementation of the
complete agreed slice, including native compiler validation, commit and push.
Sections 1–5 are implemented. Section 6 remains parked debt. Final validation
and publication evidence is recorded below.

## Classification rule

An index is not automatically a model defect. This review distinguishes:

- relationship recovery: an object already has one exact related object, but a
  consumer reconstructs that relation from a token, path, name or parallel list;
- valid indexing: a collection supports lookup, ordering, sparse membership,
  diagnostics or source provenance without pretending to be object identity;
- transient joins: a phase-local map relates independently owned records without
  adding backend or worker state to retained frontend records;
- parked legacy debt: code retained for LLVM regressions that must eventually be
  replaced by the shared preparation model rather than repaired independently.

## Summary

| Priority | Symptom | Missing or unclear concept |
| --- | --- | --- |
| High | AST occurrences recovered through token indexes | Direct syntax-occurrence relationship |
| High | Source, module and published snapshot recovered through path scans | Stable module-owned source slot |
| Medium | Parallel model roots are manually joined and reordered | Canonical per-source stage state or explicit root-view ownership |
| Medium | Incremental declaration identity is rebuilt from names and AST ancestry | Explicit declaration matching identity/provenance |
| Low | Type lookup scans its owned store while other name families are indexed | Scope-owned type-name index |

The parked LLVM token/local-index maps form a separate legacy cluster. They are
evidence for consolidation, not an invitation to establish a second preparation
model.

## 1. Syntax occurrence recovered through a token index

### Evidence

`collected_file.entries` owns each `collected_name`, and every `collected_name`
already points to its occurrence `ast_node`. The active preparation path lacks the
reverse relationship. `File_Preparation` therefore constructs
`preparation_context.occurrences`, keyed by `collected_name.token_index`, and uses
the binding/reference node's token index to recover its occurrence.

Relevant code:

- [`04_analyze/prepare/file.php`](../../04_analyze/prepare/file.php)
- [`04_analyze/prepare/structures.php`](../../04_analyze/prepare/structures.php)
- [`04_analyze/collect/structures.php`](../../04_analyze/collect/structures.php)
- [`04_analyze/collect/collect.php`](../../04_analyze/collect/collect.php)

The current parser establishes this observed cardinality:

```text
ast_node       -> zero or one collected_name
collected_name -> exactly one ast_node
```

Bindings, declarations, references, calls and fields use one occurrence for their
own named token. Type names and other nested names have separate syntax nodes.

### Agreed implementation plan — 2026-09-27

Status: implemented with specialization-only occurrence fields and shared accessors.

```text
name-bearing AST specialization -> canonical collected_name
```

1. Store the non-owning occurrence reference only on specializations that need it.
   Do not add an occurrence field to the common `ast_node` header or to unnamed
   specializations. `collected_file.entries` remains the owner; retain the existing
   occurrence-to-node reference because current consumers use it.
2. Share the attachment/access methods through a small trait on participating
   specializations. Use `attach_occurrence(collected_name $entry): void` and
   `occurrence(): collected_name` (names may be refined locally). Attachment is
   once-only; required access before attachment fails explicitly. The common
   `node_structure` access contract rejects unsupported operations without adding
   storage or kind-dispatch chains to nonparticipating specializations.
3. In `Symbol_Collector::record()`, finish the occurrence's required fields and
   collection position, then attach that exact occurrence to its syntax
   specialization. Preserve the parser's current zero-or-one cardinality and
   occurrence categories; this does not change collection or name-resolution rules.
4. Give name-bearing identifier/type syntax an occurrence-capable specialization
   instead of the current empty payload where needed. Punctuation, comments and
   other unnamed syntax remain without occurrence storage. Review every current
   `record_name()` site so only participating specialization families gain this
   capability; preserve existing handling of constructed type syntax.
5. Remove `preparation_context.occurrences` and its token-index map construction.
   Binding/reference preparation obtains the occurrence directly. Explicit type
   preparation uses the type occurrence's normalized name and lexical scope instead
   of rereading its name token. Preserve current supported-type restrictions.
6. Keep this relationship for the parsed tree's lifetime. Preparation cleanup must
   not clear it. Reparsing creates new nodes and occurrences together; failed
   preparation must clear prepared facts while preserving collection relationships.
7. Prove attach-once behavior, required-access failure, all currently collected
   occurrence kinds, identity/ownership, repeated and failed preparation, and
   existing binding/reassignment/reference/explicit-type results. Run PHP lint,
   mandatory style checks and focused regressions; native compiler validation stays
   opt-in.

An occurrence identifies the name at a syntax location. Its resolved declaration
is a separate preparation result, such as
`prepared_variable_reference.declaration`; do not conflate these relationships.
Token indexes remain valid for spans, diagnostics, punctuation and literal spelling.

Scope boundary: no source-slot/publication refactor, persistent IDs, new language
semantics, or changes to parked LLVM preparation in this slice.

## 2. Source and module relationships recovered through paths

### Evidence

Paths are the external input to synchronization, but path comparison continues
after a source has conceptually been resolved:

- `Source_Publication::find_source()` scans all modules and files;
- synchronization independently checks module directory containment;
- `publish_update()` scans `Model::$syntax_files` by source path;
- publication scans module file stores again to find the replacement position;
- a newly reported path is appended to the first module whose root contains it;
- global-scope replacement filters declarations by recovering their source path.

Relevant code:

- [`compiler/publication.php`](../../compiler/publication.php)
- [`compiler/sync/sources.php`](../../compiler/sync/sources.php)
- [`compiler/scope_publication.php`](../../compiler/scope_publication.php)
- [`01_prepare_inputs/module.php`](../../01_prepare_inputs/module.php)

This makes the same logical relationship answerable in several inconsistent ways:
path equality, directory containment, module-list membership and parsed-file source
identity. Overlapping module roots are particularly unclear because containment can
identify more than one owner while publication ultimately selects one match.

### Agreed implementation direction — 2026-09-27

Status: implemented with stable module-owned source records, canonical roots,
normalized notifications and overlap rejection; stage ownership follows section 3.

1. Give each module a stable source record for each member path. Separate that
   persistent membership from replaceable file and parse snapshots. Conceptually,
   the record exposes its path, current published file and optional parsed result,
   with a non-owning module reference. Names and exact field layout remain subject
   to the stage-state discussion; do not introduce persistent numeric IDs.
2. Maintain a path-to-source index referencing those module-owned records. Resolve
   a notification once, then pass the source record and previous published state
   in the work item alongside its private candidate. Publication must use those
   established relationships instead of rescanning roots by path.
3. Establish module ownership once for a newly discovered source. Reject overlapping
   module roots, including duplicate roots, so discovery and later additions cannot
   silently select the first of several possible owners. Use a consistent path
   representation for root-overlap checks and source lookup; do not make first-match
   containment an ownership rule.
4. Build candidate results privately and publish into the known source record after
   success. Preserve the previous published state when a candidate fails. Deletion
   retains the stable source record and the existing tombstone evidence; it must not
   silently discard declaration provenance.
5. Coordinate this change with the canonical stage-state decision in section 3,
   rather than adding another parallel owner. Module loading, synchronization,
   publication, lifecycle reset and ordering are all affected ownership areas.
6. Preserve multiple nonoverlapping modules, nested discovery, deterministic order,
   additions/edits/deletions, failed-candidate isolation, unchanged-source identity
   and clean-versus-incremental agreement in focused tests.

### Agreed scope-publication follow-up — 2026-09-27

Replace path-based provenance filtering with exact previous-collection identity.
The intended entry is `replace_collection(scope $global, collected_file $previous)`;
method naming may be refined locally.

- Publication obtains the previous collection from the stable source record's
  published parsed result. A newly added source has no old contribution to remove.
- Remove superseded live entries whose `collection` is exactly the previous
  collection. Apply the same rule to source-defined types through their declaration.
- Preserve deleted entries as tombstones, built-in types and other sources' entries.
- Keep the existing scope traversal and index rebuilding for this pass. No separate
  publication registry is needed; scope wrappers preserve the ability to optimize
  the implementation later.
- Preserve deletion and failed-candidate behavior, and verify replacement across
  successive revisions, mixed live/deleted entries, built-ins and multiple sources.

This provenance change is agreed as part of the source/publication work. It does
not authorize changing the deletion policy or merging semantic preparation paths.

## 3. Parallel retained roots require manual joining

### Evidence

`Model` retains `tokens`, `syntax_files` and `collected_files` as separate root
collections. A `parsed_file` already directly references its token list and
collection. After updates, `Source_Publication::order_roots()` constructs a
temporary object-identity map and rebuilds all three collections so their order and
membership agree.

Relevant code:

- [`compiler/model.php`](../../compiler/model.php)
- [`03_parse/structures.php`](../../03_parse/structures.php)
- [`compiler/publication.php`](../../compiler/publication.php)
- [`architecture/MODEL.md`](../architecture/MODEL.md)

The temporary object hash is reasonable for the join it performs. The model pressure
is that the join and synchronized replacement are required at all. Multiple roots
appear to own or canonically expose different stages of the same per-source state.

### Agreed implementation direction — 2026-09-27

Status: implemented. Stage projections are temporary snapshots, not retained roots.
Implement together with the source-membership direction in section 2.

```text
module -> stable source record
            current file snapshot
            token result, when available
            parsed result, when available -> collected occurrences
```

1. Make the stable source record the canonical access point for its published
   frontend stages. Retain a token result independently of parsing to support
   standalone tokenization. Reach collection through the parsed result rather
   than adding another source-level collection field.
2. Enforce snapshot consistency: a published parsed result references the same
   token result exposed by its source record. Successful synchronized updates
   publish their candidate results together; failed candidates preserve the
   previous published results. This is per-source publication, not a new
   whole-program atomicity guarantee.
3. Preserve standalone tokenize/parse operations. A source may expose tokens with
   no parsed result. Lifecycle reset clears dependent stage results through the
   source record, while retaining the currently required invalidation behavior.
4. Remove independently maintained `Model::$tokens`, `Model::$syntax_files` and
   `Model::$collected_files` stores. Callers traverse module/source order and use
   source-stage accessors; where a worker needs a batch, build a temporary ordered
   list of references rather than retaining three synchronized parallel lists.
5. Remove the temporary identity join and root reconstruction in `order_roots()`
   once its callers obtain results from canonical source records. Preserve
   deterministic module/source order and partial-stage publication.
6. Keep preparation-completion records and backend outputs outside this ownership
   consolidation. Their redesign is not required to correct frontend source-stage
   relationships.
7. Verify standalone stages, reset dependencies, snapshot consistency, failed
   candidates, unchanged-source identity, ordering, deletion/tombstone handling
   and existing consumers, including parked LLVM regressions. Native compiler
   validation remains opt-in.

## 4. Incremental declaration identity is reconstructed from syntax

### Evidence

Reparsing correctly creates fresh AST and occurrence objects. To compare old and new
declarations, `Declaration_Changes` constructs a string key from:

- node kind;
- collected name;
- enclosing function and struct names recovered while walking AST parents;
- enclosing names read again through token indexes.

It recomputes those keys and declaration spellings during nested matching passes.

Relevant code:

- [`compiler/sync/declarations.php`](../../compiler/sync/declarations.php)

Some structural matching is unavoidable across fresh parse snapshots. The concern
is not the absence of object identity across versions; it is that declaration
matching identity and enclosing declaration provenance have no explicit model, so
the synchronization algorithm repeatedly reconstructs both from raw syntax.

### Agreed implementation direction — 2026-09-27

Status: implemented with invocation-local comparison records and grouped candidates.
Matching across fresh parse snapshots establishes correspondence for change
classification, not persistent object identity.

1. Keep matching policy and temporary comparison data with `Declaration_Changes`.
   Build one comparison record per relevant declaration in the old and new
   collections. It references the collected declaration and contains its structural
   matching key, declaration/signature spelling and body spelling, computed once.
2. Form candidate groups from declaration kind, normalized name and enclosing
   declaration context. Use the occurrence links agreed in section 1 for enclosing
   names rather than rereading name tokens. Token spans remain appropriate for
   signature/body spelling comparisons. Preserve current grouping semantics.
3. Group candidates by structural key. Match identical declarations first,
   preserving source order for identical duplicates. Match remaining candidates
   only when correspondence is unambiguous, as in the current two-pass policy.
4. Preserve addition/deletion, signature-change/body-change classification,
   duplicate handling, tombstones and unchanged-file identity. An ambiguous key
   must not become a claim of declaration identity.
5. Keep comparison records temporary for the comparison invocation. Do not attach
   synchronization-specific keys or permanent IDs to every retained declaration.
   If another process later needs an explicit enclosing-declaration relationship,
   review and introduce it at its shared owner rather than expanding this slice.
6. Verify duplicate/reordered candidates, ambiguity, signature/body changes,
   additions/deletions, repeated updates and clean-versus-incremental agreement.
   Run the applicable PHP/style regressions; native compiler validation stays opt-in.

## 5. Type names use a linear lookup store

### Evidence

`scope` indexes variables and functions by name but owns type definitions only in a
numeric `Storage<type_definition>`. `types_named()` linearly scans the store for
every lookup.

Relevant code:

- [`03_parse/scopes/structures.php`](../../03_parse/scopes/structures.php)

This is currently an intentional small-store compromise, not a correctness failure.
It becomes an awkward recovery scan as the number of built-in, runtime and source
types grows.

### Agreed collection direction and usage inventory — 2026-09-27

Use a shared `Key_Storage_List<T>` collection for string-keyed groups of records
where repeated keys are legitimate. Keep the representation behind its API so
memory/storage optimizations do not require consumer refactors. Implemented for
scope variables, functions, types and declaration comparison groups.

Contract agreed in discussion:

- Adding an existing key appends another entry; it neither replaces nor rejects.
- No object-identity deduplication: repeated additions of the same object also
  remain distinct insertions.
- Per-key results preserve insertion order within that key.
- Full enumeration preserves overall insertion order across keys.
- Keep buckets private; expose records without exposing mutable internal membership.
  The proposed API is `add(key, record)`, `named(key)` and `items()`; names and the
  exact snapshot/iteration return forms can follow existing collection conventions.
- This collection owns any internal ordering/index bookkeeping. Consumers should
  not maintain a parallel storage list solely to recover insertion order.

Inventory of current and agreed upcoming my-try consumers:

| Consumer | Current shape | Planned use |
| --- | --- | --- |
| `scope.variables` | `hash<vector<collected_name>>` | Repeated variable names retained as ordered candidates. |
| `scope.functions` | `hash<vector<collected_name>>` | Repeated function names retained as ordered candidates. |
| `scope.types` | `Storage<type_definition>` with linear name lookup | Sole type collection with keyed lookup and ordered full enumeration; no extra scope-level type index/store pair. |
| Declaration comparison groups (section 4) | Planned temporary grouping by structural key | Use `Key_Storage_List<comparison_record>` for old/new candidate groups when implementing matching; the concrete record name is not fixed here. |

Scope registration/replacement owns index maintenance through existing wrappers.
Names/keys stay stable while registered. Definitions and occurrences remain the
same shared records; changing liveness does not change membership automatically.
Keep tombstones observable and let existing lookup workers select live candidates.
Shared `scope` adoption also covers file, global, language/runtime and transient
preparation scopes without introducing separate special-purpose collections.

The active-code inventory found no additional existing duplicate-key object stores
outside the scope name families. Do not replace unique-key maps (source paths,
template-parameter slots, C++ header sets), position-based lists, occurrence-category
indexes, or identity maps with this collection. The parked LLVM unique-key instance,
struct, field and external-function registries keep their current contracts; the
LLVM index-map cluster remains deferred.

Add the needed PHP/converter/native collection binding support and collection tests
alongside this wrapper, using existing collection ownership and portability paths.
Test repeated keys, repeated object identity, overall/per-key order, missing keys,
encapsulated membership, scope replacement, duplicates and tombstone filtering.
Compiling the compiler itself to native remains opt-in.

## 6. Parked LLVM index-map cluster

Status: explicitly deferred debt (user decision, 2026-09-27). Do not resolve this
cluster in the upcoming implementation. Only mechanical adaptations needed to keep
existing LLVM regressions consuming the changed shared source model are in scope;
LLVM name/type/template preparation and its index-map design remain parked.

The parked LLVM preparation path contains the largest concentration of indirect
identity maps:

- declarations, references and source types keyed by token index;
- template slots keyed by type-use token index;
- prepared calls and resolved fields keyed by token index;
- locals and initialization state keyed by `collected_name.local_index`;
- transient declaration/file/prepared-record object maps.

Relevant code:

- [`05_backend/llvm/legacy_names.php`](../../05_backend/llvm/legacy_names.php)
- [`05_backend/llvm/legacy_templates.php`](../../05_backend/llvm/legacy_templates.php)
- [`05_backend/llvm/structures.php`](../../05_backend/llvm/structures.php)
- [`05_backend/llvm/prepare.php`](../../05_backend/llvm/prepare.php)
- [`05_backend/llvm/expressions.php`](../../05_backend/llvm/expressions.php)
- [`05_backend/llvm/statements.php`](../../05_backend/llvm/statements.php)
- [`05_backend/llvm/structs.php`](../../05_backend/llvm/structs.php)

These maps belong to the legacy LLVM preparation shape. Do not repair or generalize
them independently. If LLVM work resumes, first review it against the shared
`File_Preparation` and specialization-attached-facts model. LLVM should consume
shared semantic facts and retain only backend-specific lowering state.

Some transient LLVM object maps may remain appropriate after consolidation. A
phase-local map from a frontend declaration to a backend instance does not imply
that backend state belongs on the retained frontend record.

## Valid index and provenance uses

The following current uses are not evidence of a broken object relationship:

- token indexes and spans used for source spelling, diagnostics, punctuation and
  exact literal text;
- `collected_file` category lists containing stable positions into their owning
  `entries` store;
- AST child positions and token boundaries;
- work-queue positions used to validate membership and completion state;
- name-indexed overload/candidate pools in lexical scopes;
- transient object maps used to join independently owned records within one phase;
- generated local spelling based on a source position in the current single-body
  C++ slice.

Generated names derived from token positions must not quietly become the permanent
cross-file or externally visible symbol identity scheme. A shared naming owner will
be required as the C++ backend gains functions, modules and reusable artifacts.

## Agreed sequence (implemented except parked LLVM debt)

1. Add and enforce the zero-or-one syntax-occurrence backlink.
2. Remove the active preparation token-index occurrence map and prove binding,
   reassignment, reference and explicit-type behavior.
3. Review source/module/publication ownership as a separate planned refactor.
4. Decide whether parallel stage roots are canonical owners or derived views as part
   of that source-state review.
5. Revisit incremental declaration matching only with its duplicate/tombstone
   contract and focused incremental tests in scope.
6. Introduce `Key_Storage_List<T>` and apply it to scope variable/function/type
   families and the planned temporary declaration-matching groups.
7. Leave parked LLVM maps unchanged until LLVM is explicitly resumed and adapted to
   shared preparation.

## Regression requirements

For a syntax-occurrence relationship change, validation should cover:

- attach-once and zero-or-one cardinality;
- every currently collected occurrence kind;
- AST and collection ownership/lifetime invariants;
- active C++ preparation without a token-index occurrence map;
- failed preparation cleanup and repeated preparation;
- PHP lint, the mandatory style checker and focused PHP behavior tests.

For source/publication or incremental-identity changes, additionally cover:

- multiple modules and nested source paths;
- overlapping-module ownership rejection or an explicit ownership rule;
- additions, edits, deletions and failed candidates;
- unchanged-file identity and deterministic root order;
- duplicate declarations and tombstone retention;
- clean versus incremental agreement.

Compiling `compiler/my-try` itself to a native executable remains opt-in and must not
be run unless explicitly requested. Generated C++ sample checks remain available
when a future change affects C++ output.

## Implementation checkpoint — 2026-09-27

Sections 1–5 are implemented at their existing owners. Name-bearing specializations
link to canonical occurrences; source records own published stages; replacement
uses collection identity; declaration comparisons cache temporary evidence; scope
and comparison groups use Key_Storage_List. Shared traits, parked LLVM ownership,
and the required converter/native bindings are included in the same consolidation.

PHP lint/style and the complete PHP regression runner pass. Native compiler
conversion/build/execution passes 142 PHP/native cases plus integer and scalar S2S
proofs. See [native adaptations](../portability/native_adaptations.md) for the
candidate toolchain and advisory analysis limits. Section 6 is still debt; its index
maps were not consolidated. A tombstone guard preserves its native regressions.
