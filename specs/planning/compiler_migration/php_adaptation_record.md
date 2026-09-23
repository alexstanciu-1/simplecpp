# PHP adaptation record and optimization follow-up
Doc Status: planning

## Global function convention and resumed expressions

At the user's request, active portable sources and maintained proof inputs no longer
carry per-file function imports. Plain global names expose non-PHP facilities; q_
marks the eight mapped PHP builtin names. One generated static facade preserves
reference parameters/defaults and delegates to existing internal implementations.
The fixed catalog owns naming, not host function discovery. Converter mapping and
prologue validation now own the new authoring surface; old imports are rejected or
explicitly removed by the migration command. Frozen reference/evidence and
src-runtime-preparation stay untouched. Compiler/native proofs and the UTF-8 suite
validate this convention; generated-input stale spellings were corrected in tests.

Expression work then resumed: an iterative continuation owner parses value/type
expressions on the compact arena. Constructor-supplied named dependencies replace
unsupported uninitialized named fields. Native attempt 1 exposed C++ keyword locals
operator/template; semantic local names clear attempt 2. No converter grammar growth
was needed for the expression algorithm. See [global functions](../../portability/global_functions.md)
and [expression parser](../../portability/expression_parser.md).


## First parser slice: syntax arena and angle matching (2026-09-22)

Three files provide real parser-owned storage/disambiguation dependencies, not grammar
parsing yet. Compact rows retain the original kind codes and byte spans. Explicit
copy/read/replace methods prevent PHP object identity from defining native mutation.
A last-child field replaces reference-parameter bookkeeping for constant-time append;
measure its memory cost in a later optimization pass. Internal callers own unattached
subtree/cycle discipline; this is not an arbitrary-graph validation API.

The retained 5,000-stream unit is adapted only for loading/vocabulary and exercises
the rewrite against the frozen original. Another 414 outcomes pass PHP/native. Native
build 1 succeeded but behavioral comparison exposed uint32 versus int strict tag
comparisons; normalize to int at classification, keeping compact stored tags. Build 2
passes. This lesson updates author guidance, without expanding converter semantics.
Final cumulative PHP/tool checks accompany the focused native proof; unchanged prior
stages are not natively rebuilt just to repeat evidence. Grammar units remain pending.
See [contract](../../portability/parser_foundation.md) and
[timing/cycles](results/parser-foundation-01/README.md).


## Rewrite stage 4: tokenizer (2026-09-22)

Four files retain lexical vocabulary and algorithms while replacing per-token objects
with explicit uint32 scalar rows. Source text is retained once; lexemes remain spans.
Portable byte loops replace PHP span builtins. File-scope integer constants preserve
kind numbers; a total debug encoder provides names without enum reflection. Lexical
errors return invalid buffers with path/span/reason and no partial rows, rather than
reintroducing the prototype's custom exception/file-ID framework. Batch validity must
be checked before parsing. No incremental coordinator or selective identity reuse yet.

The retained tokenizer unit body executes through a host-only shim: 41 captured scans
are replayed against the rewrite. Another 261 oracle cases and two batch cases bring
the proof to 304 outcomes. Old session-dependent incremental tests remain pending.
Native attempt 1 found Token_Buffer/runtime-name collision and return-path analysis;
Lexical_Buffer and an explicit debug fallback clear attempt 2. No converter changes.
Three earlier checker-only syntax adaptations are detailed in the stage contract.

The user's additional boundary is recorded: src-runtime-preparation stays PHP as-is
for now. Optimize keyword classification/byte scanning only after measuring; retain
the compact-row ownership model. See [contract](../../portability/tokenizer.md) and
[evidence/cycles](results/tokenizer-rewrite-01/README.md).


## Rewrite stage 3: verified source reads (2026-09-22)

Three production files turn discovered file observations into owned byte buffers.
The compiler uses a shared snapshot adapter; lstat/fstat/open/read/finally details
remain in the host framework and the target-owned native API. Sequential batch reads
publish only a complete result, preserving source order and entry position without
reintroducing Step lifecycle, stable IDs or speculative incremental scheduling.

PHP approximates the read protocol but cannot offer native open flags. Its raced-in
FIFO/open behavior is explicitly outside parity; native handles no-follow/nonblocking
open and descriptor cleanup. Same-size/whole-second-mtime edits remain undetectable
and are demonstrated by an outcome test rather than described as safe snapshots.
Buffers are fresh string-owning objects; compact record layout is not claimed.
One PHP-only correction placed the new include inside the existing bracketed namespace.
See [contract](../../portability/verified_source_reads.md) and
[measured attempts](results/snapshot-rewrite-01/README.md).


## Rewrite stage 2: source paths and discovery (2026-09-22)

Four source files preserve prototype selection rules in a synchronous discovery API.
A typed listing owns canonical roots, ordered file observations and an entry position.
There is no inherited Step lifecycle or speculative ID/change-state machinery. These
belong to later incremental ownership work; fresh scans provide its clean baseline.
Keep bytes intact in POSIX filenames; the new host-fact adapter controls Windows-only
normalization. Metadata is an observation, not a verified content snapshot.

Native attempt 1 stopped in STAN on the JSON wrapper/runtime case-only name collision.
A distinctive `Json_View` wrapper name cleared attempt 2; no target or generated code
was patched. The shared manifest proof is included in final verification. One earlier
checker-only correction moved a negative entry sentinel from a rejected property
default into explicit owner initialization. No converter grammar expansion was needed.

Optimization follow-ups: directory queue retention and file-row layout, driven by
measured project size. Do not equate listing indices with future stable IDs. See
[stage contract](../../portability/source_discovery.md) and
[elapsed time plus native cycle log](results/discovery-rewrite-01/README.md).


## Rewrite stage 1: project-manifest reading (2026-09-22)

Three active files now implement synchronous reading, schema validation and an owned
result. The prototype's Step lifecycle, shared publication and Step_Result coupling
are deliberately deferred: no current pipeline owner needs them. Keep meaningful
input/result/rejection behavior, not the prototype's incidental internal interface.
The result uses explicit single-source state and typed string vectors. Original JSON
content is retained for future change detection; declared source paths are validated
but not scanned here.

A small shared JSON schema view and checked filesystem adapters supply the real
boundary. Native #240 candidate `9b4b33f3` preserves object/list identity; PHP uses
object-mode decoding. Full number/diagnostic parity is not claimed. A first native
attempt exposed a STAN return-path advisory when parsing returned inside try/catch;
removing unnecessary catch/rethrow context lets the reader return normally while
preserving schema/I/O rejection outcomes. Caller-owned diagnostics can add context.

Optimization follow-up: measure schema-view allocations only if larger metadata
loads justify it; do not force one string/vector-owning manifest into a scalar record.
Avoid inheriting incremental lifecycle machinery before the new pipeline needs it.
All 35 stage outcomes and combined portability checks pass. See
[stage contract](../../portability/project_manifest_reading.md) and
[timings/evidence](results/manifest-rewrite-01/). Native checks were used for the new
adapter boundary and final stage checkpoint, not each PHP edit.


Checkpoint: 2026-09-22; 36 production files proved, latest cumulative evidence
`results/preparation-symbols-01`. This records broad source changes and their reasons,
not a complete diff or a claim of whole-compiler portability. Slice documents retain
exact contracts, test cases and evidence. Future slices must extend this record when
they introduce a materially different representation, algorithm shape or workaround.

## How to maintain this record

For each material adaptation record the original shape, new owner/shape, reason,
behavior that must survive, evidence link and any optimization question. Distinguish
necessary portable design from a workaround for the tested converter/native target.
Do not present an optimization hypothesis as a measured improvement. Keep accepted
but unimplemented changes separate from completed changes. Frozen originals under
tests are behavioral oracles, not another evolving compiler implementation.

## Completed adaptations

| Area / original PHP shape | Portable PHP shape and reason | Future optimization questions and invariants |
| --- | --- | --- |
| Implicit array element types and PHP-wide local scope | Explicit vector/hash/named type comments, typed method boundaries, enclosing-block initialization, uniform managed imports. The converter can emit locally visible types without resolving the program. | Measure representation and copy costs before replacing containers. Preserve dense membership, key types, null/false distinctions and initialization order. Annotations themselves are not optimizations. |
| Record construction and implicit copying | Named records; explicit shallow copy operations for source rows and snapshot acknowledgment; File_Frontend binds token/tree inputs in its constructor. Initialization and sharing are visible rather than relying on clone or uninitialized managed fields. | Consider allocation reduction and packed/indexed records only after measuring. Preserve shared buffer/tree identity, independent list membership, old snapshots and validation timing. PHP readonly is not native deep immutability. |
| Source_Set membership and lookup | Typed vectors plus ID/row and path/ID hashes; candidate indexes are validated before adoption. Logical IDs remain distinct from row positions; tombstones remain addressable. | Profile index rebuilding, snapshot copying and sparse rows. Do not replace stable logical IDs with row offsets without an explicit model change. |
| Generic cross-stage Join callable contract | User-approved marker interface; each concrete join owns its typed input/output batch. Avoids a fabricated common payload model. | Concrete dispatch is intentional; revisit only for a demonstrated scheduling/polymorphism requirement. Keep batch provenance, completion order versus acceptance order, duplicate rejection and retained identities. |
| Worker/coordinator selection mixed with larger phase dependencies | Pure selection and path-spelling owners are called by existing coordinators; coherent selection components can be proved separately. | Preserve one implementation of each policy. Assess repeated selection scans when full pipeline timings exist; splitting files is not a speedup. |
| Binary_Syntax dynamic nested scratch stacks and match expressions | Angle_Scope records with integer stacks, logical sizes and reusable storage; explicit fixed operator comparisons. Keeps existing scoped pairing and reset algorithm within supported source forms. | Measure object overhead and high-water memory retention versus contiguous scratch storage. Preserve pair insertion order, unmatched openings and scope/reset behavior; no speedup claimed. |
| Syntax_Comparer heterogeneous stack triples | Typed Comparison_Frame rows in a reusable Comparison_Stack. Copy popped scalar fields before reuse; explicit iterative loop and byte slices. | Profile frame allocation, retained capacity and traversal locality. Preserve sibling-before-child scheduling, root-sibling exclusion and error order; no speedup claimed. |
| Implicit object/array JSON export | Explicit owned schemas in Source_Set, File_Frontend and Project_Manifest; shared scpp JSON string quoting. Keeps exact fields, order, optional values and wrapped errors without reflection. | The string encoder's repeated code-point indexing may be quadratic; measure long-string export and consider linear traversal/native quoting. Avoid repeated concatenation if profiling warrants it. Keep escaping, malformed UTF-8 rejection, null versus empty and wire bytes exact. |
| PHP byte/string convenience operations | Explicit byte slicing/classification and checked byte construction; Byte_Literals retains its decoding algorithm. Native-project roots and runtime-preparation symbol spelling use explicit scanners. | Profile per-byte helper calls and concatenation; consider builder/bulk primitives where useful. Source offsets and binary identity use bytes, not Unicode character counts. Preserve non-UTF-8 bytes where the boundary permits them. |
| Runtime-preparation symbol component arrays | Explicit vector<string> contract plus dense-list/exact-string validation in PHP; native helper accepts an already typed vector. One component encoder is reused by name and append. | The native validation helper is empty because the representation supplies those properties. This does not establish arbitrary PHP/native coercion parity. Preserve separators, escaping, empty components and rejection of an empty component list. |
| Direct PHP filesystem scanning | Small scpp filesystem adapters with fresh metadata observations, explicit false extraction and native result adaptation. Source_Scanner retains sorting, selection order, duplicates, symlink rejection and task identity. | Metadata calls are observable correctness checks; profile before batching/caching. Scanner parity does not establish the stronger snapshot-read protocol. |
| Target-specific expression/control-flow limitations | Explicit null rejection before dereference, initialized result returned after try/catch where STAN required it, schema methods retained on owners where passing this to a static helper failed, local vocabulary helpers for enum names. | These are candidates for simplification after a tested target fixes the exact form. Keep error order and shared-reference semantics; do not treat historical failures as permanent language restrictions. |

## Evidence routes

- Records, ownership and indexes: [Source_Set](../../portability/compiler_source_set_slice.md),
  [scan joins](../../portability/compiler_scan_join_slice.md),
  [frontend construction](../../portability/compiler_frontend_record_slice.md),
  [type references](../../portability/compiler_type_references_slice.md).
- Algorithm reshaping: [binary syntax](../../portability/compiler_binary_syntax_slice.md),
  [syntax comparison](../../portability/compiler_syntax_comparer_slice.md),
  [byte literals](../../portability/compiler_byte_literals_slice.md).
- Boundaries: [manifest export](../../portability/compiler_manifest_record_slice.md),
  [native project paths](../../portability/compiler_native_project_slice.md),
  [source scanning](../../portability/compiler_source_scanner_slice.md),
  [runtime-preparation symbols](../../portability/compiler_preparation_symbols_slice.md).
- Selection and storage proofs, plus earlier declaration-only slices, are indexed
  in [the migration overview](README.md). Some files only gained imports/types;
  readiness does not imply every file's algorithm was rewritten.

## Subsequent completed PHP adaptation: struct-member traversal

On 2026-09-22, the [struct-member cursor](struct_member_cursor_decision.md) replaced
the generator and all ten consumers were adapted. This is a PHP source/algorithm
checkpoint, not an additional native-ready file. The cursor has constant-size state,
shares the immutable tree, validates on first advance and traverses the tail only
on demand. One existing consumer retains explicit materialization; the others stream
or count. Current-position and terminal failure/exhaustion rules are explicit.

Reason: preserve the existing lazy algorithm in a locally convertible typed form,
without adding general generator lowering or eager temporary lists. Optimization
questions: repeated traversal versus indexes, cursor allocation cost and whether
the existing materializing consumer can later stream without changing validation
order. No speedup is claimed. Focused oracle/identity tests and 39 retained fixtures
pass; the subsequent query/trait adaptation resolves `??`; native proof now encounters
the separately documented target construction defect.
Evidence: `results/struct-member-cursor-01/summary.json`.

## Accepted, not implemented at this checkpoint

- [Semantic enums](enum_portability_decision.md): typed tags plus explicit owned wire
  codecs/operations, one family and consumers at a time. Preserve external values,
  key encodings, enumeration order and failures, including implicit JSON uses.
  Optimize codec dispatch only with evidence; strings must not leak back into the
  algorithm as untyped substitutes for tags.
- [Lossless JSON](json_document_requirement.md): native typed document API with a
  PHP counterpart; retain object/list identity and exact keys before schema mapping.
  Future questions include document lifetime, node storage and parse allocation;
  no API or implementation has yet been proved.
- Filesystem snapshot operations and host-platform facts are requested in
  [#233](https://github.com/alexstanciu-1/simplecpp/issues/233#issuecomment-5771113976).
  Preserve the existing read protocol and host path policy; these are requirements,
  not completed source adaptations.

## Resume assessment

The user accepted the cursor and staged enum cross-owner scopes on 2026-09-22.
Those source adaptations can proceed without waiting for #233 or lossless JSON.
The cursor and affected PHP consumers are now adapted; next prove its portable
dependency boundary. Syntax_Access as a whole also contains other unsupported PHP forms and a
trait dependency: replacing yield alone does not make the whole file ready. Track
that distinction, preserve whole-file readiness rules, and report any new inseparable
cross-owner redesign before expanding the approved scope.

The qualified-base fix must be validated on an immutable candidate before changing
the target pin. No runtime/compiler change or new readiness claim accompanies this
record. A future optimization pass should start with representative full-pipeline
CPU/allocation/peak-memory measurements and retained behavioral proofs; native build
latency is a separate metric from the migrated compiler's execution time.

## Subsequent PHP adaptation: structural query dependencies

Optional node lookups/nullsafe probes now use private checked-node and optional-kind
operations owned by Syntax_Access. Dense one-based node IDs and existing query-specific
errors remain explicit. Enum membership arrays become fixed comparisons; alternative
eligibility becomes a boolean; optional reference/initializer results avoid ambiguous
nullable intermediate locals. Literal owner calls replace self:: for local conversion.
Record constructors are explicitly qualified to disambiguate method/type collisions.
No converter rule or new compiler feature was added.

Reason: express the existing structural validation through typed local operations.
27,560 frozen-original value/error comparisons and 39 retained fixtures pass. Native
proof is blocked by target handling of qualified same-namespace constructors, including
on d493525d; this is not additional native-ready coverage. Future optimization should
measure repeated bounds/kind checks before changing validation placement or caching
roles, preserving deferred failures and input identity. See the
[query slice](../../portability/compiler_structural_queries_slice.md) and
[target handoff](structural_query_target_handoff.md).

## Candidate validation without source adaptation

Candidate a1a1babd clears qualified construction with the existing adapted source,
but the query proof exposes eager RHS evaluation in a guarded traversal. No source
workaround was added during release validation. Retain the authored short-circuit
intent when optimizing guard placement; candidate compilation alone is insufficient
evidence. See [the proof](../../portability/release_candidate_a1a1babd.md).

## Explicit control flow for dependent guards

On 2026-09-22 the user accepted adapting portable PHP to the current S2S limitation
instead of requiring a target operator change. Syntax_Access's optional field extent
and local initializer validators now use nested conditions: validate the expression
only after its ID is known to be nonzero. This preserves the algorithm's absence,
validation order and diagnostics on PHP and native targets. Other compound conditions
in this query owner use independently safe reads or checked optional probes.

Reason: current emitted bool_t logical overloads evaluate the RHS eagerly. Portable
source must make dependent evaluation explicit; the converter remains structural.
No runtime/generator changes or broad operator rewrite are part of this slice.
The original failure evidence and #236 handoff remain historical observations, not
a requirement to block release on a universal short-circuit implementation.

Future optimization: retain these evaluation boundaries unless a later target
contract and behavioral proofs establish safe lazy source operators. Do not recombine
the guards merely to shorten code. This is not a claim about the original S2S design
rationale or a permanent language policy.

Validation: on unchanged candidate `a1a1babd07082d9abf7ac885b2328c99368ad4cf`,
the existing complete six-file structural-query/cursor PHP/native proof now passes
with its unchanged expected output. All 27,560 frozen-original PHP result/error
comparisons pass. Evidence: `results/explicit-guards-01/`. The target pin and
36-file ready manifest remain unchanged; this focused proof is not a whole-compiler
audit of dependent guards.

## Query/cursor cumulative adoption

The three query owner/trait/cursor files are now included with the prior 36 ready
files in one cumulative project. The existing focused witness is part of the main
PHP/native harness, with additional optional local initializer checks. The focused
retained cursor fixture joins the existing 21 retained compiler fixtures. This
promotes 39 production files, not the ten complete semantic consumer files.

The configured target advances from 2f0d667f to exact tested candidate
`a1a1babd07082d9abf7ac885b2328c99368ad4cf`, which supplies qualified construction.
It remains an unreleased candidate based on v0.1.77. No converter or runtime rule
changes are needed. The fast suite and expanded cumulative native proof are recorded
in `results/structural-queries-cumulative-01/`.

The [#236 correction](https://github.com/alexstanciu-1/simplecpp/issues/236#issuecomment-5772057603)
reports that source guard adaptation clears the previous downstream gate. The v0.1
owner retains release CI, final-tree reconciliation and publication responsibility.

## Native-aware scalar record authoring

The portable profile now has an explicit data-only PHP-class marker that emits a
native struct, plus both accepted `&ref` local annotations. The initial field set is
uint32/bool. Ordinary owner methods construct independent copies; PHP object aliases
and native value copies are reconciled through explicit publication/replacement
boundaries, not inferred ownership or a generic emulation runtime. Existing compiler
classes and the 39-file ready set are unchanged. A native span witness occupies
8 bytes with 4-byte alignment on the tested Linux target; this is an observation,
not an ABI or total-memory guarantee.

A fixture initially returned uint32 directly through an int return boundary; native
compilation rejected that. An explicit `(int)` in authored PHP supplies the intended
conversion. Keep this boundary visible when optimizing. No target change was needed.
See [the contract](../../portability/value_records.md) and `results/value-records-01/`.

## File statements and declarations (2026-09-22)

The File_Parser now owns the prototype's complete file grammar through its original
four handler divisions. Shared Parse_Result replaces the expression-only name and
separates grammar output from continuation state. The arena owns sibling tails, so
handlers no longer exchange mutable scalar tail references. Declaration/annotation
absence uses zero IDs; explicit branches replace enum membership/match, and locals
that survive branches are initialized in their enclosing scope. No converter feature
or target-compiler modification was needed.

Repeated file expressions exposed a continuation lifetime omission in the earlier
expression slice. Successful type/value exits now release the root frame; a 2,000-
expression host proof checks depth zero and two reusable slots. This bounds retained
frame storage but still constructs frames on reuse; measure allocation cost before
a later optimization. Statement recursion remains the prototype's existing approach.

The first native attempt caught an empty container literal assigned through a chained
result field; the documented typed-local assignment pattern fixes it. This was an
authoring correction, not new target debt. Failure now clears all published syntax
and indexes, retaining the diagnostic and exact input snapshot. Tests compare grammar
results and spans, not node allocation IDs. Retained unit inputs are reused without
claiming their not-yet-migrated semantic/session assertions pass.

See [file parser contract](../../portability/file_parser.md) and
[measured proof evidence](results/statements-declarations-01/README.md).

## Syntax access and comparison (2026-09-22)

Five prototype-owned files now query the new arena/result directly. Eleven role-view
classes and comparison frames become explicit scalar value structs. The views retain
only bounded source-local IDs/tags; parameter reference absence changes from nullable
enum to zero. Queries construct fresh views explicitly, and comparison push publishes
a fresh frame value into its vector slot, avoiding dependence on PHP object sharing.
Compact observations are cast to int at comparison/index boundaries. Raw arena access
uses the existing explicit row-copy owner; no second tree model was introduced.

The logical comparison algorithm is preserved: spelling plus ordered descendants,
excluding selected roots' own siblings and ignoring positions/comments. Explicit
spelling guards avoid the known boolean short-circuit limitation. Nonzero input roots
are validated before entering unsigned frame storage. Member traversal remains lazy,
read-only and terminal after failure/exhaustion. No converter support or target fix
was needed; the first native build and behavior run passed. Aggregate memory and
optimized performance remain future measurement work, not inferred from PHP behavior.

The first PHP-ready and native-ready commands now carry immediate timestamps and
source hashes. This slice took about 3m52s to PHP-ready, then 1m20s to native-ready
(including a 46.1-second build), with zero native corrections. See
[measured evidence](results/syntax-access-01/README.md).

## Project parser snapshots and joins (2026-09-22)

The parser's store/selection/worker/join responsibilities were rewritten against the
current fresh lexical snapshots, rather than importing the prototype's persistent-ID
Source_Set/Step interfaces into unmigrated owners. Exact supplied paths key current
membership; positions are explicitly not stable identities. Unchanged bytes reuse
syntax. Fresh tokens get a new Parse_Result wrapper around the retained tree, so
current input ownership and old snapshot immutability are both preserved. Renames
remain removal/addition; whitespace changes reparse for correct spans.

The fixed plan partitions current positions into work and reuse. Join preparation
validates that partition; merge validates complete segments before adoption; finish
preserves current order and excludes removals. Worker failure returns a diagnostic
set with no partial files. The sequential entry composes the same plan and join used
by out-of-order/segmented tests, without introducing a new generic scheduler.

Selection compares source bytes directly and join preparation builds its own validated
path index; these are local optimization candidates only if measured. Upstream source
scanning/tokenization remain fresh, so this is parser-level reuse rather than an
end-to-end incremental compiler. The source correction after the first PHP-ready
checkpoint splits range rejection from subtraction for safe negative-index handling
on the current non-short-circuit target. No converter behavior was broadened.

See [project parser contract](../../portability/parser_project.md) and
[timing/validation record](results/parser-project-01/README.md).

## Source declaration collection and symbol records (2026-09-22)

Five source files begin 04_analyze using the prototype's collect_symbols ownership.
Temporary declarations now carry compact syntax IDs, including the name node, rather
than copied strings and general PHP records. Names are materialized once per newly
indexed symbol; source bytes and syntax remain owned by the exact frontend. Symbol
records share identity, while fact/change rows become inline native value structs.
The store uses dense rows with separate ID/name/path/owner indexes. This is a clearer
basis for later memory measurement, not a measured memory saving claim.

The sequential coordinator matches named identities across file moves and preserves
monotonic allocation, removals, duplicate diagnostics and baseline purity. File entries
use current exact paths because the rewritten inputs do not yet provide persistent
source IDs. Same-frontend reuse shares records; rebinding/full extraction yields
uncompared changes. Change rows reference IDs in retained previous/current stores,
avoiding nullable previous/current record payloads without inventing comparison facts.

Runtime providers and source namespace metadata have not been fabricated. Provider
imports, asynchronous collection joining, export formats and semantic comparison stay
explicitly outstanding; the source-only coordinator is not the prototype's entire
Step/session contract. Standalone evaluated-function wrapper rejection is retained
as a known parser/collector mismatch, while templated evaluated functions work.
Review that behavior separately before widening language support.

The first host proof passed 97 outcomes. Three checker correction rounds followed:
use the supported RuntimeException for exhaustion; fully qualify named constants;
use concatenation with explicit compact-int normalization rather than unsupported
string casts. Native compilation and execution then passed on the first attempt,
without converter/framework/target edits. Keep these correction costs visible in
[the timing record](results/declaration-collection-01/README.md). Nine host serialization
assertions additionally prove candidate/baseline purity. The retained collection and
storage tests supplied guarantees/fixtures; their full session harness is not runnable
against this still-partial pipeline. Next is entry-contract preparation.

## Entry selection and top-level execution policy (2026-09-22)

The source half of prototype Entry_Resolver is now a synchronous Entry_Preparation
owner. It uses the current frontend entry index and exact symbol-store membership,
then rejects the first top-level statement outside the manifest-selected file. Source
statements and diagnostics retain byte spans; no AST nodes, symbols or filesystem
observations are invented. A typed selection result retains the store plus stable ID;
failures cannot expose a selected callable. Explicit guards validate indexes before
access, and freshness checks precede source-policy diagnosis.

This is migration of the algorithm and its guarantees, not preservation of the
prototype's init/run/finalize shell. Nine serialization checks prove input/baseline
purity; 44 PHP/native outcomes adapt the relevant program_entry.php guarantees.
Unchanged, rebuilt and reordered snapshots share or refresh selected identities as
appropriate. Supporting declarations remain allowed, while even empty block statements
and dead branches are rejected without evaluating their contents.

Inspection exposed the next actual dependency: the original entry contract retains
an exact catalog integer definition, including lifetime and operation policies. A
name/bit-width surrogate or hardcoded native int would lose valuable semantics.
Accordingly the current output is called Entry_Selection, not entry_contract. Return
binding, type-catalog ingestion and signature/body checking remain pending. This is
an explicit component boundary, not a claim that the whole Entry_Resolver migrated.
The next slice must follow type-model/catalog dependencies before completing binding.
No existing ownership area required a broad refactor, and no type-model stub was added.

The first 43-outcome PHP behavior run and first checker/conversion/native build passed
without correction. Final review then added an upper-bound guard for a malformed
frontend with a stale path index and shortened file list, plus a 44th regression case.
That source-review correction requires a separate final native verification; it is
not a native-failure cycle. No converter/framework/target change was needed. Timing and provenance:
[entry preparation evidence](results/entry-preparation-01/README.md).

## Type representation foundation (2026-09-22)

The catalog dependency begins with complete value-shape vocabulary rather than a
stripped integer type created only to finish entry preparation. Representation's
private factory-owned state and checked accessors replace the prototype's union of
nullable unrelated payload classes. All nine shapes and callable passing/result facts
are preserved. This deliberately postpones native union/layout tuning; no reduced
memory footprint is claimed. Full-width integer payloads avoid silently narrowing the
prototype's accepted widths, IDs or counts to uint32.

Shape validity is local; type/member existence remains the future store's concern.
The writer now rejects negative ranges and zero return IDs at construction. Empty
passing lists expand explicitly to value modes and provided lists are copied. Context
keys remain producer-supplied content/version facts, and lineage remains an independent
shared identity. Signedness, operations and lifecycle permissions are not inferred from
representation. Their authoritative named definitions are still pending.

The first PHP behavior run passed 98 outcomes. The checker rejected bitwise AND in
opaque alignment validation; an overflow-guarded integer doubling check preserves the
power-of-two algorithm without introducing converter support or floating arithmetic.
STAN then rejected terminal-throw codec forms as possibly missing returns. Explicit
success branches plus a common return preserve all mode mappings/rejections without
an unreachable dummy value. The next native build passed. Both costs remain recorded.

The retained representation constructors run directly in a host oracle for widths,
default passing, borrow classification and result names. Five host assertions additionally
cover observation purity and large alignment boundaries; the latter are not claimed as
native cases. The new factory/mode paths have 98 independent native expected outcomes.
No source-runtime-preparation, converter, framework or target code was changed.
See [the evidence](results/type-representations-01/README.md). Continue with lifecycle
operations/contracts, then named definitions and the catalog binding required by entry.

## Lifecycle operations and shared lifetime policies (2026-09-22)

The prototype's runtime/source operation union becomes one explicitly tagged record
with distinct named factories, validated inactive payloads and retained linkage/owner
facts. Nullable constituent operations still mean primitive behavior, not missing
information. Source member vectors are copied; constituent operations remain shared
immutable identities. Composition preserves custom copy/default initialization, custom
assignment ownership, reverse destruction and move/copy fallback. No actual operation
execution or backend availability is inferred by constructing these descriptors.

Lifetime permission fields are compact producer data copied into private contract
state. Optional implementations are indexed by lifecycle role rather than five nullable
slots. Exact required/forbidden role checks preserve the original relationship between
permissions and bindings; absent lookups require an explicit has_operation check. This
is structural adaptation, not removal of lifecycle semantics. Unknown policy strings
and tags reject through explicit codecs. Construction also checks malformed metadata,
negative member/range IDs and duplicate operation bindings earlier than the prototype.

All 154 PHP/native outcomes passed on the first checker/conversion/native build. The
retained lifecycle/definition files execute directly as a host ordering/fallback oracle;
four host serialization assertions prove input-policy/list/order-view independence.
No converter/framework/target changes were required. Timings:
[type lifetime evidence](results/type-lifetimes-01/README.md). Next is the scalar
language catalog's named definitions and authoritative entry/default bindings.

## Scalar language catalog and authoritative entry contract (2026-09-22)

The prototype scalar loader's complete void/integer/floating schema is migrated with
shared Named_Definition/Type_Catalog objects, exact default membership and entry
binding. Width does not imply signedness or operations; null lifetime belongs only to
void. Family/addition/comparison/field capabilities remain explicit. The named-definition
owner currently rejects other shapes instead of accepting incomplete resource/layout
facts. Extended runtime package/source definitions remain preserved pending their own
migration; the scalar form is not advertised as that full extended model.

Language_Types now binds its previous snapshot in a constructor and reads an explicit
path synchronously. Complete source bytes, prefixed by catalog format, replace SHA-256
as the content identity. Exact same-content reuse and changed-content invalidation are
preserved without an unproved hash adapter; compact fingerprints are a later optimization
candidate. Source path defaults and generic Step/session wiring are not copied back.

The fixed schema uses named records plus typed setup vectors/maps. Dynamic PHP array
shape tests become JSON-view object/list/kind checks. Strict integer/boolean view methods
were added to both framework runtimes using existing native json_node_int/boolean APIs.
Integer spelling/range rejects fractions, exponents and decoded-float overflow; false
and zero remain values. No converter or target implementation changed.

Two authoring/checker adjustments preceded the first PHP-ready checkpoint: nullable
previous input moved from an unsupported ordinary method parameter to constructor-owned
state, and a computed isset key became a named local. Optional boolean definition lookup
uses a supported nullable-return method rather than an unsupported named-wrapper local.
This keeps the converter structural. The initial --no-imports checker invocation was an
unsupported tool option and was corrected; it was not a source failure.

116 PHP/native outcomes passed, with the first native build successful. The original
catalog parser also agrees with independent expected valid-definition facts and the
invalid-input corpus. Eight host purity checks cover retained membership, failed-read
rollback, repair, policy views and qualified UTF-8 names. Entry_Contract now binds the
selected source symbol to the exact catalog-selected integer definition; native exit
ABI remains outside that contract. See [timing/provenance](results/scalar-catalog-01/README.md).
Continue with source name resolution in the prototype's analysis order.

## Rewrite: declaration lookup and occurrence targets

The prototype's global name lookup now consumes the migrated source store and scalar
catalog. `Name_Binding` replaces its int/definition/record union with a validated
numeric target plus nullable exact scalar definition. This preserves identity and
zero-based template parameters without dynamic payload dispatch. A later optimization
may split dense numeric rows from provider references or adopt native unions; no
memory-layout claim is made for the current ordinary shared class.

Source namespaces are absent from the current grammar, so the unused namespace input
is removed. Provider records are deferred with their catalog owner, not fabricated.
Function lookup validates the syntax role and excludes member-only functions. The
worker/reuse selector will share these lookup rules. The prototype constructor oracle
covers numeric role/target rules; 40 independent PHP/native outcomes pass, first build.
Before PHP-ready, `<= 0` became the supported equivalent `< 1`, and a stale prototype
`syntax` member became the active `tree`. Neither required converter changes.
See [measurements](results/name-lookup-01/README.md).

## Rewrite: lexical and body resolution

The original worker and four same-namespace traits retain their division of work.
Statement and expression traversal stay iterative. Reference-parameter expression
stacks become private `Expression_Stack` objects; logical stack size is separate from
retained capacity. Scope maps move from nested ad-hoc arrays into typed `Scope_Names`
objects, and only an owning block cursor retires them. These changes make PHP/native
mutation intent explicit while preserving visibility and scheduling order.

Published numeric facts become compact value records with explicit copies at input
and read boundaries. Target definitions remain shared objects. The result owns its
indexes; consumers read named accessors rather than mutate vectors. This intentionally
replaces prototype shared-row identity with equal value facts. A future optimization
can measure row widths, allocation of scratch maps/cursors and index storage.

Failures return a typed attempt with the original path and byte diagnostic, without
partial bindings. One-shot workers reject duplicate execution and stale owners.
Generic parameter policy now has its actual type-model owner and preserves copy,
assignment and destruction permissions; this fact is not dropped during binding.
Source template argument roles use the already-collected parameter-list anchor,
rather than rediscovering the outer template wrapper.

The first native attempt stopped before C++ at STAN's missing-return advisory for a
return inside an infinite loop. The index traversal now breaks and returns after the
loop, preserving the same walk. No no-STAN bypass, converter or target changes.
Pre-PHP-ready corrections were test harness count/quoting/argument-name fixes and a
constant-shadowing diagnostic anchor: the constant is visible in its own annotation.
Project reuse and structural completeness acceptance remain the next coherent owner;
this worker checkpoint does not claim phase completion.

## Rewrite: project resolution acceptance and reuse

The prototype selection/worker/join process becomes a fixed `Resolution_Plan`, the
existing worker, atomic `Resolution_Join`, and synchronous `Symbol_Resolver` wrapper.
Only `Resolution_Set::publish` populates private accepted rows. This avoids treating
arbitrarily assembled locally valid rows as a previously accepted phase. Warm results
skip structural revalidation after their current dependencies are checked; selected
results undergo coverage acceptance. Generic Step/session scaffolding and production
debug serialization are reserved for their integration steps.

Coverage keeps the original iterative acceptance walk and replaces mixed claim maps
and unset with typed positive-value indexes/consumption counts. Variable-use coverage
now checks nearest locals and read/write roles as well as declarations/scopes. An
explicit continuation publishes a value-template parameter after its annotation,
so a later parameter cannot capture an earlier annotation. These checks make the
published result model more truthful within the same name-resolution owner.

Reuse checks now include two negative dependencies required for clean/incremental
agreement: a new same-name constant blocks an existing function call; a new catalog
type conflicts with a source struct. Exact owner records also replace same-AST-only
reuse, preserving current metadata/diagnostics after frontend rebinding. Later
optimization may share binding storage while explicitly rebinding the owner.

Native stabilization exposed two authoring/tool details: STAN inferred `!` as the
receiver type after a direct test-local identity comparison; an explicitly typed
identity helper preserves the assertion. PHP's separate field/method namespaces do
not survive C++ lowering, so the private `selected` map became `selected_ids` alongside
method `selected()`. The authoring guide now states that naming rule. No target or
converter implementation was changed. Timings and separate correction counts are in
[the proof record](results/resolution-project-01/README.md).

## Rewrite: canonical type storage

Canonical identity, declaration, definition and representation remain separate states.
The store retains all representation kinds, recursive references, member ranges and
field/signature ordering. PHP clone becomes explicit `fork()`: container copies share
immutable rows/payloads, changed rows are replaced, and candidates retain no ancestor
store chain. Full reconstruction gets a new lineage even with equal context keys.
Named row constructors keep immutable fields; fresh/fork are the supported creation
paths. The low-level context/lineage constructor is producer-internal.

Representation variants already share a typed shape owner; its new `same()` operation
replaces PHP loose object structural comparison. Typed vectors replace dynamic list
shape tests. Keys preserve byte-based name lengths and incorporate field writability,
passing modes and result production. Adding production to signature keys fixes a
retained prototype defect: changing a return type's representation while retaining
its ID could otherwise reuse the old signature production. The retained oracle
reproduces that omission separately from preserved storage facts.

`Type_Cache` now owns an optional previous store through its constructor, avoiding an
unsupported nullable ordinary parameter. It preserves full/context selection and
materializes scalar authoritative definitions. Array/resource/source definition
producers and their scans remain explicit dependencies; representation availability
does not fabricate those semantics. `lifecycle_operations()` keeps source-owned
operations in type/role order and excludes imported operations.

Final review restored the prototype's early intern-hit checks to avoid temporary
representation allocation. Both native builds passed; the second validated this
refinement, not a compiler-failure workaround. One test expected-count correction
preceded PHP-ready. A preliminary declaration probe confirmed named readonly fields
must use supported constructor promotion rather than standalone initialization.
See [timings and proof](results/type-store-01/README.md). Future optimization can measure
candidate container-copy costs, key construction and hot identity/reference storage.

## Aggregate lifecycle composition (2026-09-22)

Migrated field-policy folding and complete lifecycle plans into `Lifecycle_Composition`.
A compact `Lifecycle_Bodies` row replaces several optional body-ID arguments. Member
plans use typed vectors and explicit reverse traversal; array repetition remains one
count rather than an expanded element plan. Custom copy initializes fields before its
body, custom assignment owns updates, and custom copy/assignment/destruction suppress
implicit move. Unavailable field construction remains unavailable even with a custom
constructor. General value definitions now permit aggregate shapes; integer capabilities
and inline-field eligibility remain separately validated. JSON catalog parsing stays
scalar-only. Resource/layout metadata will follow their actual producers.

36 shared outcomes pass PHP/native; eight host checks cover mutation/rejection boundaries.
One native command failed before C++ compilation because test annotations touched `=`
(`vector<T>=`); spacing corrected the fixture. The second command and first actual C++
build passed. No production algorithm, converter, or target change was needed to stabilize
native execution. Optimization follow-up: measure repeated member-definition lookups
before introducing cached projections. See `results/aggregate-lifecycles-01` for timings.

## Native record layout contract (2026-09-22)

Preserved exact target triple/data-layout keys, positive aligned size, power-of-two
alignment and strictly increasing in-range field offsets from the normalized record
model. The private typed offset vector replaces public PHP array access with field_count
and field_offset, preserving snapshot ownership on both execution surfaces. Power-of-two
validation uses overflow-guarded integer doubling, as in Representation, rather than
unsupported bitwise operators. No inferred target layout or weakened ownership checks.

18 outcomes pass PHP/native on the first build; 441 host combinations agree with the
retained constructor. The oracle needed one harness correction: PHP case-insensitive
class names require separate processes for old/new classes. No source/native correction.
Full field recipes and resource-aware record normalization remain dependencies.
See `results/native-record-layout-01` for timing, commands and hashes.

## Resource obligations and allocation effects (2026-09-22)

Moved the prototype resource-kind and effect enums to explicit integer tags/codecs.
Allocation_Effect keeps nullable destination distinct from parameter zero and requires
transfer to have a distinct nonnegative destination; argument type agreement remains
with the call checker. Resource_Obligations owns immutable nested field-path vectors,
validates nonempty/nonnegative/unique paths, and validates storage/copy/assignment
permissions. Named_Definition optionally holds this shared immutable object plus exact
native layout metadata. No metadata is fabricated for scalar definitions. Grouping
resource kind and paths gives ownership checks one reusable owner; typed element-storage
family identity checks still await their real provider/storage model.

39 shared outcomes and 90 retained allocation-effect combinations pass. Two native
builds: the first exposed omitted standalone new statements in the fixture, the second
passed after assigning those objects. Generated C++ inspection confirmed empty branches;
no algorithm/target/converter fix was made. The guide records the restriction. Constructor
dependency closures were updated for existing stages. Static paths remain dense typed
vectors; shape-bound range checking is the responsibility of later concrete producers,
as in the prototype. Next: array/field recipes and resource-aware record materialization.

## Normalized fields, arrays and record materialization (2026-09-22)

Field_Type replaces the named-definition/array-recipe union with one immutable recipe:
zero extent means the named value; fixed_array rejects nonpositive counts. Positive
extent rejects allocation owners and records with owning leaf paths. Field_Declaration
and Record_Declaration own typed immutable normalized inputs. Record_Definitions validates
ASCII names, field uniqueness, automatic lifecycle and native measurement coverage,
materializes fields, prefixes owning paths, and derives complete lifecycle contracts.
Type_Cache now handles byte-span/opaque named definitions and element/count array identity;
existing record definitions reuse their already-bound canonical shape. Array plans retain
a repeat count rather than per-element expansion. Failed private candidates must be
discarded, as in the prototype. Typed storage-family descriptors still await their owner.

27 integrated PHP/native checks cover scalar/array/nested records, owning paths, native
measurement identity and malformed record rejection. First PHP attempt corrected the
identifier helper to compare integer byte values. Both native builds passed: first 15
outcomes, then 27 after adding nested ownership and rejection coverage. No native source
correction, converter change or target change. Retained materialization algorithms were
inspected; this slice does not claim an executed retained oracle. Timings and hashes are
under results/record-materialization-01.

## Accepted definition view (2026-09-22)

Migrated Definition_View's provider-first lookup and exact source definition fallback.
Read-through accessors replace redundant public metadata copies, retaining the exact
catalog entry definition without unsupported standalone named readonly initialization.
The catalog/store are shared accepted inputs, immutable by publication convention;
the view is not a mutable-store snapshot. No spelling resolution or generic fallback.
14 PHP/native checks pass on the first build; no corrective cycle. The annotation
resolver's remaining dependencies are Instance_Context, Template_Argument and instance
bindings/preparation (including integer constant decoding), so it is not claimed complete.
See results/definition-view-01 for measured effort and source/target hashes.

## Exact integer literals and concrete instance contexts (2026-09-22)

Reused Decimal_Range's exact repeated decimal division; Integer_Literals uses explicit
ASCII byte validation and leading-zero normalization instead of regex/ltrim. Values
remain decimal strings, including beyond the PHP/native host integer width. The supplied
integer definition controls signedness/range; overflow remains RangeException and malformed
input LogicException. Template_Argument retains exact definition identity and nullable
value text (null type argument differs from integer zero).

Instance_Context preserves source provenance, ordered immutable argument handles, receiver
restrictions and disjoint context domains (source ID versus MAX_SYMBOL_ID+instance ID).
Constructor arguments are explicit; ordinary() provides the empty non-template context.
Arguments copy the vector but share immutable rows. The internal NUL-prefixed namespace is
built through string_byte_from_int(0), avoiding unsupported binary source literals.

35 PHP/native outcomes pass; 200 independent Python-bigint-generated host range cases
cover widths 1..1024. Pre-PHP-ready corrections: binary literal checker rejection and a
fixture lookup that used function rather than template-function for a template member.
One --target invocation stopped at that PHP fixture error before building; the next
invocation's first actual C++ build passed with no native correction. Context allocation,
instance registry/publication and bindings remain separate dependencies. Evidence/timings:
results/instance-contexts-01. src-runtime-preparation remains unchanged.

## Exact instance identity allocation (2026-09-22)

Instance_Identities now owns the key map and watermark instead of mutating caller PHP
arrays/integers by reference. fork() copies its private scalar map; lineage identity is
fixed and checked before materialization. Exact ordered canonical argument type IDs,
definition ID, and null-versus-value argument text determine reuse. Byte-length framing
replaces JSON serialization of ad-hoc tuples; 60 direct calls against the retained JSON
allocator yield the same allocated IDs, including delimiters, UTF-8 and embedded NUL.
The history is retained independently of current demands. Exhausted ledgers still return
known IDs and reject new ones without advancing the watermark. Failed type materialization
may mutate the private type candidate, which must be discarded as in the prototype.

Added the prototype's OverflowException to the fixed framework map (RuntimeException
parent) rather than weakening exhaustion to a different exception. The checker exposed
this missing entry before PHP-ready. 23 PHP/native checks pass on the first native build,
including specific, parent and unrelated exception catches. No target change. The retained
allocator oracle was added after the native run without source/probe changes. The instance
registry still requires real template permission results; no placeholder has been added.
Evidence/timings: results/instance-identities-01.

## Symbolic terms and permission-result contracts (2026-09-22)

Replaced type_term's int/string/definition/record union with an explicitly validated
symbolic record and factories. Formal parameters carry source owner and slot integers
rather than an encoded string. Named provider/record targets retain exact object identity;
source declarations retain their source ID. Constants retain exact provenance text.
Application arguments are ordered, copied vectors of immutable term handles. Dependency
propagates from formals through arguments. Symbolic arrays retain only element identity,
as in the prototype; concrete extent/layout belongs to later preparation.

Type_Term::same performs iterative full identity comparison with two reusable vectors and
a logical stack size, replacing ad-hoc pair arrays and array_pop. The native proof handles
1,000 nested arrays. Future optimization: profile scratch allocations before introducing
a term arena or native tagged union; no representation tuning is claimed here.

Definition_Task requires its exact owner binding. Definition_Result retains exact source,
catalog, declaration and binding snapshots; Template_Set rejects missing/stale source
permissions while ordinary declarations need none. Private vectors/maps prevent accidental
result mutation. Only the checking producer may create permission rows; this checkpoint
migrates the data contracts, not the semantic worker or provider-family rules.

40 shared PHP/native checks pass. Both native builds passed: first 30 symbolic/task checks,
then 40 after adding permission containers. No conversion/native correction. 529 pairwise
comparisons and 23 dependency flags match direct execution of the retained term model.
The first PHP-ready milestone is retained; subsequent elapsed time includes the additional
permission-model authoring, not just native stabilization. Evidence: results/symbolic-terms-01.

## Provider references and semantic signatures (2026-09-22)

The approved compiler-side provider integration starts with ABI-independent records.
Four prototype reference subclasses become a tagged Type_Reference with guarded
accessors: named language identity, exact provider/type identity, owner/slot formal,
and ordered family application. This avoids heterogeneous interface payload casts;
qualifiers and exact family keys remain unchanged. No type resolution, layout or
permission is inferred. Private copied argument vectors preserve shared immutable
child identity without exposing mutable container state.

Semantic_Parameter and Semantic_Result reuse the already-proved passing/production
tag codecs. Semantic_Signature retains ordered positions, result and optional exact
allocation-effect identity. Private parameter storage replaces public PHP arrays;
PHP rejects sparse/keyed lists, while native vectors enforce dense positions.
Provider adapters still own declaration validation and resolution.

35 shared PHP/native checks plus two PHP carrier rejections pass. One fixture catch
was corrected before PHP readiness (OutOfBoundsException is not a LogicException).
The first native build passed, with no converter changes or native corrective cycles.
Future optimization: measure tagged reference storage before replacing immutable
handles with an arena; preserve exact identities and ordering.
Evidence and phase timings: results/provider-semantics-01. Family validation,
provider import, shared symbol integration and the full template worker remain pending.

## Generic family contracts and source exposure (2026-09-22)

Migrated Family_Definition, operations, formal requirements, element effects and
source family/member payloads. Private typed containers retain stable declaration
identity; an operation hash serves exact ID lookup and a vector preserves order.
Source exposure remains separate from provider IDs. Exact family keys retain the
preparation tool's JSON tuple spelling, including escaped slash and Unicode.

Family_Contracts preserves the prototype's provider/formal/self-reference rules,
copyable-value permissions, lifecycle membership, source exposure uniqueness,
receiver passing and element overlap/invalidation rules. Nested guards replace
PHP short-circuit-dependent optional reads. Byte-level ASCII identifier checks
replace regular expressions for source names/namespaces. Typed tags reuse the
existing generic/lifecycle vocabulary instead of creating another permission model.

The constructor requires an explicit generic contract because constant defaults
are outside the converter's current promoted-parameter subset. This required one
checker correction before PHP readiness; no converter or target change.
51 PHP/native outcomes pass on the first native build, with zero native corrective
cycles. The same 33 family declaration cases agree with the retained prototype's
validator in a separate PHP process. Six proof checks and the oracle were added
after the first 45-outcome PHP checkpoint; timings identify this additional work.

This remains a semantic model checkpoint: provider JSON ingestion, normalized
record catalog composition, symbol origin integration and template execution are
not yet implemented. No synthetic frontend, ABI readiness or generic permission
is inferred from an available native implementation. Evidence: results/provider-families-01.

Final family review also made Type_Reference reject sparse PHP argument lists
before vector copying. This preserves the prototype's self-application list
requirement instead of erasing invalid keys. The added host rejection passes;
both affected native proofs are rerun on this final source. This is a review
correction, not a native-build failure; the first passing milestones are retained.

## Normalized provider records and family import acceptance (2026-09-22)

Type_Catalog now retains normalized Record_Declaration rows separately from
materialized Named_Definition rows. Both share exact qualified-name collision
checks; record lookup returns the original declaration and does not allocate type
IDs or infer layout. Existing scalar defaults retain exact definition identity.
The scalar language JSON schema remains unchanged. Read-only count/index access
replaces exported mutable arrays. Optional record input uses an explicit nullable
container unwrap before iteration.

Family_Adapter migrates acceptance, source exposure and catalog mapping validation
from the prototype. It retains provider identity, rejects duplicate identities and
source-name collisions across named/record/family declarations, and requires exact
provider-to-language mappings for every provider reference in parameters/results.
It consumes semantic records; it does not run preparation, parse package metadata,
acquire leases or register symbols. Those boundaries remain separate dependencies.

30 PHP/native outcomes pass. The first native attempt rejected foreach on a
nullable vector; take_nullable into a concrete vector is the corrected source form.
The second attempt passes, with one source/native correction and no converter or
v0.1 changes. Existing scalar-catalog (116) and project-resolution (182) native
proofs also pass on the final source. Their first parallel attempts repeated the
same failure; incremental rebuilds reuse unchanged units for final verification.
Future workflow: finish the focused native proof before launching broader native
regressions, to avoid multiplying one new-shape failure.

Evidence, source hashes, commands and phase timings: results/provider-catalog-01.
Package ingestion/composition, callable/storage contracts, shared provider symbols
and name bindings, and full template execution still remain.

## Callable ABI transport and semantic compatibility (2026-09-22)

Migrated the prototype's prepared-callable model. Integer, borrowed-address and
byte-span ABI subclasses become a tagged Runtime_Abi_Position with validated
payload shapes; byte spans retain their measured integer-length ABI. No semantic
passing or ownership capability is inferred from a pointer or native link name.
Runtime_Callable_Abi copies typed parameters and builds semantic-to-physical slot
mappings explicitly: hidden result storage takes slot zero, spans take two slots,
and ordinary parameters take one. Accessors preserve immutable container snapshots.

Compatibility validation preserves all prototype passing/result rules, including
rejection of unresolved dependent results and mismatched owned-result transport.
Runtime_Callable retains exact semantic/ABI objects and source/provider identities.
Nullable binding/conversion tags preserve absent versus zero-valued permissions;
explicit codecs retain the original provider strings. Direct ABI results remain
integer-only; generic storage primitives will have their own address-result contract.

66 independent PHP/native outcomes pass. The retained validator agrees on all
16 argument-passing and 16 result-production combinations and both direct/hidden
slot mappings. Native command attempt one stopped at STAN before C++ compilation:
codec early returns plus terminal throws were misclassified as missing returns.
The existing explicit-result/final-return style passes on attempt two, which is
the first actual C++ build. No converter or v0.1 modification.

Timing/evidence: results/callable-abi-01. Next: storage-family contracts and prepared
package consumption. Shared provider symbol integration remains incomplete.

## Typed storage families and descriptor ownership (2026-09-22)

Migrated storage primitives, family metadata, source operation roles/functions and
concrete element-storage records. Primitive parameters/results allow only integer
and borrowed-address ABI positions; byte spans stay a separate callable contract.
Private copied typed maps/vectors replace mutable PHP arrays. Role codecs preserve
metadata spellings; configured source names do not determine semantic behavior.
Allocate/release/transfer/count/push/pop retain acquire/release/transfer/observe/
mutate/mutate effects, with owner zero and transfer destination one.

Named_Definition now owns optional Element_Storage metadata, restoring the
prototype's exact descriptor invariant: typed storage requires an allocation
obligation and the same representation/lifetime objects as its family's descriptor.
Equal-looking replacement objects are rejected. Canonical element IDs remain local
to the containing type-store lineage; this record does not create or resolve IDs.

Storage_Function computes one shared effect during construction. A private nullable
initialized property plus guarded non-null accessor replaces the unsupported
uninitialized named-property form; no absent effect escapes a completed constructor.
This was one checker correction before PHP readiness. The focused 53-outcome native
proof passes on its first build, with no production native correction.

The retained prototype supplies independent role/effect and descriptor-identity
checks. Type-store native regressions cover 133 outcomes. The resource regression
required a harness dependency correction: its custom load order did not receive
the new storage closure with the standard stage lists. Native evidence and actual
phase/cycle timings are saved under results/storage-contracts-01.

Next: prepared-package storage rows and import/lease/metadata boundaries. The
preparation PHP implementation and its output contract are unchanged. Shared
provider symbol origins, name bindings and full template checking remain pending.

Final storage checkpoint: resource native regressions pass all 39 outcomes after
the harness correction; cumulative fast validation passes at 98 files. A later
uninitialized-named-property converter enhancement could remove the private
construction-state guard without changing Storage_Function's non-null API.

## Prepared-package syntax and measured runtime records

The package syntax trait becomes a stateless Package_Syntax helper. It consumes
lossless Json_View values and returns named Type_Reference / Runtime_Abi_Position
records rather than anonymous tuples or mixed arrays. Runtime_Type retains the
exact named definition and optional normalized record; measured storage remains
package-target data, not proof of semantic permissions or canonical identity.
String-backed storage/module enums use validated integer tags with exact producer
spellings. Runtime_Storage validates the tag; the forthcoming package type importer
still owns category-specific size/alignment validation.

Explicit byte scanning replaces regex splitting for integer ABI attributes. It
preserves PHP trim's edge-NUL handling and the distinct ASCII whitespace set used
by the original regex. Repeated noundef remains allowed; conflicting/repeated
extension attributes remain rejected. Address ABI accepts only ptr with empty or
noundef attributes, with no inferred aliasing/address-space permissions.

Rows now require an actual JSON array of actual JSON objects. The prototype's
associative decoding incidentally accepted array rows; rejecting these preserves
the producer's record schema without modifying preparation output. Positive integer
reads reject float/exponent tokens and overflow instead of coercing them.

The first native build passes all 66 outcomes with zero native corrective cycles.
520 retained inputs agree through both ABI validators. The first PHP milestone,
source hashes, command timings and native evidence are in results/package-syntax-01.
No converter or preparation implementation changed. This proves metadata syntax,
not package acceptance: exact SHA-256 verification has no portability binding yet;
leases, the complete type importer and provider composition remain to migrate.

Final cumulative fast validation passes with 100 registered production files.
The next coherent dependency is resource-permission ingestion; full package
acceptance remains gated on checksum/lease and remaining import contracts.

## Resource permissions at the package boundary

Resource_Import is a stateless helper over lossless JSON views and the accepted
Runtime_Type map. It converts the prototype's optional resource enum to optional
Resource_Obligations with the direct allocation kind and no field paths. Layout,
copy traits and aggregate owning paths do not imply direct allocation permission.
The original prohibitions on non-inline resource types and exposed copy operations
remain; lifecycle completeness belongs to the forthcoming lifecycle importer.

Allocation-effect ingestion keeps semantic parameter positions separate from
physical ABI slots. An integer-keyed map holds only actual direct owner parameters.
Required endpoints plus the exact owner count replace sorting a temporary position
list: every owner must be covered, transfer endpoints must be distinct and name the
same exact package type ID, and all owners must use the appropriate const/mutable
borrow mode. Transfer between different IDs is rejected even if definitions are the
same object. Results carrying allocation ownership require zero-argument construction.
Only acquire/release/transfer/inspect are allowed at this provider boundary; internal
mutate/observe contracts do not silently become accepted package effects.

The converter rejected a nullable local annotation before PHP readiness. A concrete
local destination plus explicit transfer/non-transfer result construction expresses
the same optional payload using supported forms. No converter change was needed.
54 independently expected PHP/native outcomes pass on the first native build with
zero native correction cycles. These cover sparse owner positions, reversed
transfer direction, missing/extra fields, wrong numeric shapes, ownership coverage,
borrow modes, different type IDs, missing types and resource results. Evidence and
phase timings are in results/resource-import-01.

This is a dependency for complete package import, not a substitute for its physical
ABI validation, lifecycle validation, checksum verification or lease ownership.
Runtime preparation is unchanged.

Cumulative fast validation passes at 101 registered production files.

## Provider lifecycle ingestion

Lifecycle_Import replaces the private lifecycle trait with a stateless schema
normalizer. Shared checks own pointer ABI, linkage, error boundaries, exact slots
and borrowed operands; role branches retain the separate construction, destruction,
copy/move and assignment storage/source contracts. The output is the already-proved
Lifecycle_Operation and Lifetime_Contract model, with an explicit permission policy
and typed operation vector. No layout-derived capability or source symbol is invented.

Copy/move/assignment still require both selected operations and the corresponding
verified C++ trait. Cleanup none requires verified trivial destruction. Assignment
keeps both operands live, uses mutable/const borrows, and requires the native-call
self-assignment contract. Default construction retains the original contract; it
does not gain speculative new postcondition requirements during migration.

Lossless JSON getters replace mixed arrays, null-coalescing and dependent boolean
chains. Required shape failures stop before indexed access. The provider operation
map must already be indexed by validated exact IDs; the new normalized record also
rejects empty operation IDs itself. Five focused empty-ID cases deliberately have
no direct private-helper parity assertion: the retained helper assumed the package
index had performed that check. The other 151 cases match the retained helper.

156 independent PHP/native expected outcomes cover all five roles, individual
required-field mutations and complete lifetime policy assembly. PHP and conversion
passed without corrections. The first native build exposed a C++ reserved word in
the test local case; renaming it to fixture fixes the harness. Production source
compiled unchanged and the second native build passes. Evidence, first PHP hashes,
failed attempt and phase/cycle timings are in results/lifecycle-import-01.

Preparation PHP and its output contract remain unchanged. Next dependencies are
record/callable metadata normalization and complete package composition, followed
by shared provider symbol integration. SHA-256 and lease ownership still belong
to package acceptance, which is not claimed by these dependency proofs.

Cumulative fast validation passes at 102 registered production files. Record import
will use a named batch result for records plus the updated type map, replacing the
prototype private by-reference array output without mutating accepted input maps.

## Native record metadata and explicit batch ownership

Record_Import preserves the prototype's scalar-only native record contract: inline
storage, zero construction, value copy, no cleanup, verified complete public fields,
exported offsets and the three required C++ traits. Each field retains an exact
eligible named scalar definition; offsets must be nonoverlapping, ordered and fit
inside measured storage. Native_Record_Layout retains target/layout identities and
validates whole-record measurements. No canonical IDs or field permissions follow
from measured layout alone. Nested native records remain outside this original
import contract; the general record model is broader.

Record_Import_Batch replaces the prototype private by-reference type-array update.
The importer copies membership, creates only changed Runtime_Type rows, and shares
unchanged accepted definitions/storage. It publishes records plus a copied type map
only after complete normalization. A later invalid record cannot mutate the caller's
original map. Returned map changes cannot alter the batch membership. These setup
copies are deliberate; a later optimization may consolidate publication ownership
without changing identity or failure guarantees.

The first PHP milestone passed 38 acceptance/layout cases. Four extra checks cover
empty and non-record batches plus invalid record indexing; all 42 pass natively.
The 38 acceptance cases match the retained importer, with PHP warnings promoted to
exceptions in its oracle for malformed private-helper inputs. Native testing caught
one test harness scope error at STAN: empty-batch checks used loop-local setup after
the loop. A typed helper called inside that scope fixed it. The first actual C++
build passes; no production source correction was needed. Evidence and timings are
saved in results/record-import-01.

Runtime preparation is unchanged. This normalizes records into a batch; final
catalog publication, package checksums/leases, broader package composition and
provider symbol integration remain pending. Next: callable bindings and metadata.

Cumulative fast validation passes at 103 registered production files.

## Callable language roles and conversion permissions

Binding_Import consumes lossless metadata and the shared Semantic_Signature model.
Call_Language_Binding replaces the prototype's anonymous [binding, default_literal]
tuple with a validated record. Missing or explicit-null optional fields retain the
prototype defaults; present wrong JSON kinds fail instead of being coerced.
Byte literals require one span input and an owned result. Echo requires one const
borrow and no result. Default-literal selection applies only to byte literals.
These permissions remain separate from provider spelling and ordinary exposure.

Conversion ingestion admits only explicit_cast and text from free functions, with
one non-span input, a result and no language binding. It compares both names and
namespaces, rejecting same-identity conversions without confusing same unqualified
names in distinct namespaces. Ordinary package callables must already have named
accepted language types; unresolved provider/parameter references are explicitly
rejected at this boundary rather than accidentally using a wrong tagged accessor.
This does not implement family specialization or a new implicit conversion policy.

374 independently expected PHP/native outcomes pass, including the role/arity/
passing/production matrix, optional fields, qualified identities, unresolved-type
rejections and result-record invariants. 370 applicable cases match the retained
private helper. First native build passes with no checker or native correction.
Evidence and phase timings are in results/binding-import-01. Runtime preparation
and the converter are unchanged. Next: physical result/parameter normalization and
full callable import, before complete package publication and provider symbols.

Cumulative fast validation passes at 104 registered production files.

## Physical callable positions and explicit cursors

Callable_Abi_Import normalizes result/parameter metadata using the shared semantic
and ABI models. Call_Result_Position and Call_Parameter_Position replace anonymous
PHP tuples and a by-reference offset. Every successful operation returns its next
physical position; failures publish no cursor update. Result records retain the
accepted named definition, semantic production, direct/hidden passing and optional
integer ABI. Parameter records retain a named semantic reference (including record
fallback), its ABI and next position. No canonical type IDs are invented.

Caller storage consumes slot zero, requires an owned result and verified uninitialized
storage, and additionally requires a live-object postcondition for a free-function
producer. Direct results preserve void versus integer distinction. Borrowed objects
retain const/mutable access; scalar borrows remain const-only. A semantic byte span
consumes pointer plus unsigned integer length positions. Exact ABI indices and
available position counts are checked before indexing. Length attributes preserve
the original exact empty/noundef contract rather than inheriting integer-attribute
trimming accidentally.

The byte-span width parser replaces regex capture plus PHP integer casting with
checked ASCII decimal accumulation. It rejects leading zeros, malformed spelling
and values beyond signed 64-bit range before arithmetic. One malformed overflow
case intentionally differs from PHP's saturating cast; accepted widths retain their
exact value. This does not promise backend support for arbitrarily large widths.

113 independently expected PHP/native outcomes pass; 112 applicable cases match
the retained private helpers. The first native build found a local named mutable,
a C++ keyword. Renaming it to is_mutable was the sole production correction; the
second build passes. First PHP hashes, both attempts and phase/cycle timings are in
results/callable-positions-01. Authoring must continue to avoid target-reserved local
names even where PHP and structural conversion accept them.

This proves physical boundaries, not full Callable_Import publication. Operation
identity/exposure validation, resource effects, language bindings and global callable
binding validation still need to be composed. Preparation PHP is unchanged.

Cumulative fast validation passes at 105 registered production files.

## Complete callable metadata composition

Callable_Import now composes physical normalization, resource effects, language
roles and conversion permissions into accepted Runtime_Callable records. It reserves
IDs and linker symbols for every operation, including unexposed lifecycle helpers;
compiler-owned exposures can override metadata names, and unknown binding IDs are
rejected. Source exposure retains the prototype's supported operation kinds, ccc /
terminate / caught-in-bridge boundary and unqualified exposure-name uniqueness.
Surplus ABI positions are rejected before any list is returned.

The consumer accepts explicit projections of package bindings: a named exposure map
and accepted source-payload IDs. It preserves required copy_out results and const
copy_in parameters. This avoids coupling metadata validation to unmigrated backend
export implementation types. The future package coordinator must derive these
projections from its accepted bindings; they are not a replacement for source-export
ownership or proof that backend preparation is migrated. No fake export classes or
provider declarations were introduced. The retained oracle uses payload-key
membership markers solely because this original consumer inspects membership only.

Callable_Bindings is the reusable fixed-set uniqueness owner, also needed later by
cross-package composition. Exact quoted tuple keys keep language role/type and
conversion purpose/source/destination identities separate. Default literal selection
is unique across types. The existing Package_Syntax ASCII spelling check is extracted
for language names and linker symbols; it remains a local check, not name resolution.

34 whole-callable PHP/native cases pass on the first native build with no correction,
and all 34 match the retained importer. Cases cover compiler overrides, implicit
operations, duplicate identities/roles/conversions/defaults, allocation effects,
source payload crossings and final ABI positions. The moved syntax helper receives
its focused native regression. Evidence and phase timings are in results/callable-import-01.

Prepared package type/storage import, bindings/export ownership, SHA-256 verification,
leases and whole-package composition remain. Source-runtime preparation stays PHP
unchanged. Next: remaining package type/storage and ownership dependencies before
provider symbol integration; callable-set acceptance is not whole-package acceptance.

Cumulative fast validation passes at 106 registered production files; the final
host stage includes all 34 retained-importer comparisons. Syntax native regression
passes all 66 outcomes on the extracted identifier helper.

## Complete typed storage protocol ingestion

Storage_Import now accepts the prototype's eight primitive roles and six source
operation names. Storage_Primitive_Schema names result kind, readonly access,
counter-parameter count and the second-descriptor case, replacing anonymous schema
tuples and the transfer sentinel -1. The importer validates semantic type/borrow/
index contracts alongside physical pointer/integer ABI. Count requires the exact
accepted counter Runtime_Type object, not merely equal measured storage.

Family acceptance retains the descriptor's opaque, noncopyable, cleanup-free,
explicit-default-construction requirements; the counter must be exposed and signed,
and an exposed void definition must exist. The complete primitive/name protocol,
root namespace rule and distinct source operation spellings remain required.
Descriptor resource ownership is forbidden. No element type, layout, canonical ID
or permission is inferred from the physical protocol. The operation index preserves
the prototype's last-entry behavior; whole callable acceptance separately rejects
duplicate operation identities before publication.

Package_Syntax::address_parts provides the shared pointer validator for separate
return-type/attribute fields. Existing address_abi delegates to it, avoiding synthetic
JSON assembly for a return slot. Its existing 66-outcome native proof and retained
attribute oracle pass after extraction.

144 independent storage-family PHP/native outcomes pass and agree with the retained
importer. These cover every role plus malformed signatures, incomplete protocols,
wrong count identity, duplicate source spellings and invalid descriptor/counter/
void prerequisites. One native cycle renamed the C++ reserved local void to
void_definition in production and probe; the second build passes. Evidence and
phase timings are in results/storage-import-01. Runtime preparation is unchanged.

Concrete workflow debt: case, mutable and void local names have each escaped cheap
PHP/conversion checks and failed native compilation. Before further package work,
add a narrowly local pre-build diagnostic at the existing converter/checker owner;
it must distinguish locals from supported promoted/managed fields and must not
become symbol resolution. This is avoidable feedback latency, not a target-language
feature request. Remaining migration work includes package types, imports/export
ownership, checksum/lease acceptance and provider symbol integration.

Cumulative fast validation passes at 107 registered production files.


## Reserved local names: early portable-source feedback

Repeated native failures on `$case`, `$mutable` and `$void` justified a narrow
converter preflight. The lexical body owner now distinguishes declared callable
inputs from new local bindings; it rejects C++ keyword locals without symbol lookup
or target-side changes. Keyword fields/promoted parameters stay accepted. Method
scopes restore the input-name set, and trait-expanded methods use the same path.

Three active source files (package syntax, scalar catalog syntax and family
contracts) and sixteen probe files received descriptive variable-only renames.
Algorithms, types, properties and retained prototype inputs are unchanged. This
removes avoidable build cycles rather than changing compiler semantics. Converter
implementation bytes already participate in the incremental output fingerprint,
so old converted output cannot bypass the new check after a tool update.

Evidence: `results/reserved-locals-01`. Readiness remains 107 production files.
Next: measured package type ingestion and its accepted-owner dependencies; complete
source export ownership remains required before claiming full package acceptance.


## Package type measurements before owner binding

`Package_Type_Import::measure` migrates the physical validation segment of the
prototype `Package_Types::types` into a named `Package_Type_Measurement`. It retains
provider-local identity, kind, size, alignment and nullable integer width/signedness.
It deliberately does not return a `Runtime_Type` or grant language/resource/lifetime
permissions; the remaining importer must bind those through their existing owners.

A bounded doubling loop replaces the alignment bitmask, and a guarded multiplication
replaces unchecked `size * 8`. This preserves the width-fit rule without native signed
overflow or PHP floating-point promotion. Signed false is retained as a present value.
As in the prototype, unexposed void measurement uses zero size without inspecting
`size_bytes`; the later exposed-void binding check must still require explicit zero.

The proof uses all six kinds, malformed/missing fields, non-power-of-two alignment,
misaligned size and limits around INT64_MAX. Retained comparisons invoke the actual
prototype importer on unexposed rows with a real catalog, not a rewritten validator.
The initial oracle harness missed a lifecycle include; one host-harness correction
fixed it after the portable PHP outcomes had already passed. No production correction
was needed. Review also tightened the rejection probe so fixture-field reads cannot
mask unexpected acceptance; a final native verification covers that test change.
Evidence: `results/package-measurements-01`.


## Ordinary package language exposure

`Package_Type_Exposure` extracts the ordinary-exposure branch from the retained
`Package_Types` handler. It consumes validated measurement plus an explicit named
binding and shared catalog. Matching integer/void bindings return the exact catalog
object; byte spans and opaque values build the same explicit lifetime/permission
contracts as the prototype. Records return no named definition yet: their existing
field normalization/materialization owners remain responsible. Qualified compiler
bindings stay distinct from producer language-name syntax restrictions.

Native-import and source-payload rows cannot pass through this owner. They require
their separate accepted native/source identity, still pending. Resource validation
applies before the kind split, so scalar/span/record exposure cannot acquire an
allocation marker merely because the opaque branch uses resources. The helper does
not allocate canonical IDs or publish into the catalog. A focused handler file keeps
this branch in the prototype's load_runtime/handlers ownership area.

PHP iteration corrected a mistaken method access to Type_Reference's existing kind
field. The retained oracle then needed its resource-enum include. Review broadened
resource rejection tests before native compilation. Evidence/timings are recorded
in `results/type-exposure-01`; complete package acceptance is not yet claimed.


## Accepted native type imports and generic requirements

`Runtime_Type_Import` preserves the prototype's accepted provider/type identity,
exact runtime type object and target provenance. `Native_Type_Import::definition`
checks physical metadata against that owner and returns its existing language
definition, without copying it or deriving capabilities from equal storage.
The package coordinator still owns target-triple/data-layout acceptance and conflicting
compiler binding routes; this helper does not claim whole-package acceptance.

The existing `Generic_Contracts` owner now also reports missing value lifetime, copy
construction or assignment from the accepted definition's lifetime policy. C++ copy
trait flags remain necessary import evidence but cannot replace semantic permissions.
Version-1 native-import metadata retains its exact provider/id key protocol, including
order. Empty object/array lifecycle metadata both match the retained decoded empty
array contract. A resource key is forbidden even when null: retained-oracle comparison
caught an initial mistaken expectation that all null markers meant absence. That
expectation and the helper were corrected before native compilation. Other nullable
markers preserve their existing isset-like behavior.

Evidence: `results/native-type-import-01`. Remaining source-export binding, full type
map publication/retention and package checksum/lease validation are still separate
migration work.


## Project/backend provenance for source exports

Two records retain the prototype's owners and folder locations: `compile/data`
contains `Native_Project`, and `prepare_backend/data` contains `Backend_Configuration`.
Their original algorithms/contracts were already portable. Adaptation removes obsolete
function imports and follows current class spelling; it does not change path or
identity meaning. No filesystem resolution, inferred project key, target default or
backend verification is added. `src-runtime-preparation` is untouched.

Project roots use the existing lexical POSIX policy: normalize separators/dot
segments, reject parent traversal and NUL, preserve every other byte. Changing roots
does not change explicit project identity. Backend, target, layout, ABI and runtime
keys must be nonempty; CPU/features may remain explicitly empty. The native proof
checks all 256 appended path bytes and reads accepted bytes back numerically, so
UTF-8 substitution cannot hide behind comparing equally transformed strings.

All 279 cases pass PHP/native and retained acceptance checks on first attempts.
Evidence/timings: `results/export-provenance-01`. These are provenance records, not
source identity projection or accepted backend layout/export publication.


## Tagged export identity keys

`Export_Type_Identity` and `Export_Argument` replace the prototype's heterogeneous
`parts` arrays with typed construction operations. Factories encode the same complete
language/provider/family/source/array tags, and argument records distinguish type
from normalized integer constants. Nested keys are embedded as tagged JSON values,
not quoted again. Only source roots carry the source flag; an array of a source type
is not itself a source declaration. Keys remain exact encodings, never hashes,
display names, path-derived project keys or local canonical IDs.

The shared checked JSON string encoder escapes slashes by default; a byte-level
post-pass removes only JSON slash escapes to retain the prototype's exact key
protocol. Unicode/control escaping remains delegated to that encoder. Named factories
retain only the finished key and source flag, avoiding the duplicate heterogeneous
parts tree. This does not claim measured memory/performance improvement. Guarded
accessors reject uninitialized default construction; source identity projection will
use factories instead of assembling PHP arrays. Public keys cannot be mutated.

The original normalized-integer requirement moves into the argument constructor;
invalid signs/leading zeroes/fractions are rejected without narrowing large literals.
Array counts remain nonnegative and serialize exactly through INT64_MAX. Initial
converter checking rejected `(string)`; supported string concatenation provides
that local formatting without converter growth. Evidence: `results/export-identity-01`.
Source symbol/provider selection, lineage validation and accepted layout/export
ownership are still separate components.


## Accepted layout and dependency provenance

The prototype's layout dependency, batch input, selected task, measured layout and
private result records now use explicit member vectors and integer-keyed dependency
hashes in their original `prepare_backend/data/layout.php` owner. Container membership
is copied by value while immutable member/definition/configuration/lineage/dependency
objects retain exact shared identity. No mutable canonical store or AST is retained.

Measured layout validation preserves positive size, power-of-two alignment, aligned
size, dense equal-length field/offset lists and strictly increasing in-range offsets.
Bounded doubling replaces the unsupported bitmask without overflow. Unlike provider
Native_Record_Layout, this contract permits an empty field list; the two owners are
not collapsed into a misleading shared validator. The existing target `is_int` is
exposed as `q_is_int` through the function map/global facade to preserve PHP carrier
rejection of string/float/bool/null offsets. No target runtime change is needed.

Layout tasks now require an explicit native-command vector (empty when unused),
avoiding an unsupported bare array default. Missing batch dependencies/field indices
raise the original logical errors through explicit guards. Proofs cover copied
membership, shared identities, metadata bounds, native predicate behavior and six
PHP-only malformed carrier cases. Native measuring, join acceptance and source export
publication remain future components. Evidence: `results/layout-contracts-01`.


## Physical ABI and source-export capability records

Backend ABI parameter/target records retain explicit LLVM spellings, extension tags
and exact selected lifecycle objects. The prototype's runtime/source lifecycle union
uses the already-proved tagged Lifecycle_Operation owner. Source-export capability
validation additionally requires a source operation, preserving the former PHP
parameter-type restriction rather than accepting an imported operation by accident.

Source role/state enums become compact codes with exact protocol-name codecs. The
nine-field semantic profile becomes a named readonly record instead of an ad hoc
array. Both move roles stay explicitly unsupported for source export; requiring one
cannot silently select copying. Task/export maps copy membership and retain exact
project, identity, accepted layout, capability and ABI associations. Implementation
and import targets remain distinct. Producer/join acceptance still owns publication;
record construction alone is not an accepted export.

The fixed source payload profile is a static `profile()` accessor because class
string constants are not in the portable converter subset. The wire spelling is
unchanged. An early reserved-local check also caught `$export` in the test probe.
These are source adaptations, not converter/runtime feature additions.

The final source_linkage record is deferred until its runtime-input and analyzed
verification owners exist; it is not replaced with mixed containers or fake types.
Source-export selection/preparation/join validation remain pending. Evidence and
correction timings: `results/source-export-contracts-01`.


## Package type composition and source payload binding

The retained Package_Types trait is composed from typed measurement, ordinary
exposure and accepted-native-import helpers, with Package_Type_Map owning private
batch construction and final binding membership checks. Package_Bindings is a
separate record in the original data folder so native import records do not pull
in source-export dependencies. Explicit empty maps replace nullable/default binding
carriers. Callables remain a distinct binding map, consumed by callable ingestion.

Project_Import::source_type consumes real Source_Type_Export records, requiring
the exact portable identity, measured size/alignment, empty lifecycle metadata and
absence of competing owners. Source payload storage becomes the record kind while
retaining the exact existing source definition. Source export generation and receipt
authorization are not implemented by this helper. Resource metadata is checked even
for unexposed rows; resource:null remains invalid under the retained resource owner.

For future optimization: opaque exposure currently repeats the pure resource
validation performed at the batch boundary. Centralizing a normalized resource value
can remove that duplicate work once complete package normalization is established;
do not weaken validation for unexposed or source/native rows. Validation order for
simultaneously malformed metadata may differ, but acceptance, identity and no partial
publication are the supported behavior.

Evidence: 38 PHP/native cases and retained acceptance comparisons in
results/package-type-map-01. First native build passed without correction. Two PHP
proof-authoring rounds corrected fixture/API spellings before the first passing
checkpoint; no production or converter correction was required.


## Runtime package data and queries

Runtime_Package keeps the prototype's normalized dataset and query responsibilities
in load_runtime/data/package.php. Typed maps/vectors replace untyped collection
carriers; explicit constructor arguments replace empty-array defaults. Private
collection fields use distinct names from query methods, and returned containers
copy membership while sharing immutable semantic records. Project_Binding retains
exact receipt bytes plus source export membership in the existing data/project.php
owner. Neither constructor implies verified artifact or receipt acceptance.

Lifecycle enumeration uses the existing tagged lifetime API with explicit null
guards and the prototype's destroy/copy/move/assign/default order. Bindings to
accepted native/source owners suppress duplicate operations; ordinary compiler name
bindings do not. Missing type/module queries retain their exception families.

The prototype's matches() and retain_bound_types() rely on PHP recursive object
equality. Those methods are intentionally not available yet: the next comparison
owner must cover names, representations, lifetime policies and operation identities,
resources, native layouts, record schemas and typed-storage contracts. Package reuse
also depends on exact manifest bytes, base catalog identity and binding/project
contracts. Package_Adapter also retains structurally unchanged callable contracts
by provider-local ID; their semantic signature, ABI, effects and exposure flags must
be compared before reusing an old callable. Equal byte layout or shared-pointer equality alone is insufficient.
Runtime_Lease and heterogeneous debug to_array() remain separate migration work;
there is no stub lease, fallback comparator or permissive reuse implementation.

Evidence: results/runtime-package-01, all 32 lifecycle policy combinations across
five binding configurations (160 PHP/native and retained comparisons), snapshot
membership/identity, missing-query errors and module selection. First build passes;
one final verification build follows a source-fixture consistency improvement.
Zero native correction cycles.


## Explicit callable equality and retention

Callable_Contracts in type_model owns immutable callable comparison: tagged nested
type references, semantic positions/results/allocation effects, ABI linkage and
transport/shapes, provider identity and source exposure flags. It compares facts
strictly, with explicit optional-state guards. Derived physical slot indices need
no separate comparison because parameter shapes and result transport determine them.
Shared object identity is only a safe early success, never the sole equality test.
Callable_Retention in the load_runtime utilities folder reproduces the adapter's
old-ID lookup and exact-contract reuse while preserving current order and coverage.
Its inputs are already validated callable lists; it does not authorize imports.
The full Package_Adapter is not yet migrated/integrated with this helper.

The first oracle used PHP == on migrated records and exposed why that is unsafe:
nullable integer tag zero compares loosely equal to null, unlike the prototype's
nullable enum object. The oracle now constructs actual retained prototype records
and applies their existing PHP structural comparison. Explicit target comparison
preserves the original distinction. This learning is also in the authoring guide.

Evidence: results/callable-contracts-01. The 14-reference and 27-callable all-pairs
matrices yield 925 PHP/native and retained comparisons. Each callable case also
checks selected object identity, unchanged inputs, added coverage and result order.
One reserved test-local checker correction and one oracle correction preceded the
first native build; production implementation was unchanged. First native build
passes, zero native correction cycles. Type and package-context comparison remain
separate unfinished work.


## Explicit lifetime and recursive operation comparison

Lifecycle_Contracts owns value comparison for the existing immutable lifetime
model. Permission scalars compare in a common integer domain. Operation lookup is
by semantic role, independent of input binding order. Exact imported identity and
source type/body identity, repeat count, linkage, convention and ordered member
plans all participate. Primitive-null and implemented member operations stay
distinct; recursive plans compare structurally without serialization or cloning.
Derived composition order follows from kind/body and is not compared redundantly.

This helper assumes the same accepted type/symbol lineage, as the retained adapter's
canonicalization boundary does. It does not make unrelated local IDs portable.
Shared identity is an early equality result, not a substitute for structural
comparison. Full definition/resource/storage and package-context comparison is
still required before integrating type retention.

Evidence: results/lifecycle-contracts-01. 441 all-pairs imported/source-plan cases,
396 valid-policy self/baseline comparisons, and five changed-role implementation
cases give 842 PHP/native and retained comparisons. First checker/PHP/oracle/native
attempts pass with no correction cycles.

Comparison follow-up: the migrated record materializer always creates a
Resource_Obligations record, including RESOURCE_NONE with no paths. The prototype
stored a nullable resource enum and a separate path list. Full definition equality
must compare these normalized facts; an absent wrapper and an explicit empty
obligation represent the same original resource/path contract. Wrapper allocation
identity must not introduce a false contract change.


## Full semantic definition comparison

Definition_Contracts composes existing representation, lifecycle and callable ABI
comparison with explicit definition metadata, ordered resource paths, exact target
layout, record field recipes/body IDs and typed-storage dependencies. Storage_Family
now provides typed membership snapshots for its primitive contracts and operation
spellings; returned maps cannot mutate its membership. Contained immutable primitive
objects remain shared. The comparator checks both key membership and values, without
requiring map insertion order to agree. Record fields and resource path sequences
remain ordered.

Absent resource wrappers and RESOURCE_NONE/no-path wrappers compare as the same
original resource/path contract. Optional native layouts and element storage remain
distinct from absence; matching physical dimensions alone does not establish equal
semantic types. Recursive descriptor/element dependencies use the immutable acyclic
model and the same accepted type/symbol lineage; no general PHP object-graph
comparison, serialization fingerprint, reflection or new canonical IDs are used.

Evidence: results/definition-contracts-01. Independent 30-definition, 18-family and
17-record all-pairs matrices give 1,513 PHP/native outcomes, including membership
snapshot checks. Seven targeted retained-prototype equality checks confirm critical
normalization/order/identity distinctions; this is not 1,513 retained comparisons.
One reserved local in the test fixture was corrected before PHP readiness. The
first native build passed without correction. Complete Runtime_Type retention and
package-context reuse are the next consumers, not yet wired by this checkpoint.


## Bound runtime type retention

Type_Retention in load_runtime utilities replaces the retained Package_Types trait's
implicit object ==/!= checks with explicit reference and full Runtime_Type contract
comparison. Storage kind/size/alignment, optional integer facts, language definition
and record declaration all participate. This composes Definition_Contracts instead
of duplicating its semantic field inventory. The private result map copies input
membership, reuses old objects only under unchanged bindings/contracts, and is
published only after every source payload binding still points at its exact export
definition. A late mismatch cannot mutate caller maps or the old package.

As in the prototype, the adapter must first establish matching provider, directory,
target and base-catalog context. This helper does not establish that boundary or
accept artifacts. The future complete adapter will own those gates; no permissive
whole-package reuse path is introduced here.

Evidence: results/type-retention-01. An 11-row comparison matrix and 48 retention
cases give 169 retained-prototype comparisons; three additional native/PHP source
binding cases cover exact identity, equal-but-distinct definitions and missing rows.
The first native build passes with zero corrections. One final verification build
follows fixture consistency improvements (separate old source bindings and matching
ordinary U row/name metadata); production implementation is unchanged.


## Package reuse context and accepted owner identity

Runtime_Package::matches now takes a Package_Context record containing the current
directory, manifest bytes, base catalog and optional binding/project selection.
This keeps the complete selection explicit and avoids a loose positional group of
optional method arguments. It is a cache query, not artifact or receipt acceptance;
the adapter must revalidate current artifacts and project receipt first, as in the
prototype. Existing isolated proof closures include the new context and reference
comparison dependencies.

Ordinary type/callable binding maps compare by exact keys and typed reference value,
independent of insertion order or wrapper allocation. Imported-owner wrappers may
be rebuilt, but their provider/type ID/target facts and accepted Runtime_Type object
must match. Source maps and project export maps require the exact accepted export
objects; project receipt bytes must also agree. Null selection and an explicit
selection remain different. The base catalog still uses exact identity.

This intentionally tightens the prototype's recursive PHP == cache heuristic for
accepted owners. The adapter separately requires current source bindings to be the
exact current project exports; structural equality must not return an old package
with different accepted owner associations. A reconstructed accepted native type or
source export produces a cache miss/re-import, even if its fields compare equal.
This may reduce cache hits when callers unnecessarily recreate accepted owners;
sharing immutable accepted owners is the intended optimization, not weakening the
identity boundary. No new language feature or producer-output change is introduced.

Evidence: results/package-context-01, 33 independently expected PHP/native context
cases covering changed bytes/catalog, map membership/reference values, each native
owner fact, exact owner identity, nullable selection pairs and input preservation.
First native build passes without correction. Full acceptance remains unfinished.


## Runtime publication schema records

`Manifest_Reader` separates producer-schema parsing from filesystem/integrity acceptance.
`Package_Target`, `Package_Pointer`, `Package_Manifest` and `Package_Metadata` replace
ad-hoc associative records at this boundary. Expected digests remain unverified strings.
The unchanged producer defines the target pair (triple/data_layout), input key, known
module formats and exact driver arguments. We require those facts explicitly; target
member order is immaterial. Missing input keys no longer match accidentally. Backslash
artifact names are rejected alongside slash/NUL/dot names for portable basename safety.
Numeric JSON object names remain strings, including artifact name "0"; no associative
JSON decode key coercion is used. Extra target fields are rejected by the closed schema.
Metadata rows remain Json_View objects until their existing typed importers consume them.

Independent schema expectations cover 77 accepted/rejected cases in PHP and native,
including ordinary/project mode and snapshot membership isolation. Negative cases fail
immediately if parsing succeeds, so later inspection errors cannot hide acceptance bugs.
First native build passed; one final test-hardening verification, zero native correction
cycles. Timings and hashes: `results/package-manifest-01`. No retained adapter parity or
complete artifact acceptance is claimed. Future optimization can fuse repeated immutable
JSON member reads; do not remove schema/identity checks or substitute lexical digest
validation for hashing exact bytes.


## Source-export validation and stable linkage

`Source_Export_Preparation::symbol/validate` and
`Callable_Contract::lifecycle_matches` now operate on the existing typed export/ABI
records. Exact capability and lifecycle-operation identity remains mandatory; an
otherwise equal reconstructed object cannot replace an accepted owner. Import ABI
parameters compare explicit type/extension values instead of PHP object equality.
Absent implementation/import objects are rejected before dereference. Validation
reads fixed records and publishes no state. Capture, production, joins and receipt
ingestion are not claimed by this checkpoint.

Role tables are validated by exact six-name membership, independent of hash insertion
order; iteration follows the canonical role vocabulary. This deliberately removes
the prototype's incidental array-key-order dependency while retaining missing/extra
role rejection. Physical parameter order remains significant. The first well-formed
LLVM DataLayout A entry determines whether the stack is in address space zero;
byte scanning replaces regex and avoids integer overflow when only zero/nonzero is
needed. It preserves malformed-entry skipping and first-valid-entry selection.

Stable export symbol encoding preserves the original bytes: ASCII alphanumeric
passes through, underscore doubles, other bytes use uppercase _xHH_. Small bounded
byte arithmetic replaces unsupported bit operators; no Unicode-text transformation
is used. Independent expected names and actual retained symbol implementation agree
for all 128 cases, including punctuation and Unicode nominal names. These are symbol
comparisons, not 128 retained whole-validator comparisons. Independent mutation
fixtures exercise all four implemented roles, stale identity, missing ABI halves,
role coverage, parameter count/type/extensions, return/convention/linkage and stack
layout rules. The retained source_exports unit supplied semantic regression scenarios;
its end-to-end build/join execution remains outside this component's proof.

Three checker correction cycles preceded PHP readiness. Two native builds reached
the first pass: one correction to a test's bare vector reassignment; production
remained unchanged after PHP readiness. Timings/hashes/failure logs live in
`results/source-export-validation-01`. Optimization follow-up: retain the direct byte
encoder and fixed role traversal; avoid adding dynamic serialization/equality to
these validation paths.


## Project receipt authorization

`Project_Receipt::validate` migrates the receipt acceptance portion of the retained
`Project_Import` into a dedicated compiler-side protocol owner. The existing
`Project_Import::source_type` still owns binding an accepted payload to its exact
source definition. The receipt owner returns a typed symbol-to-current-operation map;
it does not reconstruct exported owners from JSON or publish partial authorization.
Receipt bytes must match the captured Project_Binding before parsing. Each current
export passes Source_Export_Preparation validation before its receipt row is accepted.
Source project/key/profile, measured size/alignment, target facts, complete role/state
coverage, all nine semantic effect facts and ordered physical ABI parameters agree.
Every required artifact import must be one of those same authorized operations.

JSON member order is not semantic; exact membership and values replace associative
array strict equality/order checks. Extra operation, semantics, ABI and parameter
fields are rejected by explicit closed shapes; the prototype was inconsistent about
extra top-level operation fields. Target uses the already-proved closed target schema.
PHP producer empty keyed maps may appear as [] or {}; nonempty lists are rejected.
The four artifact variants remain mandatory. Required imports are deduplicated across
variants and returned in sorted symbol order, retaining exact current operation
identity. An explicit insertion ordering pass preserves prototype ksort behavior;
these names have nonnumeric ASCII prefixes, avoiding PHP numeric-string comparison.
This boundary is not a hot analysis loop. If import counts become large, replace
that quadratic ordering pass with a shared proved sorting helper in an optimization
pass; do not change accepted membership or identity.

122 independently expected PHP/native outcomes prove accepted/rejected receipts,
including all implemented roles, altered effects and ABIs, unknown imports, source/
layout mismatch, exact receipt bytes, empty maps, subset union, sorted results and
current operation identity. Retained producer and ABI declarations were inspected;
no retained whole-validator execution parity is claimed. Fixtures initially used
"none" where the existing ABI protocol requires the empty string; corrected before
PHP readiness. One earlier copied runner path was also corrected. First native build
passed with zero native correction cycles. Evidence/timing: `results/project-receipt-01`.

This does not verify artifact bytes, inspect bitcode, acquire leases, produce exports
or integrate the complete Package_Adapter. src-runtime-preparation remains unchanged.


## Package reservation ownership

Runtime_Lease replaces mixed PHP resources and its explicit resource destructor with
a private named scpp\Lock_Reservation. The framework owns the PHP File_Lock/native
file_lock_handle difference; the converter merely preserves a literal named class.
Construction transfers ownership, invalidating the acquisition token's aliases,
instead of leaving an external raw resource alias able to unlock the package. The
lease retains exact Runtime_Package identity and exposes active/idempotent release.
Underlying token final-reference cleanup covers normal scope exit and exceptions;
no portable destructor/finally language extension was added. Acquisition/validation
must stay in a private scope until transfer. Native allocation and handle cleanup
remain runtime responsibilities, not ad-hoc compiler resource emulation.

Eighteen PHP/native outcomes use independent-process flock attempts and check stable
lock inode/content. A checker correction switched an unsupported uninitialized named
field to supported private constructor promotion. PHP's Xdebug develop mode delayed
exception-frame object destruction; an isolated reproducer confirmed cleanup with
XDEBUG_MODE=off. The proof records that environment and separately exercises native
unwinding. First native build passed; zero native correction cycles. Timings and
framework fingerprints: `results/runtime-lease-01`. No package artifact acceptance or
new backend/platform support is claimed; src-runtime-preparation remains unchanged.


## Package semantic composition

Package_Composition now connects the proved type map, context-gated bound-type
retention, record import, storage families, catalog composition, callable import and
callable retention in the retained adapter's dependency order. Package_Composition_Input
carries the fixed selection, schema-normalized manifest/metadata, optional previous
package and catalog content key. Package_Contract_Set contains typed contracts only;
it is not a Runtime_Package, lease, or artifact verification certificate. The future
adapter must compute the exact SHA-256 catalog-content-key from base key plus manifest
bytes after artifact acceptance. A supplied test key proves composition wiring, not
that missing hash implementation. No weak replacement key/hash was introduced.

Record_Import now consumes the normalized Package_Target directly, avoiding JSON
re-encoding just to pass two already-typed target facts. Its isolated native closure
and fixtures were updated; all 42 regression outcomes and retained checks pass.
Record_Import_Batch's updated type map is consumed explicitly before callable import;
records are published with that map's exact declarations. Storage-family vectors
become typed ID-keyed maps, and their descriptor definitions remain excluded from
ordinary catalog rows. Base catalog/default identity is preserved when no extra
catalog contracts are required. New catalogs preserve base records and bindings.

Current native imports must match target facts; source payload bindings must refer
to the exact current project export before composition. Previous bound-type identity
is reused only for the same directory/base catalog/provider/target; callable reuse
uses complete contract equality. The result is privately assembled, preserving the
fixed input catalog. Manifest matching/file rechecking and receipt authorization are
still adapter responsibilities, not shortcuts inside this pure component.

Twenty-six independent PHP/native composition outcomes cover empty/scalar/opaque/
byte-span/record/callable packages, full eight-primitive storage-family composition,
duplicate and malformed rows, project-mode rejection, catalog defaults/record identity,
matching-versus-foreign bound-type reuse and callable identity retention. These are
composition outcomes, not a full filesystem/provider pipeline proof. First native build
passed, plus one native record-import regression. One reserved-local preflight fix
and one constructor-metadata fixture correction preceded PHP readiness. Timings and
source hashes: `results/package-composition-01`. Optimization follow-up: profile repeated
metadata indexing across the import owners before sharing an index; preserve validation
ownership and all duplicate checks. src-runtime-preparation is unchanged.


## Source-export work, reuse and batch acceptance

Callable_Preparer::prepare_lifecycle now produces a typed task/result pair and a
pointer ABI target retaining the exact accepted lifecycle operation. It shares the
existing Callable_Contract validator for stack/ABI policy. Source_Export_Work prepares
stable external imports from those targets, without copying lifecycle field algorithms.
Unavailable roles retain their capabilities and no ABI. The fixed-task worker and
reuse selection are separated from the existing schema/identity validator; capture
from type/layout stores is not implemented by this extraction. Ordinary callable
preparation is also still pending.

Reuse requires the exact accepted layout plus equal project roots/key, portable
identity keys/source flags, complete dependency identity map and complete capability
facts. Explicit Lifecycle_Contracts comparison replaces PHP recursive equality for
operation plans in that same lineage. Map order is immaterial; role, state, reason,
optional presence and every operation fact remain significant. A full selection
skips previous-contract inspection explicitly, preserving the original short-circuit
behavior. Missing prior results select work normally. Removed demands are excluded.

Source_Export_Join accepts arbitrary worker completion order but requires exact
selected/current task association. It rejects missing, duplicate, foreign, stale or
ABI-invalid results before returning a private map, rejects duplicate portable keys
or mismatched local IDs, and preserves unchanged previous result objects. It follows
the active compiler's concrete typed join style; no callable-polymorphic interface
or fabricated backend/type state was introduced.

Twenty-four independent PHP/native scenarios cover first/full/incremental builds,
removed demands, reversed results, reconstructed equal tasks, changes to project/root/
identity/dependency maps/capability state/reason/operation/layout, invalid selection/
result batches, stale prior work, malformed ABI and nonzero stack rejection. Produced
ABIs retain exact operations and pass the existing validator. Prior task snapshots
remain unchanged. These are worker/join proofs, not source-body execution, layout
capture or native module publication. First native build passed; one final verification
added the no-prior-export case, with zero native correction cycles. One preflight fix
replaced a nested isset key expression with a local. Evidence/timing:
`results/source-export-work-01`. src-runtime-preparation remains unchanged.


## Selected layout dependency capture

Layout_Capture extracts the prototype layout preparer's pure graph capture/subset/
comparison/currentness responsibilities. It consumes the actual Type_Store; it does
not fabricate types, measure storage or execute tools. Iterative enter/finish visits
follow structure fields and fixed-array elements only. Pointer targets and unrelated
canonical types are not storage edges. Active private rows detect cycles, completed
nodes deduplicate shared children, and accepted Layout_Dependency rows retain exact
definition/member/child identity. A logical stack depth replaces PHP array_pop/unset;
finished scratch rows remain private until capture returns. No recursive traversal
or dynamic tuple arrays were introduced.

Layout_Ids uses bottom-up merge sorting plus unique filtering, giving deterministic
numeric root/dependency order without scanning the entire canonical store or adding
quadratic insertion sorting to capture. Subset preserves requested root membership
while sharing the selected reachable nodes. Dependency comparison is iterative and
assumes canonical per-ID nodes within an accepted lineage; fields are ordered and
exact, child map insertion order is not semantic. Reuse also checks the known node's
ID, so a malformed reuse hint is rebuilt instead of imported under another ID.
Current layout reuse requires exact lineage, configuration and definition before
comparing the complete reachable dependency graph.

Traversal records initially targeted compact inline storage, but the portable value
record contract currently permits only uint32 integer fields. Keeping full-width
canonical IDs takes priority over an implicit narrowing assumption: ordinary named
records are used for visits/pairs. A future optimization can extend/prove full-width
inline records or introduce an explicitly bounded ID contract. The scratch maps and
queues are O(V+E) in reachable work; sort cost is O(V log V). No hot-path allocation
or timing speedup claim is made by the graph correctness proof.

Forty-two PHP/native scenarios use independent expected reachability and affected-
ancestor sets, including deterministic generated DAGs, shared/repeated children,
array storage, pointer leaves, reachable cycles, ignored unreachable cycles, bad root
IDs, empty roots, identity reuse/subsets and a 513-node chain. Replacing one definition
rebuilds its reachable ancestors while preserving unaffected identities. Measured-layout
records in the currentness checks are explicit fixtures, not target measurement evidence.
First native build passed; zero native correction cycles. One preflight correction
changed the traversal representation; three host fixture corrections supplied valid
lifetimes, integer signedness and pointer field eligibility. Evidence/timing:
`results/layout-capture-01`. Layout measurement/selection, source identity projection
and complete export capture remain unfinished. src-runtime-preparation is unchanged.


## Layout selection and backend storage spelling

Layout_Selection owns the prototype layout preparer's pure work selection. Its
input is the captured dependency graph, exact backend configuration, previous
layouts and explicit tool arguments. Full selection never examines prior layouts;
incremental selection reuses only Layout_Capture::current-approved layouts. Tasks
retain exact input/configuration/definition/member owners and caller root order.
Tool invocation and measurement-result acceptance remain separate unfinished work.

LLVM_Storage separates the existing LLVM scalar/storage/compound vocabulary from
the larger LLVM_Types utility. It also owns the pure opaque-storage reachability
query previously on Native_Layout. Void has a scalar spelling but no storage;
unsupported pointer/span/signature storage must not silently become an LLVM type.
Opaque bytes preserve size in spelling, while the separate alignment flag requests
the native witness. No target padding/alignment is computed in PHP.

Compound spelling now uses iterative postorder and typed private maps/visit records
rather than recursion. It memoizes each child spelling; repeated fields still appear
in their declared order. A logical stack depth reuses slots. New vector slots must
append explicitly: native bounds checks exposed PHP indexed-write growth in the
first executable proof. This is an authoring correction, not converter inference.
The target STAN also rejected nested early-return scalar branches with a terminal
throw; exhaustive assignments and one return preserve the same behavior.

The memoized strings can retain substantial intermediate text for deep/expansive
shapes; a future optimization can use a streamed spelling representation if profiles
justify it. This slice makes no speed or memory reduction claim. Native layout
witness generation, command execution and layout joins are not covered here.
Evidence and correction/timing records: `results/layout-selection-01`.

Forty-two PHP/native outcomes pass: independent expected LLVM text and alignment
requirements cover all five floating formats, varied integer widths, empty records,
zero-length arrays, shared generated DAGs and a 201-node chain. Selection checks
initial/full/partial reuse, changed configuration and foreign lineage. Void scalar
spelling and explicit invalid storage rejection are included. Three native build
attempts were needed (STAN rejection, executable bounds failure, pass), with two
correction cycles; one checker correction removed unsupported decrement syntax.


## Native layout witnesses and shared storage traversal

Native_Layout now produces a Layout_Witness containing generated C++ source and
an explicitly typed primitive-ID-to-LLVM-spelling map. This replaces the prototype's
nullable string plus by-reference output array with one private result record.
Empty source means no reachable opaque shell needs a native alignment witness;
primitive facts are still retained, matching the original traversal's output.
The record is not a measured layout and grants no publication authority.

Layout_Order owns child-before-parent storage traversal shared by LLVM_Storage and
Native_Layout. It visits each reachable canonical ID once, preserves declared field
order, rejects cycles and never follows pointer targets as inline storage. This
small refactor keeps stack growth/bounds and cycle behavior in one owner. It retains
full-width IDs and the previously proved explicit append/overwrite discipline.

Generated types preserve the prototype: unsigned _BitInt integer witnesses,
alignas byte-array opaque shells, fixed-array aliases and field-ordered structs.
Only sizeof/alignof/offsetof constants are emitted; no instances, special members
or source lifecycle behavior are introduced. Unsupported constituents, including
floating fields, still reject in this native-shell path. Ordinary LLVM storage
spelling supports floats independently; this migration does not widen shell support.

The proof folds witness constants with Clang 18 for x86_64-unknown-linux-gnu and
compares size/alignment/offsets against independent bounded layout expectations.
Integer witnesses are also compared with separately folded LLVM GEP constants.
No target executable is run. These tests exercise tool output in the host harness;
the migrated compiler's process invocation and measurement-result acceptance remain
unfinished. The target scope is explicit, not a cross-platform ABI claim.
Evidence/timing: `results/layout-witness-01`. src-runtime-preparation is unchanged.

Thirty-five PHP/native cases pass on the first native build, including shared
children, nested arrays/records, empty records, no-opaque results, all five floating
rejections and a 201-node chain. Twenty-seven generated witnesses have independent
Clang layout checks; primitive LLVM checks run wherever integers occur. The existing
42-case layout-selection native regression also passes after the traversal refactor.
One preflight correction rewrote a computed keyed literal as explicit assignments;
no native correction cycles were needed.


## Layout measurement-output boundary

Layout_Facts owns the small protocol reader for folded i64 probe globals.
Layout_Measurement checks the selected opaque-alignment policy, requests size,
alignment and ordered field offsets, compares every native integer primitive with
its LLVM counterpart, then constructs a private Layout_Result with exact task,
configuration, definition, field, lineage and dependency provenance. Storage_Layout
continues to own size/alignment/offset invariants. The layout join still owns batch
acceptance and publication; these records do not bypass it.

The prototype's repeated regex searches and FILTER_VALIDATE_INT become one byte-line
scan, typed requested/found maps and checked decimal accumulation. Required values
are canonical nonnegative decimals in 0..9223372036854775807, checked before arithmetic;
zero remains present and is interpreted by the owning measurement invariant. Digits
are subtracted from ASCII before adding to the accumulator so the maximum valid
integer never creates an overflowing intermediate. Unrequested globals are ignored.
This is deliberately a probe-output reader, not a general LLVM grammar validator.
It accepts unquoted linkage/visibility/address-space/thread-local prefixes and trailing
comma metadata/comments.

Target declarations must be exact, unique lines, allowing CRLF. Comment text or a
matching substring no longer authorizes the selected target. Duplicate requested
facts, malformed numeric prefixes such as `1.0`, unresolved expressions, negative
values, and overflowing integers reject explicitly. This is intentional boundary
hardening; valid observed Clang output is preserved. LLVM_Text::quote centralizes
byte-preserving LLVM escaping, including quote/backslash, control and non-ASCII bytes.
It will also serve the forthcoming probe-source producer.

Seventy-six PHP/native outcomes include the 27 actual Clang outputs saved by
`layout-witness-01`, matching primitive LLVM outputs, ordinary non-opaque storage,
malformed/missing/duplicate declarations and facts, primitive mismatch, offset and
alignment rejection, CRLF, missing final newline, integer maxima and escaped target
identities. Private result identity is checked separately from measured values.
Negative tests flag unexpected operation success before inspecting expected values.
First native build passed with no checker, host or native correction cycles. Review
then added valid addrspace/thread-local prefix cases and a final native verification;
this preserves valid LLVM declaration forms beyond the captured x86_64 corpus.
Evidence/timing: `results/layout-measurement-01`.

The compiler does not yet launch its measurement commands or join the resulting
batch. Those are the next dependencies. src-runtime-preparation remains unchanged.


## Layout batch acceptance

Layout_Join migrates the prototype's concrete selected-task/result acceptance owner.
It builds a private candidate only after checking current root membership, exact
definition/configuration/input identity, selected field count/order/identity and LLVM
spelling, and opaque alignment policy. Result membership must match the exact selected
task object; equal reconstructed tasks, duplicate results and incomplete batches
reject. Returned layout records must preserve selected definition, target, lineage,
dependency and field owners and the proper ordinary/aligned storage spelling.

Explicit typed vectors/maps and identity loops replace PHP loose/dynamic container
operations. Dependent map access uses separate guards. Dense PHP task-field carriers
are checked by ordinal before indexed access; native vectors already supply this
shape. The join follows the current concrete typed-join convention, not a new shared
polymorphic contract. It changes no cross-stage interface.

Candidate membership follows current roots regardless of completion order. Current
unselected previous layouts retain exact identity; removed roots disappear. Reuse
requires Layout_Capture's complete currentness check. As in the prototype, previous
layouts are assumed to have passed their original acceptance; currentness alone is
not a new measurement certificate. Native provider layout target, size, alignment,
field count and every offset must agree for selected and reused records alike.
Failure leaves all input maps/objects unchanged because candidate state stays local.

The proof exercises initial/full, reverse completion, partial selection, reuse,
removed/empty roots, opaque storage, provider agreement, distinct target/layout/size/
alignment/count/offset disagreements, selected/result ownership corruption, missing
and duplicate batches, and stale previous lineage/configuration. Provider-offset
fixtures hold size equal so the offset check itself is reached. These are explicit
accepted-measurement fixtures; this join does not itself measure target storage.
One host fixture correction aligned record names and member field vocabulary with
the existing canonical store. First native build passed with no native correction;
a final verification expands initial-empty-store and field-type-count coverage.
Evidence/timing: `results/layout-join-01`. Compiler-side tool execution and complete
coordinator integration remain unfinished; src-runtime-preparation is unchanged.


## Compiler tool execution through the managed process owner

Tool_Run migrates the synchronous run/ready/result/close behavior used by the
prototype backend. It consumes an explicit command vector, binary input and positive
integer deadline in milliseconds, then returns exact stdout or throws a diagnostic.
Executable resolution remains the selecting toolchain's responsibility. The helper
accepts absolute executables, literal argv and inherited cwd; there is no shell
interpolation. The prototype's default ten seconds will be supplied explicitly by
worker callers. Stderr is preserved rather than trimmed.

The already proved process token now owns private streams, process groups, timeout
tracking, descendant termination and reaping. Reimplementing proc_open/setsid/clock
logic in compiler PHP would duplicate that owner and obstruct conversion. Compiler
code closes explicitly after collection and on supported runtime-error paths; status
snapshots survive close. The original shared tool_process service and PHP runtime
preparation stay unchanged. Layout_Task still retains its old launcher field for
now; worker integration must remove that obsolete selected fact rather than silently
pretend to execute it through this managed runner.

A single dt_sleep_ms framework mapping permits one-millisecond polling waits without
busy spinning. Its PHP implementation uses bounded quotient/remainder conversion to
seconds/nanoseconds and resumes interrupted sleep. Native code calls the existing
datetime module API. No general resource-field support, converter inference or new
Simple C++ runtime functionality was added.

Sixteen PHP/native scenarios cover exact binary input/output, literal shell-looking
arguments, large stderr/stdout before input consumption, nonzero status including
127, signal exit, exec failure, relative/empty command rejection, mandatory deadlines,
real Clang IR folding, and descendants after success/failure/timeout. An independent
host process-state check requires each recorded descendant to be gone or non-running
(zombie awaiting its external reaper); no live child is accepted as cleaned up. The
first native build passed; final verification tightens unexpected-error rejection
and saves explicit cleanup observations. Framework source hashes accompany evidence
because the new helper is outside the production-file count.
Evidence/timing: `results/tool-run-01`. Measurement-worker integration is next.


## Complete selected layout-measurement worker

Layout_Probe produces LLVM size/alignment/offset constants and separate integer
primitive queries from the selected snapshot and exact backend configuration.
Layout_Worker composes that source with Tool_Run, Native_Layout and
Layout_Measurement. Ordinary storage invokes the LLVM command once. Aligned storage
invokes the native shell command and invokes LLVM primitive verification only when
integer constituents are present. Each invocation receives an explicit positive
millisecond deadline; this preserves the prototype's per-command timeout boundary,
not a new aggregate deadline. No target machine code is executed.

Native output target headers are checked before launching the optional second tool,
retaining the prototype's early failure behavior. Missing native commands and stale
alignment policy reject before spawning. Private measured results still require
Layout_Join acceptance; the worker does not publish a global store or bypass provider
layout compatibility. Discovery of canonical executables, toolchain configuration
fingerprints and coordinator scheduling are separate unfinished work.

Layout_Task and Layout_Selection no longer carry a setsid launcher string. Process
group/session ownership now truthfully belongs to the managed process API; retaining
or silently ignoring that field would describe an operation the worker no longer
performs. This local prepare_backend model change updates its existing contract,
selection, measurement and join proof call sites. Preserved prototype code and the
PHP runtime-preparation service are unchanged.

Twenty-three PHP/native scenarios run real Clang on x86_64 for integer/floating,
array/record/nested and opaque layouts, then join the measured result and prove
incremental selection performs no new work and retains exact identity. Fixed expected
size/alignment/offsets are independent of Clang output. A transparent exec wrapper
records the actual selected tool order. Failure cases cover missing commands, policy
mismatch, target changes, primitive disagreement, tool failure and timeout. Opaque-only
storage proves the LLVM command is unnecessary. The native target header failure
proves no second command is launched. First native build passed with no correction
cycles; evidence/timing: `results/layout-worker-01`.

Witness rendering is currently repeated when measurement validation derives its
required primitive facts. This is correct and bounded by the selected graph, but an
optimization pass may retain one explicit probe plan across execution/validation if
profiling shows it matters. It is not a reason to loosen validation or claim the full
compiler pipeline is ready.


The final worker integration proof additionally changes an integer leaf from 8 to
64 bits in a forked canonical store. It remeasures the containing record from size 8,
alignment 4, offsets [0,4] to size 16, alignment 8, offsets [0,8], while retaining the
old snapshot unchanged. This exposed a separate intermittent PHP launch handshake
race later in the fixture sequence: READY was written before the child opened the
ack FIFO, and the parent could write G and close the final reader/writer before that
open. The acknowledgement vanished and both processes waited indefinitely. The
runner's 240-second outer deadline caught it; it was not a native build failure or
an incremental-layout defect.

The PHP framework now flushes G and retains its ack writer until control EOF confirms
exec. A forced interleaving reproduces the old hang and passes after the fix. The
permanent process_launch_ack regression holds the child before its ack-reader open
until the parent reaches exec-confirmation reading; success, exit 127 and exec failure
all pass. It is registered in the cumulative fast loop. The existing PHP process/lock
suite also passes with the required local IPC sandbox exception. No Simple C++ target
code, protocol surface or runtime-preparation code changed. Native correction cycles
remain zero; this is a recorded PHP framework correction during final integration.


## Provider declaration carrier and retention

The prototype's five-way external-declaration union now has one closed typed owner,
`collect_symbols\Provider_Declaration`, with exactly one authoritative callable,
storage family, storage function, generic family declaration or generic method.
Checked accessors preserve the original shared objects; provider/name/namespace
facts are derived from those objects. This carrier creates no frontend, syntax IDs,
accepted package or body. Source/provider integration in Symbol_Record and the
namespace-aware Symbol_Store is the next dependent change, not completed here.

Retention keeps callable and family owner identity exact. Fresh storage-function
wrappers may retain an old declaration only for the same family and role; fresh
generic-method wrappers require the same family declaration and operation object.
Provider/id/exposed-name/namespace also agree, so malformed or renamed wrappers
cannot recover stale metadata. This is deliberately stricter than the prototype's
wrapper shortcut, which relied on its producer constructing consistent metadata.
The future symbol lookup still owns scope/category and stable symbol-ID retention.

The native target cannot return `$this` as an ordinary shared class handle. The
first native attempt exposed that mismatch. Retention therefore accepts explicit
current and previous handles in a static operation, preserving ownership without
reconstructing objects or changing generated code. Two builds reached the first
native pass (one correction); a final verification adds mutable/missing/out-of-range
receiver coverage. All 67 PHP/native outcomes pass on the pinned target. Evidence:
`results/provider-declaration-01`; timing preserves the original PHP-ready checkpoint
and notes that the stabilization interval includes the user's chunk-estimate discussion.

The proof covers payload exclusivity/access, metadata, exact-owner retention,
reconstructed owners versus member wrappers, changed owners/roles/exposure and
receiver passing. Storage fixtures test the carrier, not complete provider-package
acceptance. No converter or runtime-preparation changes are involved. A later union
or layout optimization may reduce the five nullable handle fields, after this
closed variant contract is integrated and profiled.


## Shared source/provider symbol origins and qualified indexes

Symbol_Record now contains exactly one Source_Declaration or Provider_Declaration.
The source carrier owns the actual frontend and compact Declaration_Fact; provider
records never carry empty/fabricated syntax. Checked source_frontend/source_fact and
provider accessors replace direct source-only field reads across existing consumers.
Semantic kind, template status and receiver constness are available independently
of source presence. Ordinary published records remain immutable by authoring discipline;
this does not introduce a new deep-freezing or copy-on-write mechanism.

Symbol_Store keeps dense rows and stable IDs. Its name key now retains the prototype
byte-length-prefixed namespace plus owner and kind; only source records enter file/entry indexes.
All common/source/provider ownership checks precede mutation. A provider method must
refer to the exact generic family carried by its existing parent symbol; same-name
reconstructed families do not substitute. Namespace arguments are explicit because
the converter does not currently accept ordinary method defaults. Existing source-only
collect delegates through one shared with_providers implementation using an empty typed
vector; there are not separate collection algorithms.

The collector consumes a fixed ordered vector of authoritative provider declarations,
with family declarations before their methods. Accepted package/family consumers will
supply this vector; this boundary does not accept or authenticate package artifacts.
It retains exact unchanged symbols, reuses regenerated member wrappers under their
proved owner rules, reports removals, and keeps stable IDs for replacements at the
same scope/category/name, including source/provider origin changes. Source diagnostics
retain their existing path/span behavior; project/provider collisions reject the private
candidate without modifying the baseline.

Entry checks and resolution task selection/publication now count actual source owners.
A provider cannot become a source worker task or a Symbol_Resolution. Source calls can
still bind provider symbol IDs. Explicit provider template arguments use the declared
generic-family arity (one for the prototype's storage/function forms) and type roles,
without fabricated formal-parameter nodes. The source application's exact declaration
association participates in incremental validity, so a reconstructed provider template
owner invalidates its dependent resolution.

The first focused native build passed 18 integrated scenarios without native correction.
The expanded 23-scenario PHP proof additionally covers template arity errors, generic
family references, provider-template dependency invalidation and source/provider
replacement while retaining IDs and old snapshots. All 23 scenarios pass final focused native verification;
cumulative native regression status is tracked in `results/symbol-origins-01`.
The existing source collection's 97 outcomes remain unchanged. Ready-file counts do
not increase: this is integration of existing owners, not another group of leaf records.

Remaining provider work includes provided-record Name_Binding identity/lookup/reuse,
prepared-package-to-declaration orchestration and full symbolic template workers.
Package artifact hashing/executable verification and end-to-end coordinator execution
remain separate unfinished dependencies. No prototype language functionality, v0.1
code or src-runtime-preparation implementation is changed.

Consolidation found the same source-only boundary in Template_Set: provider families
do not require a source definition permission result. The prototype origin guard is
restored, with a 24th integration scenario contrasting provider and source templates.
The cumulative native suite must prove this final source state before commit.

Optimization note: Source_Declaration currently adds one ordinary shared-class
allocation per source symbol and checked access through that handle. It keeps compact
Declaration_Fact fields inline inside the source payload and avoids pretending a
provider has syntax. A later measured optimization can use dense origin payload
tables/indices or a proved tagged value layout; do not flatten origins back into
always-present fake fields merely to remove this allocation.

The broad native gate caught stale dependency packaging in the older provider-family
proof: Generic_Contracts now reads Named_Definition, but that harness had not gained
the definition/storage/callable source closure. The fixture dependencies are corrected;
no target or production behavior is changed by this repair. All 27 completed native
stages had staged production bytes matching the current tree, so their proofs are
retained and execution resumes at the failed stage instead of rebuilding them.

The final 24-scenario integration proof and all 68 registered native stages pass.
The cumulative certificate audits current production bytes, rather than treating
older success reports as sufficient evidence after shared-model changes.


## Provider-record bindings

Name_Binding restores the prototype's provided-record category using a distinct
nullable Record_Declaration handle. Constructor validation permits exactly the
payload appropriate to each tag/role; normalized provider records have no invented
numeric symbol or canonical type ID. same_target compares exact record identity,
ignoring occurrence position. A structurally equal reconstructed record is a changed
dependency, while rebuilding a catalog around the same accepted records permits reuse.

Declaration_Lookup receives an explicit namespace and tries named definitions,
normalized records, then source structs. Existing source callers pass their owner's
namespace. Source-struct collision checks and incremental validity now consider both
catalog definition categories. Missing or replaced records invalidate dependent
source resolutions; unrelated record replacement does not.

Nineteen focused PHP/native scenarios and the affected 40/320/182 native regression
suites pass. First native build passed, with no native correction. Timing preserves
the original PHP-ready checkpoint. No converter, target or runtime-preparation changes.

This adds one optional shared handle per binding. A later tagged-layout optimization
can reduce variant storage after profiling; do not erase the distinct record identity.


## Symbolic template interpretation

The prototype Terms algorithms now interpret real parser/resolution snapshots using
explicit Type_Term variants. Annotation traversal uses a compact Annotation_Visit
continuation stack with separate used length and append/overwrite operations; a typed
node-to-term map caches completed subexpressions. Formal slots retain their declaring
symbol identity through substitutions. Constant provenance remains symbolic; no
constant evaluation or canonical type creation is introduced. Type_Term.source_id is
renamed symbol_id because applications can refer to provider declarations as well as
source declarations. The source() factory still constructs a source-defined named type.

Exact declaration and binding dependencies are deduplicated while preserving first
read order. Missing/stale bindings reject before interpretation. Source field lookup
uses the shared struct-member cursor and applies receiver substitutions; extent
presence creates the prototype's symbolic array term without evaluating the extent.
Method lookup uses the receiver's exact declaration scope and qualified name index.
Provider members use their actual declaration owner, never fabricated source syntax.

Generic forwarding, family argument baseline, default construction and whole-provider
value restrictions retain the prototype policy. Provider signature mapping selects
formal arguments, the receiver, or exact catalog definitions/records. Normalized
metadata validation remains the producer's responsibility; unsupported/missing mappings
are internal LogicExceptions, not user semantic failures.

Terms captures an immutable Template_Diagnostic (path, span, reason) before throwing
the supported RuntimeException for semantic rejection. The future worker must inspect
that diagnostic rather than treating arbitrary runtime exceptions as user errors.
The worker also owns absence checks before default_construction/provider_value_use:
these methods now accept a present Type_Term, and absent expression types remain a
no-op at the caller. This avoids unsupported nullable ordinary method parameters.

The first native attempt stopped at STAN's return-completeness check. Field selection
now resolves its node before producing a final typed return. Provider-reference dispatch
has a final mapping call, whose helper has a final typed return. Rejection behavior is
unchanged; no STAN bypass or generated-code patch was used. Thirty-two PHP/native
scenarios pass on attempt two, including 100-level nested annotations, substitutions,
record identity, field/method access, provider requirements, construction, signature
mapping and attributed errors. The existing 40-outcome symbolic-model PHP proof and
its retained comparison oracle also pass. Preserve all phase timings and both native
attempts during installation.

No full template body worker/join or compiler pipeline is claimed. Runtime preparation
and the target toolchain remain unchanged.

After integration, the existing 40-outcome symbolic-model regression and retained
oracle passed again, including one separate native verification build.


## Definition-level template body checking

The preserved Template_Worker algorithm now consumes the migrated parser, symbol,
resolution and Terms owners. It checks fields, locals, both ordinary branches,
loops, assignments, returns, output, symbolic expression operands and declared calls.
It does not create concrete instances, evaluate constants or claim executable body
or lifetime validation. Retained generic_contracts.php supplies the positive source
and fourteen definition-level rejection cases; the concrete-instantiation lifetime
case stays outside this component's proof.

Dense one-based binding IDs map to zero-based Expression_Type vectors. Nullable
expression types remain explicit inside the named record: compatibility checks take
records, and guard absent types before calling Terms. This avoids unsupported nullable
ordinary parameters without collapsing unknown ordinary types into generic identities.
Postorder expressions use inline Expression_Visit records and a used-length stack;
statements use an integer stack. Both preserve source order without PHP array_pop,
array_reverse, unshift, map callbacks or per-node ad-hoc tuple arrays. A later measured
optimization can replace the sparse node-ID result map with dense scratch storage.

Calls consume declared signatures, never callee bodies. Source receiver substitutions
and constness carry through field/index access. Provider calls omit the actual declared
receiver position, retain exact semantic type mappings and query formal requirements;
ABI details cannot add generic permissions. The proof includes a receiver in slot one.

Source_Lifecycle owns reserved method spelling and returns the existing lifecycle tags,
with LIFECYCLE_NONE for absence. Only role interpretation is migrated here; source body
normalization and concrete signature validation remain required work in resolve_types.
The template checker reuses Lifecycle_Roles::composition for implicit field construction.

Template_Worker::create supplies fresh Terms scratch to a promoted constructor field.
This avoids unsupported uninitialized typed properties. Workers are one-shot; exact
source owner and binding snapshots are checked before body traversal. Semantic failure
retains Terms' source diagnostic; stale/internal failures do not acquire a fabricated
source diagnostic. Twenty host-only assertions verify input snapshot purity and stale
owner/binding rejection. Production changes do not touch runtime preparation or native
target code.

Initial checker corrections were a C++-reserved test variable, unsupported <=/helper
spelling, an uninitialized typed property and decrement syntax. PHP fixture corrections
were constexpr branch spelling and a mismatched concrete-callable ABI parameter count.
Native stabilization is recorded separately in the saved timing/result evidence.

All 46 PHP/native outcomes and 20 host-only assertions pass. Native attempt one
passed without corrections; see `results/template-body-01`.


## Template selection, reuse and batch acceptance

Template_Plan now owns fixed symbols, bindings, catalog and previous permissions.
It selects only source templates; provider contracts require no fabricated source
checking task. Unchanged exact dependencies retain the previous Definition_Result;
full rebuilds select every current source template. Additions and deletions derive
membership from current declarations rather than copying the previous map.

Template_Join accepts completion in any order, but requires the exact selected task,
unique complete results, current dependencies and positive work with both owner
provenances. Definition_Result::has_owner_provenance keeps that query with the owner
of its private dependency lists. Invalid batches construct no published permission
set and do not mutate previous results. The plan cannot be caller-populated with
forged or duplicate tasks; malformed external results are still rejected by the join.

Template_Checker composes plan, private workers and join behind the current migrated
static stage-entry convention. Template_Update holds either complete permissions or
one attributed diagnostic, never both or neither. Full shared compile Step lifecycle
orchestration remains later coordinator work; this slice does not claim that protocol
or a full compiler pipeline. A failure after an earlier worker succeeds discards the
private partial batch; stale/internal failures do not become source diagnostics.

While connecting reuse, dependency removal exposed a preexisting gap: current() asked
Resolution_Set::declaration_for for an ID that might have disappeared. The resolution
store now exposes has_declaration, and Definition_Result returns false for an absent
dependency before looking it up. Missing dependencies cause rechecking rather than an
internal lookup exception. This is a narrow query on the existing symbol-store owner,
not a second symbol index or a new validity heuristic.

The proof covers cold/warm/full updates, reversed arrival, missing/duplicate/foreign
results, missing declaration/binding provenance, zero visits, stale catalogs, changed
and removed dependencies, added/removed templates, unrelated ordinary edits, missing/
stale bindings and failed outcomes. Host-only snapshot assertions additionally prove
purity and recovery after rejecting batches and after a later worker fails. Existing
symbolic-model expectations and retained oracle are rerun because the result owner
changed. Timing preserves the first passing PHP checkpoint separately from native.

All 22 PHP/native scenarios, 13 host invariants and the 40-case model regression
pass. Native build one passed without correction (`results/template-project-01`).


## Concrete instance registry and fixed read views

Concrete argument binding first needs the prototype Instance_View/Store/Set owner.
That storage layer now retains contexts, application links, concrete types, literal
constants, constant owners, source bindings, template permissions and allocation
history. Instance_Identities remains the sole allocator and enforces the exact type
lineage. Even an empty registry now has that explicit lineage rather than a nullable
uninitialized allocation domain.

Instance_State names the internal typed containers. Context IDs and instance IDs stay
separate. Application links use an unambiguous context-ID/node-ID string key rather
than a nested PHP map; they still distinguish the same syntax node in different
concrete contexts. Type lookup retains the prototype length-prefixed namespace/name
key. Registry mutations append the newly introduced work frontier once; draining it
does not scan the full registry or invalidate published snapshots.

State fork copies containers and the mutable allocation ledger, while sharing immutable
semantic objects. Set construction and candidate creation defensively copy their seed;
export_state returns a copy, never mutable access to retained storage. Public functions()
returns only template-function or owned-callable contexts, preserving source algorithm
membership. The prototype's dynamic to_array diagnostic export is not reproduced here;
all retained data remains available through typed state export for later serialization.

The converter rejects nullable object returns on interfaces. Instance_View is therefore
a concrete read-only handle over a registry fixed during a worker batch. Store and Set
both expose view(); the same local Instance_Lookup trait serves all three. Snapshot
views remain isolated from candidate mutation; candidate views intentionally observe
later accepted work between batches. This changes representation, not lookup semantics.
No converter inference, target change or fabricated polymorphism was introduced.

accept_type rejects ordinary contexts and clears an obsolete inverse spelling when
replacing an instance type. The current portable subset has no unset statement, so
zero is an explicit absent-instance sentinel; lookups guard it, and snapshot/candidate
index rebuilding drops such tombstones. The first native attempt hit a STAN false
self-recursion report for method count calling the global count helper; size names the
query consistently with other stores and avoids that collision without bypassing STAN.

Optimization follow-up: snapshot currently forks state to install source-binding
provenance and Set construction defensively forks that state again. These are bounded
publication-time copies, not per-lookup copies. A measured ownership-transfer API can
remove the second copy later while preserving the native isolation proofs. No argument
normalization, application/member joins or whole concrete-preparation pipeline is claimed.

The second native attempt exposed empty array resets inferred as mixed tables when
assigned through another object's fields or an inferred local. All such resets now
use explicit typed empty maps/vectors. This was an authoring correction, not a new
converter feature. Both failed attempts and the first passing PHP hash checkpoint
are retained in the timing evidence.

All 29 PHP/native outcomes pass on native attempt three, after two correction cycles
(`results/instance-registry-01`).


## Bound annotations and concrete argument reading

The prototype's Annotation_Types and Bindings algorithms now consume fixed migrated
name, definition and instance views. Accepted type bindings carry exact provider
objects or source symbol IDs; consumers do not repeat source spelling resolution.
Missing/stale owner bindings and reconstructed provider identities remain internal
errors. A source or provided-record definition absent from the merged view returns
null as a preparation prerequisite. Existing strict errors for canonical identities
present without a definition are preserved.

Definition_View accepts a catalog-only constructor as well as a catalog plus canonical
store. This replaces the Type_Catalog|Definition_View union with one concrete read
contract, without inventing an empty type store or weakening merged-view semantics.
Annotation_Types::definition takes an explicit Instance_Context and Bindings reader;
ordinary callers use Instance_Context::ordinary. There are no nullable/default ordinary
method parameters, and byte-span operands remain forbidden as source-storage types.

Bindings is now a fixed-input reader rather than a static utility with repeated view
arguments. Type slots must contain type arguments; integer slots must contain present
exact decimal values. Explicit type applications read accepted registry links and
remain pending until a type is published. Literal decoding/range checks reuse the
proved Integer_Literals owner. Global constants retain exact accepted Template_Argument
objects; local constants, expressions and calls do not acquire evaluation support.

Annotation_Types owns one private source diagnostic for its reader. Semantic rejection
records path/span/reason before throwing the supported RuntimeException. Internal stale
or malformed-state errors remain LogicExceptions without a source diagnostic. A worker
must treat a semantic failure as terminal for that reader and inspect its diagnostic;
no fabricated type or value is returned to bypass failure. Full custom diagnostic
exception integration remains a later compiler-coordinator concern.

Thirty PHP/native scenarios cover exact provided identities, catalog-only reads,
source/record readiness, prepared applications, missing or wrong-role arguments, integer
normalization and range rejection, unsupported evaluation and byte-span rejection.
Semantic failures compare exact parser-derived source anchors. Existing fourteen
Definition_View outcomes verify strict merged-view behavior after the constructor change.
The first PHP corrections were fixture construction only: byte-span lifetime, the
Field_Type class spelling, and using a field-admissible int32 definition for records.

All 30 PHP/native scenarios and the 14-case view regression pass. Native attempt one
passed without corrections (`results/concrete-bindings-01`).


## Literal-constant workers and batch acceptance

The prototype Constant_Worker and Constant_Join now use the fixed Bindings reader.
Unannotated constants use the configured integer-literal definition; annotations
follow accepted type bindings and must denote an integer representation. Literal
normalization and range checks stay with Integer_Literals through Bindings::literal.
Expressions and references remain rejected as initializers; this adds no constant
execution or folding capability.

Tasks are an explicit vector of source Symbol_Record objects, normalized once into
an ID-keyed map. The constructor rejects duplicates, non-constant owners and stale
binding snapshots before accepting work. Results remain a typed ID-keyed map of
Template_Argument objects, so duplicate result keys are not a representable batch
shape. Cardinality, key membership, exact definition identity and normalized source
spelling are checked before returning any accepted map. Output order follows task
order even when worker results arrive in reverse order.

The join preserves the prototype's division of responsibility: workers perform range
validation, while the join checks that already-computed outputs match their selected
source/type provenance without repeating decoding. Leading-zero normalization uses
an explicit byte scan and handles all-zero input as '0', replacing PHP ltrim/truthiness.
The accepted values feed Instance_State constants and owner provenance directly.

Twenty-two PHP/native outcomes cover literal limits, zero normalization, unsupported
initializers, source/type/value mismatches, incomplete/extra/foreign-key batches,
duplicate or stale tasks, empty batches and registry integration. Ten host-only
assertions check worker/join purity, rejection after an earlier valid row, recovery
and accepted map isolation. The first fixture used a nonexistent int8 catalog name;
it was corrected to the actual signed int32 contract and its exact limits.

No constant selection/reuse coordinator, expression evaluator or application instance
join is claimed. Phase timings retain the first passing PHP checkpoint; native waiting
overlapped host-purity checks and consolidation drafting.

All 22 PHP/native scenarios and 10 host invariants pass on native build one,
without native corrections (`results/constant-batch-01`).


## Application argument normalization and concrete storage elements

`Application_Worker` retains the prototype's ordered explicit argument algorithm:
source templates stop at their first unresolved type prerequisite; provider families
collect every unresolved type argument and discard partial normalized arguments.
Source value arguments still require the catalog's exact integer contract, including
formals referring to an earlier type parameter. Accepted constant identity and
source-attributed errors are retained. Template permissions are checked before work.

A named `Application_Result` replaces the prototype's nullable argument array with
explicit typed argument/prerequisite vectors. Ready results have no prerequisites;
pending results expose no partial arguments. Constructor checks reject mixed states,
invalid node IDs and duplicate prerequisites; copied membership protects the result
from caller container edits. Empty ready argument lists remain representable.
`Bindings` supplies the already-proved fixed annotation/catalog/instance view.
No argument worker allocates identities or publishes instances.

`Storage_Definitions` remains the semantic owner for element eligibility, exact
family/element identity and materialization into a private canonical type candidate.
Explicit guards replace dependent nullable expressions. The storage key remains an
exact JSON tuple, and its namespace retains the NUL prefix. Resource ownership uses
the shared obligations model. Existing storage with a different accepted family or
element object rejects rather than silently retaining stale ownership.

The storage signature/matching/element-passing methods are not migrated in this
checkpoint: their signature-request owner belongs to later concrete callable
preparation. Application acceptance/instance joins and full preparation orchestration
also remain unfinished. This is not an expanded template-expression evaluator.

The first PHP run found a fixture using a bit width where Representation::floating
requires a format name; it was corrected before the PHP-ready checkpoint. The first
native build exposed a field/method name collision (`arguments`), already covered by
the authoring guide. Renaming private storage to `ordered_arguments` preserves the
public contract. This was one native correction cycle, not a target/compiler change.

Evidence includes 25 PHP/native scenarios and 23 host invariants for worker purity,
result container independence and bounds. Timing records preserve the first passing
PHP source hashes and separate native stabilization from authoring. Wall intervals
include continuation/status-discussion gaps; command timings remain the precise
execution measurements.

Evidence: `results/application-arguments-01`.


## Application batch acceptance and instance publication

`Instance_Join` now owns complete application-batch acceptance and adoption into the
coordinator's private instance/type candidates. It preserves task order independently
of result arrival order, validates exact selected-task and current binding/declaration
identity, requires template permissions, and rejects unaccepted or replaced concrete
contexts. A join-local `Bindings` reader is tied to the supplied candidate; no mutable
reader or nullable reader state is added to the public contract.

All worker results are validated before identity allocation. Source type arguments
retain exact accepted definition identity; value arguments retain exact typed decimal
values. Pending source results may report any nonempty subset of unresolved type
arguments, as in the prototype. Provider-family prerequisites must match the complete
ordered missing list; storage prerequisites must name the sole unresolved element.
The worker is reused for provider normalization, while source provenance validation
retains the prototype's direct argument checks. Typed vectors/maps replace dynamic
lists and membership helpers; keyed lookups use explicit guards and local keys.

After validation, the existing identity ledger performs deduplication. Current
contexts are retained; previous contexts are reused only for the same declaration
and exact arguments. Storage type applications publish their concrete descriptor;
storage function applications ensure that descriptor exists without publishing it
as a function's instance type. Multiple occurrences share the accepted context.

The ownership boundary is unchanged: validation failures leave candidates untouched;
failures during allocation/materialization require the coordinator to discard its
private candidates. This checkpoint does not claim transactional rollback for the
adoption phase. Prior snapshots remain isolated. Full concrete preparation, selection,
member instances, signature preparation and coordinator integration remain unfinished.

The proof reuses the join invariants from the retained `explicit_instances.php` test:
reversed arrivals, exact identity reuse, incomplete/duplicate batches, forged argument
and prerequisite rejection, foreign lineage rejection and unchanged prior state.
Its whole-program execution, nested preparation loop and incremental executable
replacement portions require later stages and are not counted as passing here.
New focused coverage includes provider/storage publication and accepted/absent/stale
concrete-context provenance. Host serialization checks prove inputs and failed
candidates stay unchanged; native execution proves the accepted identities and values.

Cheap checker iterations corrected standalone named-field declarations and a method
call used directly as an isset key. Constructor promotion and a join-local reader
keep the intended ownership without adding nullable initialization state. The
existing skill/authoring guide already documents these restrictions.

The first native build failed in the test fixture: a factory method named `create`
shadowed the emitted unqualified object-construction helper. Renaming that factory
to `prepare` was the only native correction; the production join did not change.
The authoring guide now records this observed target limitation. No generated output
or pinned target implementation was patched.

32 PHP/native scenarios and 16 host invariants pass on native build two after one fixture naming
correction. Evidence: `results/instance-join-01`.


## Concrete member receiver preparation and acceptance

`Member_Worker` and `Member_Join` now prepare and accept member calls and selected
method declarations against fixed symbol/name/type/instance inputs. Receiver types
come from an already-bound local or parameter annotation, the concrete `$this`
receiver, or the selected ordinary/template record definition. An unresolved receiver
remains pending; non-record receivers, unknown methods and explicit calls to source
lifecycle bodies keep attributed semantic errors. Accepted provider-family receivers
may have opaque storage and retain their real provider owner, without a fabricated AST.

The task is a small explicit variant: context plus call/receiver node IDs, or context
plus a method declaration. Call bindings are value rows in the new model, so copied
row object identity is not provenance. The task copies their scalar IDs and the join
checks both IDs against the current owner's published bindings. Declaration requests
check exact record/method ownership. Concrete contexts must be the current accepted
objects. Constructor checks reject ambiguous/empty task variants.

`Member_Result` copies its ordered argument membership and represents either pending
or an exact receiver/method pair. Pending results cannot smuggle concrete targets.
Inherited template arguments retain exact accepted argument-object identity, including
when their scalar contents would compare equal. The join reuses the worker's receiver
and nominal method interpretation to validate a complete batch before allocating
identities, rather than maintaining a second partially duplicated resolver. The fixed
reader is local to the join and does not escape into accepted state.

Selected order controls adoption despite arbitrary result arrival order. Repeated
calls/declaration work reuse concrete member identities; previous contexts are reused
only with the same method, receiver and exact inherited arguments. Only call requests
publish occurrence bindings. Validation failures leave private candidates unchanged;
as with application joins, allocation/materialization failures require discarding
those candidates. No transactional rollback or complete preparation loop is claimed.

The proof builds on real parsed/bound methods and accepted application instances. Its
prepared record/opaque receiver definitions are explicit fixture inputs; it does not
claim full record/signature preparation or method-body execution. Covered paths include
local, parameter and `$this` receivers, ordinary/template declarations, provider-family
methods, pending work, lifecycle rejection, current/previous identity reuse and forged
batch provenance. Host checks additionally cover copied binding-row/task membership,
result-container independence, malformed variants and unchanged semantic snapshots.

PHP stabilization corrected a provider fixture's Semantic_Result wrapper and stale
prototype helper naming (Declaration_Syntax moved to File_Collector, corrected in two
iterations). These were source/fixture corrections, not converter or target changes.
The current skill and guide already describe the required declaration/container/guard
forms; this checkpoint adds no new converter capability.

28 PHP/native scenarios and 20 host invariants pass on native build one without
correction. The final host proof also reuses the exact cloned-receiver rejection
from retained `resolve_types/concrete_preparation.php`; its whole-program preparation
checks remain later work. Evidence: `results/member-instances-01`.


## Source/provider record normalization and acceptance

`Record_Preparation` now selects missing ordinary source records and normalized
provider records, normalizes source fields, and returns private `Record_Result`
contracts. Explicit concrete template contexts use the same worker. Fields retain
exact accepted element definitions and named `Field_Type` extents, rather than a
source-side union of scalar and array-definition classes. Constant and template-value
extents use the existing fixed binding reader; no expression evaluation is introduced.

`Record_Task` replaces the prototype's input union and optional read-input bundle
with an explicit source-context/provider-declaration alternative plus fixed Bindings
and Symbol_Store owners. Both origins share the canonical qualified-name key; source
instances already carry their reserved internal type namespace/name. There is no
second origin-specific type namespace. Task constructors reject ambiguous alternatives.

The existing `Lifecycle_Bodies` value record holds constructor/destructor/copy/
assignment symbol IDs. `Source_Lifecycle_Bodies` owns extraction and source signature
shape checks (mutable receiver, body presence, copy-source arity and const-reference
syntax). The small `Source_Lifecycle` role query now delegates its spelling map to
name_role. Body extraction lives separately so symbolic template checking does not
depend on concrete record-task/annotation machinery. This is a local decomposition
of the prototype lifecycle owner, not a new semantic stage. Exact return/receiver/copy
source type validation still belongs to upcoming callable-signature preparation.

Extents are checked as exact positive decimal values against the signed 64-bit compiler
index domain before arithmetic conversion. This replaces host-dependent filter_var
integer ingestion. The maximum boundary is tested as a normalized contract, without
claiming that an array of that size can be allocated or laid out. Accumulation groups
the decoded digit before addition to avoid an intermediate overflow at that boundary.

`Record_Join` checks current source/provider ownership, catalog context, accepted
template contexts, complete result membership, exact field definitions/extents and
lifecycle IDs before applying declarations in selected order. Source conflicts retain
source anchors; provider-only conflicts cannot invent one. Provider outputs retain
exact normalized declaration identity. Current type/instance/name inputs remain
fixed while workers and validation run; accepted canonical publication deliberately
changes the private candidate observed by task readers. Failed materialization still
requires discarding that private candidate; rollback is not implied.

The PHP corpus exposed fixture boundaries: duplicate fields and source/provider name
collisions are already rejected by name resolution; their checks are recorded as
upstream host proofs. Const methods use `public const function`. Generic arrays of a
dependent element type remain deliberately rejected by template permissions, so the
concrete-record proofs separately exercise supported T substitution and an integer
value parameter used as the extent of a concrete element type. No restriction was
weakened to make those fixtures pass.

The first 33 PHP behavior outcomes passed before a host purity assertion was repaired:
it had incorrectly serialized the intentionally mutable candidate through task reader
views after successful publication. The corrected proof compares immutable facts and
normalized outputs separately, while requiring complete candidate purity on rejection.
This was a test correction after the PHP-ready timestamp, not a production behavior fix.

Full preparation queues, callable signatures, lifecycle body type checking and compiler
coordinator execution remain unfinished. src-runtime-preparation remains untouched.

33 PHP/native outcomes and 27 host invariants pass on native build one without
correction; the existing 46-case template-body PHP regression also passes.
Evidence: `results/record-preparation-01`.


## Concrete callable signature requests and source boundary contracts

`Callable_Input` replaces the prototype's symbol-or-instance union with an exact
owner and optional accepted instance. It carries the callable ID explicitly and
rejects mismatched declaration/instance pairs. `Callable_Inputs` supplies fixed
membership checks, ordinary/concrete enumeration and accepted provider implementation
lookup. Prepared family-method callables remain caller-supplied accepted associations;
this checkpoint consumes them but does not produce or authorize native family code.

`Signature_Request` owns ordered parameter-definition and passing-mode vectors.
Omitted passing modes normalize to explicit value modes instead of keeping an empty
sentinel alongside a nonempty parameter list. Definitions retain exact shared identity;
constructor copies isolate list membership. No canonical IDs or type-store writes are
performed by request construction or signature resolution.

`Signature_Resolver` now resolves entry, ordinary source, concrete template/member,
imported callable, prepared family-method and storage-callable requests. Source
annotations use the existing fixed Bindings reader. Receiver passing comes from the
accepted concrete context and source constness. Imported names must resolve against
the accepted definition view; provider declarations never receive a fake syntax tree.
Compile-time function execution remains rejected, and raw template/member declarations
remain nonparticipants until they have a current accepted instance.

`Parameter_Contracts` preserves plain-value versus const/mutable borrow semantics:
source aggregate value parameters remain unsupported, scalar references reject,
mutable record/opaque references require value-copy/no-cleanup policy, and const
record borrows preserve managed lifetimes. `Source_Lifecycle_Signature` validates
void return, mutable exact receiver, and exact same-type const copy source after
receiver preparation. It is separate from the lightweight role map and body-ID
extraction, retaining the lifecycle semantic owner without adding concrete signature
dependencies to symbolic checking.

`Storage_Signatures` is the signature-facing part of the existing storage-definition
owner. It derives all six operation roles from the accepted family and element;
scalar push arguments travel by value, record pushes by const borrow, and count
borrows its receiver const. Exact descriptor/element/family provenance is checked.
During review, matches() was restored to direct provenance validation without creating
a second request, preserving the prototype's distinction between resolution and
acceptance. A missing accepted descriptor returns false during matching, while the
worker rejects missing preparation. Earlier storage eligibility/materialization
consumers do not acquire a dependency on signature-request types.

The first 33 PHP outcomes passed without source corrections. The host bounds helper
needed to catch OutOfBoundsException explicitly. The first native attempt stopped at
STAN's known final-throw return-flow limitation in participates() and passing(); both
were reshaped to final typed returns without weakening validation or bypassing STAN.
Timing separates that native correction from the earlier host test/review adjustments.

This is request preparation, not a complete type stage. Incremental signature selection,
retained-signature validity, batch acceptance/publication and local types still depend
on the upcoming Type_Resolution/Callable_Signature result model. Full lifecycle body
checking and complete coordinator execution remain unfinished. src-runtime-preparation
was not changed.

33 PHP/native outcomes and 26 host invariants pass on native attempt two after one
STAN return-shape correction. Evidence: `results/signature-requests-01`.


## Type result records: authoritative callable input and local associations

Callable_Signature retains Callable_Input as its exact declaration/instance owner.
Syntax, declaration and body facts will be read through that owner rather than
copied into separately constructible fields. External/storage identities stay
explicit; prepared family callables still require coordinator acceptance. This
reduces inconsistent provenance states without replacing identity with equality.
Local_Types copies a typed vector and exposes one-based lookup, retaining exact
name-resolution/instance ownership. Constructor validation checks association
shape, not canonical type existence; the containing snapshot must do that.
Debug serialization and completed snapshot assembly are not yet migrated.
For optimization, revisit handle/row representation only after publication and
reuse proofs; do not weaken exact owner checks to reduce record fields.

18 PHP/native outcomes and 23 host invariants pass. A fixture short-name STAN
ambiguity required one qualification correction; production code passed the
first C++ build. Evidence: `results/type-result-records-01`.


## Signature selection, canonical publication and reuse

Signature_Set owns ordered callable membership and its canonical Type_Store.
This extracts the signature association part of the prototype Type_Resolution
without manufacturing a completed type stage. The completed snapshot will compose
this result with local associations and accepted family packages. Published stores
remain immutable by coordinator discipline; no copy-on-write inference is assumed.
Signature_Selection is separate from the annotation worker so basic request
resolution does not depend on prior publication. Both selection and joining use
Signature_Validity: exact declaration/instance/provider owners, same lineage and
shared representation/type rows, with resolved parameter and result types.
Bounds are checked explicitly because migrated store lookups use InvalidArgumentException
rather than the prototype's OutOfBoundsException. Invalid retained IDs mean stale
work, not successful reuse or an unrelated lookup exception.

The join copies task/map membership, validates every result against its exact
selected Callable_Input and authoritative annotation/provider contract, then checks
all unselected participants before writing. Publication follows declaration/instance
order, independent of completion order. Validation does not rerun the worker or
allocate duplicate requests. Materialization failures still require candidate discard;
there is no rollback claim. Normalized passing modes preserve explicit all-value
contracts rather than the prototype's optional empty passing sentinel.

32 PHP/native scenarios and 28 host invariants pass on the first native attempt.
Selective rebuilding after a uint8 parameter-type invalidation retains unchanged
entry identity and leaves the old snapshot untouched. Evidence:
`results/signature-publication-01`. Local types, full snapshot assembly and type
coordination remain open. No src-runtime-preparation code changed.


## Type snapshot association assembly and family records

Type_Resolution composes Signature_Set with copied local/family membership and a
fixed Instance_Set. Signature and snapshot stores must be identical; local results
retain exact current binding/owner identity, parameter ordering and canonical types.
Entry identity/return definition and concrete instance identities are checked before
publication. The constructor checks association coherence, not all worker obligations.

Language/conversion indexes use flat numeric tuple keys instead of nested PHP maps.
Missing lookups retain zero/null sentinels. Numeric tags are never inferred from
provider names. Construction lookup and debug projections remain explicit migration
debt; neither is silently replaced with an empty result. Body signatures expose an
ordered typed vector; all signatures remain available through the shared Signature_Set.

Family_Preparation_Task/Result copy operation/source-export memberships while sharing
exact context, package, export and callable owners. The family preparation service
and its acceptance join still own authorization; these carriers and snapshot checks
do not replace package receipt/artifact validation. The existing Runtime_Type_Import
file stays independent of the new concrete-instance dependency. No changes to the
preserved PHP preparation tool were needed. Future optimization may replace string
numeric-tuple keys with a typed composite key once justified by measurements.

26 PHP/native outcomes and 26 host invariants pass on the first native
attempt. Evidence: `results/type-snapshot-01`. Next are construction lookup/debug
projection dependencies and local-type preparation, followed by coordinator assembly.


## Local type annotation workers and final association join

Local_Type_Request retains an exact Callable_Input and name-resolution owner with
a copied typed vector containing only body-local definitions. Parameter IDs remain
owned by the accepted Signature_Set. The join copies them as the local-ID prefix
and materializes the suffix in declaration order after validating the full batch.
It does not re-resolve parameter annotations or allocate a second worker result.

Local_Type_Validity owns an optional prior completed snapshot and serves both
selection and joining. Exact binding/instance and canonical-row identity determine
reuse; absent history and invalidated/missing type rows select work. Explicit bounds
avoid depending on the prototype's old lookup exception class. Void diagnostics use
the existing Annotation_Types channel and retain path/byte span/reason.

The local join validates all selected and unselected participants, current catalog,
separate candidate, exact request ownership and signature parameter counts before
writing. Materialization still requires candidate discard on failure. Task/result
arrival order cannot change published local ordering. The new owner stays inside
resolve_types; no source-preparation tool or runtime behavior changed.

22 PHP/native outcomes and 26 host invariants pass without a native
corrective cycle. Evidence: `results/local-types-01`. Construction lookup and debug
projections remain recorded debt; concrete queues/coordinator remain incomplete.


## Construction lookup and explicit type-association diagnostics

Construction_Types moves the prototype snapshot's construction query into a
worker-owned reader over that exact snapshot. Its Annotation_Types channel retains
source path/span/reason without mutating the snapshot or flattening source errors.
The query returns only an already-prepared exact canonical definition; capability
checks remain body-owned. Source bindings and concrete instances must be current.

Type_Association_Debug replaces reflection/array assembly for the explicit
signature/local/family portion of the old type dump. Nullable receiver positions,
provider IDs, passing names and ordered local/parameter IDs remain explicit. Source
paths replace unavailable prototype file IDs rather than synthesizing identifiers.
No empty catalog/store/instance placeholders are emitted under a complete-dump API.
The remaining serializers and their fields are inventoried in
[type_debug_projection_inventory.md](type_debug_projection_inventory.md).

12 PHP/native scenarios and 57 host invariants pass. Native attempt two passed
after one fixture return-flow correction; production bytes were unchanged and only
one C++ build was needed. Evidence: `results/construction-types-01`.

## Preparation queue dependency: keyed removal (2026-09-23)

The prototype releases completed requests and per-fact dependency edges with
`unset`. Preserve that ownership behavior rather than retaining task payloads in
permanent tombstones or rebuilding entire maps on each completion. The converter
now supports one explicit hash-slot removal, including fixed field/key paths;
it adds no inferred types and no PHP runtime shim. The queue itself remains to
migrate using named records for its nested indexes.

Evidence: `results/keyed-removal-01` extends the existing map-iteration PHP/native
proof on pinned `9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First native attempt
passed, zero corrective cycles, one C++ build. Per-command times are saved; exact
authoring/PHP-ready elapsed time was not captured. Checker rejection/publication
purity and the existing converter/runtime/incremental suite also passed. No new
compiler production files are claimed by this tooling checkpoint.

## Concrete preparation queue (2026-09-23)

Migrated the prototype request payload and fact-indexed readiness owner. Named
entry/dependent/batch records replace nested mutable arrays; integer task tags
and checked typed getters replace the enum/union carrier. Facts still wake only
indexed consumers. Hash deletion releases completed payloads and consumed edges.
Two local invariant repairs remove stale ready membership when an already-ready
request acquires a prerequisite or completes before take. Existing coordinator
flows retain their behavior. No new compiler functionality or runtime-preparation
changes were introduced.

The focused trace proves 31 scheduling/identity/rejection assertions in PHP and
native, including fixed mixed-kind batches, deduplication, prepublished facts,
blocked cycles and reuse after completion. A separate host weak-reference check
proves request/task release. Native passed attempt one, zero corrective cycles;
per-command timings, first PHP checkpoint and final source hashes are retained in
`results/preparation-queue-01`. Authoring start was not captured; no estimated
elapsed authoring time is presented. Full coordinator/provider execution remains
separate. See `specs/portability/preparation_queue.md` for the ownership contract.

## Instantiation policy snapshot (2026-09-23)

Preserved positive integer validation through MAX_SYMBOL_ID and extra-key
acceptance. The existing lossless JSON view replaces ad-hoc decoding; load and
parse share one validator and retain path-attributed read/policy errors. The
caller supplies the path or a compiler language-data directory for default name
construction. Source __DIR__ cannot identify a compiled installation, so no
reference-tree location is embedded in production. Final default discovery and
cache-input tracking remain owned by the compiler session.

Twenty-two documents agree in PHP/native and with the retained prototype;
real-file success and missing-file failure are also tested. Authoring to first
PHP checkpoint was 63.072 seconds, excluding initial inspection. Native passed
first attempt with no corrective cycle. Command times and source hashes live in
`results/instantiation-policy-01`. No allocation policy enforcement or new compiler
functionality was added. See `specs/portability/instantiation_policy.md`.

## Checked body flow model and graph queries (2026-09-23)

Moved the prototype's flow vocabulary into its own shared data/flow.php owner.
Checked integer tags replace string-backed enum cases; named nullable reservation
records replace an untyped array of null-or-block entries. The builder retains
reserve/begin/terminate/complete behavior, including ignored termination of an
already closed path and immutable returned membership. Successor IDs are explicit
method arguments because the current converter rejects their defaults.

Reachability uses a dense visited vector and iterative worklist, followed by
bottom-up merge sorting by statement start and ID. This preserves deterministic
results and O(n log n) ordering without callback sorting or per-comparison vector
arguments. Cycles/shared joins visit each block once. Missing reachable blocks
still fail; unreachable invalid edges remain irrelevant. Future optimization can
compact private reservations without changing published block meaning.

Evidence: 48 PHP/native graph cases, a builder protocol trace and retained
prototype agreement in results/body-flow-01. Authoring to first PHP checkpoint:
160.660 seconds; one checker-only correction. First native build passed in 27.903
seconds, zero native corrective cycles. Final harness cleanup removed unused
copied fixture/data artifacts; tested production and probe sources were byte-audited.
The body worker, lifetime analysis and full coordinator are not completed here.

## Checked storage places (2026-09-23)

A dedicated data/places.php owner now holds fixed storage-root/projection records.
Checked integer tags replace the enum. Place copies ordered membership and exposes
size/at instead of a public array; exact readonly projection objects remain shared.
The prototype indices generator becomes an explicit next_index cursor, retaining
order and call_end boundaries without a second operand list or generator allocation.
Dynamic element projections alone establish allocation dependency. Constructor
callers supply explicit empty vectors for bare local roots. Later syntax/lifetime/
lowering consumers must use the new accessors; no permission checks are bypassed.

Evidence in results/body-places-01 covers 47 PHP/native cases with retained
agreement, projection identity, membership isolation and bounds checks. Authoring
to first PHP checkpoint: 82.526 seconds. First native
build passed in 25.929 seconds with zero corrective cycles. Compact value-layout
projection storage remains an optimization opportunity, not a claimed property.

## Exact operation contracts and selection (2026-09-23)

Before adapting operation-valued body rows, migrated their truthful type-model
owner into data/operations.php. Binding tags are checked integers; operand IDs
are copied typed-vector membership with bounded accessors. The selector retains
exact-ID matching and declared wrapping/ordered capability checks, with explicit
boolean-result availability and provider/native implementation identity. It does
not infer operations from storage, mutate canonical rows, or add coercion.

Evidence: 294 PHP/native catalog combinations with preserved-prototype agreement,
plus membership/binding identity and bounds checks in results/body-operations-01.
Authoring to first PHP checkpoint: 194.821 seconds,
including one fixture correction from the obsolete definitions() accessor to
size()/definition_at(). First native build passed in 54.024 seconds with no
native correction. Typed value records and expression ordering remain separate.

## Typed body records (2026-09-23)

The prototype union payload becomes an explicit tagged Typed_Value with checked
named accessors and shared typed payload objects. Decimal literals remain strings;
binary literal contents remain unchanged and expose an explicit hex JSON method
instead of JsonSerializable. Primitive conversion and operation operands remain
IDs into the containing body; cross-row/canonical checks are deferred to that
truthful owner, not fabricated in record constructors. Mismatched payloads now
fail at construction rather than later consumer access.

Calls retain zero-for-void results and contiguous argument ranges. Argument passing
uses the existing semantic modes. Statement tags and renamed write_kind/return_mode
fields preserve the prototype constructor's scope, destination, construction and
return acceptance. Scope ranges allow empty blocks. Native union/compact carrier
storage is explicitly deferred; this representation keeps typed shared payloads.

Evidence: 1,009 PHP/native outcomes, 1,000 prototype statement comparisons and the
full byte range in results/body-records-01. Authoring to first PHP checkpoint:
192.885 seconds. Two native validation attempts, one
fixture block-visibility correction, one actual C++ build (27.25 seconds).
Production passed unchanged from the original PHP checkpoint. Completed-body
queries and evaluation ordering remain next; no whole body-checker claim is made.

## Retained checked body owner (2026-09-23)

Copied typed membership and named accessors replace public result arrays; canonical
IDs remain separate from zero-based storage positions. Exact callable input/name/
local-type provenance is checked without claiming that construction performs body
checking. Conversion/operation queries retain earlier-operand and exact-type
requirements. Projected location queries preserve the original field-bound,
array/element-identity and integer-index behavior. Missing dependencies fail.

Signature_Dependency captures parameter IDs because the migrated Representation
uses canonical member ranges while the prototype embedded parameter arrays.
This keeps downstream body queries independent of a mutable Type_Store, with exact
provider/storage identity for resource effects. Body signature queries return
that dependency view. Body debug serialization remains explicitly inventoried;
no source IDs or finished debug dump are fabricated.

The 23 PHP/native scenarios use parsed ownership plus synthetic canonical body
associations, not end-to-end source checking. Evidence in results/checked-body-01
records 395.105 seconds to first PHP readiness, one
checker fixture name correction and one PHP array-materializer fixture correction.
First native build passed in 231.61 seconds with no native correction. Expression
ordering, real checking workers/joins and lifetime analysis remain incomplete.

## Streaming expression order (2026-09-23)

Replaced the prototype generator with a lazy explicit next iterator over the same
checked rows. Left-to-right postorder, strictly ordered call segments, void roots,
earlier operand bounds and partial events before errors are preserved. Fixed slots
plus logical depth replace array_pop, retaining scratch proportional to maximum
expression depth without recursion or a retained event list. Cursor objects emitted
to consumers are not mutated again. Full segment validation still requires draining;
discard the iterator after errors. Invalid range bounds are now rejected explicitly.

The 24 traces match both independent expectations and the real prototype generator;
a 4,096-level conversion chain proves iterative behavior. The host oracle initializes
only the original Checked_Body fields used by traversal through reflection, so this
is not an end-to-end source checking claim. Authoring to first PHP readiness:
256.681 seconds. One checker-only rename of the copied
void local; first native build passed in 236.056 seconds, zero native corrections.
An oracle include correction is recorded separately. Evidence: results/expression-order-01.
Lifetime values, allocation flow and lowering expressions must adopt explicit next
loops during their migration; no consumer algorithms have been dropped.

## Purpose-aware conversion selection (2026-09-23)

The existing single selector now consumes canonical types and the accepted
Type_Resolution provider index. Integer tags and zero-as-absent primitive payloads
replace enums/nullable enums; mutually exclusive identity/primitive/provider
selections are checked locally. Empty integer-family strings follow the migrated
Named_Definition model. Guarded width access preserves eligibility-before-read.

Void and condition requests remain unsupported here. Exact identity, strictly
wider same-family/signedness implicit conversion, and purpose-specific provider
lookups retain their original meaning. Provider presence does not authorize
implicit narrowing or condition conversion. No chain search or materialization
is added, and applying conversions remains the future body worker's job.

Evidence in results/body-conversions-01: 196 PHP/native combinations, retained
selector agreement and 12 payload invariants. Authoring to first PHP readiness:
179.337 seconds. First native build passed in 230.486
seconds, no corrective cycles. The retained oracle initializes just the original
snapshot fields needed by selection; no provider machine execution is claimed.

## Quoted-byte decoder re-adoption (2026-09-23)

Reused the preserved portable Byte_Literals algorithm and removed its obsolete
function-import prologue in favor of global helpers. The original frozen corpus
generator and baseline decoder supply all 8,593 deterministic inputs: malformed
quotes, interpolation/Unicode rejection, numeric/control/unknown escapes, all
raw/escaped/dollar-prefixed bytes and seeded combinations. Both active PHP and
native now check every outcome, including exact error text; no new string feature
or provider selection is introduced.

The harness reads ASCII hex corpus data from a file instead of embedding the
entire corpus in generated source. Results remain byte-based, including invalid
UTF-8. Evidence in results/byte-literals-active-01 records
79.98 seconds to first PHP readiness; first native
build passed in 25.633 seconds, zero corrective cycles. Historical runtime-string
integration results are not presented as active full-worker coverage.

## Body worker output ownership (2026-09-23)

The prototype stores pending places and finished values in one PHP union array,
and null placeholders inside argument/scope arrays. Body_Output replaces those
with typed row/slot records, retaining one-based value/call/scope IDs and zero-based
argument ranges. Parent argument ranges are reserved before nested calls finish;
completion order does not change membership or evaluation order. Consumer-selected
read versus borrow is resolved once, preserving the original rejection on conflict.
Type retention and source permissions remain worker responsibilities.

Private slot replacement leaves previous row views intact. Completed exports require
all slots and locations to be finished and return independent container membership.
The original final body constructor also checked unfinished slots; the new buffer
makes that boundary explicit before assembly. It adds duplicate completion rejection
for internal producer mistakes. No source grammar, type permission or lifetime rule
changes. Temporary wrapper records can be compacted in a measured optimization pass;
they are not retained by Checked_Body.

Proof: 33 PHP/native checks, plus 24 host access sequences executing the retained
Place_Checking trait and the active buffer. This is state ownership coverage, not
an end-to-end body worker. Authoring through first PHP readiness: 232.124 seconds;
three checker-only corrections (literal class reference, explicit property type,
compound assignment). Native build one passed in 26.032 seconds with no corrective
cycles. Evidence: results/body-output-01. Worker integration is the next task.

## Body checking context and retained dependencies (2026-09-23)

Extracted the prototype Body_Worker constructor, signature/local queries and implicit
retention into Body_Context. Callable_Input supplies exact declaration/instance
identity instead of duplicated syntax/body fields. The snapshot's accepted names
must be the supplied binding owner; source templates require current definition
permissions. No new type permission or source grammar is introduced. Nullable locals
remain meaningful for a callable with no local bindings, including the entry body.

Signature_Dependency captures canonical parameter IDs and exact provider/storage
associations once per callable. Type retention follows fixed-array and storage element
edges iteratively, preserving immutable Type_Record identity and terminating repeated
or cyclic edges. A queue replaces PHP array_pop; retained membership, not incidental
map iteration order, is the contract. Struct fields are deliberately not an implicit
closure edge, matching the prototype. Missing resolved definitions now fail explicitly
rather than reaching an invalid nullable dereference. Discard a failed context.

23 PHP/native scenarios cover ordinary/entry/template/method acceptance, stale input
rejections, provider signatures, reuse, copied membership, deep (4,096-array) and cyclic
closure, storage closure and canonical-store purity. Four host cases execute the
retained private closure through reflection with only its type snapshot initialized;
that is algorithm comparison, not original whole-worker execution. Evidence:
results/body-context-01. Authoring to first PHP readiness: 286.227
seconds; one fixture checker correction and one fixture diagnostic-expectation fix.
First native build passed in 214.795 seconds, zero native corrections. Statement and
expression traversal remain the next work; no full-worker coverage is claimed.

## Source body worker integration (2026-09-23)

Migrated Body_Worker and the preserved statement, control, expression, place and write
traits. The worker consumes Body_Context and Body_Output rather than union arrays
and duplicate source identity fields. Private typed continuation frames replace
instanceof dispatch over unrelated cursor classes; dense reusable stacks preserve
iterative postorder expression and lexical/control traversal. Operation cursors carry
their result ID instead of mutating an integer reference argument. Place completion
returns its typed cursor instead of a heterogeneous pair. Argument ranges are reserved
before nested calls and receiver positions remain explicit. Indexed projections keep
their call boundary, and assignment-target indices execute before RHS values.

Existing semantic owners supply named types, local types, signatures, lifecycle
policies, conversion/operation selection, literal decoding and construction lookup.
Fixed numeric tuple keys replace nested operation caches. Conditional guards protect
void/missing values before dereference. Read versus borrow stays consumer-selected;
initialization, assignment, fresh results, and owned return construction retain their
separate permissions. Source failures use the existing path/span/reason diagnostic
channel on the worker's Annotation_Types reader; internal failures stay distinct.
A one-shot worker guard prevents duplicate publication after success or failure.
Body_Output gained guarded read access to a completed reserved argument for named
provider conversion checking.

58 source scenarios now parse, collect/resolve names, prepare signatures and locals,
and produce checked bodies in PHP/native. Independent assertions cover exact nested
call result IDs and argument ranges, value conversions and operation operands,
borrow/write/return modes, scope ranges, projected types, purity and repeat rejection.
One nested-parameter scenario reuses the explicit expectations in the preserved
parameter_bodies.php test. Expression_Order consumes the resulting plans. Depth cases
exercise 256-term addition, 128 nested calls and 128 nested blocks. Ordinary provider
calls and named conversion calls run through accepted signature metadata; provider
machine code is not executed by this analysis proof.

Coverage does not yet establish concrete method/template application bindings,
storage-element source expressions, or successful metadata-bound byte/echo handling.
Those algorithms are adapted but need focused integration scenarios. Complete body
selection/reuse/join, debug serialization, lifetime analysis and global coordination
remain separate. Test fixtures normalize Point/Buffer record definitions explicitly;
this is not proof of full concrete-preparation orchestration.

Authoring through first PHP readiness: 806.153
seconds (47 scenarios). One checker-only negative-default correction, one production
PHP name-owner correction and one fixture legality correction preceded it. Native
attempt one stopped in STAN: private Expression_Frame collided with parse's short
name, and the infinite traversal loop lacked a recognized terminal return. Distinct
body-frame names and explicit completion resolve both. Attempt two passed its first
C++ build (280.028 seconds), with zero C++ corrective cycles. A separate final native
build verifies the expanded 58-case suite. Detailed commands, both builds and the
original PHP-ready hashes are saved in results/body-worker-01.

## Concrete body-call integration proof (2026-09-23)

No production adaptation was needed. A bounded test preparation loop feeds actual
Application_Worker/Instance_Join and Member_Worker/Member_Join outputs into the
existing signature/local preparation and type snapshot. It follows newly accepted
source contexts, including a concrete template calling another template and a method
calling through `$this`. It is explicit fixture assembly, not a substitute for the
unfinished production preparation coordinator.

14 PHP/native scenarios prove mutable/const method receivers, a const parameter
receiver, mutable-from-const rejection, receiver plus explicit arguments, repeated
method calls, ordinary/nested template applications, bound type parameters, integer
value parameters, contextual result widening and method arity rejection. The proof
checks concrete callee bodies as well as caller plans, preserves accepted instance
identity, verifies receiver passing/local identity and the instantiated literal value,
and drains Expression_Order for resulting statements. Canonical-store counts remain
unchanged during checking; repeated worker execution is rejected.

One fixture assertion initially assumed receiver local ID one even when two function
parameters precede it. The corrected expectation follows lexical local IDs. First PHP
readiness: 189.669 seconds; first native
build: 278.757 seconds, zero native corrections. Evidence: results/body-calls-01.
This expands coverage without increasing the 226-file readiness count. Storage source
expressions, successful byte/echo bindings and managed lifecycle paths remain focused
integration follow-ups; complete body-stage selection/reuse/join remains to migrate.

## Project body selection and publication (2026-09-23)

Body_Plan captures current signature-order inputs and the selected fixed tasks.
Body_Validity is the single reuse/acceptance rule: exact declaration/instance, names,
local association, canonical type rows, callable/provider/storage signature identities,
and captured canonical parameter IDs must remain current. Explicit ID bounds turn
removed dependencies into cache misses without depending on differing exception
classes in old/new stores. The original nullable-body helper becomes a concrete helper;
selection/join guard absence explicitly to fit the portable method contract.

Body_Join validates selected results before publication, accepts out-of-order completion,
retains unchanged bodies and excludes nonparticipants. Body_Set copies membership while
sharing completed body identities, with distinct ordinary-symbol/concrete-callable
queries. The fixed Body_Plan replaces externally supplied arbitrary task arrays, so
selection itself owns unique task IDs and exact type/name snapshots. Body_Checker
executes that plan and returns a complete Body_Update or a path/span/reason failure;
no partial body set escapes. This follows the already migrated Template_Checker facade.
The original session Step init/run/finalize lifecycle adapter remains a coordinator
integration obligation; this checkpoint does not claim the complete compiler CLI.

23 PHP/native scenarios cover fresh/full/warm agreement, reversed workers, copied
membership, duplicate/incomplete/unselected/foreign results, exact dependency
invalidation, removed signature/nonparticipant handling, missing own dependencies,
wrong captured parameters, stale owners, semantic-failure isolation, and concrete
method/template body publication. Targeted dependency tests build changed candidate
associations to isolate the reuse algorithm; they are not a full incremental source
edit-to-type-preparation proof. Narrow fixture plan projections compare supported
literal/call/plain-record cases and do not replace the deferred body debug serializer.

Authoring through first PHP readiness: 328.803
seconds (22 scenarios); one checker-only nullable-parameter correction. The additional
concrete project scenario passed before native build. First native build passed in
283.572 seconds with zero corrections. Evidence: results/body-project-01. Original
body/Body_Set debug views and remaining storage/provider/managed source scenarios are
still tracked, alongside lifetime analysis and full pipeline coordination.

## Byte-literal and echo body integration (2026-09-23)

No production adaptation was needed. A focused fixture supplies ordinary accepted
catalog definitions and provider declarations, then uses real symbol, signature,
local-type and body workers. Literal construction is selected by metadata and the
expected type; raw byte-span arguments bypass construction. Context-free literal
use requires a default binding. Echo dispatch uses the produced value's canonical
type. Tests verify exact byte payloads, call/argument order, retained provider
identity, expression-order consumption, diagnostic attribution and type-store purity.
This proves compiler checking/planning; it does not execute the provider functions
or claim completed lowering/runtime integration. Existing decoder corpus remains
registered independently.

20 PHP/native cases pass. Two PHP fixture corrections: RESULT_NONE is the void tag;
BINDING_BYTE_LITERAL=0 is a valid present role, so fixture absence must be separate.
ABI fixture widths were made consistent with uint8/int definitions. No converter or
production change. Authoring through first PHP readiness: 140.997
seconds. Exact argument ID checks were added before native execution. The first native build rejected a nullable object ternary in the fixture; typed nullable-return helpers replace it. The second native build passed in 298.425 seconds (one native correction cycle). Evidence:
results/body-language-01. Storage-element and managed lifecycle integration remain.

## Typed-storage body integration (2026-09-23)

Real application workers and joins prepare concrete storage types/functions before
signature, local-type and body checking. Twenty-three PHP/native cases cover scalar
and record elements, nested indices/fields, const reads and rejected writes, record
borrows, all six storage roles, exact retained storage effects and call-before-RHS
ordering. A target index is consumed with its own call boundary before the RHS;
Expression_Order remains the shared value/call traversal owner.

The first fixture attempted mutable source reference parameters for noncopyable
storage owners. Parameter_Contracts correctly rejects this in both migrated and
preserved implementations. Tests now use local owners for mutation and const source
parameters for observation. No production restriction was changed. Checked storage
plans do not establish initialized allocation state, bounds safety or legal resource
lifetimes: allocation analysis must still reject unsafe complete programs. Physical
storage primitives are outside this body-checking fixture and are not executed.

Remaining body work includes managed lifecycle construction/assignment/return plans,
complete debug export and session coordination. Lifetime analysis remains unmigrated.

Authoring through first PHP readiness: 158.059 seconds. PHP correction cycles: 1. First native build passed in 314.593 seconds with zero native corrections. Evidence: results/body-storage-01; effort.json also records wall time through native stabilization.

## Managed-value body integration (2026-09-23)

Twenty-three PHP/native scenarios exercise authoritative lifecycle permissions through
real signature/local/body checking: explicit/implicit default construction, copies
from owned locals and const borrows, assignment, owned provider results, direct
return construction, move/copy return selection, call-scoped const borrowing, and
rejection when copying/default/assignment/expiring construction is unavailable.
Exact statement write/return modes and borrowed source selections are checked.
Provider metadata defines five opaque managed categories; tests neither run their
native operations nor claim cleanup scheduling or source lifecycle-body integration.
No production adaptation was needed. Lifetime analysis remains responsible for
consumption, cleanup ordering and allocation ownership. Storage and managed body
plan coverage is now present; debug export and complete stage coordination remain.

Authoring through first PHP readiness: 64.391 seconds. PHP correction cycles: 0. First native build passed in 267.852 seconds with zero native corrections. Evidence: results/body-managed-01; effort.json also records wall time through native stabilization.

## Definite initialization (2026-09-23)

Definite initialization now has a typed per-block owner. Compact Initialization_Fact
value records carry local/initializing-statement IDs; statement zero means an entry
parameter. Initialization_State preserves declaration order in a vector and uses a
hash only for membership. Publication/read boundaries copy value records explicitly
so PHP sharing cannot mutate native-style value snapshots. Initialization_Entries
retains the exact checked body and omits unreachable blocks.

Local_Flow keeps the preserved LIFO fixed-point algorithm: seed parameters, transfer
local declarations, remove out-of-scope facts and intersect predecessor keys. The
intersection retains old values/order, so unchanged size proves unchanged state.
The reusable worklist uses logical depth instead of PHP array_pop; dense records
replace nested ad-hoc maps without depending on hash iteration order. Transfer
updates a private vector/index in place, avoiding a full copy for every declaration.

Sixteen PHP/native outputs agree with the unchanged preserved solver and graph-query
implementation, invoked through a host-only shape bridge. Fourteen source-produced
bodies cover parameters, branches/returns, loops, nesting, unreachable blocks, 256
locals, 128 nested scopes and 64 branches. Two isolated checked-graph fixtures force
predecessor intersection/requeue independently of source CFG generation. Repeat
solutions and mutation of returned fact copies preserve the published facts.
This is initialization readiness only: value/local lifetime records, cleanup planning,
allocation/ownership flow, joins and the complete lifetime stage remain to migrate.

Authoring through first PHP readiness: 164.807 seconds. First PHP and native attempts pass, with zero correction cycles. Native build: 291.336 seconds. Evidence: results/local-flow-01.

## Lifetime record contracts (2026-09-23)

Temporary consumption, local exits, active-local rows and cleanup obligations now
have portable typed records. The preserved string-backed enums become explicit
numeric tags plus stable boundary-name codecs. Value_Lifetime preserves the exact
consumer-ID requirements for call arguments, conversions, operations and indices;
statement-boundary uses retain consumer zero. Local initialization zero continues
to identify incoming parameters. Local_Lifetime remains producer data: complete
range/body validation still belongs to the future Analyzed_Body owner.

The records remain immutable shared objects where their original constructor
contracts or worker stack/index identity matter. The new initialization facts use
compact value records separately. Neither choice claims that PHP readonly alone
establishes deep native immutability. The prototype's allocation_analysis carrier
is deferred to the resource-state migration, where its nested lane maps need an
explicit owner; it has not been dropped or claimed ready with these records.

A 1,105-case constructor/output matrix agrees in PHP/native with the actual retained
record classes, covering all 14 temporary end reasons, consumer and ID boundaries,
both cleanup subjects, local exits and active-local values. This does not prove
complete lifetime analysis or cleanup execution. The first native attempt stopped
at STAN on two terminal-throw return paths; a common explicit return preserves the
codec behavior. The first actual C++ build then passed.

Authoring through first PHP readiness: 73.101 seconds. One STAN-only native correction; first C++ build passed in 24.302 seconds. Evidence: results/lifetime-records-01.

## Lifetime consumption and cleanup plan (2026-09-23)

The lifetime owner now separates consumption/cleanup planning from resource
ownership acceptance. Lifetime_Plan_Worker and the three original handler concepts
consume a fixed Checked_Body and produce a Lifetime_Plan. This intermediate is
explicitly not Analyzed_Body and cannot authorize lowering; the later coordinator
must compose it with accepted allocation/ownership facts. The original combined
worker/result remain preserved references for that integration.

The worker retains source-attributed failures, one-shot execution, exact scope
ranges, initialized live locals, one-consumer value facts and ordered full-expression
cleanup. It reads the shared Expression_Order iterator. evaluate returns its call
boundary instead of changing a scalar by reference. Indexed assignment consumes
target indices before the RHS. Logical-depth vectors replace array_pop scratch
stacks; ordered consumption rows plus a membership index replace array_values on
an insertion-ordered hash. Scope exits preserve reverse initialization order.

Lifetime_Plan owns copied membership and shared immutable lifetime rows. Its
validation preserves local-entry/index checks and exact cleanup completeness,
boundaries and order. A named lexicographic Cleanup_Rank replaces tuple comparison;
temporaries precede locals at a shared boundary, with each group in reverse
construction order. Type definitions remain shared in the checked body. Full
allocation coverage/provenance validation and the combined Analyzed_Body result
are still required, rather than fabricated as empty successful allocation facts.

Twenty-nine PHP/native scenarios cover scalar/void flows, calls/nested calls,
conversions/operations, local initialization/assignment, managed default/copy/assign,
move/copy/direct returns, owned provider results, temporary borrowing, scope/branch/
loop exits, unreachable statements, indexed assignment, 128 nested calls and 64
managed locals. Flat cases assert exact consumption/local/cleanup rows; branch,
loop and stress cases check counts, validation and repeat agreement. The retained
lifetime_analysis.php reachable/void/unreachable example supplies one explicit
baseline; this suite does not claim a full retained-worker differential test.
Missing, duplicate and reversed cleanup lists are rejected. Repeated planning does
not change type-store sizes and a second call on the same worker is rejected.

The result debug/export projection, project selection/join and complete lifetime
stage remain unfinished. Native provider bodies are not executed by this compiler
plan proof. Resource location/state discovery and allocation-flow checking are next.

Authoring through first PHP readiness (25 cases): 377.868 seconds. One checker correction and one expanded-fixture PHP correction; final 29 cases pass. First native build rejected an untyped empty-array assignment to the live-local hash. Reset through a typed empty local preserves the source contract; second native build passed in 290.416 seconds (one native correction cycle). Evidence: results/lifetime-plan-01.

## Resource-state algebra (2026-09-23)

Resource_States preserves the prototype four-bit relation model: one two-bit output
set for empty input and one for owned input. Composition, required-state acceptance
and deterministic-input queries keep their original meaning, including missing and
ambiguous outputs. File-scope RESOURCE_* constants replace unsupported class constants.

The converter's supported token set has no bitwise/shift operators. The portable
implementation expresses the two finite lanes using modulo/division and explicit
set union, with no temporary arrays or table allocation. This is a local algorithm
adaptation, not a converter extension. Relation values are explicitly restricted to
0..15 and required masks to 0..3; out-of-domain integers now fail at this internal
boundary instead of accidentally depending on the prototype's bit truncation.
Consumers must supply validated relation values. No deeper performance claim is made.

All 336 valid-domain outcomes agree in PHP/native with the original helper and an
independent edge-set relational oracle (256 compositions, 64 required-mask queries,
16 determinism queries). The independent oracle follows the preserved resource
contract test's edge-set method. Tests also cover 4,096 associativity combinations,
both identity directions for all 16 relations, and six invalid-domain rejections.
These prove the algebra, not whole-body allocation safety. Resource locations,
aliasing, effects, fixed-point flow and ownership acceptance remain to migrate.

Authoring through first PHP readiness: 1.748 seconds. First PHP/native passes with zero corrections. Native build: 97.379 seconds. Evidence: results/resource-states-01.

## Resource locations and endpoint identities (2026-09-23)

Resource_Location owns copied static field-path membership. Root descriptors keep
the original local-ID key; nested keys retain colon/dot boundaries. An element or
array index stops descriptor projection before the dynamic component. Overlap is
same-local ancestor equality, independent of element index and unrelated siblings.

Resource_Locations discovers descriptor roots and accepted record resource leaves
from the migrated Resource_Obligations owner. Parameter_Resources replaces the
prototype parameter-to-nested-array result with explicit ordered parameter rows;
Parameter_Endpoint and Distinct_Endpoints replace destructured tuple results. Future
ownership consumers must use these records, retaining zero-based parameter positions
and one-based checked-local IDs. No symbol/type resolution is added to the converter.

Canonical endpoint parsing uses a bounded byte scanner instead of regex, explode,
array_map and integer round-trip conversion. Decimal components reject signs, leading
zeros, empty components, non-ASCII digits, separators and signed-64-bit overflow.
The maximum accepted ordinal remains 9223372036854775807; digit subtraction occurs
before accumulation to avoid a transient overflow. Pair ordering is byte lexical,
not PHP numeric-string comparison. Callers pass explicit string paths rather than
string|int unions and must normalize numeric PHP hash keys at that boundary.
Projection validates its canonical path input instead of coercing malformed text;
location constructors reject nonpositive local IDs/negative field ordinals. These
are internal producer constraints, not new language syntax. Diagnostic construction
returns the existing attributed diagnostic record for the consuming worker, replacing
the old direct Source_Error throw shortcut; full stage failure handling remains to wire.

491 PHP/native outcomes pass. 477 compare directly with preserved project/overlap/
endpoint/pair helpers. Seven explicit place-prefix cases cover dynamic projection
stopping; seven checked-body inventories prove root descriptors, scalar exclusion,
parameter positions and nested record paths 0/0.0. The nested cases materialize real
storage/record contracts before body checking. Copies preserve original path membership,
and body-derived diagnostics retain path/span/reason. Allocation safety, alias-effect
application and complete ownership flow are still separate dependencies.

Authoring through first PHP readiness: 114.106 seconds. First PHP/native attempts pass without correction; three record cases added before native execution. Native build: 279.553 seconds. Evidence: results/resource-locations-01.

## Resource flow joins and requirement intersections (2026-09-23)

The retained allocation solver and return aggregation union possible relation edges
with PHP bitwise OR; requirement inference intersects allowed inputs with bitwise
AND. The portable Resource_States owner now names these distinct concepts as join
and intersect. The two-lane arithmetic implementation reuses its existing finite-set
union operation, avoiding unsupported bitwise syntax and temporary containers.
No algorithm semantics or converter support changed. These helpers prepare the
actual flow/alias consumers, which are still pending.

All 608 PHP/native outcomes pass: the original 336 plus 256 union and 16 intersection
pairs, independently modeled with sets and compared with retained inline operators.
The suite retains composition laws and now rejects ten out-of-domain inputs.
Measured automated edit-to-PHP readiness: 0.447s (reference inspection, reasoning
and patch drafting preceded the timer and are not included);
native build: 27.057s; first native attempt passed with zero corrective cycles.
Evidence: results/resource-joins-01. Ready counts remain 242 files / 108 suites.

## Ownership flow and summary records (2026-09-23)

Resource_Flow_State owns the solver's private typed relation/mutation maps. Its
explicit copy operation replaces clone and preserves independent block/sibling-exit
membership. Ownership_Observations remains a distinct validation-only scratch owner:
requirements, mutations, normal returns, owned results, accesses and exclusions never
belong to the solver state. Actual traversal must retain that separation; these records
alone do not prove the two-pass algorithm.

Resource_Transition retains independent required-mask, result-relation, mutation and
access fields. Bound_Resource_Effect shares the exact immutable location/transition
objects. Parameter_Effects replaces a nested parameter/path PHP array with a named
zero-based parameter owner and copied field membership. Ownership_Summary copies
parameter, exclusion and result membership and returns container values; immutable
transition and parameter objects retain shared identity. Numeric PHP hash keys are
normalized by concatenating an empty string at canonical path boundaries.

Local malformed-contract checks now reject negative/duplicate parameter positions,
noncanonical paths, missing exclusion endpoints, self exclusions and nonfixed owned
result states. Reversed/duplicate exclusion pairs normalize to canonical membership.
These checks consolidate internal producer requirements already enforced by the
prototype acceptance layer. Full expected body/type path coverage, constness,
unaccessed-transition consistency, producer provenance and reuse remain the future
ownership join's responsibility. No task/result or completed allocation claim is made.

340 transition-constructor outcomes agree with the retained prototype in PHP and
native (180 accepted, 160 rejected). Additional assertions prove copy independence,
empty summaries, immutable row identity, numeric/root paths, canonical exclusions,
unchanged exported snapshots and ten malformed/missing-contract rejections.
The first PHP behavior execution and first native build pass; two earlier checker
corrections concern fixture keyed literals and unsupported source string casts.
Lifecycle tasks, allocation facts, effect/alias consumers, flow solving and ownership
acceptance remain to migrate.

Authoring through first PHP readiness: 197.977 seconds. Native build: 304.416 seconds. Two checker correction cycles; zero PHP behavior/native correction cycles. Evidence: results/ownership-records-01.

## Bound resource calls and alias checks (2026-09-23)

Resource_Calls owns application of already bound ownership contracts. The two
prototype private traits depended implicitly on Allocation_Flow fields and passed
nullable validation observations through every helper. The migrated local owner
captures fixed locations/parameter membership and an optional observations handle
at construction. A solver instance has no observations; validation uses a separate
instance. No checked-expression traversal, symbol resolution or global lookup is
introduced here.

Parameter-relative fields bind once to exact endpoint effects. Every source-call
requirement reads the unchanged pre-call state; aliases retain all requirements but
select only one writer poststate. Read-only aliases cannot overwrite that writer.
Explicit alias exclusions and active element borrows are checked before effects are
committed. Independently applied object fields retain their explicit iteration order.
State compatibility intersects inferred parameter requirements; concrete local
states never acquire caller preconditions. Preceding mutations produce parameter
alias exclusions; mutation and access remain independent observations. Solving
updates flow facts without collecting observations, matching the retained traits.

Ownership_Failure records the failing source node and reason before throwing. The
future checked-body worker must attach the frontend path/span via the existing
diagnostic owner; this helper does not claim complete source-diagnostic integration.
All facts/observations remain private work products and are discarded on rejection.

449 PHP/native cases agree with a bridge invoking the preserved effect/alias traits.
Seven cases also have independently specified complete outcomes. Coverage includes
all 16 input relations across reader/writer, release/acquire and unused contracts;
alias order, multiple-writer rejection, local versus inferred parameter requirements,
active ancestor/sibling borrows, preceding mutations, explicit exclusions, field
application, solver-only operation, and unchanged flow on failed call validation.
First PHP/native attempts pass without corrections. Runtime allocation metadata
binding/transfer ordering, expression traversal, fixed-point solving and complete
ownership acceptance remain dependencies, not established by this proof.

Authoring through first PHP readiness: 150.051 seconds (includes preceding checkpoint consolidation). Native build: 286.88 seconds. No checker/PHP/native corrective cycles. Evidence: results/resource-calls-01.

## Runtime allocation call effects (2026-09-23)

Allocation_Calls applies runtime allocation metadata to already bound semantic
operands. The owner uses metadata positions rather than argument order conventions;
proofs deliberately select owner position 2 and transfer destination position 0.
Acquire/release/transfer/inspect/mutate/observe preserve their original required
states, result relations and independent mutation/access behavior.

Runtime transfer deliberately retains the prototype's ordered destination-then-source
application. Source-call summary checks instead share one pre-call state. The common
Resource_Calls owner supplies requirements, alias exclusions, active-borrow checks,
access observations and transition application for both protocols. Transfer rejects
overlapping descriptors even during solving. Validation rejects an occupied
destination or a mutation invalidating a live allocation borrow. A later source
rejection may leave the private destination scratch updated, as in the prototype;
the worker must discard the failed work product rather than publish partial facts.

The optional destination position is unwrapped explicitly with take_nullable.
Failure exposes the precise node/reason from either runtime ordering or common
contract checks; frontend diagnostic attribution remains a body-worker obligation.
Missing mapped operands are internal producer errors. Checked-value binding and
dynamic-subobject rejection are separate dependencies, not bypassed here.

896 PHP/native cases compare directly with the preserved Resource_Effects trait
through a bound-operand bridge. The matrix covers all 16 source relations, every
transfer source/destination relation pair, solving versus validation, inferred
parameter requirements, active ancestor/sibling borrows, overlapping owners and
preceding mutations. Independent outcomes anchor acquisition, empty release,
invalid inspection and successful transfer. First PHP/native attempts pass without
corrections. No runtime allocation is actually performed: this proof concerns the
compiler's ownership-effect analysis, not allocator implementation or full CFG
acceptance.

Authoring through first PHP readiness: 141.906 seconds (overlaps preceding native proof). Native build: 304.726 seconds. No checker/PHP/native corrective cycles. Evidence: results/allocation-calls-01.

## Checked ownership operand binding (2026-09-23)

Resource_Bindings connects accepted checked values/arguments to static resource
locations. Zero-based semantic parameter positions are converted explicitly to the
checked body's one-based argument queries. Existing local borrows are required for
call operands. Copy/source binding retains only statically named field projections;
index and element selection reject with the preserved ownership-contract diagnostic.
Summary binding follows explicit parameter membership, while runtime metadata
selects owner/destination positions independently of map iteration or source names.

The prototype private trait's body-dependent lookup now has an explicit checked-body
owner, separate from bound contract application. Failure retains node/reason and
projects the exact frontend path/start/length into Annotation_Diagnostic. No name
resolution or inference is added to the converter. Allocation/resource type validity
remains the already checked signature/contract owner's responsibility.

Nine PHP/native cases use real parsing, resolution, signature/local preparation and
body checking: root and nested field paths, reordered arguments, summary/runtime
position mapping, literal rejection and indexed-owner rejection. Expected locations
and reasons are explicit; failure spans are checked against the actual syntax node.
First native build passes. One PHP fixture correction replaced a second source
function's unprepared generic storage signature with proved storage-provider calls.
That complete concrete-preparation coordinator remains a separate dependency; the
fixture does not claim to implement it. Complete allocation traversal, fixed-point
flow and ownership acceptance remain unfinished.

Authoring through first PHP readiness: 100.828 seconds. One PHP fixture correction, no production/native correction. Native build: 303.204 seconds. Evidence: results/resource-bindings-01.

## Checked resource expression traversal (2026-09-23)

Allocation_Traversal connects the checked Expression_Order cursor to resource
contracts. Runtime call arguments are removed from pending borrows before metadata
effects; source-call summaries retain the full active borrow set during checking.
Allocation-backed reads require owned storage and local element borrows remain
active until consumed. Explicit maps preserve value-ID membership without relying
on PHP array_diff_key or generators.

Resource_Field_State replaces constructed-value nested arrays with a named private
field-state owner. Constructor summaries start in empty destination storage;
copy/move source contracts apply before constructing the destination. Unconsumed
field facts are returned to the statement owner for transfer or destruction. Temporary
destruction checks the complete selected summary. Definitions, signature metadata
and preselected dependencies remain authoritative; no recursive body inference or
converter resolution is introduced.

Pass owners are constructor-injected because uninitialized named object fields are
not currently convertible. The outer allocation pass must assemble one consistent
observation context for contracts/traversal. Solver instances carry no observations;
validation instances do. Attributed failures propagate from binding, runtime effects
or common contracts through the existing frontend diagnostic projection.

25 PHP/native cases use real checked bodies and explicit expected outcomes. They
cover balanced acquisition/release, empty count/release, double acquisition,
transfer overlap, indexed reads, nested record borrows, current-call consumption,
pinned-borrow invalidation, supplied source-call summaries, constructed expression
facts, construction requirements, temporary destruction and copy/move source effects.
Lifecycle summaries in helper cases are supplied fixtures, not inferred callee
contracts. This is not a retained full-worker differential proof or a complete
allocation-safety result. Statement/scope traversal, fixed-point merging, ownership
summary inference and final acceptance remain separate dependencies.

The first native build passed without corrective cycles. Earlier checker/PHP fixture
corrections and expanded coverage are preserved in the evidence; first PHP readiness
remains the initial nine-case checkpoint.

Authoring through first PHP readiness: 184.824 seconds. One checker correction; two PHP fixture/expectation corrections across initial and expanded suites. Native build: 320.275 seconds, first attempt passed. Evidence: results/allocation-traversal-01.

## Resource statement and scope-exit transfer (2026-09-23)

Allocation_Pass owns statement/block resource transfer and scope-exit validation.
Named Local_Resource_Leaves groups descriptor leaves by root binding. Destination
indices execute before the RHS; allocation-backed destinations pin their descriptor
while the source evaluates. Local construction consumes temporary field facts;
assignment uses the selected complete source contract. Owned record results transfer
before cleanup, and remaining temporaries finish in reverse construction order.

Scope filtering uses the existing Local_Flow scope model. Each block starts from an
explicit copy of its entry facts. Exit destruction runs on a second private copy;
only preceding mutation observations propagate back, while states remain available
unchanged for sibling exits and normal-return summaries. Borrowed parameter leaves
retain their caller obligations. Aggregate result handling follows the prototype's
static resource-field list, distinct from a raw descriptor's root location.

Eight PHP/native cases establish balanced and leaking local owners, nested scope
exits, empty release, owned/empty indexed assignment, and supplied complete record
construction/destruction contracts. Assertions check entry immutability and repeated
exit checks without sibling state contamination. These are focused block/exit proofs,
not complete coverage of result/copy/assignment contracts or a whole ownership stage.
Those paths require continuing integration coverage with fixed-point analysis and
accepted lifecycle summaries. The first native build passes without corrections;
one PHP fixture changed an unsupported int-to-int32 assignment to Storage<int>.

The solver must assemble consistent contracts, bindings and expression traversal
for each observation context. Fixed-point merging, caller-summary inference and
final ownership/lifetime acceptance remain unfinished at this checkpoint.

Authoring through first PHP readiness: 207.517 seconds. One PHP fixture correction; no native correction. Native build: 338.974 seconds, first attempt passed. Evidence: results/allocation-pass-01.

## Fixed-point allocation analysis and summary inference (2026-09-23)

Allocation_Flow now owns the prototype's two-pass algorithm. Initial parameter
membership is fixed independently of inferred requirements. A LIFO worklist merges
possible relation edges and preceding parameter mutations until stable. Each block
and sibling exit retains independent snapshots. Solver contexts receive no validation
observations; a second traversal of converged entries collects requirements, access,
mutation, exclusions and return facts. Scope filtering retains the existing Local_Flow
rules. Worklist depth replaces array_pop; named state/entry records replace nested
PHP arrays and implicit clone behavior.

Convergence compares relation and mutation membership by meaning, not PHP map insertion
order. Publication uses ascending block IDs. Canonical alias exclusions remain a set;
byte-identical incidental PHP key order is not a requirement. Summary inference keeps
parameter positions and static field paths explicit, intersects deterministic return
requirements, preserves const identity and demands fixed owned result states. Raw
allocation-owner parameters retain the prototype's contract rejection.

Allocation_Entry and Allocation_Analysis capture immutable membership and the exact
Checked_Body. They are allocation evidence, not a completed lifetime authorization.
The worker is one-shot; summary() now requires successful analysis rather than yielding
an uncomputed empty contract. Accepted ownership task/result provenance and complete
lifetime publication remain separate obligations.

15 PHP/native cases prove balanced/leaking owners, consistent/inconsistent branch
merges, loop fixed points, loop-local scope filtering, unreachable code, empty analysis,
borrowed record-field requirements and raw-owner parameter rejection. Tests assert
complete reachable-block membership, detached exported maps, exact converged relations
for branch/loop/parameter cases, a 32-branch stress case and one-shot behavior. These
are independent expected-behavior integration proofs, not a full retained-worker
differential. The first native build passed; one checker-only fixture correction
preceded first PHP readiness. Final ownership acceptance and remaining owned-result/
mutable-lifecycle integrations still need their selected proofs.

Authoring through first PHP readiness: 180.64 seconds. One checker-only fixture correction; no PHP behavior/native correction. Native build: 310.149 seconds, first attempt passed. Evidence: results/allocation-flow-01.

## Ownership tasks and lifecycle workers (2026-09-23)

Ownership_Task captures exactly one checked body or lifecycle subject plus fixed
dependency-summary membership. Two checked nullable handles replace the prototype's
PHP union; ambiguous or absent subjects reject. Ownership_Lifecycle retains exact
type/definition/role/body identity and ordered named Ownership_Child rows. Copied
membership and immutable summary handles make reuse inputs explicit. Lifecycle_Order
is computed as a fresh compact value, avoiding PHP object sharing of mutable rows.
Ownership_Result retains the exact producing task and rejects mismatched allocation
body provenance. Final batch acceptance is still a separate owner.

Ownership_Worker now has an explicit one-shot task instance so attributed body-flow
failures remain accessible after an exception. Body work delegates to Allocation_Flow;
lifecycle work composes child and body contracts in the preserved order. Constructor
and destructor requirements, independent source/destination lanes, alias endpoint
prefixing, and discharge restrictions retain their meaning. Named private mutable
parameter builders replace nested arrays and by-reference scalar/container mutation;
published Parameter_Effects copy their field membership. Child order remains a
preparation responsibility, including reverse destruction and custom member roles.

53 PHP/native lifecycle outcomes agree with the preserved Ownership_Worker. Its host
bridge supplies only the definition name/resource paths actually consumed, using a
minimal reflected definition; it does not prove old type construction. Additional
PHP/native assertions cover real body tasks, exact allocation/task identity, captured
dependency maps, detached children/order values, malformed subject alternatives,
provenance rejection and one-shot operation.

Review also corrected allocation parameter classification to use the existing
Semantic_Modes::is_borrow owner, matching the prototype and excluding byte-span
passing. The focused allocation PHP suite passes. First worker native build passed;
a second final build verifies the current dependency revision after that review
correction. This is a verification build, not a response to a native failure.
Ownership batch acceptance, request selection/scheduling and complete lifetime
publication remain uncompleted at this checkpoint.

Authoring through first PHP readiness: 177.566 seconds. First native passed, zero corrective cycles; one subsequent verification build after dependency review. Final native build: 338.492 seconds. Evidence: results/ownership-worker-01.

## Ownership batch acceptance (2026-09-23)

Ownership_Join validates an entire fixed task batch before publishing accepted
results. Exact task identity, allocation-body provenance, parameter and resource-path
membership, const-source rules and complete owned-result fields are checked. Immutable
Ownership_Summary construction already owns endpoint and state-domain validation.
Previous results remain unchanged on rejection. Reuse retains only a semantically
equal previous summary handle; the current task and allocation provenance survive.

Ownership_Contracts replaces prototype PHP object equality with explicit typed
membership and transition comparison. Parameter/field insertion order and canonical
alias ordering are not semantic differences. All four transition facts, parameter
and field membership, alias exclusions and owned-result states participate. This
avoids depending on PHP array order or object comparison in the native result.

The focused proof passes 15 PHP/native acceptance/rejection cases and 18 symmetric
comparison assertions. It covers reordered batches, equal-summary retention, changed
contracts, a real checked-body worker result, const/move sources, malformed fields,
incomplete/duplicate/foreign/stale results and unchanged previous identities after
failure. First native build passed without corrective cycles.

Request selection and scheduling, complete lifetime publication and whole-stage
coordination remain outside this checkpoint. This is a concrete typed join; it does
not introduce a generic callable interface or change the runtime-preparation tool.

Authoring through first PHP readiness: 95.997 seconds. Native build: 347.282 seconds; one attempt, zero corrective cycles. Evidence: results/ownership-join-01.

## Ownership dependency queue (2026-09-23)

Ownership_Request captures one checked-body or lifecycle subject and deduplicated
required keys. It is deliberately separate from Ownership_Task, which receives actual
accepted dependency summaries. Named queue nodes own remaining counts and reverse
edges; missing keys and duplicate requests reject before execution.

Ownership_Queue executes dependency-ready batches. It captures the complete batch's
inputs before running workers, accepts results through Ownership_Join before waking
dependents, and publishes only a complete current result map. Unsupported dependency
cycles retain an explicit diagnostic. Worker exceptions retain attributed diagnostics;
no partially accumulated results are returned. This is sequential execution, not a
threading proof. The queue is one-shot.

Incremental reuse checks exact body identity or explicit lifecycle subject facts,
plus exact dependency-summary handles. Rebuilt lifecycle descriptors can reuse work;
removed requests disappear; forced rebuilds recompute tasks but retain equal summary
handles through the join. Typed comparisons replace PHP union tests and ad-hoc nested
array equality without relying on insertion order for keyed dependency membership.

Eight PHP/native cases prove out-of-order readiness, duplicate prerequisite removal,
unchanged reuse, full rebuild, removed work, body replacement, changed dependency
propagation, and missing/duplicate/cyclic request rejection. Additional assertions
verify composed resource transitions, captured accepted handles and one-shot execution.
The changed-dependency fixture changes a synthetic leaf role at a fixed arbitrary
queue key: it proves queue invalidation, not canonical lifecycle-key selection.

Full Ownership_Preparation still needs to select requests from Body_Set and Type_Store,
map source lifecycle bodies, preserve member-role overrides and destruction order,
and collect actual call/construction/return dependencies. Complete analyzed-body
publication and whole-stage coordination remain unfinished. src-runtime-preparation
is unchanged.

Authoring through first PHP readiness: 117.419 seconds. Native build: 345.483 seconds; one attempt, zero corrective cycles. Evidence: results/ownership-queue-01.

## Ownership request selection (2026-09-23)

Ordered type access and named request/child records replace structural catalog copies and ad-hoc nested maps. Selection captures semantic body/lifecycle dependencies; readiness and acceptance stay in Ownership_Queue. Source methods retain concrete callable identities and per-field lifecycle roles. See specs/portability/compiler_ownership_selection_slice.md for scope and explicit remaining integration obligations.

Integration uncovered the preserved prototype mismatch between RETURN_STORE for zero-initialized record returns and allocation analysis requiring a construction return. Allocation_Pass now transfers already prepared facts only for RETURN_STORE + VALUE_RECORD_DEFAULT; arbitrary resource stores remain rejected. Direct and called owned returns assert exact empty nested result states. Eight PHP/native scenarios pass, with 23 existing PHP allocation regressions.

First native build passed without correction. Detailed authoring, fixture correction and first-PHP timing evidence: results/ownership-selection-01/effort.json.
