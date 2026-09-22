# Nested-family consolidation review
Doc Status: supporting

## Scope and conclusion

Review the completed native-family boundary against the bounded
[foundation sequence](../planning/compiler_foundations.md#accepted-remaining-implementation-sequence).
The required capability proofs for steps 1–8 exist within their documented subsets.
Step 7 covers owned results; owned parameters were explicitly deferred. This review
checks the recently changed preparation/import path and reconciles coverage; it is
not a new audit of every compiler process or a scalability benchmark.

No additional correctness blocker was identified in the reviewed path. One repeated
preparation read was consolidated locally. Previously deferred language features do
not become requirements for closing this bounded sequence.

## Reviewed boundaries

| Concern | Owner and evidence | Conclusion |
|---|---|---|
| One concrete type model | `Concrete_Preparation` materializes accepted family types through the existing type cache; `Package_Adapter` binds imported arguments to exact accepted definitions. | Template origin remains provenance. Downstream checking and lowering use the common type contracts. |
| Metadata authority | `Catalog`, `Requests` and `Arguments` supply declared family semantics and native recipes; the bridge maps exact definitions. | No vector/string/family-name dispatch was introduced. Unsupported argument categories diagnose rather than infer a layout or implementation. |
| Native dependency readiness | Current accepted family results feed later coordinator batches; type-only header discovery is compared with the inner package's manifest. | Workers do not recursively prepare types. Header snapshots are checked at selection, execution and join; publication retains its existing input checks. |
| Imported lifecycle ownership | `Package_Types`, `Family_Preparation_Join` and final `Input_Join` validate imports and owner closure. | Imported rows introduce neither a duplicate definition nor another lifecycle implementation. |
| Work and acceptance | Preparation selects a complete batch, workers return private candidates, joins validate selection and required coverage. | Serial execution retains the same future worker boundary. Publication remains per package, without a multi-package rollback promise. |
| Incremental reuse | Exact specialization identity excludes operation coverage; equivalent contracts retain compiler definitions. | The nested proof preserves earlier snapshots and exact package objects after one body edit. Broader recovery/invalidation policies remain deferred. |
| Execution | `provider_family_nested.php` exercises actual vectors and a second two-argument family. | Copies, assignment, normal/early returns, observed allocation balance, O1/ThinLTO and address checks pass. LeakSanitizer is unavailable under the traced harness. |

## Local cleanup

`Compiler_Bridge::arguments()` previously reopened a shared inner package and
rediscovered its headers for every argument occurrence in a preparation batch.
`prepare()` now owns a local memo indexed by the exact compiler type name within
its fixed accepted input set. Canonical definition identity is still checked before
using the entry. Each validated recipe/header result is read once per batch.

The memo holds no reader leases and is discarded when that coordinator call ends.
Every selected task still carries and verifies its dependency snapshots; final
package reservations still revalidate accepted packages. No mutable cache is added
to retained compiler state or shared with workers. This removes repeated work by
construction; it does not establish a measured compiler speedup.

## Explicitly deferred coverage

- Source-defined native arguments were deferred at this review. The subsequent
  [source-family integration](source_family_integration_plan.md) now proves automatic
  source records; [custom lifecycle exports](custom_source_exports.md) are now also proved.
- The subsequent [managed storage/list proof](source_list_plan.md#managed-growing-list-proof)
  closes the concrete managed-element path. Generic migration, indexed allocation-owner
  elements and generic fixed-array initialization remain deferred.
- Owned source parameters, broader movement, smart-pointer families and recoverable
  exceptions/failure cleanup.
- Additional capability syntax, constant evaluation, escaping interior references,
  actual compiler threading, native porting and broader recovery guarantees.

The subsequent [ordinary runtime argument slice](provider_family_compiler_integration.md#ordinary-managed-runtime-arguments)
extends the common native description/import boundary with `vector<string>` and a
second configured type. The later [source-family integration](source_family_integration_plan.md)
closes the automatic source-record boundary while keeping source operations compiler-owned.

## Validation

The preceding full checkpoint is 102/102 compiler fixtures with ten workers,
plus 163 ordinary and 54 family preparation checks. After the local memo change,
all three focused integration fixtures passed: nested families, scalar append and
family types. These cover shared dependencies, fixed joins, stale-header rejection,
execution and one incremental attempt. PHP syntax, brace/doc-comment, whitespace
and documentation file-link checks also passed. The full suite was not repeated
for this contained change.
