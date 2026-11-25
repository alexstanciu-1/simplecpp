# Language verification coverage checklist
Doc Status: planning

Coverage map — 2026-10-18. Companion to the
[verification and repair plan](language_verification_plan.md).
All 36 families now have [119 concrete checks](language_verification_checks.md),
[36 probe/reconciliation tasks](language_verification_probes.md), and a
[machine-readable inventory](language_verification_inventory.json).
These are obligations and source mappings, not claims of fresh verification.
Existing runner names are discovery anchors, not permission to execute whole
suites blindly or to treat their fixtures as exhaustive.

## Language surface

The catalog links identify owning chapters and their semantic references. Read
those references under [spec authority](../../../../specs/spec_map.md) before deriving
expected behavior. Include source forms accepted by v0.1 even if absent from the
current my-try catalog; separate intentional changes and deferred legacy spellings.

| ID | Remaining verification obligations | Owners / initial test evidence |
| --- | --- | --- |
| LV01 | Lexing, comments, identifiers, reserved words, literal boundaries, escapes/bytes, precedence/token ambiguity, malformed-source diagnostics and source locations | [Literals/locals](../catalog/01_literals_locals.md); `tokenizer.php`, `token_cleanup.php`, `token_generations.php` |
| LV02 | Local declarations/inference, explicit types, initialization/defaults, reassignment, shadowing, duplicate names, scope exits, uninitialized/unknown reads and source constants | [Locals](../catalog/01_literals_locals.md); `initialization.php`, `source_constants.py`, `declaration_model.py` |
| LV03 | Type identity and aliases; canonical and fixed-width integers/signedness; float/bool/string; type-use modifiers, generic arguments, supported nesting, invalid/unsupported types | [Value types](../catalog/09_value_types.md); `type_catalog.php`, `integer_widths.py`, `type_capabilities.php` |
| LV04 | Bool equality/identity, numeric arithmetic/comparison, bitwise/shifts, power, unary signs/not, increment/decrement, compound updates; every admitted operand family and explicit rejection | [Expressions](../catalog/02_expressions.md); `scalar_admission.py`, `operators.php`, matrix audit; expand gaps listed below |
| LV05 | Assignment, explicit casts, arguments, returns, fields, elements and reference boundaries; narrowing/widening, invalid casts, wrapper extraction and failed-store preservation | [Dynamic boundaries](../catalog/05_dynamic_boundaries.md); `conversions.php`, `dynamic_boundaries.php`, `nullable.py`, `results.py` |
| LV06 | Left-to-right eager evaluation/conversion, each operand once, snapshots before RHS mutation, failure ordering, receiver/argument order, lazy `&&`/`\|\|`, word forms and `xor` | [Evaluation contract](../../../../specs/expression_evaluation.md); `eager_binary.py`, `compound_effects.py`, `logical.php` |
| LV07 | Conditions and negation across families; ternary, Elvis, coalesce, nested branches, selected value/type, skipped effects, nonexecuted branch typechecking and join rejection | [Conditional contract](../../../../specs/conditional_expression_matrix.md); `control_flow.php`, `shared_joins.php`, conditional matrix |
| LV08 | if/else forms including brace-free strict fixtures; loops and loop scope; switch/match where contracted; break/continue/return, nesting, evaluation counts and fallthrough | [Control flow](../catalog/03_control_flow.md); `control_flow.php`, `fallthrough.php`, original strict control-flow fixtures |
| LV09 | Functions, recursion, forward calls, value/reference/variadic parameters, defaults, named-argument status, return/void/fallthrough rules and rejected signatures | [Functions](../catalog/04_functions.md); `calls.php`, `void_returns.php`, `reference_forwarding.php` |
| LV10 | Null, nullable, Result, falseable and bool-able states; present false/zero versus sentinel; construction, copying, take, extraction, comparison and errors | [Error/take contract](../../../../specs/error_values_and_take.md); `take.php`, `error_values.php`, `result_containers.php`, `results.py` |
| LV11 | Mixed/dynamic family separation, scalar/table states, explicit crossing, typed boundaries, COW versus shared identity, failed mutation guarantees and prohibited nesting | [Dynamic types](../../../../specs/dynamic_types.md); `dynamic_families.py`, `mixed.py`, `mixed_layout.py` |
| LV12 | String bytes/UTF-8 distinctions, embedded NUL, interpolation, concatenation and `.=` conversions, identity/equality/ordering admission and errors | [Expressions](../catalog/02_expressions.md), [interpolation](../../../../specs/string_interpolation.md); `string_identity.py`, `runtime_strings.py` |
| LV13 | Data notation and contextual literals; empty/positional/keyed defaults, key normalization/duplicates, construction-target resolution, explicit construction contracts and malformed input | [Containers](../catalog/06_containers.md), [initialization contract](../../../../specs/data_initialization.md); `data_preparation.php`, `constructed_type.php`, `derived_data.php` |
| LV14 | Vector/hash/mixed/dynamic reads, writes, append, nested paths and property-root paths; bounds/missing/null keys, key/RHS order, replacement lifetime and partial failure | [Arrays](../../../../specs/array_semantics.md), [indexing](../../../../specs/index_operator.md); `keyed_paths.py`, `field_container_writes.py`, `index_compound.py` |
| LV15 | count/empty/isset/unset on values and paths; observational purity, missing/null distinctions, lazy keys, noncreating probes, removal and current null-parent no-op | [Probe contract](../../../../specs/count_empty_isset_contract.md); `collection_probes.php`, `field_container_probes.py` |
| LV16 | foreach keys/values, empty and nested iteration, loop variable lifetime, mutation during iteration, copy/alias behavior; separately record disabled reference foreach and required safety rules | [Containers](../catalog/06_containers.md); `iteration.php`, CH06-ITER-01 / CH06-REF-01 debt |
| LV17 | References/aliases, reference arguments/returns, same/different origins, escape prevention, addressability, readonly places, nested calls and lifetime failures | [References/lifetime](../catalog/07_references_lifetime.md); `references.py`, `reference_forwarding.php`, `reference_captures.php` |
| LV18 | Shared/unique/weak handles, move/copy/lock/reset, null handles, expired access, ownership conversion, temporary receivers, destructors and exceptional cleanup | [References/lifetime](../catalog/07_references_lifetime.md), [destructors](../../../../specs/destructors_v0_2.md); `ownership.py`, `cleanup.py`, `nullable_handles.php` |
| LV19 | Classes/structs, fields/defaults/visibility, constructors/data construction, instance methods, readonly/inline receivers, property mutations and storage identity | [Objects](../catalog/10_objects.md); `records.py`, `constructors.php`, `methods.php`, `property_defaults.php`, `indexed_properties.py` |
| LV20 | Inheritance, abstract/interface conformance, overrides/dispatch, traits/conflicts, parent/static/self resolution, casts/instanceof and access-control errors | [Inheritance](../catalog/11_inheritance.md); `inheritance.php`, `interfaces.php`, `traits.php`, `abstract_dispatch.php`, `interface_handles.php` |
| LV21 | Static properties/methods/constants, initialization/visibility, called-type identity, late static behavior and incremental declaration replacement | [Static contract](../../../../specs/static_members_v0_2.md); `static_members.php`, `late_static.php`, `qualified_static_calls.php`, `static_redeclarations.php` |
| LV22 | Enum creation, backing values, equality/identity, declared helpers, conversion/invalid values, exhaustiveness and explicitly deferred extensions | [Value types](../catalog/09_value_types.md); `enum_model.php`, enum/runtime fixtures |
| LV23 | Closures/callables, value/reference/receiver captures, defaults and signatures, invoke/adaptation, storage/returns, escape/borrow checks and cleanup | [Closures](../catalog/12_closures.md), [closure contract](../../../../specs/language/closures.md); `callables.php`, `callable_adaptation.php`, `callable_storage.php`, `reference_captures.php` |
| LV24 | Error values versus exceptions, throw/catch/finally, propagation, cleanup order, return/break/continue through protected regions, uncaught presentation and recovery | [Errors](../catalog/13_errors.md), [exception contract](../../../../specs/exceptions_v0_2.md); `records.py` error/exception/finally suites, `preparation_recovery.php` |
| LV25 | Namespaces/imports, qualified/unqualified lookup, cross-file symbols, visibility/export, dependency ordering, collisions, cycles and unresolved names | [Project/symbols](../catalog/08_project_symbols.md); `namespaces.py`, `namespace_identity.py`, `qualified_lookup.php`, `module_discovery.php` |
| LV26 | Echo/output conversion, multiple-operand order, stream behavior, program entry/exit/CLI args, diagnostics and source locations | [Output/runtime](../catalog/14_output_runtime.md), [output contract](../../../../specs/output_v0_2.md); `echo.php`, `runtime_cli.py`, `runtime_observation.py` |
| LV27 | Generic/specialized callable and runtime type contracts, argument inference, constraint rejection, dependent instances, invalidation; distinguish proposed metaprogramming from promised language | [Metaprogramming contract](../../../../specs/metaprogramming_contract.md); `runtime_generics.py`, `specialization_dispatch.php`, `callable_instances.php` |
| LV28 | Async/await, tasks/scheduling and suspension/lifetime obligations: determine v0.1 and v0.2 applicability, discover runnable source tests, explicitly classify absent compiler support | [Async contract](../../../../specs/async_await.md), `specs/builtins/tasks/`; no my-try execution claim from runtime tests alone |

## Runtime and compiler integration

Runtime availability does not establish source-language availability. For each
public symbol, check metadata visibility/signature, preparation, generated linking,
success/failure behavior and ownership of returned resources. Split strict and
legacy APIs before counting. Internal C++ helpers are not automatically public APIs.

| ID | Remaining verification obligations | Owners / initial evidence |
| --- | --- | --- |
| LV29 | Inventory all public modules/symbols against spec, runtime exports and compiler metadata; enabled/disabled module behavior and missing providers | [Runtime coverage](runtime_catalog_coverage.md), [metadata contract](../../../../specs/runtime_module_metadata.md); `runtime_catalog.py`, `runtime_exports.py`, `runtime_types.py`, `runtime_signatures.php` |
| LV30 | Core/string/JSON APIs, builders, parsing/encoding, malformed inputs, state/ownership, byte handling and scalar/container return boundaries | `specs/builtins/`; `runtime_strings.py`, `runtime_builders.py`, `json.php`, `result_containers.php` |
| LV31 | Filesystem/IO/datetime/process/environment/regex APIs; optional providers, handles/resource cleanup, error paths, host constraints and time-sensitive oracle design | `specs/builtins/`; `runtime_filesystem.py`, `runtime_datetime.py`, `runtime_environment.py`, `runtime_regex.py`, `runtime_handles.py`, `runtime_assets.py` |
| LV32 | Remaining providers and language-adjacent APIs: network/curl, databases, UI/WebView, source/tokenizer, binary codec and public Storage; inventory first, record dependencies/platform restrictions and missing source bindings | [CH15 runtime debt](../catalog/15_blocking_debt.md); `runtime_source.py`, `mysql_rows.py`, existing root runtime/API tests; no assumed external services |
| LV33 | Actual compiler entrypoints, project composition, entry selection, exports/imports, multi-file C++, standalone headers, provider linking, build/run success and diagnostics | [Project contract](../../../../specs/project_build_v1.md), [output/runtime](../catalog/14_output_runtime.md); `project_cpp.py`, `cpp_artifacts.py`, `runtime_module_build.py`, strict project samples |
| LV34 | Fresh versus incremental equivalence after body/type/signature/import/provider edits, deletion/re-add, rejection/repair, cleanup, stale facts and no-op behavior | `incremental_preparation.php`, `dependency_cleanup.php`, `module_sync.php`, `preparation_recovery.php`, `output_publication.py`, `native_invalidation.py`; distinguish generated-program builds from native compiler builds |
| LV35 | Original strict v0.1 corpus and project examples; retain pinned baseline result, untouched source/hash, intended incompatibilities and first blocker; independently label strict-declaration/observer migration probes | [Compatibility harness](v0_1_compatibility_testing.md), [strict causes](v0_1_compatibility_strict_causes.md), expanded inventory and manifests |
| LV36 | Cross-feature scenarios: containers across calls/returns, wrappers plus selection, closures plus ownership, inheritance plus construction, exceptions plus mutation/cleanup, optional API results plus typed boundaries | `container_calls.py`, `derived_data.php`, callable/exception suites, strict sample applications; each scenario links the underlying checks and has observable effects |

## Required proof axes for each applicable check

| Axis | What must be established |
| --- | --- |
| Admission | Valid syntax/type combinations prepare; invalid combinations reject for the relevant reason, not an unrelated missing prerequisite |
| Result | Expected value, semantic type and branch state; compile-only and output-only proofs labelled separately |
| Boundary | Zero/empty/null/false/error, numeric edges and signed zero, absent keys, invalid indices and relevant encoding cases |
| Effects | Ordering, once-only evaluation, lazy skipping, destination preservation or documented partial effects after failure |
| Ownership | Copy versus alias, addressability, move/escape, owner retention, destruction and exceptional cleanup where relevant |
| Composition | Selected combinations with fields, calls, containers, wrappers, closures or exceptions rather than only literal expressions |
| Integration | Same contract across local/parameter/return/property/element and multi-file/export sites where applicable |
| Recovery | Incremental edits, failed preparation followed by repair, source deletion and fresh/incremental agreement where facts are retained |

Concrete checks now use IDs such as `LV04.001`, with source locators, contracts,
expected observations and execution recipes in the linked inventory. Verification
will record which assertions prove each obligation and add only missing probes.
Applicability is determined independently of current implementation acceptance.

## Known inputs requiring reconciliation

- S01 and S03 have focused resolved evidence. S02 is deferred legacy syntax;
  strict migration exposed a separate IO resource issue. Reconcile the historical
  Sxx totals with these follow-ups; do not carry old counts forward as current.
- The full generated matrix had 22,816 emitted cases. Its full-run evidence predates
  the scalar repair; the 60-case follow-up does not establish a fresh full pass.
- Add the 39 unseeded probe rows (`empty_keyed`, `empty_value`, `count_value`) to
  the coverage ledger; determine meaningful valid sources before executing them.
- Matrix gaps include dynamic/fixed-width operand pairs, power, concat/concat-assign,
  spaceship, xor and distinct word-form logical operators. Reuse focused tests
  where adequate and document missing combinations.
- Scalar float division by zero is settled: preserve `division_by_zero`, including
  floating positive/negative zero. The old IEEE counterfactual is not a failing
  acceptance test or a new open issue.
- Fixture migration and table comparison semantics remain separate workstreams.
  No adaptation should silently convert an unchanged-source failure into a pass.
- The 52 CH15 review groups plus later additions are discovery inputs, not 52
  confirmed defects or 52 simple fixes. Some are intentional future extensions,
  some coverage gaps, some contract decisions and some implementation issues.
- Current catalog closeouts contain bounded proofs and historical native runs;
  they do not prove every language combination on the current workspace snapshot.

## Inventory reconciliation

The concrete inventory records discovery inputs from the sources below. During
verification, reconcile each routed card/symbol/fixture to its actual assertion
and final disposition; routing is not a pass:

1. All catalog cards/debt, normative language specs and strict library contracts.
2. Existing my-try PHP tests, Python runners and their fixtures/required arguments.
3. Root `tests/`, runtime tests, strict examples, project samples and historical
   compatibility manifests; record exclusions explicitly, not just name filters.
4. Public runtime exports/metadata and optional-module inventories.
5. Known missing tests and interactions inferred from each semantic rule.

Unmapped discoveries extend this checklist with stable IDs. The planning inventory
is ready for review; no verification goal has started. A statement-by-statement
coverage claim still requires the evidence and reconciliation work defined in the
verification goal, including the explicitly recorded discovery inputs.
