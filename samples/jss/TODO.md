# JSS Manual Conversion Todo
Doc Status: planning

Purpose: track the manual Codex conversion queue from `tests/php/*/level_*/*.phs` into `samples/jss/`, ordered by frontend complexity.

Current status: STAN-classified emission now covers the current JSS sample mirror. The high-confidence ES6-shaped clean mapping candidates and the clean second-wave items from the active planning note have first sample coverage. JSS summaries now use a JSS-owned file summary builder, request factory, and classification normalizer; the builder now owns declaration/member/param/local summary shapes through summary version 3. Declaration summaries and classification requests/results carry source line/range metadata, and classification-origin emitter failures include source locations when available. Project STAN sessions now include `.jss` files through the JSS summary extractor. `mixed`/`dynamic` JSS `+` lowers to the runtime-backed `js_plus(...)` helper, and a dedicated strict `.jss` project build/run test now validates the normal project path, classified PHS intermediate, runtime compile, and binary output. Unsupported-syntax diagnostic cleanup now has a separate planning lane in `specs/planning/jss_unsupported_syntax_diagnostics_2026_06_12.md`; the next feature-focused P1 candidate is strict single-site null coalescing. New manual conversions may resume, but each new sample should pass both the normal JSS emission path and the STAN-classified emission path.

## Status Values

- `converted`: JSS sample and expected PHS exist
- `ready`: source is a good next manual conversion candidate
- `blocked`: source depends on unsupported JSS syntax or semantics
- `needs_frontend`: parser/emitter support should be added first
- `needs_stan`: requires semantic classification beyond the current isolated frontend

## Complexity Order

1. Output-only calls and string literals
2. Typed locals, scalar literals, numeric `+`
3. String concatenation through JSS `+`
4. Function calls with simple arguments
5. Comparisons and `if` blocks
6. `while` and `for` loops
7. Array/vector/hash literals and indexing
8. `foreach` over vector values
9. `foreach` over hash key/value pairs
10. Functions and returns
11. Classes, constructors, properties, methods
12. Static/class constants and namespace/use forms
13. References, wrappers, runtime modules, and dynamic/mixed-heavy cases

## Current Queue

| Status | Complexity | Source PHS | JSS Sample | Notes |
| --- | ---: | --- | --- | --- |
| converted | 1 | `tests/php/output/level_01/output_001_echo_string_literal.phs` | `samples/jss/output/level_01/output_001_echo_string_literal.jss` | `print(...)` maps to `echo ...;` |
| converted | 1 | `tests/php/output/level_01/output_002_echo_multiple_values.phs` | `samples/jss/output/level_01/output_002_echo_multiple_values.jss` | multiple print args |
| converted | 3 | `tests/php/output/level_02/output_003_echo_concat_basic.phs` | `samples/jss/output/level_02/output_003_echo_concat_basic.jss` | concat expression in print args |
| converted | 2 | `tests/php/output/level_02/output_004_echo_numeric_cast_path.phs` | `samples/jss/output/level_02/output_004_echo_numeric_cast_path.jss` | numeric print path |
| converted | 2 | `tests/php/operators/level_01/operators_001_add_basic.phs` | `samples/jss/operators/level_01/operators_001_add_basic.jss` | typed local plus numeric `+` |
| converted | 3 | `tests/php/operators/level_01/operators_005_concat_basic.phs` | `samples/jss/operators/level_01/operators_005_concat_basic.jss` | string `+` emits PHS `.` |
| converted | 2 | `tests/php/operators/level_01/operators_002_sub_basic.phs` | `samples/jss/operators/level_02/operators_019_clean_arithmetic.jss` | arithmetic `-`, `/`, `%` coverage |
| converted | 5 | `tests/php/operators/level_02/operators_010_lte_gte_basic.phs` | `samples/jss/operators/level_02/operators_020_comparison_logic_or.jss` | `<=`, `>=`, and `||` |
| converted | 6 | `tests/php/operators/level_02/operators_014_compound_assign_basic.phs` | `samples/jss/operators/level_02/operators_021_updates_and_compound.jss` | compound assignment plus prefix/postfix update forms |
| converted | 13 | `specs/dynamic_types.md` | `samples/jss/operators/level_02/operators_022_dynamic_plus_helper.jss` | frontend contract for `mixed`/`dynamic` JSS `+` lowering to `js_plus(...)`; runtime helper still separate |
| converted | 13 | `specs/dynamic_types.md` | `samples/jss/operators/level_02/operators_023_dynamic_string_plus_helper.jss` | `mixed`/`dynamic` JSS `+` keeps the `js_plus(...)` boundary even when the other operand is statically string |
| converted | 2 | `tests/php/types/int/level_01/int_001_init_basic.phs` | `samples/jss/types/int/level_01/int_001_init_basic.jss` | typed int local |
| converted | 2 | `tests/php/types/int/level_01/int_002_assign_basic.phs` | `samples/jss/types/int/level_01/int_002_assign_basic.jss` | reassignment |
| converted | 2 | `tests/php/types/int/level_02/int_003_arithmetic_basic.phs` | `samples/jss/types/int/level_02/int_003_arithmetic_basic.jss` | multiplicative precedence in arithmetic expression |
| converted | 5 | `tests/php/types/int/level_02/int_004_comparison_basic.phs` | `samples/jss/types/int/level_02/int_004_comparison_basic.jss` | comparisons; uses explicit `if` while ternary policy is open |
| converted | 4 | `tests/php/types/int/level_02/int_005_concat_cast_path.phs` | `samples/jss/types/int/level_02/int_005_concat_cast_path.jss` | int concat cast path |
| converted | 2 | `tests/php/types/string/level_01/string_001_init_basic.phs` | `samples/jss/types/string/level_01/string_001_init_basic.jss` | typed string local |
| converted | 2 | `tests/php/types/string/level_01/string_002_assign_basic.phs` | `samples/jss/types/string/level_01/string_002_assign_basic.jss` | reassignment |
| converted | 3 | `tests/php/types/string/level_02/string_003_concat_basic.phs` | `samples/jss/types/string/level_02/string_003_concat_basic.jss` | chained string concat |
| converted | 5 | `tests/php/types/string/level_02/string_004_comparison_basic.phs` | `samples/jss/types/string/level_02/string_004_comparison_basic.jss` | string comparison; uses explicit `if` while ternary policy is open |
| converted | 2 | `tests/php/types/float/level_01/float_001_init_basic.phs` | `samples/jss/types/float/level_01/float_001_init_basic.jss` | decimal numeric literal |
| converted | 2 | `tests/php/types/float/level_01/float_002_assign_basic.phs` | `samples/jss/types/float/level_01/float_002_assign_basic.jss` | float reassignment |
| converted | 2 | `tests/php/types/float/level_02/float_003_arithmetic_basic.phs` | `samples/jss/types/float/level_02/float_003_arithmetic_basic.jss` | float arithmetic |
| converted | 5 | `tests/php/types/float/level_02/float_004_comparison_basic.phs` | `samples/jss/types/float/level_02/float_004_comparison_basic.jss` | `<` and `>` comparisons; uses explicit `if` while ternary policy is open |
| converted | 4 | `tests/php/types/float/level_02/float_005_concat_cast_path.phs` | `samples/jss/types/float/level_02/float_005_concat_cast_path.jss` | float concat cast path |
| converted | 2 | `tests/php/types/bool/level_01/bool_001_init_basic.phs` | `samples/jss/types/bool/level_01/bool_001_init_basic.jss` | boolean literal plus `if` output shape |
| converted | 2 | `tests/php/types/bool/level_01/bool_002_assign_basic.phs` | `samples/jss/types/bool/level_01/bool_002_assign_basic.jss` | bool reassignment; uses explicit `if` while ternary policy is open |
| converted | 5 | `tests/php/types/bool/level_02/bool_003_logic_basic.phs` | `samples/jss/types/bool/level_02/bool_003_logic_basic.jss` | narrow `&&` and unary `!` support |
| converted | 5 | `tests/php/types/bool/level_02/bool_004_comparison_basic.phs` | `samples/jss/types/bool/level_02/bool_004_comparison_basic.jss` | strict `!==` comparison; uses explicit `if` while ternary policy is open |
| converted | 4 | `tests/php/types/bool/level_02/bool_005_concat_cast_path.phs` | `samples/jss/types/bool/level_02/bool_005_concat_cast_path.jss` | bool concat cast path |
| converted | 5 | `tests/php/types/nullable/level_01/nullable_001_nullable_int_init.phs` | `samples/jss/types/nullable/level_01/nullable_001_null_literal_and_type.jss` | `null` literal and `?T` type spelling |
| converted | 5 | `tests/php/types/nullable/level_01/nullable_002_nullable_int_assign.phs` | `samples/jss/types/nullable/level_01/nullable_002_nullable_int_assign.jss` | nullable assignment from null to value |
| converted | 13 | `tests/php/types/nullable/level_02/nullable_003_null_coalesce_basic.phs` | `samples/jss/types/nullable/level_02/nullable_003_null_coalesce_basic.jss` | strict single-site `??` over explicit nullable left operand |
| converted | 13 | `tests/php/types/nullable/level_02/nullable_004_mixed_null_coalesce_basic.phs` | `samples/jss/types/nullable/level_02/nullable_004_mixed_null_coalesce_basic.jss` | strict single-site `??` at explicit `mixed` boundary |
| converted | 5 | `tests/php/types/nullable/level_02/nullable_004_nullable_return_basic.phs` | `samples/jss/types/nullable/level_02/nullable_004_nullable_return_basic.jss` | nullable typed return with explicit null checks while `??` policy remains open |
| blocked | 13 | `tests/php/types/nullable/level_02/nullable_005_mixed_null_coalesce_chain.phs` | | chained null coalescing remains out of the first strict JSS `??` slice |
| converted | 13 | `tests/php/types/nullable/level_02/nullable_008_ternary_literal_string_null.phs` | `samples/jss/types/nullable/level_02/nullable_008_ternary_literal_string_null.jss` | strict bool-only ternary with `T` / `null` branch pair |
| converted | 4 | `tests/php/casts/level_01/casts_001_int_to_string_via_concat.phs` | `samples/jss/casts/level_01/casts_001_int_to_string_via_concat.jss` | string operand makes `+` emit PHS concat |
| converted | 4 | `tests/php/casts/level_01/casts_002_float_to_string_via_concat.phs` | `samples/jss/casts/level_01/casts_002_float_to_string_via_concat.jss` | float concat cast path |
| converted | 4 | `tests/php/casts/level_01/casts_003_bool_to_string_via_concat.phs` | `samples/jss/casts/level_01/casts_003_bool_to_string_via_concat.jss` | bool concat cast path |
| converted | 4 | `tests/php/casts/level_02/casts_004_echo_cast_path.phs` | `samples/jss/casts/level_02/casts_004_echo_cast_path.jss` | direct numeric print path |
| converted | 5 | `tests/php/casts/level_02/casts_006_mixed_to_nullable_typed_return.phs` | `samples/jss/casts/level_02/casts_006_mixed_to_nullable_typed_return.jss` | explicit null check plus nullable typed return from hash slot |
| converted | 5 | `tests/php/control_flow/level_01/control_flow_001_if_basic.phs` | `samples/jss/control_flow/level_01/control_flow_001_if_basic.jss` | `if` block and `===` |
| converted | 5 | `tests/php/control_flow/level_01/control_flow_002_if_else_basic.phs` | `samples/jss/control_flow/level_01/control_flow_002_if_else_basic.jss` | `if`/`else` |
| converted | 6 | `tests/php/control_flow/level_01/control_flow_003_while_basic.phs` | `samples/jss/control_flow/level_01/control_flow_003_while_basic.jss` | `while`, `<`, and postfix increment |
| converted | 6 | `tests/php/control_flow/level_01/control_flow_005_for_basic.phs` | `samples/jss/control_flow/level_01/control_flow_005_for_basic.jss` | `for` header parsing |
| converted | 6 | `tests/php/control_flow/level_02/control_flow_009_break_one_level.phs` | `samples/jss/control_flow/level_02/control_flow_009_break_one_level.jss` | one-level `break` statement |
| converted | 6 | `tests/php/control_flow/level_02/control_flow_010_continue_one_level.phs` | `samples/jss/control_flow/level_02/control_flow_010_continue_one_level.jss` | one-level `continue` statement |
| converted | 6 | `tests/php/control_flow/level_01/control_flow_004_do_while_basic.phs` | `samples/jss/control_flow/level_02/control_flow_033_do_while_basic.jss` | `do`/`while` |
| converted | 6 | `tests/php/control_flow/level_02/control_flow_008_if_elseif_else_basic.phs` | `samples/jss/control_flow/level_02/control_flow_032_else_if_basic.jss` | `else if` lowers as nested `if` in `else` |
| converted | 6 | `tests/php/control_flow/level_02/control_flow_011_switch_basic.phs` | `samples/jss/control_flow/level_02/control_flow_034_switch_basic.jss` | scalar `switch`/`case`/`default` |
| converted | 7 | `tests/php/types/vector/level_01/vector_001_empty_vector_init.phs` | `samples/jss/types/vector/level_01/vector_001_empty_vector_init.jss` | generic type spelling and empty array literal |
| converted | 7 | `tests/php/types/vector/level_01/vector_002_vector_literal_init.phs` | `samples/jss/types/vector/level_01/vector_002_vector_literal_init.jss` | array literal expression |
| converted | 7 | `tests/php/types/vector/level_02/vector_003_vector_assign_basic.phs` | `samples/jss/types/vector/level_02/vector_003_vector_assign_basic.jss` | vector assignment and index readback |
| converted | 7 | `tests/php/types/vector/level_02/vector_005_vector_index_read.phs` | `samples/jss/types/vector/level_02/vector_005_vector_index_read.jss` | vector index read |
| converted | 7 | `tests/php/types/hash/level_01/hash_001_empty_hash_init.phs` | `samples/jss/types/hash/level_01/hash_001_empty_hash_init.jss` | empty hash init |
| converted | 8 | `tests/php/control_flow/level_01/control_flow_006_foreach_value_basic.phs` | `samples/jss/control_flow/level_01/control_flow_006_foreach_value_basic.jss` | value-only `for (... of ...)` |
| converted | 9 | `tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs` | `samples/jss/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.jss` | hash literal and key/value `for (... of ...)` |
| converted | 9 | `tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs` | `samples/jss/control_flow/level_01/control_flow_031_foreach_hash_value_basic.jss` | hash value-only foreach |
| converted | 10 | `tests/php/control_flow/level_01/control_flow_007_return_basic.phs` | `samples/jss/control_flow/level_01/control_flow_007_return_basic.jss` | function declaration and `return` |
| converted | 10 | `tests/php/functions/level_01/functions_001_typed_param_basic.phs` | `samples/jss/functions/level_01/functions_001_typed_param_basic.jss` | typed parameter |
| converted | 10 | `tests/php/functions/level_01/functions_002_typed_return_basic.phs` | `samples/jss/functions/level_01/functions_002_typed_return_basic.jss` | typed return |
| converted | 10 | `tests/php/functions/level_01/functions_003_default_param_basic.phs` | `samples/jss/functions/level_01/functions_003_default_param_basic.jss` | default parameter values |
| converted | 10 | `tests/php/functions/level_02/functions_004_multi_param_basic.phs` | `samples/jss/functions/level_02/functions_009_void_multi_param.jss` | multi-param plus `void` return |
| converted | 10 | `tests/php/functions/level_02/functions_007_void_return_basic.phs` | `samples/jss/functions/level_02/functions_007_void_return_basic.jss` | explicit `return;` from void function |
| converted | 11 | `tests/php/classes/level_01/classes_001_basic_construction.phs` | `samples/jss/classes/level_01/classes_001_basic_construction.jss` | class, constructor, property read |
| converted | 11 | `tests/php/classes/level_01/classes_002_property_read_write_basic.phs` | `samples/jss/classes/level_01/classes_002_property_read_write_basic.jss` | property default and write |
| converted | 11 | `tests/php/classes/level_01/classes_003_method_call_basic.phs` | `samples/jss/classes/level_01/classes_003_method_call_basic.jss` | method declaration/call |
| converted | 12 | `tests/php/classes/level_01/classes_004_class_const_basic.phs` | `samples/jss/classes/level_01/classes_004_class_const_basic.jss` | class constants/static access |
| converted | 12 | `tests/php/classes/level_02/classes_005_static_property_basic.phs` | `samples/jss/classes/level_02/classes_005_static_property_basic.jss` | static property declaration/read |
| converted | 12 | `tests/php/classes/level_02/classes_006_static_method_basic.phs` | `samples/jss/classes/level_02/classes_006_static_method_basic.jss` | static method declaration/call |
| converted | 12 | `tests/php/classes/level_02/classes_009_inheritance_basic.phs` | `samples/jss/classes/level_02/classes_009_inheritance_basic.jss` | simple `extends` declaration and inherited instance method call |
| converted | 12 | `tests/php/classes/level_02/classes_010_namespace_forward_decl_cycle.phs` | `samples/jss/classes/level_02/classes_010_namespace_forward_decl_cycle.jss` | namespace-scoped classes with nullable cross-references |
| converted | 11 | `tests/php/classes/level_02/classes_012_doc_hash_property_basic.phs` | `samples/jss/classes/level_02/classes_028_typed_hash_property.jss` | typed hash property with read-only object literal initializer |
| converted | 12 | `tests/php/classes/level_02/classes_005_static_property_basic.phs` | `samples/jss/classes/level_02/classes_029_static_field_polish.jss` | static field property syntax polish |
| converted | 12 | `tests/php/constants/level_01/constants_001_class_const_basic.phs` | `samples/jss/constants/level_01/constants_001_class_const_basic.jss` | second class constant sample |
| converted | 12 | `tests/php/constants/level_01/constants_002_const_read_basic.phs` | `samples/jss/constants/level_01/constants_002_const_read_basic.jss` | top-level constant |
| converted | 12 | `tests/php/constants/level_02/constants_003_const_in_expression.phs` | `samples/jss/constants/level_02/constants_003_const_in_expression.jss` | constant used in arithmetic expression |
| converted | 12 | `tests/php/constants/level_02/constants_005_top_level_array_const_regression.phs` | `samples/jss/constants/level_02/constants_005_top_level_array_const_regression.jss` | top-level hash constant literal |
| converted | 12 | `tests/php/namespaces/level_01/namespaces_001_basic_namespace_class.phs` | `samples/jss/namespaces/level_01/namespaces_001_basic_namespace_class.jss` | namespace plus static method |
| converted | 12 | `tests/php/namespaces/level_01/namespaces_002_fully_qualified_class_access.phs` | `samples/jss/namespaces/level_01/namespaces_002_fully_qualified_class_access.jss` | dotted namespace path emits fully qualified class access |
| converted | 12 | `tests/php/namespaces/level_02/namespaces_004_namespace_const_access.phs` | `samples/jss/namespaces/level_02/namespaces_004_namespace_const_access.jss` | dotted namespace constant access emits fully qualified PHS constant |
| converted | 12 | `samples/jss/namespaces/level_02/namespaces_005_block_namespace_basic.expected.phs` | `samples/jss/namespaces/level_02/namespaces_005_block_namespace_basic.jss` | block namespace syntax lowers to existing semicolon-style namespace semantics |
| converted | 12 | `tests/php/use/level_01/use_001_use_class_basic.phs` | `samples/jss/use/level_01/use_001_use_class_basic.jss` | class import |
| converted | 12 | `tests/php/use/level_01/use_002_use_namespace_alias_basic.phs` | `samples/jss/use/level_01/use_002_use_namespace_alias_basic.jss` | namespace alias import |
| blocked | 13 | `tests/php/references/level_01/references_001_local_reference_basic.phs` | | JSS reference syntax/policy needs explicit design before conversion |
| blocked | 13 | `tests/php/references/level_01/references_002_reference_assignment_basic.phs` | | JSS reference alias chaining should not be invented implicitly |
| converted | 2 | `tests/php/variables/level_01/variables_001_definition_basic.phs` | `samples/jss/variables/level_01/variables_001_definition_basic.jss` | basic variable definition |
| converted | 2 | `tests/php/variables/level_01/variables_002_reassignment_basic.phs` | `samples/jss/variables/level_01/variables_002_reassignment_basic.jss` | basic variable reassignment |
| converted | 5 | `tests/php/variables/level_02/variables_003_inner_scope_shadow_basic.phs` | `samples/jss/variables/level_02/variables_003_inner_scope_shadow_basic.jss` | inner-block assignment with outer value reuse |
| converted | 2 | `tests/php/variables/level_02/variables_004_assign_from_variable.phs` | `samples/jss/variables/level_02/variables_004_assign_from_variable.jss` | assignment from existing local |
| converted | 2 | `tests/php/variables/level_02/variables_005_assign_from_expression.phs` | `samples/jss/variables/level_02/variables_005_assign_from_expression.jss` | assignment from simple expression |
| converted | 13 | `tests/php/process/level_01/process_001_argc_argv_basic.phs` | `samples/jss/process/level_01/process_001_argc_argv_basic.jss` | CLI globals plus index access |
| converted | 13 | `tests/php/process/level_01/process_002_shell_exec_basic.phs` | `samples/jss/process/level_01/process_002_shell_exec_basic.jss` | `shell_exec` and explicit false check |
| converted | 13 | `tests/php/process/level_01/process_003_cli_helpers_in_function.phs` | `samples/jss/process/level_01/process_003_cli_helpers_in_function.jss` | CLI helper calls inside function |
| converted | 7 | `tests/php/types/vector/level_02/vector_004_vector_append_basic.phs` | `samples/jss/types/vector/level_02/vector_004_vector_append_basic.jss` | statement-form `push(...)` vector append |
| converted | 7 | `tests/php/types/vector/level_02/vector_006_vector_index_write.phs` | `samples/jss/types/vector/level_02/vector_006_vector_index_write.jss` | direct index assignment |
| blocked | 13 | `tests/php/types/vector/level_02/vector_007_vector_index_unset.phs` | | unset/mutation syntax needs explicit JSS policy |
| converted | 7 | `tests/php/types/hash/level_01/hash_002_hash_literal_and_access.phs` | `samples/jss/types/hash/level_01/hash_002_hash_literal_and_access.jss` | read-only hash literal and index access |
| converted | 7 | `tests/php/types/hash/level_01/hash_003_hash_literal_mixed_keyed_append.phs` | `samples/jss/types/hash/level_02/hash_005_keyed_update_basic.jss` | keyed hash update shape |
| converted | 12 | `tests/php/constants/level_02/constants_004_const_cross_namespace.phs` | `samples/jss/constants/level_02/constants_004_const_cross_namespace.jss` | cross-namespace constant read through dotted namespace access |
| converted | 12 | `tests/php/use/level_02/use_003_use_function_basic.phs` | `samples/jss/use/level_02/use_003_use_function_basic.jss` | function import |
| converted | 12 | `tests/php/use/level_02/use_004_use_const_basic.phs` | `samples/jss/use/level_02/use_004_use_const_basic.jss` | const import |
| converted | 12 | `tests/php/use/level_02/use_005_use_multiple_basic.phs` | `samples/jss/use/level_02/use_005_use_multiple_basic.jss` | class, function, and const imports together |
| converted | 12 | `samples/jss/use/level_02/use_006_reserved_helper_surface.expected.phs` | `samples/jss/use/level_02/use_006_reserved_helper_surface.jss` | reserved helper-family lowering through `fs.*` and `json.*` call surfaces |
| converted | 14 | `samples/jss/filesystem/strict_level_01/strict_filesystem_003_take_fs_get_basic.expected.phs` | `samples/jss/filesystem/strict_level_01/strict_filesystem_003_take_fs_get_basic.jss` | first-pass `take(...)` result-wrapper flow with `fs.get(...)` |
| converted | 14 | `samples/jss/filesystem/strict_level_01/strict_filesystem_004_bool_and_path_helpers_basic.expected.phs` | `samples/jss/filesystem/strict_level_01/strict_filesystem_004_bool_and_path_helpers_basic.jss` | reserved `fs.*` bool/string helper-family coverage without new wrapper semantics |
| converted | 14 | `samples/jss/filesystem/strict_level_01/strict_filesystem_005_put_scan_realpath_basic.expected.phs` | `samples/jss/filesystem/strict_level_01/strict_filesystem_005_put_scan_realpath_basic.jss` | richer reserved `fs.*` coverage for known `result<T>` helpers `fs.put(...)`, `fs.scan(...)`, and `fs.realpath(...)` |
| converted | 14 | `samples/jss/io/strict_level_01/strict_io_003_take_io_open_read_close_basic.expected.phs` | `samples/jss/io/strict_level_01/strict_io_003_take_io_open_read_close_basic.jss` | first-pass `take(...)` result-wrapper flow with `io.open(...)`, `io.read_line(...)`, and `io.close(...)` |
| converted | 14 | `samples/jss/io/strict_level_01/strict_io_004_tell_and_eof_basic.expected.phs` | `samples/jss/io/strict_level_01/strict_io_004_tell_and_eof_basic.jss` | reserved `io.*` helper-family coverage for `take(...)` on `io.tell(...)` and plain bool `io.eof(...)` |
| converted | 14 | `samples/jss/io/strict_level_01/strict_io_005_seek_nullable_basic.expected.phs` | `samples/jss/io/strict_level_01/strict_io_005_seek_nullable_basic.jss` | reserved `io.*` nullable helper-family coverage through existing strict `??` behavior |
| converted | 14 | `samples/jss/io/strict_level_01/strict_io_006_write_flush_rewind_read_basic.expected.phs` | `samples/jss/io/strict_level_01/strict_io_006_write_flush_rewind_read_basic.jss` | richer reserved `io.*` coverage using known write/flush/rewind/read contracts through the existing `take(...)` flow |
| converted | 14 | `samples/jss/json/strict_level_01/strict_json_002_decode_encode_basic.expected.phs` | `samples/jss/json/strict_level_01/strict_json_002_decode_encode_basic.jss` | reserved `json.*` helper-family round-trip through existing strict helpers |
| converted | 14 | `samples/jss/json/strict_level_01/strict_json_003_encode_literal_basic.expected.phs` | `samples/jss/json/strict_level_01/strict_json_003_encode_literal_basic.jss` | reserved `json.*` helper-family encoding of an explicitly typed hash literal |
| converted | 14 | `samples/jss/json/strict_level_01/strict_json_004_roundtrip_template_basic.expected.phs` | `samples/jss/json/strict_level_01/strict_json_004_roundtrip_template_basic.jss` | `json.*` round-trip plus explicit template interpolation over a known string result |
| converted | 14 | `samples/jss/datetime/strict_level_01/strict_datetime_003_parse_format_basic.expected.phs` | `samples/jss/datetime/strict_level_01/strict_datetime_003_parse_format_basic.jss` | reserved `dt.*` helper-family flow with existing `take(...)` result extraction |
| converted | 14 | `samples/jss/datetime/strict_level_01/strict_datetime_004_parse_format_iso_utc_basic.expected.phs` | `samples/jss/datetime/strict_level_01/strict_datetime_004_parse_format_iso_utc_basic.jss` | reserved `dt.*` ISO UTC helper-family coverage through existing result extraction |
| converted | 14 | `samples/jss/datetime/strict_level_01/strict_datetime_005_format_now_basic.expected.phs` | `samples/jss/datetime/strict_level_01/strict_datetime_005_format_now_basic.jss` | reserved `dt.*` plain string helper-family coverage for `dt.format_now(...)` |
| converted | 14 | `samples/jss/datetime/strict_level_01/strict_datetime_006_parse_and_render_variants_basic.expected.phs` | `samples/jss/datetime/strict_level_01/strict_datetime_006_parse_and_render_variants_basic.jss` | richer reserved `dt.*` coverage combining parse, format, ISO UTC format, and format-now helpers with known contracts |
| converted | 12 | `tests/php/namespaces/level_02/namespaces_003_cross_namespace_static_access.phs` | `samples/jss/namespaces/level_02/namespaces_003_cross_namespace_static_access.jss` | cross-namespace static call |
| converted | 11 | `samples/jss/classes/level_02/classes_030_public_static_constructor_polish.expected.phs` | `samples/jss/classes/level_02/classes_030_public_static_constructor_polish.jss` | explicit public/static constructor flow within the existing class subset |
| converted | 11 | `samples/jss/classes/level_02/classes_031_instance_defaults_and_methods.expected.phs` | `samples/jss/classes/level_02/classes_031_instance_defaults_and_methods.jss` | constructor default parameter plus straightforward instance member/default-value access |
| converted | 12 | `samples/jss/classes/level_02/classes_032_static_const_instance_polish.expected.phs` | `samples/jss/classes/level_02/classes_032_static_const_instance_polish.jss` | safe static field/class-constant access alongside ordinary instance method/member reads |
| needs_frontend | 12 | `tests/php/classes/level_02/classes_022_late_static_method_basic.phs` | | `extends` and late-static `static::` semantics need explicit JSS frontend/STAN policy |
| blocked | 13 | `tests/php/curl/level_01/curl_001_legacy_file_surface.phs` | | legacy curl wrapper shape; JSS should target strict typed curl policy instead |
| blocked | 13 | `tests/php/curl/level_01/curl_002_legacy_http_get.phs` | | legacy curl wrapper shape; do not import PHP-legacy falseable curl behavior into JSS by default |
| blocked | 13 | `docs/examples/php/strict/project_samples/strict_curl/main.phs` | | strict curl needs result-wrapper, negation, else-if, append, ternary, and runtime-module policy first |
| converted | 14 | `docs/examples/php/strict/project_samples/strict_fs_json/main.phs` | `samples/jss/json/strict_level_01/strict_json_005_fs_take_roundtrip_basic.jss` | usable fs/json/take prototype lane now has both expected-PHS coverage and dedicated real project build/run validation |
| blocked | 13 | `docs/examples/php/strict/project_samples/strict_str_io/main.phs` | | strict IO wrapper/result-flow policy needed before conversion |
| blocked | 13 | `docs/examples/php/strict/project_samples/strict_error_paths/main.phs` | | ternary and `take(...)` result-flow policy needed before conversion |
| converted | 13 | `samples/jss/TODO.md` | `samples/jss/POLICY_BACKLOG.md` | policy backlog note for references, wrappers, ternary, runtime modules, and append/update syntax |
| converted | 3 | `tests/php/known_gap/interpolation/level_01/interpolation_001_basic_var_known_gap.phs` | `samples/jss/templates/level_01/templates_002_identifier_interpolation.jss` | narrow template literal `${identifier}` interpolation lowers to explicit concat |
| converted | 3 | `samples/jss/templates/level_01/templates_003_member_interpolation_basic.expected.phs` | `samples/jss/templates/level_01/templates_003_member_interpolation_basic.jss` | dotted template interpolation lowers to explicit concat without full JS expression semantics |
| converted | 3 | `samples/jss/templates/level_01/templates_004_static_member_interpolation_basic.expected.phs` | `samples/jss/templates/level_01/templates_004_static_member_interpolation_basic.jss` | template interpolation also covers classified static property and class-constant dotted chains |
| converted | 6 | `samples/jss/operators/level_01/operators_006_unary_minus_basic.expected.phs` | `samples/jss/operators/level_01/operators_006_unary_minus_basic.jss` | unary minus on numeric literals within the existing strict numeric subset |

## Policy Backlog

These areas should not be expanded through accidental sample conversion:

- references: decide whether JSS exposes any explicit reference alias syntax at all, and if so how it maps to the reduced Prism++ native-reference subset
- result wrappers: decide the JSS spelling for `take(...)`, error captures, and success/failure branching before converting strict filesystem, IO, curl, or error-path samples
- ternary output: decide whether JSS supports `condition ? a : b` directly or prefers ordinary `if` blocks for strict clarity
- null coalescing: decide strict `??` behavior before converting nullable coalesce samples
- unsupported-syntax diagnostics: continue separately under `specs/planning/jss_unsupported_syntax_diagnostics_2026_06_12.md` so parser-diagnostic normalization stays out of feature-focused work
- runtime modules: decide how JSS projects opt into modules such as `curl`, `regex`, IO, and filesystem, and whether sample metadata should carry module requirements
- arrow functions/callables: do not add JSS arrow functions until PHS callable/closure lowering has a stable supported target

See `samples/jss/POLICY_BACKLOG.md` for the current local planning detail.
