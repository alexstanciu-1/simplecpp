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
