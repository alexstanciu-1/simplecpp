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
