# Compiler portability inventory — 2026-10-22
Doc Status: planning

Audit revision: `394e6edc0` (remote merge through `96c86d3f6`).
User scope: audit and classify the compiler, converter and bindings; discuss before
fixes. This report changes no language contract or implementation. Storage_Cursor
widening remains excluded from implementation, but is included as an inventory item.

Follow-up: [discussion decisions and implementation backlog](compiler_portability_decisions_2026_10_23.md).
That record distinguishes accepted design directions from open questions; implementation
is paused while the contracts are discussed.

## Assessment

There is substantial common ground between executable portable PHP and Simple C++.
The current compiler is **not yet inside the implemented intersection of both routes**.
Successful structural conversion is not evidence of that intersection. The remaining
work is a mixture of source contracts, conversion forms, runtime exposure and deliberately
bounded v0.2 semantics—not one queue of v0.2 bugs.

The immediate merged-source failure is an unsupported empty-array parameter default
in `Native_CPP_Commands::link`. Behind it are broader known requirements: self-handle
use, AST virtual contracts, initialization of enum fields, typed read cursors,
compiler work-object transfer, and publication/JSON runtime bindings.

No new v0.2 defect violating an established supported contract was conclusively
demonstrated by this audit. Several existing rejections were reproduced and found
to agree with explicit restrictions. This is not a claim that v0.2 has no bugs:
native execution and the remaining whole-program diagnostics have not been proved.

## Scope and evidence

The snapshot follows the previous bootstrap envelope: seven stage/core directories,
255 compiler PHP files (including 12 parked LLVM files), and four generated driver/proof
inputs. Host loading, tools and the entire test tree are not converted. Disabling LLVM
execution does not remove its files from this envelope. Framework PHS is separately
assembled by `install_native_runtime.php`; it is not PHP emitted by the converter.

Fresh checks:

- Whole conversion fails at [cpp_commands.php:62](../../compiler/my-try/06_native/cpp_commands.php#L62).
  Framework assembly and bootstrap were not run after this failure.
- Independent in-memory conversion checks accept 258/259 inputs; the same file is the
  sole reported failure. Each failed file stops at its first diagnostic, so this is not
  proof that the offending file contains exactly one unsupported construct.
- PHP-token inspection finds 45 mapped helper names. This separates actual call tokens
  from strings containing emitted C++/PHS. Counts include the driver and parked code,
  and are conservative lexical observations, not a reachability analysis.
- Twenty small source probes ran with both ordinary and prelowered output (40 attempts).
  Results distinguish preparation/emission acceptance from rejection. No probe's
  generated C++ was compiled or executed during this audit.
- The previous qualified-name bootstrap log contains 811 affected work entries under
  59 diagnostic messages. Many entries propagate a failed declaration or dependency.
  These are **historical observations**, not 811 defects or 59 independent root causes.

[Machine-readable evidence](compiler_portability_inventory_2026_10_22.json) contains
the revision, source hashes, per-file failure, helper policies/example locations,
all final probe sources/results, and every historical diagnostic group with examples.
Raw artifacts: `/tmp/compiler-portability-audit-20261022/`.

The previous full bootstrap's 190.40 s / 418.8 MiB RSS is a failed preparation run
at the preceding source checkpoint, not a measurement of this merged revision or
an estimate of completed self-compilation. No full bootstrap or native compiler
rebuild was performed here. Earlier native compiler checkpoints and generated-program
proofs remain distinct; see [portability status](../../compiler/my-try/docs/portability/conversion_review.md).

## Classification

| Code | Category | Meaning |
| --- | --- | --- |
| A | Portable PHP source adaptation | An existing equivalent can express the intent, but the authored shape does not fit the shared subset. |
| S | Specification/contract decision | Meaning, ownership, initialization or version scope needs agreement or reconciliation. |
| V | v0.2 bug | Evidence shows an implementation contradicting its supported contract. A rejection alone is insufficient. |
| D | Documented v0.2 gap or deferral | A known capability is absent or explicitly bounded. Record whether it is promised or expressly excluded. |
| N | No supported equivalent in either route | The required behavior—not just its PHP spelling—has no established common native equivalent. |
| C | Converter bug or limitation | PHP-to-PHS parsing/emission cannot express the requirement correctly for the chosen route. |
| B | Binding/framework bug or limitation | Mapping, signature, adapter, module exposure or concrete transport is absent/incompatible. |

Rows may have a primary and secondary category. **Unknown** is an evidence status,
not a reason to classify something as V or N. Normative specs outrank guide prose,
tests and historical conversion success under [the authority map](../spec_map.md).

## Current gaps and decisions

| ID | Requirement and evidence | Classification | Route distinction and recommended owner; no fix performed |
| --- | --- | --- | --- |
| C01 | `Native_CPP_Commands::link(..., array $provider_options /** vector<string> */ = [])` is rejected by `Converter::methodSignature`. Fresh conversion failure. A direct v0.2 vector-default probe also rejects. | **C limitation + D default boundary** | Not merely an emission bug: teaching the converter `[]` would still leave v0.2 admission. Review explicit call arguments or a supported portable optional form first; converter signatures and callable defaults are separate owners. Current fresh-source conversion is blocked for both routes. |
| C02 | Annotated empty expressions such as `/** vector<int> */ []` become `new vector<int>()`; `Symbol_Collector::refresh_occurrences` uses them. Fresh PHS `new vector<int>()` rejects, while a typed empty vector local emits. | **C target-compatibility gap + A option** | The documented spelling has v0.1 proof. It is not an inherently nonportable operation. Converter expression emission/common typed construction owns the gap; a typed local is a candidate source adaptation, not an implemented repair. See [empty expressions](../portability/authoring_guide.md#explicit-empty-container-expressions). |
| A01 | AST concrete defaults are overridden by descendants/expanded traits: `clear_preparation`, and other AST observation hooks. `loop_node` was the first failing type contract. Abstract-method control passes; concrete override rejects in both modes. | **A + D explicit restriction** | PHP ordinary virtual dispatch is broader than [v0.2 inheritance](../inheritance_v0_2.md#abstract-types-and-overriding). v0.1 has historical compiler evidence. Review the whole AST contract together; do not convert each next method to abstract without reviewing its rightful family/default owner. The earlier collected-query/name repairs are completed examples, not permission to redesign the AST. |
| A02 | `return null` from `?Class`/interface accessors, and explicit null arguments. Raw-null probes reject; a typed empty shared handle returned from the same signature emits. Production examples include `preparation_work_owner` and iterator exhaustion. | **A + D explicit restriction** | [Handle defaults](../type_use_modifiers.md#null-defaults-for-handle-parameters--2026-10-21) deliberately did not widen argument/return conversions. `Ownership_Conversions::decide` admits raw null only at assignment. Not a demonstrated regression. Decide on common source spelling versus an explicit boundary extension. |
| D01 | Passing/returning `$this`, including `worker->enter($this)` and constructing typed child iterators with `$this`. Fresh self-handle return rejects. | **D + S ownership decision** | [Instance methods](../instance_methods_v0_2.md) permit `$this` as a member receiver and bounded closure capture, not a copied/escaped owning handle. This is different from the repaired lexical closure access bug. v0.1 compiler evidence exists; v0.2 needs a truthful receiver-to-handle/lifetime contract or a deliberate source architecture adaptation. Do not fake it with object_cast. |
| C03 | Enum fields are initialized in PHP constructor bodies, e.g. `switch_label::$label_kind`. Fresh body-only enum initialization rejects; `__construct(set:$e)` emits. Numerous historical diagnostics share this cause. | **C common-form gap + A/S initialization review** | [Data initialization](../data_initialization.md) requires initialization before the constructor body and forbids invented enum defaults. PHP cannot literally author `set:`. The converter currently expands promotion/assignments without expressing this contract. Define a supported portable initialization form or an honest source default where one exists; do not silently move arbitrary body statements or add dummy enum values. |
| A03 | `children_iterator` and `pair_children_iterator` separately declare private `position`. Fresh private-shadow probe rejects. | **A + D explicit restriction** | [Inheritance layout](../inheritance_v0_2.md#layout-and-lookup) rejects same-category inherited names even when private. Distinct backing names are a candidate portable adaptation; preserving PHP's private-slot shadowing would be an extension. |
| D02 | `Storage_Cursor<T>` and safe derived-storage-to-base-read widening are used throughout typed AST inspection. | **D + B protocol/exposure gap** | [Cursor contract](../compiler_storage.md#typed-read-cursors), runtime and v0.1 bindings exist. `compiler.json` registers the other three Storage families, not the cursor. Existing typed Iterator support does not supply cursor construction/widening. Metadata alone has not been proved sufficient. Remains excluded from implementation. |
| D03 | Nullable container value boundaries (e.g. nullable vectors in signatures/defaults). A declaration-only probe emits, but passing null through that boundary rejects. | **D bounded payload support** | `Nullable_Conversions::require_value_payload` admits scalar/enum/inline-record payloads, not arbitrary registered containers. v0.1 nullable-container evidence does not establish v0.2 support. Declaration acceptance must not be advertised as end-to-end support. |
| B01 | Eleven safe publication/digest API names are absent from v0.2 module metadata: `scpp_output_permissions`, `scpp_input_digest`, `scpp_output_digest`, `scpp_output_root`, `scpp_output_read`, `scpp_output_write`, `scpp_output_remove`, `scpp_output_sha256`, `scpp_output_stage`, `scpp_output_promote`, `scpp_output_discard`. | **B exposure gap** | Legacy strict symbol/contracts and native implementations are established. A fresh digest-call probe fails name resolution. Current `filesystem.phs` and `output_stage.phs` require these leaves. Audit complete signatures, wrappers, headers and module composition before deciding whether this is metadata-only. PHP-host publication working does not expose these APIs to compiled source. |
| B02 | `Json_Reader` framework uses `json_pull_reader`, which is absent from v0.2 type metadata; fresh type-resolution probe fails. | **B exposure gap; lifetime feasibility unproved** | Native `modules/json/pull_reader.hpp` and v0.1 type mapping exist. Registered `json_document`/`json_node` support is a different API. Audit pull-reader construction, state, result methods and concrete representation; do not replace it with boxed JSON implicitly. |
| D04 | `Compiler::frontend` submits `vector<source_work>` and callbacks capturing the shared queue. `source_work` holds shared records/tokens/parse graphs. | **D + S transfer boundary** | Current `Transfer_Preparation` rejects source-class payloads; the task acceptance report explicitly excludes shared/resource captures. v0.1 native compiler work is historical evidence, while portable PHP executes tasks sequentially. The [unordered publication contract](../builtins/tasks/unordered_publication.md) explicitly discusses parser publication under synchronization, so reconcile that intended use with v0.2's narrower transfer admission. Existing task API coverage is not proof for these graphs. Do not loosen transfer checks or silently serialize the compiler. |
| A04 | Fixed-width token/storage positions meet canonical `int` parameters and literals. Historical failures include integer arithmetic, mutation and exact runtime overload selection. | **A; individual V candidates unproved** | Fresh same-width uint32 arithmetic emits; mixing uint32 with a signed literal rejects with an explicit signedness diagnostic. Do not retain the historical blanket “only canonical int” diagnosis for current code. Normalize meaningful numeric boundaries with range intent; replay specific remaining sites before assigning a compiler defect. |
| D05 | Projected inline receiver method calls, nullable/enum comparisons/probes, indexed assignments and typed construction reported by the old run. | **D/A or V, unresolved per site** | Existing receiver/place/conditional contracts contain deliberate limits; some remote changes may close former failures. The evidence file preserves every group and representative owner. A diagnostic shared by many bodies does not prove a missing general feature; isolate original expressions before choosing owners. |
| A05 | `static_member_reference_node::enum_case_preparation()` narrows a base preparation record. The old log reports a nonpolymorphic source. | **A + D cast prerequisite; current exact site not reproved** | Checked narrowing requires a polymorphic source under [object access](../inheritance_v0_2.md#checked-shared-object-access-and-type-tests). A PHP abstract class alone does not guarantee the required native dispatch/RTTI property. Preserve a truthful typed accessor/family or discuss its contract; never substitute an unchecked cast. |
| B03 | Historical unresolved facade calls include byte creation, text length, exception identity, JSON quoting and process adapters. | **B/secondary dependency, unresolved** | Several named `scpp_portability_*` functions are actually defined by installed PHS, unlike B01's missing leaf symbols. Their diagnostics may propagate failed signatures/bodies or lookup/export issues. Do not register duplicate helpers merely because a call says “unresolved.” Review the facade-to-provider chain. |
| N01 | Former PHP readonly field enforcement. Modifiers have been removed with source TODOs. Fresh v0.2 readonly probe rejects. | **N/S deferred behavior, no longer an active syntax blocker** | Neither route currently establishes the desired portable write-enforcement contract. [CH15-READONLY-FIELDS-01](../../compiler/my-try/docs/catalog/15_blocking_debt.md#readonly-instance-fields--ch15-readonly-fields-01) records the intentional loss of PHP enforcement and the decisions needed before restoring it. Constructor-only writes are currently a convention. |
| S01 | General portability guides contradict newer sections/code on inheritance, finally, trait fields, nullable interface returns, and old prelowered handle/null debt. | **S documentation drift** | Examples: `tools/php_portability/README.md` still says inheritance/finally are outside the subset; `authoring_guide.md` both rejects and later supports nullable interface returns; `weak_fields.md` still describes the now-merged handle/null defect as unfixed. Do not use these stale summaries to classify current source. No guide repairs made in this audit. |

## Requirements already covered, with limits

These are not requests to implement the same functionality again. “Covered” refers
to the stated bounded evidence, not successful compilation of the full compiler.

| Requirement family | Current evidence / remaining limit |
| --- | --- |
| Scalar/control flow, namespaces, classes/interfaces, traits, enums | Structural conversion and extensive existing language proofs. Numeric boundary, dispatch and initialization restrictions above still apply. The converter expands direct traits; a PHP trait is not necessarily emitted as a PHS trait. |
| Storage, Keyed_Storage, Key_Storage_List | Registered runtime types; aliasing/membership/identity and snapshots pass ordinary/prelowered merge checks. These are aliased record collections, not vector value semantics. Cursor remains separate. |
| Typed source Iterator | Exact key/value signatures and retained receiver supported in ordinary/prelowered output. Not PHP Generator lowering or generalized runtime inheritance. |
| Source exception hierarchy | Ordinary/prelowered source descendants and runtime identity checked during merge. Native foreign failures, root factory and framework exception mapping remain separate contracts. |
| Finally and async exception sequencing | Incoming value/loop/async transport retained by merge. Focused functional tests pass; one sanitizer build remained environment-blocked. Does not prove full-compiler lifetime behavior. |
| Weak derivation/acquisition | Common `weak` and `weakref_get`→`lock` spelling exists for both routes. PHP facade retains strong objects; native expiration needs native proof. |
| object_cast / object_is | Common spelling and prelowered transport implemented. Polymorphic narrowing and explicit nullable extraction remain distinct requirements. |
| Object identity hash keys | Common bare class/interface key types, identity and base views pass focused PHP/v0.1/v0.2 tests. No arbitrary value-key or weak-key claim. |
| Nullable scalar and copyable handle null defaults | Implemented and proved in their bounded domains. Explicit raw null handle arguments/returns and nullable container values are separate gaps. |
| Closure lexical member access | The earlier private/protected access bug was repaired. Self-handle copying and cross-thread shared captures are not covered by that repair. |
| Handle/null comparisons in prelowered output | Incoming presence transport fixes the previously recorded defect; focused merge controls pass. Full native scope lookup remains unproved. |
| JSON document/node, filesystem observations, locks, processes, text/byte helpers | Substantial v0.2 registration and existing proofs. Missing publication leaves and JSON pull-reader must not be conflated with these completed families. |
| Retained/incremental compiler behavior | Historical PHP/native compiler checkpoints provide meaningful evidence. Fresh merged-source conversion now fails, so those checkpoints are not a current rebuilt-compiler pass. |

Closed bootstrap issues belong to different categories: parameter ordering was a
converter bug; copied framework parameter spelling was an adapter issue; Storage,
Iterator and source exceptions required semantic/runtime integration; closure lexical
access and prelowered handle/null transport were v0.2 defects; typed punctuation,
numeric boundaries and explicit collected dispatch were source adaptations. Keeping
these distinctions prevents measuring every successful retry as “one compiler bug fixed.”

## What category N does—and does not—contain

There is no evidence from this audit that the compiler algorithm as a whole requires
an impossible-to-represent PHP feature. The concrete former readonly requirement is
an acknowledged unsupported behavior, now removed from active source syntax.

PHP GC/strong-reference behavior and sequential task execution do not establish native
weak expiration, cycle freedom, thread safety or deterministic destruction. These are
explicit proof limits, not automatic bugs and not permission to change lifetimes.
The source-token scan found no `yield`, `eval`, `clone`, include/require, arrow-function
or readonly tokens in this conversion envelope. This does not prove absence of every
dynamic PHP idiom or host-only use outside the envelope. No new general-PHP support
requirement should be inferred from compiler-generated language text in strings.

No full alias/cycle audit, constructor definite-initialization audit, numeric range
audit, mutation-during-Storage-iteration audit or native concurrency audit was performed.
These remain necessary proof boundaries, not fabricated lists of confirmed defects.

## Discussion decisions before more fixes

1. **Choose the bootstrap capability target.** The current envelope includes the native
   build consumer, framework, driver/proofs and parked LLVM files. A narrower C++ emitter
   bootstrap is an explicit scope decision, not an accidental way to call the current
   compiler self-hosted.
2. **Agree the portable object-model boundary.** Review AST virtual defaults and owning
   self references together, while respecting the existing stage/data ownership model.
   `$this` escape is a larger lifetime issue than renaming a field or returning an empty handle.
3. **Agree portable construction.** Determine how valid PHP expresses pre-body enum/record
   initialization for v0.2. Existing PHP body assignment is not sufficient evidence that
   constructor rewriting is safe.
4. **Agree compiler work transfer.** Either provide an explicitly bounded native contract
   for the existing private-candidate/shared-publication design, or discuss a source
   architecture change. Do not infer approval from generic task support.
5. **Batch integration by owner.** Publication APIs, JSON pull-reader, cursor registration,
   converter defaults and typed empty expressions are separate, reviewable inventories.
   Metadata-only sufficiency is a conclusion to prove, not an assumption.
6. **Then resume measured retries.** Use them to validate the agreed slice and expose new
   evidence; do not let the next emitted diagnostic automatically choose language semantics.

Recommended discussion order: object/self lifetime and construction first, task transfer
second, then the bounded converter/binding backlog. Small source adaptations can be
scheduled afterward without hiding the larger prerequisites. No repair is authorized or
performed by this document.
