# PHP stage joins
Doc Status: supporting

The [organization convention](../code_organization.md#join-organization-in-the-php-prototype)
gives each implemented task-result acceptance boundary its own process-local
file, including descriptive `*_join.php` names. All owners implement
[compile/Join](../../src/compile/join.php). Construction captures context
and selected tasks through assignments only; instance `join(array $results)`
validates and reconciles them, returning the owner's concrete output.

```php
$join = new Resolution_Join($previous, $symbols, $tasks, $catalog);
$current = $join->join($results);
```

Paths below are relative to `src/`.

| Process | Join file and owner | Accepted output |
|---|---|---|
| Native family preparation import | [load_runtime/family_preparation_join.php](../../src/01_prepare_inputs/load_runtime/family_preparation_join.php), `Family_Preparation_Join` | Complete instance/package/operation associations with exact internal bindings and semantic contracts; canonical materialization follows in type resolution |
| Runtime package composition | [load_runtime/input_join.php](../../src/01_prepare_inputs/load_runtime/input_join.php), `Input_Join` | Validated package membership, composed catalog and provider-scoped input set |
| Directory discovery | [read_sources/scan_join.php](../../src/01_prepare_inputs/read_sources/scan_join.php), `Source_Scan_Join` | Scanned directory batch, reconciled file identities and next directory tasks |
| Source reading | [read_sources/snapshot_join.php](../../src/01_prepare_inputs/read_sources/snapshot_join.php), `Snapshot_Join` | Current source snapshots |
| Tokenization | [02_tokenize/join.php](../../src/02_tokenize/join.php), `Token_Join` | Current token buffers |
| Parsing | [03_parse/join.php](../../src/03_parse/join.php), `Frontend_Join` | Current parsed files |
| Declaration collection | [collect_symbols/join.php](../../src/04_analyze/collect_symbols/join.php), `Declaration_Join` | Symbol identities, membership and initial changes |
| Declaration comparison | [collect_symbols/comparison_join.php](../../src/04_analyze/collect_symbols/comparison_join.php), `Comparison_Join` | Completed change catalog |
| Name resolution | [resolve_symbols/join.php](../../src/04_analyze/resolve_symbols/join.php), `Resolution_Join` | Current declaration/body bindings |
| Template permissions | [check_templates/join.php](../../src/04_analyze/check_templates/join.php), `Template_Join` | Current definition permission results with exact declaration/binding dependencies |
| Literal constants | [instantiate/constants.php](../../src/04_analyze/instantiate/constants.php), `Constant_Join` | Selected typed literal values |
| Explicit applications | [instantiate/join.php](../../src/04_analyze/instantiate/join.php), `Instance_Join` | Exact instance identities and occurrence targets in a private candidate |
| Member declarations/applications | [instantiate/member_join.php](../../src/04_analyze/instantiate/member_join.php), `Member_Join` | Selected receiver/member contracts and concrete callable targets in Instance_Store |
| Signature types | [resolve_types/signature_join.php](../../src/04_analyze/resolve_types/signature_join.php), `Signature_Join` | Materialized signature associations |
| Local types | [resolve_types/local_join.php](../../src/04_analyze/resolve_types/local_join.php), `Local_Type_Join` | Local type associations using completed signatures |
| Body checking | [check_bodies/join.php](../../src/04_analyze/check_bodies/join.php), `Body_Join` | Current checked bodies |
| Lifetime analysis | [analyze_lifetimes/join.php](../../src/04_analyze/analyze_lifetimes/join.php), `Lifetime_Join` | Current per-body lifetime facts |
| Lifecycle ABI preparation | [prepare_backend/lifecycle_join.php](../../src/05_generate_code/prepare_backend/lifecycle_join.php), `Lifecycle_Join` | Complete implicit targets before backend-context acceptance |
| Selected source exports | [prepare_backend/source_export_join.php](../../src/05_generate_code/prepare_backend/source_export_join.php), `Source_Export_Join` | Exact selected source/layout/lifecycle/import-ABI declarations consumed by native family preparation |
| Source export verification | [analyze_lifetimes/export_join.php](../../src/04_analyze/analyze_lifetimes/export_join.php), `Export_Join` | Current complete-operation evidence after body/lifetime analysis |
| Backend preparation | [prepare_backend/backend_join.php](../../src/05_generate_code/prepare_backend/backend_join.php), `Backend_Join` | Prepared callable binding context |
| Lowering | [lower/join.php](../../src/05_generate_code/lower/join.php), `Lowering_Join` | Current lowered plans |
| Function emission | [emit_llvm/join.php](../../src/05_generate_code/emit_llvm/join.php), `Emission_Join` | Accepted functions grouped by source file |
| Module assembly | [emit_llvm/module_join.php](../../src/05_generate_code/emit_llvm/module_join.php), `Module_Join` | Current emitted program |
| Native object compilation | [build_native/join.php](../../src/06_build_output/build_native/join.php), `Native_Join` | Accepted object set before linking |

## Shared role, distinct contracts

Joins check selected tasks and returned results against their current inputs,
reject duplicate/stale/incomplete batches, and establish ordered output. Where
the phase supports retention, they reuse valid unchanged contributions and
exclude removed participants. Existing stage methods remain the coordinator's
entry points; no caller needs a second execution path.

Instance/member joins validate a fixed batch before updating the private
Instance_Store. Workers have completed before these mutations; later batches see
accepted state. Concrete_Preparation publishes one immutable Instance_Set at the
end. Rejected provenance leaves the registry untouched; materialization failures
require discarding the private candidate.

Directory discovery mutates only its private discovery candidate and returns
the next breadth-first batch. Signature and local joins materialize into the
coordinator's private type candidate after validation. Failures still require
discarding those candidates. Other batch joins construct replacement sets
or lists, retaining exact valid input/result objects.

`Frontend_Join` owns an accumulator because parsing accepts segments before
finalization. Its `merge()` and `finish()` methods remain instance operations.
The common `join()` entry merges the supplied batch and calls `finish()`; it
can complete earlier accepted segments. Duplicate/stale task checks happen on
first join(), merge() or finish(), including empty results. Other joins accept
complete batches through instance join() methods over their captured context.
Joins do not add a status machine, shared algorithm or base class.

Checks shared by selection and joining live in process-local utilities:
`Frontend_Validation`, `Resolution_Validity`, `Signature_Validity`,
`Local_Type_Validity`, `Body_Validity`, `Callable_Contract` and `Module_Validity`.
Their algorithms and dependency checks are preserved. The LLVM callable
utility reads policy constants from `LLVM_Backend`; join extraction introduces
no second authority for linkage or calling conventions.

## Boundaries that are different concepts

- `Source_Paths::join()` concatenates paths; it remains in `paths.php`.
- Control-flow merging inside body/lifetime workers is part of their analysis.
- Manifest and language-catalog loading have no independent task-result batch.
  Prepared runtime package reads now use `Input_Join`; the adapter retains per-package
  validation, while the importer owns batch reservations and their release.
- `Type_Resolver()` coordinates record, signature and local joins and wraps their
  completed associations; that final container construction stays there.
- Native linking and session publication remain separate operations after
  object acceptance. A join does not publish compiler state or artifacts.

This is a PHP organization change. It adds no language feature, worker
scheduling, data field, invalidation rule or PHP++ port.

## Verification

The full `python3 tests/run.py` suite passed after extraction, including
real source-to-native execution, full/selective reuse, exports, input purity,
reversed result order, stale/incomplete batch rejection and failure recovery.
A before/after source comparison also confirmed that all 68 original static
method bodies in the touched processing files survive unchanged apart from
owner qualification. No benchmarks were run.

## PHP typing and native-port preparation

`compile\Join` is a marker. Each concrete owner declares its own typed input and
output contract; the marker provides no callable polymorphic `join` method. Constructor annotations specify the element
types of selected tasks and other array inputs; join() annotations specify worker
result elements. The array-returning joins have these concrete meanings:

| Join | Returned array elements |
|---|---|
| Source_Scan_Join | source_scan_task: next breadth-first directory batch |
| Signature_Join | Callable_Signature: ordered current callable associations |
| Local_Type_Join | Local_Types: ordered current local-type associations |
| Native_Join | Native_Object: ordered accepted objects for linking |
| Storage_Join | prepared_storage_primitive: current native prefix ABI targets |
| Source_Export_Join | source_type_export: accepted exact source/layout/lifecycle/import-ABI associations keyed by canonical type ID |
| Export_Join | export_verification: exact current plan/body/ownership evidence keyed by complete operation link |
| Lifecycle_Join | abi_target: current imported and source implicit targets |
| Lifecycle_Emission_Join | emitted_lifecycle: accepted generated type-operation definitions |
| Layout_Join | storage_layout: accepted target layouts keyed by canonical type ID |

During migration, replace unparameterized arrays with explicit typed container
contracts, preserving identity, order and copy/reference behavior. Source_Scan_Join
now proves this path in PHP and native execution. Other owners require their own
adaptation and proofs; no shared result-wrapper hierarchy is introduced.

[Join contract tests](../../tests/compile/join_contracts.php) check marker membership
and invoke each concrete operation through host-only reflection, exercising empty
batches, concrete outputs, retained-input purity and native object reuse. Existing stage tests retain nonempty, reversed,
duplicate/stale/incomplete and parser segmented-acceptance proofs.

`Record_Join` accepts normalized structural requests into the private `Type_Store`.
`Layout_Join` accepts selected target measurements for explicit root subsets, retaining
current unselected layouts. Early record preparation and final backend preparation use
the same update-scoped Layout_Coordinator. Fixed dependency graphs replace mutable
type-store worker inputs; acceptance and reuse validate lineage, target and exact
reachable constituent contracts.
Both have marker-contract and nonempty reversed/rejected-batch proofs.

Record_Join now accepts selected source and normalized provider declarations;
Record_Definitions owns common canonical materialization. Layout_Join verifies any
imported native layout constraints before accepting the current target layouts.

`Ownership_Join` accepts fixed body/lifecycle tasks through its concrete typed operation.
It validates parameter/path coverage and const-source preservation before reusing
equal summary meaning; growing-list proofs cover reordered and rejected batches.
