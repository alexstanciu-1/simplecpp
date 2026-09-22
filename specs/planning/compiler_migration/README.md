# Compiler adoption and portability migration
Doc Status: planning

See [the current dependency frontier](current_frontier.md) for next actions and
accepted migration decisions and outstanding target requirements.

Date: 2026-09-21. Phase: full source adoption and history preservation completed;
relocation validation recorded in [the adoption checkpoint](adoption.md).
The [first portability component](../../portability/compiler_context_slice.md),
`compile\Update_Context`, now passes PHP/native proofs. The [native representation gate](representation_gate.md)
now passes on selected release v0.1.76; the older provider pin remains separate. The baseline
also records a default-concurrency timeout; see the results before treating it as
a clean gate.

## Current resumed slice

The user resumed bounded portability work after authoring/check/proof preparation.
[Fixed tool-service request contracts](../../portability/compiler_tool_contracts_slice.md)
and [fixed source read tasks](../../portability/compiler_read_tasks_slice.md)
and [discovery records](../../portability/compiler_discovery_records_slice.md)
and [Source_Set indexes and snapshots](../../portability/compiler_source_set_slice.md)
now pass the cumulative PHP/native proof; thirty-six production files are ready.
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
and 39 retained fixtures. Query dependencies now convert and pass 27,560 PHP oracle comparisons, and the focused native query/cursor proof now passes on a1a1babd after explicit
dependent guards; see [the adaptation record](php_adaptation_record.md#explicit-control-flow-for-dependent-guards).
see `results/struct-member-cursor-01`. The ready count remains 36.
[Source diagnostic preflight](source_diagnostic_preflight.md) records the next
qualified-base target blocker (#233) and a passing typed-dispatch native control.
It adds no file to the ready set.
Earlier pause and file-count statements below describe their historical checkpoints.
Process/lock facade parity passes on selected candidate `2f0d667f`; integration
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
