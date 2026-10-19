# v0.1 compatibility first pass — 2026-10-17

Doc Status: planning

Baseline: `75de94c71e37060c564b6ade19850b7686bd457a`; v0.2: `d675aca8251374555c9191067793175b43d8a79c`.

Unchanged source; PHP-host compilers and generated native programs. Operator matrices excluded. This does not rebuild the native compiler.


Executed with the [50-case manifest](../../tests/legacy_compatibility.json) and
[runner](../../tests/legacy_compatibility.py). See the [usage and evidence guide](v0_1_compatibility_testing.md).
The [retained JSON](v0_1_compatibility_results_2026_10_17.json) records source hashes,
profiles, per-stage results, observed outputs and failure diagnostics. Full command
logs, snapshots and artifacts: `/tmp/scpp-compat-pass1-final/` (295 logged commands).
The final rerun includes explicit opt-in source-module selection. The two PHP-host
compilers used their own runtime snapshots; PHP-FPM fell back to CLI because the
sandbox denied socket creation. No native compiler rebuild or operator matrix ran.

This is 50 selected cases, not 50 feature families or a release completion percentage.
Forty-seven reuse pinned test metadata, two special tests compare baseline execution,
and one extracts the existing async tool test's PHS nowdoc with an explicit oracle.
All 21 v0.2 differences stop at generation/preparation; no successfully generated
v0.2 program in this set failed C++ compilation or its runtime oracle.

## Disposition of the 21 differences

These are first blockers, not assertions about every later operation in each file.
Automatic `compatibility-gap` means an observed difference, not an automatic bug verdict.

| Cases (basename prefix) | Count | First blocker and disposition |
| --- | ---: | --- |
| `strict_control_flow_001`, `arrays_001`, `arrays_002` | 3 | Untyped container literals lack a known construction target. Approved v0.2 construction change; migration fixtures should be added separately, preserving these originals. |
| `strict_json_001` | 1 | Mixed output does not match default JSON's dynamic result. Approved family/JSON change. Later JSON operations in this fixture remain untested. |
| `take_001`, `vector_003`, `vector_004`, `strict_io_001` | 4 | Comment type intent is lost: wrapper extraction, typed literal targets or declared IO locals fail. Missing original annotation support; strict equivalents do not close these cases. |
| `strict_control_flow_002`, `strict_strings_001`, `strict_strings_002`, `test_08_finally_return_for_loop`, `nullable_004` | 5 | Unresolved `is_bool`, `substr_replace`, `strpos`, or `var_dump`. Existing deferred API work. The finally case stops at var_dump, so this run does not demonstrate a finally-control-flow failure. |
| `control_flow_013`, `control_flow_016` | 2 | Reference foreach is rejected; existing iteration safety gates. The second filename says negative, but its metadata and successful baseline execution say positive. |
| `closures_004_use_reference_capture` | 1 | Explicit `function<int()>` receiving a reference-capturing closure fails exact-signature admission. Investigate callable adaptation/return contract; this is narrower than claiming all reference captures are absent. |
| `strict_strings_004_strpos_false_branch_regression` | 1 | Brace-free if/else bodies fail parsing (`Expected '{'`). Source grammar compatibility candidate, distinct from the also-deferred strpos API. |
| `strict_source_001_buffer_span` | 1 | After selecting source/core/strings, owning buffer calls resolve and `source_buffer_span` does not. Deferred borrowed-span/lifetime contract; no longer a default-module configuration failure. |
| `constants_005_top_level_array_const_regression` | 1 | Non-scalar source constant initializer rejected. Review collection-constant compatibility separately from local data construction. |
| `test_scpp_async_core_language` | 1 | `async function` syntax rejected. Confirms the missing async path using the old tool test's actual source. |
| `take_006_result_wrapper_basic` | 1 | Bare `error` constant cannot resolve. Review error-sentinel migration plus the fixture's comment annotations; do not treat this as proof that result extraction itself is absent. |

Thus four first blockers directly match already-approved construction/JSON changes;
the other seventeen need implementation or an explicit compatibility disposition.
This does not imply that migrating those four fixtures will make their whole programs pass.

## Baseline failure

`test_07_finally_return.phs` fails C++ compilation on the pinned v0.1 adapter:
`MyEx` has no constructor accepting the supplied string. v0.2 also rejects its
runtime-base relationship. This single-file fixture is **baseline-unproven**, not
a v0.2 regression; review original harness composition/inherited constructors
before promoting it into a positive compatibility obligation.

## Inventory and next work

The baseline inventory contains 50 selected files, 465 other PHP source candidates,
413 expectation-metadata files, 145 runtime-only files, 65 harness-coupled files,
504 other source/project files needing an oracle/adapter, and 2,211 operator/matrix
paths reserved for the second pass. These are file counts with overlapping source/
metadata concepts, not counts of distinct tests. Detailed paths are in the run's
`inventory.json`; the committed manifest is the exact executable selection.

Extend coverage next through additional positive source metadata and adapters for
multi-file projects, negative diagnostics and optional libraries. Preserve the
separate operator semantic-change review before running matrix expectations.
The focused oracle tests also pass: exact nowdoc extraction/ambiguity rejection,
declared newline policy, baseline-failure classification, and binary-output fidelity.
Both host adapters pass PHP syntax checks.

## Complete executed list

| Status | Count |
| --- | ---: |
| baseline-unproven | 1 |
| compatibility-gap | 21 |
| compatible-proved | 28 |

| Source | Profile | Result | v0.1 stage | v0.2 stage |
| --- | --- | --- | --- | --- |
| `tests/php/functions/level_01/functions_001_typed_param_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/functions/level_01/functions_002_typed_return_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/control_flow/level_01/control_flow_001_if_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/control_flow/level_01/control_flow_002_if_else_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/control_flow/strict_level_01/strict_control_flow_001_php_helpers.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/control_flow/strict_level_01/strict_control_flow_002_mixed_type_predicates.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/variables/level_01/variables_001_definition_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/variables/level_01/variables_002_reassignment_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/constants/level_01/constants_001_class_const_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/constants/level_01/constants_002_const_read_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/classes/level_01/classes_001_basic_construction.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/classes/level_01/classes_002_property_read_write_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/namespaces/level_01/namespaces_001_basic_namespace_class.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/namespaces/level_01/namespaces_002_fully_qualified_class_access.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/closures/strict_level_01/closures_001_typed_callable_use_capture.phs` | strict | compatible-proved | passed | passed |
| `tests/php/closures/strict_level_01/closures_002_typed_callable_supplies_return.phs` | strict | compatible-proved | passed | passed |
| `tests/php/arrays/level_01/arrays_001_empty_append_scalar.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/arrays/level_01/arrays_002_packed_readback.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/types/bool/level_01/bool_001_init_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/types/bool/level_01/bool_002_assign_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/types/int/strict_level_01/strict_int_006_fixed_width_local_returns.phs` | strict | compatible-proved | passed | passed |
| `tests/php/types/int/strict_level_01/strict_int_007_fixed_width_unsigned_local_returns.phs` | strict | compatible-proved | passed | passed |
| `tests/php/references/level_01/references_001_local_reference_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/references/level_01/references_002_reference_assignment_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/strings/strict_level_01/strict_strings_001_str_surface.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/strings/strict_level_01/strict_strings_002_str_edges.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/take/level_02/take_001_wrapper_extraction_basic.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/take/level_02/take_006_result_wrapper_basic.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/output/level_01/output_001_echo_string_literal.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/output/level_01/output_002_echo_multiple_values.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/use/level_01/use_001_use_class_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/use/level_02/use_003_use_function_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/generators/php/special_tests/test_07_finally_return.phs` | legacy | baseline-unproven | cpp-build | generation |
| `tests/generators/php/special_tests/test_08_finally_return_for_loop.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/control_flow/level_02/control_flow_013_foreach_ref_value_basic.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/control_flow/level_02/control_flow_016_foreach_key_ref_value_negative.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/types/vector/level_02/vector_003_vector_assign_basic.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/types/vector/level_02/vector_004_vector_append_basic.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/types/nullable/level_02/nullable_003_null_coalesce_basic.phs` | legacy | compatible-proved | passed | passed |
| `tests/php/types/nullable/level_02/nullable_004_mixed_null_coalesce_basic.phs` | legacy | compatibility-gap | passed | generation |
| `tests/php/closures/strict_level_02/closures_004_use_reference_capture.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/closures/strict_level_02/closures_006_vector_return_typed_callable.phs` | strict | compatible-proved | passed | passed |
| `tests/php/strings/strict_level_01/strict_strings_004_strpos_false_branch_regression.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/strings/strict_level_01/strict_strings_007_explicit_string_units.phs` | strict | compatible-proved | passed | passed |
| `tests/php/strings/strict_level_01/strict_strings_008_string_parts_builder.phs` | strict | compatible-proved | passed | passed |
| `tests/php/io/strict_level_01/strict_io_001_io_surface.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/json/strict_level_01/strict_json_001_json_surface.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/source/strict_level_01/strict_source_001_buffer_span.phs` | strict | compatibility-gap | passed | generation |
| `tests/php/constants/level_02/constants_005_top_level_array_const_regression.phs` | legacy | compatibility-gap | passed | generation |
| `tests/tools/test_scpp_async_core_language.php` | strict | compatibility-gap | passed | generation |
