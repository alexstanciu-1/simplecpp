# Concrete language verification checks
Doc Status: planning

Prepared 2026-10-18. **119 checks, 36 probe/reconciliation tasks; none executed
for this inventory.** The [machine-readable inventory](language_verification_inventory.json)
contains exact source locators, execution recipes, runner options, source hashes,
330 catalog-card routes, 198 runtime declarations, 283 test/support files, 1,518
root metadata records and the 126-case strict compatibility manifest. It also
retains all 997 cases from the earlier broader discovery manifest for exclusions
and example/runtime/probe reconciliation, plus 661 current catalog/debt
section locators so post-import additions are not lost.

This expands all 36 rows of the [coverage checklist](language_verification_checklist.md).
The listed tests are evidence candidates or partial proofs, **not a claim that they
already satisfy the complete check**. During verification, match the required
observation to actual assertions, run it, and add only the missing probe. Catalogue
routing is by owning family; it does not mark individual cards passed. Scope and
completion rules remain in the [plan](language_verification_plan.md).

## Execution rules

Use a frozen isolated snapshot on `alex-ai@dev002.travelfuse.ro`. No compiler/runtime
fixes, no WSL tests, no blanket invocation of `tests/run.py`. That runner includes
LLVM/native paths and its exporter-argument list does not cover every current suite.

- Select the per-check recipe from the JSON inventory. For `records.py`, use the
  exact `--suite` and `--no-pch`; it exports, compiles and runs the selected cases.
- Some focused runners have a fixed 12-worker pool. Budget **32 total active
  workers** across them; do not launch 32 runners each spawning 12 workers.
- Several runners invoke literal `php`; provide an isolated PATH selecting the
  known mbstring-enabled `/usr/bin/php8.2`, or an explicit supported `--php` option.
  Record CLI/FPM selection. Do not modify the server's global PHP installation.
- Invoke PHP-only suites through `CommandRunner`; pass their required output path.
  An exported `.cpp`/`executions.json` is not executed evidence until its generated
  program is built/run with the shared fixture compiler and the declared oracle.
- Read special CLI arguments and platform/provider requirements before dispatch.
  The JSON recipes are reviewed starting points, not a runnable blanket batch.
- Each result records parse/preparation, C++ compile, runtime, integration and
  recovery evidence separately. A successful rejection must reach the intended
  check; an unrelated early blocker remains blocked evidence.

Suggested execution waves: (1) snapshot/preflight and source-oracle reconciliation;
(2) scalar/control/function boundaries and original strict corpus; (3) wrappers,
containers, objects, ownership and callable interactions; (4) runtime symbols and
project/incremental integration; (5) uncovered probes, deduplicated issue triage and
final coverage reconciliation. Independent checks can share the server pool.

## LV01

Lexing, comments, identifiers, reserved words, literal boundaries, escapes/bytes, precedence/token ambiguity, malformed-source diagnostics and source locations.

Contracts: [rules.md](../../../../generators/php/specs/rules.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV01.001 — Token spans and longest matching | Scan adjacent comparison, shift, exponent and comment forms; preserve exact byte spans and distinguish separated punctuation. | [tokenizer.php:60](../../tests/tokenizer.php#L60) |
| LV01.002 — Lexical rejection and edit recovery | Malformed quotes/comments report the offending source position; repairing the edit restores fresh-equivalent output. | [tokenizer.php:162](../../tests/tokenizer.php#L162) |
| LV01.003 — source-diagnostic | Create one malformed source with CRLF, a multibyte comment and a later bad token; check the declared byte/line convention, then repair incrementally. | Probe/reconciliation task; see [LV01-P01](language_verification_probes.md) |

## LV02

Local declarations/inference, explicit types, initialization/defaults, reassignment, shadowing, duplicate names, scope exits, uninitialized/unknown reads and source constants.

Contracts: [rules.md](../../../../generators/php/specs/rules.md), [namespaces_v0_2.md](../../../../specs/namespaces_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV02.001 — Constant bytes and dependency updates | NUL-containing string and signed float constants preserve values; changing an upstream constant refreshes consumers without stale output. | [source_constants.php:54](../../tests/source_constants.php#L54) |
| LV02.002 — Declaration identity and scopes | Check declaration versus assignment, shadowing and conflicting declarations; rejected edits publish no successful output. | [declaration_model.php:3](../../tests/declaration_model.php#L3) |
| LV02.003 — scope-composition | Exercise same local spelling in sibling blocks and a nested closure, then edit a declaration to a conflict; observe the intended bindings and rejection/repair. | Probe/reconciliation task; see [LV02-P01](language_verification_probes.md) |

## LV03

Type identity and aliases.

Contracts: [compact_layout_types.md](../../../../specs/compact_layout_types.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV03.001 — Integer widths and result domains | Check same-signedness promotion, explicit narrowing limits and result types; signed/unsigned implicit pairs reject. | [integer_widths.php:69](../../tests/integer_widths.php#L69) |
| LV03.002 — Capabilities versus type identity | Check that storage/copy/result-payload permission comes from the declared capability, not spelling or incidental representation. | [type_capabilities.php:6](../../tests/type_capabilities.php#L6) |
| LV03.003 — type-boundaries | For each admitted signedness/width pair, check min/max boundary conversion and result type through local, argument, field and return sites; record unsupported pairs explicitly. | Probe/reconciliation task; see [LV03-P01](language_verification_probes.md) |
| LV03.004 — Union/layout surface | Check declared union payload/layout rules, unsupported fields and size/alignment probes; distinguish source admission from runtime layout support. | [type_catalog.php:1](../../tests/type_catalog.php#L1) |

## LV04

Bool equality/identity, numeric arithmetic/comparison, bitwise/shifts, power, unary signs/not, increment/decrement, compound updates.

Contracts: [spec.md](../../../../runtime/specs/spec.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV04.001 — Direct scalar operators | Bool equality truth table and float/int arithmetic yield exact types/values; division by positive/negative zero reports division_by_zero. | [scalar_admission.php:41](../../tests/scalar_admission.php#L41) |
| LV04.002 — Deferred versus admitted operators | Classify each scalar/fixed-width/wrapper operator rejection against current contract; do not equate bounded implementation with permanent incompatibility. | [operators.php:269](../../tests/operators.php#L269) |
| LV04.003 — operator-completion | Create an operand-family/operator grid including float comparisons, bool equality, unary/mutation symmetry, fixed-width, dynamic, wrappers, power, concat, spaceship, xor and word aliases; record valid/invalid policy before probing. | Probe/reconciliation task; see [LV04-P01](language_verification_probes.md) |

## LV05

Assignment, explicit casts, arguments, returns, fields, elements and reference boundaries.

Contracts: [dynamic_types.md](../../../../specs/dynamic_types.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV05.001 — Typed-boundary conversions | Check declaration, assignment, argument and return conversions retain the correct source/target type and conversion operation. | [conversions.php:73](../../tests/conversions.php#L73) |
| LV05.002 — Boxed extraction and rejected conversion | Check source and destination values on successful and failed boxed conversion; require current mixed/dynamic family separation. | [dynamic_boundaries.php:55](../../tests/dynamic_boundaries.php#L55) |
| LV05.003 — failed-store | Force one invalid boxed conversion at local, field, element, argument and return boundaries with an effect counter; verify which effects occur and that a rejected store preserves the destination. | Probe/reconciliation task; see [LV05-P01](language_verification_probes.md) |

## LV06

Left-to-right eager evaluation/conversion, each operand once, snapshots before RHS mutation, failure ordering, receiver/argument order, lazy `&&`/`\.

Contracts: [expression_evaluation.md](../../../../specs/expression_evaluation.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV06.001 — Eager sequencing and snapshots | Left conversion completes before right evaluation; mutation of a left source cannot change its snapshotted operand value. | [eager_binary.php:35](../../tests/eager_binary.php#L35) |
| LV06.002 — Compound RHS and lifetime | Resolve destination and old value before RHS effects; preserve owners and use the agreed write-back policy on success/failure. | [compound_effects.php:1](../../tests/compound_effects.php#L1) |
| LV06.003 — error-precedence | Make left conversion and right expression independently fail with distinct errors/counters; test eager arithmetic and comparison in both operand orders and lazy alternatives. | Probe/reconciliation task; see [LV06-P01](language_verification_probes.md) |

## LV07

Conditions and negation across families.

Contracts: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV07.001 — Nullable selection states | Selected branch determines value with the declared join type; unselected expressions do not execute; branch order does not change the type. | [nullable.php:48](../../tests/nullable.php#L48) |
| LV07.002 — Shared selection joins | Base/interface joins preserve owner and identity; rejected joins are diagnosed before emission and remain rejected after edits. | [shared_joins.php:7](../../tests/shared_joins.php#L7) |
| LV07.003 — selection-states | Enumerate empty/present nullable and success/error/false/true wrapper states on each selection side; assert selected value/type, RHS count and rejected result_or_bool coalesce. | Probe/reconciliation task; see [LV07-P01](language_verification_probes.md) |

## LV08

if/else forms including brace-free strict fixtures.

Contracts: [rules.md](../../../../generators/php/specs/rules.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV08.001 — Branches, loops and transfers | Execute bounded if/loop/switch/match fixtures and compare exit/output; skipped branches and break/continue counts follow the contract. | [control_flow.php:187](../../tests/control_flow.php#L187) |
| LV08.002 — Return completeness and rejection | Reject reachable fallthrough in required-return functions and invalid control-flow forms; include loops which may execute zero times. | [fallthrough.php:7](../../tests/fallthrough.php#L7) |
| LV08.003 — brace-free-control | Run original strict brace-free if/else and nested loop transfers with exact counter outputs; isolate downstream missing builtins in a separate probe. | Probe/reconciliation task; see [LV08-P01](language_verification_probes.md) |

## LV09

Functions, recursion, forward calls, value/reference/variadic parameters, defaults, named-argument status, return/void/fallthrough rules and rejected signatures.

Contracts: [rules.md](../../../../generators/php/specs/rules.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV09.001 — Call argument order and specialization | Arguments execute once in source order; repeated specializations reuse definitions while unused specializations emit no executable body. | [calls.php:157](../../tests/calls.php#L157) |
| LV09.002 — Void/value return boundary | Accept valid void calls/returns; reject using void as a value or violating return contracts; cleanup still occurs. | [void_returns.php:7](../../tests/void_returns.php#L7) |
| LV09.003 — argument-contracts | Inventory named/default/variadic/reference combinations; use an ordered trace to distinguish argument evaluation order from parameter binding, and reject invalid duplicates/mixing. | Probe/reconciliation task; see [LV09-P01](language_verification_probes.md) |
| LV09.004 — Defaults and variadic parameter binding | Execute omitted/provided defaults and variadic inputs with argument traces; record reference/default restrictions and free-function versus method differences. | [method_parameters.php:34](../../tests/method_parameters.php#L34) |

## LV10

Null, nullable, Result, falseable and bool-able states.

Contracts: [error_values_and_take.md](../../../../specs/error_values_and_take.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV10.001 — Result payload versus sentinel | Wrapped false/zero values remain distinct from sentinels; construction/extraction chooses the correct Result family and preserves error state. | [results.php:49](../../tests/results.php#L49) |
| LV10.002 — Take output/state and recovery | Take assigns the correct value/error/flag branch, clears prior error on success, and invalidates then repairs after a source return-type edit. | [take.php:79](../../tests/take.php#L79) |
| LV10.003 — wrapper-state-grid | For nullable/result/falseable/bool-able scalar and admitted container payloads, contrast wrapped false/zero/empty with each sentinel; take twice and inspect output/error reset. | Probe/reconciliation task; see [LV10-P01](language_verification_probes.md) |

## LV11

Mixed/dynamic family separation, scalar/table states, explicit crossing, typed boundaries, COW versus shared identity, failed mutation guarantees and prohibited nesting.

Contracts: [dynamic_types.md](../../../../specs/dynamic_types.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV11.001 — Mixed versus dynamic identity | Compare copied mixed COW values with shared dynamic containers, explicit crossings and rejected implicit family mixing. | [dynamic_boundaries.php:1](../../tests/fixtures/dynamic_boundaries.php#L1) |
| LV11.002 — Boxed numeric/text write-back | Numeric compound updates preserve stored numeric kind; text replacement follows its separate policy; failure must not install an invalid value. | [mixed.php:146](../../tests/mixed.php#L146) |
| LV11.003 — boxed-copy-lifetime | Copy nested mixed containers and alias dynamic containers, mutate one path, then destroy/replace an owner during a call; verify values and identity separately. | Probe/reconciliation task; see [LV11-P01](language_verification_probes.md) |

## LV12

String bytes/UTF-8 distinctions, embedded NUL, interpolation, concatenation and `.=` conversions, identity/equality/ordering admission and errors.

Contracts: [string_interpolation.md](../../../../specs/string_interpolation.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV12.001 — String identity and bytes | Strict identity compares exact bytes including NUL; same-text values match without truthiness conversion, and operand effects stay ordered. | [string_identity.php:35](../../tests/string_identity.php#L35) |
| LV12.002 — Public string operations | Execute registered helpers/builders with explicit byte/result expectations, including empty inputs and declared failure returns. | [runtime_strings.php:23](../../tests/runtime_strings.php#L23) |
| LV12.003 — string-operator-grid | Probe ordinary equality/order separately from strict identity, then interpolation/concat with empty/NUL/UTF-8 and scalar peers; compare byte values and conversion failure. | Probe/reconciliation task; see [LV12-P01](language_verification_probes.md) |
| LV12.004 — Interpolation value and error behavior | Verify literal escapes, embedded expression conversions and once-only source order, including invalid interpolation operands. | [interpolation.php:8](../../tests/interpolation.php#L8) |

## LV13

Data notation and contextual literals.

Contracts: [data_initialization.md](../../../../specs/data_initialization.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV13.001 — Contextual data construction | The resolved target controls literal/key/value conversion; malformed or unsupported construction rejects before facts/output are published. | [data_preparation.php:93](../../tests/data_preparation.php#L93) |
| LV13.002 — Initialization input contracts | Check input views are call-scoped/readonly, entry types have the proper key/value arguments, and registration does not instantiate every input eagerly. | [initialization.php:69](../../tests/initialization.php#L69) |
| LV13.003 — construction-order | Construct nested data with two side-effectful entries and a failing conversion; check entry/key order, duplicate-key policy and cleanup of already-created values. | Probe/reconciliation task; see [LV13-P01](language_verification_probes.md) |

## LV14

Vector/hash/mixed/dynamic reads, writes, append, nested paths and property-root paths.

Contracts: [array_semantics.md](../../../../specs/array_semantics.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV14.001 — Container reads and missing keys | Read each admitted container/key family with correct value/reference semantics; missing, out-of-range and invalid key cases follow the contract. | [indexing.php:7](../../tests/indexing.php#L7) |
| LV14.002 — Property-root mutation and replacement | Nested writes/append evaluate receiver/key/RHS in order, retain replaced owners and preserve the specified effects on failure. | [field_container_writes.php:102](../../tests/field_container_writes.php#L102) |
| LV14.003 — nested-write-failure | Use keys and RHS that replace the root or invalidate a parent; test vector/hash/mixed/dynamic paths and assert documented partial effects/owner retention. | Probe/reconciliation task; see [LV14-P01](language_verification_probes.md) |

## LV15

count/empty/isset/unset on values and paths.

Contracts: [count_empty_isset_contract.md](../../../../specs/count_empty_isset_contract.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV15.001 — Value probe semantics and invalidation | Count/empty/isset return the specified values without introducing mutable places; dependency edits invalidate and repair facts. | [value_probes.php:46](../../tests/value_probes.php#L46) |
| LV15.002 — Lazy path probes and removal | Missing/null parents skip later keys, probes do not create entries, and unset preserves aliases/COW according to the receiver family. | [field_container_probes.php:31](../../tests/field_container_probes.php#L31) |
| LV15.003 — unseeded-probes | Turn the 39 unseeded empty_keyed/empty_value/count_value semantic rows into valid strict sources with exact bool/count and no-mutation observations. | Probe/reconciliation task; see [LV15-P01](language_verification_probes.md) |

## LV16

foreach keys/values, empty and nested iteration, loop variable lifetime, mutation during iteration, copy/alias behavior.

Contracts: [array_semantics.md](../../../../specs/array_semantics.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV16.001 — Iteration binding and retained dependencies | Changing vector iteration to hash updates key type without reparsing the consumer; noniterable edits invalidate output and repair matches fresh. | [iteration.php:34](../../tests/iteration.php#L34) |
| LV16.002 — Iteration execution and loop scope | Execute empty/populated/nested value loops; verify keys, values, order and loop-variable scope rather than merely emitted syntax. | [iteration.php:17](../../tests/iteration.php#L17) |
| LV16.003 — iteration-mutation | Remove/append/replace the iterated container through both direct binding and a called alias, including an empty source; classify permitted behavior versus required guard/rejection. | Probe/reconciliation task; see [LV16-P01](language_verification_probes.md) |

## LV17

References/aliases, reference arguments/returns, same/different origins, escape prevention, addressability, readonly places, nested calls and lifetime failures.

Contracts: [references.md](../../../../specs/references.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV17.001 — Reference origin and escape rejection | Reference returns preserve admitted origins; temporary/escaping/read-only-invalid references reject before emission. | [references.php:88](../../tests/references.php#L88) |
| LV17.002 — Reference forwarding across calls | Mutations through forwarded reference arguments reach the original storage; return-reference lifetimes do not outlive owners. | [reference_forwarding.php:37](../../tests/reference_forwarding.php#L37) |
| LV17.003 — reference-composition | Forward a reference through a method/closure and selection boundary, then attempt return/escape of a temporary owner; verify write-through or a justified rejection. | Probe/reconciliation task; see [LV17-P01](language_verification_probes.md) |

## LV18

Shared/unique/weak handles, move/copy/lock/reset, null handles, expired access, ownership conversion, temporary receivers, destructors and exceptional cleanup.

Contracts: [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV18.001 — Ownership observations and transfers | Move/lock/observe retain payload identity and correct ownership family; unique owned results differ from borrowed/reference results. | [ownership.php:76](../../tests/ownership.php#L76) |
| LV18.002 — Destruction and exceptional cleanup | Check destruction order and exactly-once cleanup on scope exit, return and exception; inaccessible/throwing destructors follow declared rejection rules. | [destructors.php:7](../../tests/destructors.php#L7) |
| LV18.003 — exceptional-ownership | Transfer unique/shared/weak state across a call that throws before/after acquisition; observe destructor trace, weak expiry and no double release. | Probe/reconciliation task; see [LV18-P01](language_verification_probes.md) |

## LV19

Classes/structs, fields/defaults/visibility, constructors/data construction, instance methods, readonly/inline receivers, property mutations and storage identity.

Contracts: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV19.001 — Construction and field initialization | Defaults and explicit inputs initialize fields in contract order; constructor visibility and duplicate/missing inputs are checked. | [constructors.php:255](../../tests/constructors.php#L255) |
| LV19.002 — Property updates and receiver retention | Execute prefix/postfix and compound property mutation, verify old/new snapshots, and retain receivers during RHS replacement. | [property_mutations.php:23](../../tests/property_mutations.php#L23) |
| LV19.003 — receiver-replacement | During method/property RHS evaluation, replace the last owning receiver; check method result, postfix snapshot and destructor timing for supported receiver forms. | Probe/reconciliation task; see [LV19-P01](language_verification_probes.md) |

## LV20

Inheritance, abstract/interface conformance, overrides/dispatch, traits/conflicts, parent/static/self resolution, casts/instanceof and access-control errors.

Contracts: [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV20.001 — Inherited dispatch and overrides | Virtual calls select the correct implementation; invalid override signatures/access/abstract construction reject. | [inheritance.php:3](../../tests/inheritance.php#L3) |
| LV20.002 — Interface contracts and handles | Interface conversion and dispatch preserve object identity and valid lifetime; missing implementations and incompatible contracts reject. | [interfaces.php:7](../../tests/interfaces.php#L7) |
| LV20.003 — trait-lifecycle | Inventory trait constructor/destructor conflicts, interface/static dispatch and unique/weak joins; use minimal source probes to identify first blocker and governing contract. | Probe/reconciliation task; see [LV20-P01](language_verification_probes.md) |
| LV20.004 — Trait composition and conflicts | Execute nested trait composition with origin deduplication; reject ambiguous members and invalid abstract requirements without losing visibility/dispatch. | [traits.php:7](../../tests/traits.php#L7) |

## LV21

Static properties/methods/constants, initialization/visibility, called-type identity, late static behavior and incremental declaration replacement.

Contracts: [static_members_v0_2.md](../../../../specs/static_members_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV21.001 — Static member storage and access | Type-owned properties/constants/methods obey visibility and initialization, with one appropriate storage identity across source files. | [static_members.php:139](../../tests/static_members.php#L139) |
| LV21.002 — Called-type dispatch | Late-static factories/calls preserve the selected called type; dependency changes invalidate affected specializations. | [late_static.php:10](../../tests/late_static.php#L10) |
| LV21.003 — static-cross-file | Use two source units referring to one static property; mutate/read it and edit its initializer/type/import; verify storage identity and incremental equivalence. | Probe/reconciliation task; see [LV21-P01](language_verification_probes.md) |

## LV22

Enum creation, backing values, equality/identity, declared helpers, conversion/invalid values, exhaustiveness and explicitly deferred extensions.

Contracts: [compact_layout_types.md](../../../../specs/compact_layout_types.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV22.001 — Enum nominal identity | Enums retain nominal identity rather than silently becoming integers; only declared conversions/operators are admitted. | [enum_model.php:69](../../tests/enum_model.php#L69) |
| LV22.002 — Enum execution and failure | Execute enum name/value/from-value helpers and invalid value paths; distinguish currently deferred reflection/backing extensions. | [enums.php:6](../../tests/enums.php#L6) |
| LV22.003 — enum-surface | Inventory string backing, cases/from/tryFrom, methods/interfaces and wrapper payloads; compare promised v0.1 forms with documented v0.2 deferrals. | Probe/reconciliation task; see [LV22-P01](language_verification_probes.md) |

## LV23

Closures/callables, value/reference/receiver captures, defaults and signatures, invoke/adaptation, storage/returns, escape/borrow checks and cleanup.

Contracts: [closures.md](../../../../specs/language/closures.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV23.001 — Closure concrete versus erased identity | Inferred closures retain concrete capture types; explicit callable boundaries erase intentionally; no-op compilation does not recreate types. | [closures.php:100](../../tests/closures.php#L100) |
| LV23.002 — Reference capture lifetime | Borrowing captures remain within approved lexical regions; escaping or unsupported captures reject with no stale successful publication. | [reference_captures.php:117](../../tests/reference_captures.php#L117) |
| LV23.003 — capture-escape | Copy a borrowing closure into a property/container or return it, contrast with an owning capture, and observe admitted cleanup or the required escape rejection. | Probe/reconciliation task; see [LV23-P01](language_verification_probes.md) |
| LV23.004 — Callable parameters, results and storage | Check concrete-to-erased adaptation, callable parameter/result transport and property/container storage; keep borrowing escape restrictions explicit. | [callable_storage.php:7](../../tests/callable_storage.php#L7) |

## LV24

Error values versus exceptions, throw/catch/finally, propagation, cleanup order, return/break/continue through protected regions, uncaught presentation and recovery.

Contracts: [exceptions_v0_2.md](../../../../specs/exceptions_v0_2.md), [error_values_and_take.md](../../../../specs/error_values_and_take.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV24.001 — Exception propagation and recovery | Execute throw/catch propagation and cleanup; invalid exception edits reject, then repair restores fresh-equivalent output. | [exceptions.php:92](../../tests/exceptions.php#L92) |
| LV24.002 — Finally execution and transfers | Finally executes once on normal/exceptional exit; admitted returns preserve values and cleanup while unsupported transfers are classified. | [finally.php:7](../../tests/finally.php#L7) |
| LV24.003 — finally-transfer-grid | Cross finally with scalar/void/value/reference/owning returns and loop transfers; record the admitted set, exact cleanup trace and rejection stage. | Probe/reconciliation task; see [LV24-P01](language_verification_probes.md) |
| LV24.004 — Structured error payloads and take | Verify no-error versus real empty-message error, payload fields and take error clearing; keep error distinct from Exception. | [error_values.php:7](../../tests/error_values.php#L7) |

## LV25

Namespaces/imports, qualified/unqualified lookup, cross-file symbols, visibility/export, dependency ordering, collisions, cycles and unresolved names.

Contracts: [namespaces_v0_2.md](../../../../specs/namespaces_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV25.001 — Namespace bodies and entry selection | Distinct namespace bodies retain independent environments; the selected executable body is not hardwired to the first body. | [namespace_bodies.php:42](../../tests/namespace_bodies.php#L42) |
| LV25.002 — Qualified lookup and imports | Qualified/rooted/imported symbols resolve under current namespace rules; collisions and changed imports invalidate affected consumers. | [qualified_lookup.php:5](../../tests/qualified_lookup.php#L5) |
| LV25.003 — import-edit | Change an alias/namespace/provider declaration in one file while a consumer body remains unchanged; check new binding, collision rejection and repaired fresh output. | Probe/reconciliation task; see [LV25-P01](language_verification_probes.md) |
| LV25.004 — Module discovery and exported symbols | Check multi-root discovery, exported/private symbol visibility, path identity and added/deleted source files; do not use legacy dynamic include as composition. | [module_discovery.php:28](../../tests/module_discovery.php#L28) |

## LV26

Echo/output conversion, multiple-operand order, stream behavior, program entry/exit/CLI args, diagnostics and source locations.

Contracts: [output_v0_2.md](../../../../specs/output_v0_2.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV26.001 — Echo conversion and order | Multiple echo operands evaluate once in order and produce exact bytes; unsupported output types reject at the documented boundary. | [echo.php:21](../../tests/echo.php#L21) |
| LV26.002 — CLI entry and arguments | Generated executable startup initializes argument state once; argc/argv/args reflect invocation and returned collections have the documented copy behavior. | [runtime_cli.php:19](../../tests/runtime_cli.php#L19) |
| LV26.003 — output-failure-order | Emit a successful operand followed by a failing conversion and a counter-producing third operand; compare prior bytes and skipped effects under the output contract. | Probe/reconciliation task; see [LV26-P01](language_verification_probes.md) |

## LV27

Generic/specialized callable and runtime type contracts, argument inference, constraint rejection, dependent instances, invalidation.

Contracts: [runtime_module_metadata.md](../../../../specs/runtime_module_metadata.md), [metaprogramming_contract.md](../../../../specs/metaprogramming_contract.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV27.001 — Runtime generic substitution | Installed generic signatures substitute parameters and constraints correctly; unsupported payload or arity rejects before emission. | [runtime_generics.php:6](../../tests/runtime_generics.php#L6) |
| LV27.002 — Specialization demand and invalidation | One required specialization is generated per settled identity; changed dependencies replace stale signatures/bodies without duplicating unrelated instances. | [specialization_dispatch.php:6](../../tests/specialization_dispatch.php#L6) |
| LV27.003 — specialization-isolation | Call one generic with two admissible types, invalidate one provider constraint, repair it and compare reused versus replaced instances and executable values. | Probe/reconciliation task; see [LV27-P01](language_verification_probes.md) |

## LV28

Async/await, tasks/scheduling and suspension/lifetime obligations: determine v0.1 and v0.2 applicability, discover runnable source tests, explicitly classify absent compiler support.

Contracts: [async_await.md](../../../../specs/async_await.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV28.001 — Async source admission and scope | Replay the existing strict PHS async fixture unchanged via the strict compatibility manifest; baseline expects exit 0 and stdout 4242; record first v0.2 blocker. | [test_scpp_async_core_language.php:64](../../../../tests/tools/test_scpp_async_core_language.php#L64) |
| LV28.002 — Suspension and task lifetime | If source admission exists, verify values/errors across suspension, capture/resource lifetime and scheduler completion; otherwise record the blocking owner without inferring support from runtime tests. | Probe/reconciliation task; see [LV28-P01](language_verification_probes.md) |
| LV28.003 — async-admission | Locate strict v0.1 async fixtures and run the smallest matching v0.2 source without translating to callbacks; if blocked, record the stage and defer suspension probes behind it. | Probe/reconciliation task; see [LV28-P01](language_verification_probes.md) |

## LV29

Inventory all public modules/symbols against spec, runtime exports and compiler metadata.

Contracts: [runtime_module_metadata.md](../../../../specs/runtime_module_metadata.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV29.001 — Metadata validity and reset | Invalid schema/contract/cycle/collision must not replace installed configuration; resets reuse decoded metadata with fresh semantic identities. | [runtime_catalog.php:41](../../tests/runtime_catalog.php#L41) |
| LV29.002 — Runtime exports and source shadowing | Public source bindings resolve through metadata and update correctly when source names shadow/unshadow them; private or disabled surfaces reject. | [runtime_exports.php:48](../../tests/runtime_exports.php#L48) |
| LV29.003 — runtime-overloads | For every public signature/type/constant/property in the metadata ledger, map one exact source use and declared failure/disabled-module behavior where applicable. | Probe/reconciliation task; see [LV29-P01](language_verification_probes.md) |

## LV30

Core/string/JSON APIs, builders, parsing/encoding, malformed inputs, state/ownership, byte handling and scalar/container return boundaries.

Contracts: [first_pass.md](../../../../specs/builtins/json/first_pass.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV30.001 — JSON source integration | Decode/encode success and malformed input retain declared wrapper/value families; explicit mixed versus default dynamic contracts remain distinct. | [json.php:28](../../tests/json.php#L28) |
| LV30.002 — String module signatures and values | For every registered overload, identify argument/default/reference mode and an independent result oracle; a name occurrence is not overload coverage. | [runtime_strings.php:41](../../tests/runtime_strings.php#L41) |
| LV30.003 — json-shape | Round-trip empty object/array, numeric-key object, NUL string, malformed JSON and both mixed/dynamic decode entrypoints; distinguish approved shape changes from defects. | Probe/reconciliation task; see [LV30-P01](language_verification_probes.md) |

## LV31

Filesystem/IO/datetime/process/environment/regex APIs.

Contracts: [runtime_module_metadata.md](../../../../specs/runtime_module_metadata.md), [first_pass.md](../../../../specs/builtins/datetime/first_pass.md), [first_pass.md](../../../../specs/builtins/regex/first_pass.md), [process.md](../../../../specs/builtins/process.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV31.001 — Filesystem outputs and failures | Execute operations in temporary isolated paths; verify content, failure values, container Results and resource cleanup. | [runtime_filesystem.php:22](../../tests/runtime_filesystem.php#L22) |
| LV31.002 — Opaque handle semantics | Handle source operations obey registered ownership/storage contracts; unsupported payload access rejects and recovery preserves metadata. | [runtime_handles.php:199](../../tests/runtime_handles.php#L199) |
| LV31.003 — resource-failure | Open/use/close a resource, trigger one declared IO failure and unwind; check return wrapper and release behavior with local fixtures. | Probe/reconciliation task; see [LV31-P01](language_verification_probes.md) |
| LV31.004 — Datetime and environmental clocks | Check UTC parse/format exact values, malformed dates, monotonic ordering and declared failure results; use bounded clock checks rather than fixed current timestamps. | [runtime_datetime.php:23](../../tests/runtime_datetime.php#L23) |
| LV31.005 — Regex enabled/disabled module | Verify exact captures, malformed patterns, replacements and reference outputs for registered overloads; disabled module rejects and PCRE2 absence is environmental. | [runtime_regex.php:6](../../tests/runtime_regex.php#L6) |
| LV31.006 — Environment and process contracts | Check absent/empty environment and shell output, failure wrappers and binary bytes; exercise managed process timeout/stop/close with local child programs. | [runtime_environment.php:33](../../tests/runtime_environment.php#L33) |

## LV32

Remaining providers and language-adjacent APIs: network/curl, databases, UI/WebView, source/tokenizer, binary codec and public Storage.

Contracts: [runtime_module_metadata.md](../../../../specs/runtime_module_metadata.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV32.001 — Source module ownership | Buffers/indexes/locations obey transfer/release and byte-position contracts with no unsupported borrowed-span escape. | [runtime_source.php:62](../../tests/runtime_source.php#L62) |
| LV32.002 — Optional providers and resource behavior | Test enabled/disabled provider visibility, link requirements and local resource lifetime without requiring external network services. | [runtime_assets.php:5](../../tests/runtime_assets.php#L5) |
| LV32.003 — provider-difference | Cross-reference public v0.1 APIs against eight current metadata modules; record absent source bindings, optional dependencies and internal-only helpers, then probe each settled supported signature. | Probe/reconciliation task; see [LV32-P01](language_verification_probes.md) |

## LV33

Actual compiler entrypoints, project composition, entry selection, exports/imports, multi-file C++, standalone headers, provider linking, build/run success and diagnostics.

Contracts: [project_build_v1.md](../../../../specs/project_build_v1.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV33.001 — Project C++ composition | Compile multi-file artifacts and standalone headers; symbol/export/provider identities and generated behavior agree across project entrypoints. | [project_cpp.php:129](../../tests/project_cpp.php#L129) |
| LV33.002 — Artifact boundaries and determinism | Artifacts retain unique ownership and correct includes/dependencies; no-op generation is stable and failures do not publish partial success. | [cpp_artifacts.php:5](../../tests/cpp_artifacts.php#L5) |
| LV33.003 — production-cli | Compile and run a minimal strict multi-project exported callable with one selected provider through the actual v0.2 CLI; test missing entry/provider and stale executable rejection. | Probe/reconciliation task; see [LV33-P01](language_verification_probes.md) |
| LV33.004 — Publication and cached executable freshness | Verify failed generation/build cannot publish a ready stale executable, reader identity matches outputs, and lock/failure recovery preserves last valid state. | [lifecycle_handoff.php:1](../../tests/lifecycle_handoff.php#L1) |

## LV34

Fresh versus incremental equivalence after body/type/signature/import/provider edits, deletion/re-add, rejection/repair, cleanup, stale facts and no-op behavior.

Contracts: [metaprogramming_contract.md](../../../../specs/metaprogramming_contract.md), [incremental.md](../lifecycle/incremental.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV34.001 — Incremental dependency precision | Body-only changes preserve unrelated signatures/consumers; signature/type/import changes invalidate dependents; no-op retains identity. | [incremental_preparation.php:61](../../tests/incremental_preparation.php#L61) |
| LV34.002 — Failed preparation and repair | Parse/preparation failure blocks emission, and repairing the source reproduces a fresh build without stale facts or output. | [preparation_recovery.php:134](../../tests/preparation_recovery.php#L134) |
| LV34.003 — incremental-language-grid | For each feature with retained facts, edit its defining type/signature/state, force rejection, repair, and compare executable values plus fresh output on the same snapshot. | Probe/reconciliation task; see [LV34-P01](language_verification_probes.md) |

## LV35

Original strict v0.1 corpus and project examples.

Contracts: [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV35.001 — Original strict compatibility corpus | Run each enabled strict manifest case unchanged against pinned v0.1 and frozen v0.2; compare its declared oracle and preserve baseline failures separately. | [legacy_compatibility_strict.json:5](../../tests/legacy_compatibility_strict.json#L5) |
| LV35.002 — Migration probes and approved differences | Keep original-source and adapted-source results distinct; justify each intentional difference by an approved contract and retain the first blocker. | [legacy_compatibility_strict.json:5](../../tests/legacy_compatibility_strict.json#L5) |
| LV35.003 — baseline-and-ledger | Refresh reference selection, freeze both checkouts, rerun the strict ledger after S01/S03 and keep S02/legacy/adapted lanes separate. | [legacy_compatibility_strict.json:5](../../tests/legacy_compatibility_strict.json#L5) |

## LV36

Cross-feature scenarios: containers across calls/returns, wrappers plus selection, closures plus ownership, inheritance plus construction, exceptions plus mutation/cleanup, optional API results plus typed boundaries.

Contracts: [array_semantics.md](../../../../specs/array_semantics.md). Follow the current amendments and spec hierarchy.

| Check | Required observation | Existing evidence / next probe |
| --- | --- | --- |
| LV36.001 — Containers through calls and returns | Exercise container values/aliases across argument and return boundaries; verify copy/identity and failed conversion effects. | [container_calls.php:20](../../tests/container_calls.php#L20) |
| LV36.002 — Construction plus inheritance | Derived data construction obeys inherited input/default/access contracts and initializes through the responsible construction owner. | [derived_data.php:7](../../tests/derived_data.php#L7) |
| LV36.003 — end-to-end-scenarios | Run small strict programs combining JSON/container boundaries, callable captures, inheritance construction, and exception cleanup with independent exact output/trace oracles. | Probe/reconciliation task; see [LV36-P01](language_verification_probes.md) |
