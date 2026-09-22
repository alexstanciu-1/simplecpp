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
