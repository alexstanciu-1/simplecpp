# Compiler adoption and portability migration
Doc Status: planning

See [the current dependency frontier](current_frontier.md) for next actions and
accepted migration decisions and outstanding target requirements.

**Rewrite reset completed; source collection proved:** input preparation, tokenization,
parser storage/grammar/queries/project reuse, source symbols, entry selection, representations, lifecycle contracts and scalar catalog/entry binding provide
101 active production files, including declaration lookup, lexical/body binding, project resolution acceptance/reuse, canonical type storage, symbolic terms and provider-family semantic contracts. Source collection adds 97 PHP/native outcomes
and nine host purity assertions. Source entry selection adds 44 PHP/native outcomes and nine host purity checks; its scalar catalog return-type binding is now proved (116 PHP/native outcomes).
See [file parser](../../portability/file_parser.md).
See [parser foundation](../../portability/parser_foundation.md).
`src-runtime-preparation` stays PHP as-is for now; do not convert it.
See [tokenizer and reused units](../../portability/tokenizer.md).
See [verified read scope and timings](../../portability/verified_source_reads.md).
See [discovery scope and timings](../../portability/source_discovery.md).
The previous compiler and 39-file proof are preserved reference, not active coverage.
See [the reset record](rewrite_reset.md) and [manifest stage](../../portability/project_manifest_reading.md).

Pre-reset adoption history follows; its results are reusable evidence.

Date: 2026-09-21. Phase: full source adoption and history preservation completed;
relocation validation recorded in [the adoption checkpoint](adoption.md).
The [first portability component](../../portability/compiler_context_slice.md),
`compile\Update_Context`, now passes PHP/native proofs. The [native representation gate](representation_gate.md)
now passes on selected release v0.1.76; the older provider pin remains separate. The baseline
also records a default-concurrency timeout; see the results before treating it as
a clean gate.

See the [native-aware convertible-PHP work list](native_aware_portable_php_plan.md)
for the planned record, alias, storage and skill-guidance slice.

## Current methodology: stage-by-stage rewrite

The user confirmed restarting the prototype-to-convertible-PHP rewrite on
2026-09-22. Preserve the converter, PHP framework, skills, tests, findings and
original prototype reference at `/home/alexv/__AI/scpp_compiler_3/prototype/src`.
Reuse suitable adapted components, but do not let the previous implementation or
39-file readiness checkpoint dictate the new internal design. Restarting is a work
strategy, not authorization to delete sources/evidence or create a second evolving
compiler. Keep one maintained development home here and complete migration before
adding compiler functionality.

For each component:

1. Read the original implementation and relevant tests. Load both the
   [portable-PHP skill](../../../.agents/skills/simple-cpp-portable-php/SKILL.md) and
   [Simple C++ strict skill](../../../.agents/skills/simple-cpp-php-strict/SKILL.md).
   Write executable PHP for both PHP and converted native execution; target support
   does not automatically imply converter support.
2. Define a short contract: inputs, outputs, important rejection behavior, ownership,
   IDs versus positions, absent sentinels and mutation/deletion policy. Preserve
   intended language behavior and external protocols; internals need not be replicas.
3. Implement a complete stage or coherent responsibility. Reuse good algorithms and
   replace awkward PHP mechanisms with deliberate record/class/vector/hash choices.
   Report wide cross-owner decisions before implementing them.
4. Iterate primarily through PHP and frequent cheap checker/converter runs. Compile
   natively when introducing a new representation/capability and at component
   completion, rather than after every edit. Do not accumulate unproved native
   assumptions or call a PHP-only stage native-ready.
5. Compare meaningful results: valid-program meaning, relevant invalid-program
   rejection, clean-versus-incremental agreement and PHP/native outcomes. Exact bytes
   are required only by an actual contract, such as source bytes/spans, binary output
   or specified serialization. Existing stdout equality is a useful test technique,
   not a blanket future requirement. Preserve diagnostic details and identity only
   where algorithms or consumers depend on them; investigate prototype disagreements
   rather than automatically copying bugs or weakening acceptance.
6. Consolidate once per component: significant design changes and reasons, validation,
   remaining limits and a working commit. Update the skill for reusable lessons,
   linking detailed evidence instead of duplicating it across documents.
7. Record authoring, portability-debugging and validation effort for initial stages;
   record the first passing PHP-behavior checkpoint and separate authoring/PHP debugging
   from subsequent conversion/native stabilization, regression verification and
   consolidation (see [timing boundaries](../../portability/validation_workflow.md#migration-timing-checkpoints));
   adjust batch size and estimates from observed work. The earlier 80–200-hour
   preservation-led estimate is not a commitment or an estimate for this approach.

### Pipeline order and first milestone

Start with a usable input-preparation path, not the tokenizer:

1. Project manifest reading, schema validation and normalized project records.
2. Source path resolution and directory/file discovery, including selection rules.
3. Verified source reads/snapshots and the source-set result consumed downstream.
4. Tokenization, then parsing.
5. Semantic analysis by responsibility (collection, resolution, type checking,
   lifetime analysis), then backend/code generation and build-output integration.

Runtime/package metadata preparation and driver integration join where their
actual dependencies require them; folder numbering is not a reason to delay an
independent useful component. If a concrete target capability blocks native proof
(e.g. the JSON document API tracked in #240), document the boundary and continue
independent input work. Do not remove schema checks or fabricate successful native
coverage to force strict sequential progress. Verify issue/candidate status when
that component is selected.

Keep unions and deeper layout tuning for a later optimization pass unless a concrete
stage needs them. Explicit kinds/tags can leave that path open without premature
packing or a union-emulation framework. Small scalar records and stable-local aliases
already have a [proved contract](../../portability/value_records.md).

The following completed-slice history describes reusable assets from the earlier
migration approach, not the implementation order for the restart.

## Preserved migration checkpoints

The user resumed bounded portability work after authoring/check/proof preparation.
[Fixed tool-service request contracts](../../portability/compiler_tool_contracts_slice.md)
and [fixed source read tasks](../../portability/compiler_read_tasks_slice.md)
and [discovery records](../../portability/compiler_discovery_records_slice.md)
and [Source_Set indexes and snapshots](../../portability/compiler_source_set_slice.md)
now pass the cumulative PHP/native proof; 39 production files are ready.
The [source-scan result record](../../portability/compiler_scan_result_slice.md)
and [source-scan reconciliation](../../portability/compiler_scan_join_slice.md)
are also proved, including the approved marker Join contract. Source reader and
remaining coordinator algorithms still need migration.
[Snapshot acceptance](../../portability/compiler_snapshot_join_slice.md) now also
passes PHP/native proofs. [Fixed read selection](../../portability/compiler_read_selection_slice.md)
is now extracted and proved, with Source_Reader delegating to it.
[Token storage](../../portability/compiler_token_store_slice.md) now also passes
nullable lookup, identity, constructor validation and JSON/byte-range proofs.
[Token acceptance](../../portability/compiler_token_join_slice.md) now also passes
PHP/native identity, membership, ordering and rejection proofs.
[Token selection](../../portability/compiler_token_selection_slice.md) is also
extracted and proved, with the existing phase using its pure selection owner.
[Completed file frontends](../../portability/compiler_frontend_record_slice.md)
now bind their inputs at construction and pass validation/export proofs.
[Frontend storage and segmented acceptance](../../portability/compiler_frontend_join_slice.md)
now pass PHP/native proofs for partial batches, rejection recovery and retained identity.
[Binary syntax](../../portability/compiler_binary_syntax_slice.md) now passes
PHP/native proofs and 5,000 frozen-prototype scoped-angle comparisons.
[Parser selection](../../portability/compiler_parser_selection_slice.md) now also
passes current-source/token identity and full/incremental membership proofs.
[Logical syntax comparison](../../portability/compiler_syntax_comparer_slice.md)
now uses typed traversal frames and passes PHP/native and frozen-oracle proofs.
[Quoted-byte decoding](../../portability/compiler_byte_literals_slice.md) now preserves
escapes and diagnostics with a proved byte-construction framework adapter.
[Semantic type-reference records](../../portability/compiler_type_references_slice.md)
now preserve heterogeneous interface collections and independent list membership.
[Native project identity/roots](../../portability/compiler_native_project_slice.md)
now preserves lexical roots and constructor validation without filesystem access.
[Manifest snapshot export](../../portability/compiler_manifest_record_slice.md)
now retains exact JSON schema, virtual content and wrapped diagnostics.
[Source directory scanning](../../portability/compiler_source_scanner_slice.md)
now preserves real discovery/selection behavior through filesystem adapters.
[Runtime-preparation symbol spelling](../../portability/compiler_preparation_symbols_slice.md)
now preserves complete reversible link identities; its retained preparation suite
passes 170 checks. Latest cumulative evidence: `results/preparation-symbols-01`.
The [struct-member cursor decision](struct_member_cursor_decision.md) was accepted
on 2026-09-22. The typed cursor and ten consumers now pass the focused PHP proof
and 39 retained fixtures. Query dependencies now convert and pass 27,560 PHP oracle comparisons, and the cumulative native query/cursor proof now passes on adopted a1a1babd after
explicit dependent guards; see [the adaptation record](php_adaptation_record.md#explicit-control-flow-for-dependent-guards).
Latest evidence: `results/structural-queries-cumulative-01`. The ready count is 39.
[Source diagnostic preflight](source_diagnostic_preflight.md) records the next
qualified-base target blocker (#233) and a passing typed-dispatch native control.
It adds no file to the ready set.
Earlier pause and file-count statements below describe their historical checkpoints.
Process/lock facade parity passes on adopted candidate `a1a1babd`; integration
into compiler owners still requires component proofs.

The [PHP adaptation record](php_adaptation_record.md) records broad source changes,
their reasons, preserved contracts and questions for a future optimization pass.
Update it when a migration slice introduces a materially different source shape.

## Agreed objective

Adopt the existing compiler as one complete development line, then make its
implementation portability-ready. Preserve the native preparation/lowering/LLVM
path and its tests. Complete the full agreed migration before adding compiler
functionality, including the new C++ backend. Extensions to the portability tool
and PHP support framework required to preserve existing behavior are migration work.

After adoption this repository is the sole compiler development home. The original
checkout is a preserved reference, not an independently maintained fork. During
adoption each component has one writable owner. Do not retire or delete the original
until preservation and baseline equivalence are verified.

## Deliverables

- [Deferred Simple C++ batch](simple_cpp_batch.md): target helpers and capabilities
  collected for later joint resolution, starting with typed map/filter helpers.

- [UTF-8 text contract](../../portability/utf8_text_contract.md): managed code-point
  helpers, malformed-input rejection, and explicit byte operations. The eleven
  ready files keep their byte-sensitive behavior; migration remains paused.

- [Restricted traits and incremental index](../../portability/traits_and_incremental_index.md):
  authorized tool improvement during the broader migration pause. Production
  readiness remains eleven files; no compiler algorithms were migrated in this slice.

- [Authoring-shape audit](authoring_shape_audit.md): production census, required
  source adaptations, representation decisions and target enhancement candidates.
  Audit performed while the migration goal is paused; no rewrites started.

- [First component slice](../../portability/compiler_context_slice.md): shared update
  context, locally converted and run on v0.1.76 with its existing compiler witnesses.

- [Native representation gate](representation_gate.md): all nine probes pass on
  v0.1.76, including richer fields and struct value construction.
- [Adoption checkpoint](adoption.md), [per-file adopted hashes](adoption_inventory.json)
  and [relocation-only diff](relocation.patch).
- [Migration boundary and dependency map](boundary.md).
- [Baseline validation plan and results](baseline.md), distinguishing original
  suite outcomes from setup corrections and focused retries.
- [Per-file inventory](source_inventory.json): all 2,968 tracked source-repository
  files, byte counts, SHA-256 fingerprints, preservation roles and proposed target
  paths. These are fingerprints, never compiler entity identities.

The source revision is `75e9b0f7c3f6420255b3b126429afbfa0e13ccb1` at
`/home/alexv/__AI/scpp_compiler_3`. Tracked/untracked non-ignored status was clean.
The configured vendored Simple C++ repository is also clean at
`fc20d73d040c4e69758bcec0b1caf40c26755f72`, matching `tools/toolchain.json`.
The original inventory remains the pre-adoption record. The adoption inventory
records the complete source copy; generated runtime artifacts are rebuilt.

## Next migration step

Continue dependency-ordered portable PHP slices against the verified v0.1.76 target,
separately from the pinned LLVM provider dependency. Carry the
default-concurrency timeout forward explicitly; do not silently increase limits or
discard its test.
The baseline driver and evidence are under `tools/compiler_migration/` and
`results/`. No compiler algorithm or test assertion was changed for the baseline.

The original prototype remains untouched. The
[portability debt](../../portability/debt.md) remains applicable; the native escape
hatch is documentation-only.

The second [portable component slice](../../portability/compiler_token_slice.md)
adds tokenizer vocabulary and rows, with PHP/native evidence in `results/token-02`.
The compiler ready set now contains two production files; full compiler migration
and packed token storage remain unfinished.

The third [portable component slice](../../portability/compiler_step_slice.md)
adds lifecycle states and interfaces. Evidence is in `results/step-03`; the goal
remains active for dependency-ordered slices without new compiler functionality.

The fourth [slice](../../portability/compiler_path_slice.md) extracts and ports
source-path spelling; `results/path-03` records the cumulative successful proof.
The retained `path-02` binary-literal mismatch is recorded as remaining debt.

The [string-byte consolidation](../../portability/compiler_string_bytes_slice.md)
corrects the `strlen` mapping and guards unsafe literals. Cumulative evidence is
in `results/string-bytes-01`; four production files remain ready.

The [parser node slice](../../portability/compiler_nodes_slice.md) ports the
unchanged syntax vocabulary and node row from an extracted parser/data owner.
Five files are ready; `results/nodes-01` contains the cumulative proof.

The [role-view slice](../../portability/compiler_role_views_slice.md) ports eleven
readonly constructor records. `results/roles-04` is the cumulative proof;
`results/readonly-preflight-01` records native enforcement/promotion limitations.

The [cursor slice](../../portability/compiler_cursor_slice.md) ports the remaining
parser data declarations with explicit typed lists. `results/cursor-02` is the
passing cumulative proof; seven production files are ready.

The [storage slice](../../portability/compiler_storage_slice.md) ports source
snapshots and syntax trees. `results/storage-02` records the cumulative proof;
nine production files are ready, while indexed lookup sets remain pending.

The [decimal algorithm slice](../../portability/compiler_decimal_slice.md) extracts
and ports exact positive decimal range checking. Ten production files are ready;
`results/decimal-01` records cumulative native and independent PHP-oracle evidence.

[Exception preflight](exception_boundary.md) records five selected-target probes
and a viable caught-message hierarchy. It prepares the next framework slice;
no additional compiler file is marked ready by these probes.

The [handled-exception slice](../../portability/compiler_exception_slice.md) ports
backend configuration with constructor guards. `results/exception-03` records the
passing cumulative proof and native framework artifact. Eleven files are ready.

The [semantic enum audit](enum_portability_decision.md) found 41 string-backed enums
and seven enum methods unsupported by the selected target. The proposed explicit
typed-tag/codec adaptation across owners was accepted on 2026-09-22; implementation
and proofs remain outstanding. The native lossless JSON document recommendation
was accepted at the same time.

[JSON ingestion preflight](json_ingestion_preflight.md) confirms that the pinned
decoder loses object/list identity needed by manifest validation. A lossless input
boundary is required before porting Manifest_Syntax; snapshot export is unaffected.

Aggregate lifecycle composition is now proved: 36 shared PHP/native outcomes plus
eight host checks; active coverage is 101 files, including native layout, resource obligations and record materialization. See `specs/portability/aggregate_lifecycles.md`.
