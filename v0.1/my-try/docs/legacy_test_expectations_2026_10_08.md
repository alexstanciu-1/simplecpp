# Legacy PHP test expectation review — 2026-10-08
Doc Status: supporting

Read-only review of 309 `.test-info.json` files under `tests/php/`, excluding the entire `operators/` subtree. No test sources or implementations were needed for this refresh; statuses derive from active expectations and their notes. No tests were run. Active expectations are presumed green at the user’s direction. This is not a newly measured run.

306 active cases: 245 generation/compilation/execution expectations, 35 compile-only cases, 25 generation rejections, and one compilation rejection. Three disabled/todo interpolation cases are not support evidence. Green negative tests prove rejection, not feature support. Compile-only cases do not prove execution.

The [historical reference inventory](../../../tests/php/reference_matrix/README.md) explicitly defers to metadata-backed arrays/references suites; its unmigrated positive fixtures are excluded. No operator matrix was read. Filename suffixes such as `negative` and directories such as `known_gap` do not override expectation fields.

This ledger includes library suites for inventory completeness; the comparison remains a compiler-feature report, not a complete library migration audit. The external curl case is marked opt-in by its own notes; its existence does not establish inclusion in an ordinary run.

Each linked JSON contains the exact stdout/stderr, exit status, diagnostics, and build configuration. Summaries below retain expectation notes; disabled-case notes may intentionally disagree with placeholder success fields.

## arrays

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [arrays_001_empty_append_scalar](../../../tests/php/arrays/level_01/arrays_001_empty_append_scalar.test-info.json) | Generate / compile / run | Append scalars into an empty packed array. |
| [arrays_002_packed_readback](../../../tests/php/arrays/level_01/arrays_002_packed_readback.test-info.json) | Generate / compile / run | Read back packed indexes after multiple appends. |
| [arrays_003_explicit_int_keys_basic](../../../tests/php/arrays/level_01/arrays_003_explicit_int_keys_basic.test-info.json) | Generate / compile / run | Explicit integer keys preserve key-value association. |
| [arrays_004_string_keys_basic](../../../tests/php/arrays/level_01/arrays_004_string_keys_basic.test-info.json) | Generate / compile / run | String keys work for basic set/get. |
| [arrays_005_mixed_int_string_keys](../../../tests/php/arrays/level_01/arrays_005_mixed_int_string_keys.test-info.json) | Generate / compile / run | Mixed integer and string keys coexist in one table. |
| [arrays_006_overwrite_existing_key](../../../tests/php/arrays/level_01/arrays_006_overwrite_existing_key.test-info.json) | Generate / compile / run | Overwriting an existing key updates only that slot. |
| [arrays_007_append_after_high_int_key](../../../tests/php/arrays/level_01/arrays_007_append_after_high_int_key.test-info.json) | Generate / compile / run | Appending after a high explicit integer key follows PHP next-index behavior. |
| [arrays_008_append_after_string_keys_only](../../../tests/php/arrays/level_01/arrays_008_append_after_string_keys_only.test-info.json) | Generate / compile / run | Appending after only string keys starts packed indexes at zero. |
| [arrays_009_nested_array_literal_read](../../../tests/php/arrays/level_01/arrays_009_nested_array_literal_read.test-info.json) | Generate / compile / run | Nested array literals can be read through a dim chain. |
| [arrays_010_nested_dim_write](../../../tests/php/arrays/level_01/arrays_010_nested_dim_write.test-info.json) | Generate / compile / run | Nested dims can be created and written incrementally. |
| [arrays_011_nested_append_rows](../../../tests/php/arrays/level_01/arrays_011_nested_append_rows.test-info.json) | Generate / compile / run | Appending row arrays builds a list of records. |
| [arrays_012_copy_value_top_level](../../../tests/php/arrays/level_01/arrays_012_copy_value_top_level.test-info.json) | Generate / compile / run | Top-level array assignment copies by value. |
| [arrays_013_copy_value_nested_independence](../../../tests/php/arrays/level_01/arrays_013_copy_value_nested_independence.test-info.json) | Generate / compile / run | Nested rows remain independent after array copy-by-value. |
| [arrays_014_assign_nested_into_slot_then_mutate_source](../../../tests/php/arrays/level_01/arrays_014_assign_nested_into_slot_then_mutate_source.test-info.json) | Generate / compile / run | Assigning a row into another slot snapshots it by value. |
| [arrays_015_append_then_overwrite_nested_row](../../../tests/php/arrays/level_01/arrays_015_append_then_overwrite_nested_row.test-info.json) | Generate / compile / run | A row can be appended and then updated through its nested dims. |
| [arrays_016_numeric_string_key_normalization](../../../tests/php/arrays/level_01/arrays_016_numeric_string_key_normalization.test-info.json) | Generate / compile / run | Numeric string keys should follow PHP array key normalization. |
| [arrays_017_small_loop_row_growth](../../../tests/php/arrays/level_01/arrays_017_small_loop_row_growth.test-info.json) | Generate / compile / run | A small loop appending rows exercises repeated growth. |
| [arrays_018_row_update_after_loop_append](../../../tests/php/arrays/level_01/arrays_018_row_update_after_loop_append.test-info.json) | Generate / compile / run | Rows appended in a loop remain writable afterwards. |
| [arrays_019_nested_snapshot_value](../../../tests/php/arrays/level_01/arrays_019_nested_snapshot_value.test-info.json) | Generate / compile / run | Copying one nested row into another slot snapshots current contents only. |
| [arrays_020_root_row_leaf_basic_consistency](../../../tests/php/arrays/level_01/arrays_020_root_row_leaf_basic_consistency.test-info.json) | Generate / compile / run | Root, row, and leaf views stay coherent in a simple non-ref flow. |
| [arrays_021_pass_array_by_value_snapshot](../../../tests/php/arrays/level_02/arrays_021_pass_array_by_value_snapshot.test-info.json) | Generate / compile / run | Passing an array by value should not mutate the caller. |
| [arrays_022_pass_array_by_ref_mutation](../../../tests/php/arrays/level_02/arrays_022_pass_array_by_ref_mutation.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_023_root_row_leaf_alias](../../../tests/php/arrays/level_02/arrays_023_root_row_leaf_alias.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_024_same_row_twice_by_ref](../../../tests/php/arrays/level_02/arrays_024_same_row_twice_by_ref.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_025_same_leaf_value_and_ref_split](../../../tests/php/arrays/level_02/arrays_025_same_leaf_value_and_ref_split.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_026_nested_parent_same_leaf](../../../tests/php/arrays/level_02/arrays_026_nested_parent_same_leaf.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_027_duplicate_row_refs_root_path](../../../tests/php/arrays/level_02/arrays_027_duplicate_row_refs_root_path.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_028_fanout_root_mid_leaf](../../../tests/php/arrays/level_02/arrays_028_fanout_root_mid_leaf.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_029_row_copy_then_mutate_original](../../../tests/php/arrays/level_02/arrays_029_row_copy_then_mutate_original.test-info.json) | Generate / compile / run | A copied row should not change when the original row mutates later. |
| [arrays_030_row_copy_then_mutate_copy](../../../tests/php/arrays/level_02/arrays_030_row_copy_then_mutate_copy.test-info.json) | Generate / compile / run | Mutating a copied row should not change the original row. |
| [arrays_031_large_loop_row_growth](../../../tests/php/arrays/level_02/arrays_031_large_loop_row_growth.test-info.json) | Generate / compile / run | A larger append loop stress-tests repeated row allocation and growth. |
| [arrays_032_dense_growth_then_patch](../../../tests/php/arrays/level_02/arrays_032_dense_growth_then_patch.test-info.json) | Generate / compile / run | After dense growth, previously appended rows remain writable. |
| [arrays_033_numeric_string_and_int_key_collision](../../../tests/php/arrays/level_02/arrays_033_numeric_string_and_int_key_collision.test-info.json) | Generate / compile / run | Numeric string and integer keys should collide where PHP normalizes them. |
| [arrays_034_bool_keys_casting](../../../tests/php/arrays/level_02/arrays_034_bool_keys_casting.test-info.json) | Reject generation | Boolean array keys are outside the current supported Prism++ array-key subset and must be rejected during generation. Expected diagnostic: Boolean PHP array keys; not supported |
| [arrays_035_null_key_casting](../../../tests/php/arrays/level_02/arrays_035_null_key_casting.test-info.json) | Reject generation | Null array keys are outside the current supported Prism++ array-key subset and must be rejected during generation. Expected diagnostic: Null PHP array keys; not supported |
| [arrays_036_root_ref_append_other_row_stability](../../../tests/php/arrays/level_02/arrays_036_root_ref_append_other_row_stability.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_037_cross_row_nested_alias](../../../tests/php/arrays/level_02/arrays_037_cross_row_nested_alias.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_038_matrix_like_nested_writes](../../../tests/php/arrays/level_02/arrays_038_matrix_like_nested_writes.test-info.json) | Generate / compile / run | Matrix-like nested integer dims should remain addressable and stable. |
| [arrays_039_deep_three_level_duplicate_refs](../../../tests/php/arrays/level_02/arrays_039_deep_three_level_duplicate_refs.test-info.json) | Reject generation | This shape now fails during generation because by-reference array slot arguments require directly stable native-reference-bindable storage in the current safe subset. Expected diagnostic: By-reference argument requires directly stable native-reference-bindable storage in the current safe subset. |
| [arrays_040_self_assign_from_nested_slot](../../../tests/php/arrays/level_02/arrays_040_self_assign_from_nested_slot.test-info.json) | Generate / compile / run | Assigning one nested slot into another slot should clone the current value cleanly. |

## casts

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [casts_001_int_to_string_via_concat](../../../tests/php/casts/level_01/casts_001_int_to_string_via_concat.test-info.json) | Generate / compile / run | Int to string conversion through concatenation path. |
| [casts_002_float_to_string_via_concat](../../../tests/php/casts/level_01/casts_002_float_to_string_via_concat.test-info.json) | Generate / compile / run | Float to string conversion through concatenation path. |
| [casts_003_bool_to_string_via_concat](../../../tests/php/casts/level_01/casts_003_bool_to_string_via_concat.test-info.json) | Generate / compile / run | Bool to string conversion through concatenation path. |
| [casts_004_echo_cast_path](../../../tests/php/casts/level_02/casts_004_echo_cast_path.test-info.json) | Generate / compile / run | Echo cast path for a numeric value. |
| [casts_005_condition_bool_cast_path](../../../tests/php/casts/level_02/casts_005_condition_bool_cast_path.test-info.json) | Generate / compile / run | Condition bool cast path. |
| [casts_006_mixed_to_nullable_typed_return](../../../tests/php/casts/level_02/casts_006_mixed_to_nullable_typed_return.test-info.json) | Generate / compile / run | Typed return from an array slot should allow the centralized mixed_t -> nullable<T> cast path. |

## classes

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [classes_001_basic_construction](../../../tests/php/classes/level_01/classes_001_basic_construction.test-info.json) | Generate / compile / run | Basic object construction. |
| [classes_002_property_read_write_basic](../../../tests/php/classes/level_01/classes_002_property_read_write_basic.test-info.json) | Generate / compile / run | Basic property read and write. |
| [classes_003_method_call_basic](../../../tests/php/classes/level_01/classes_003_method_call_basic.test-info.json) | Generate / compile / run | Basic method call. |
| [classes_004_class_const_basic](../../../tests/php/classes/level_01/classes_004_class_const_basic.test-info.json) | Generate / compile / run | Basic class constant access. |
| [classes_005_static_property_basic](../../../tests/php/classes/level_02/classes_005_static_property_basic.test-info.json) | Generate / compile / run | Basic static property access. |
| [classes_006_static_method_basic](../../../tests/php/classes/level_02/classes_006_static_method_basic.test-info.json) | Generate / compile / run | Basic static method call. |
| [classes_007_method_reference_param](../../../tests/php/classes/level_02/classes_007_method_reference_param.test-info.json) | Generate / compile / run | Method pass-by-reference parameter. |
| [classes_008_method_reference_return](../../../tests/php/classes/level_02/classes_008_method_reference_return.test-info.json) | Generate / compile / run | Method return-by-reference. |
| [classes_009_inheritance_basic](../../../tests/php/classes/level_02/classes_009_inheritance_basic.test-info.json) | Generate / compile / run | Basic inheritance. |
| [classes_010_namespace_forward_decl_cycle](../../../tests/php/classes/level_02/classes_010_namespace_forward_decl_cycle.test-info.json) | Generate / compile / run | Same-namespace class properties should compile through namespace-level forward declarations. |
| [classes_011_doc_vector_property_forward_decl](../../../tests/php/classes/level_02/classes_011_doc_vector_property_forward_decl.test-info.json) | Generate / compile / run | A class property annotated as /** vector<T> */ must lower to vector_t<shared_p<T>> and include a same-namespace forward declaration for T. |
| [classes_012_doc_hash_property_basic](../../../tests/php/classes/level_02/classes_012_doc_hash_property_basic.test-info.json) | Generate / compile / run | A class property annotated as /** hash<int> */ should use the default string-key typed hash surface for keyed mutations. |
| [classes_013_hash_sharedp_foreach_typing](../../../tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.test-info.json) | Generate / compile / run | Typed hash<model_property> foreach should preserve concrete key/value typing across loop values, property access, and assignment into another typed hash. |
| [classes_014_hash_sharedp_foreach_value_only](../../../tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.test-info.json) | Generate / compile / run | Value-only foreach over hash<model_property> should preserve the shared_p<T> element type for property access. |
| [classes_015_hash_sharedp_key_stabilization](../../../tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.test-info.json) | Generate / compile / run | Typed hash keys and values should stabilize cleanly through typed locals and write back into another hash<model_property>. |
| [classes_016_hash_sharedp_method_source_direct_key_usage](../../../tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.test-info.json) | Generate / compile / run | Typed hash iteration should preserve direct key-helper usage and typed object field access without stabilization workarounds. |
| [classes_017_hash_sharedp_direct_key_helper_and_reindex](../../../tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.test-info.json) | Generate / compile / run | Typed hash foreach should preserve direct loop-key helper calls, typed override reads, typed object field writes, and direct reindexing into another hash<model_property> without explicit stabilization. |
| [classes_018_hash_sharedp_method_nullable_key_boundary](../../../tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.test-info.json) | Generate / compile / run | Typed hash foreach keys passed to a method helper expecting ?string should lower without a synthetic cast<nullable<string_t>>(...) bridge. |
| [classes_019_hash_method_return_local_foreach_nullable_key](../../../tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.test-info.json) | Generate / compile / run | A fresh local initialized from a method returning ?model-like object should preserve the typed hash property shape strongly enough for foreach key locals to stay concrete at a ?string helper boundary. |
| [classes_020_hash_foreach_non_this_method_source](../../../tests/php/classes/level_02/classes_020_hash_foreach_non_this_method_source.test-info.json) | Generate / compile / run | Typed hash foreach over a non-this method-call source should preserve concrete key/value typing for nullable helper boundaries, typed field writes, and typed reindexing. |
| [classes_021_hash_foreach_local_from_non_this_method](../../../tests/php/classes/level_02/classes_021_hash_foreach_local_from_non_this_method.test-info.json) | Generate / compile / run | A local initialized from a non-this method call returning hash<model_property> should preserve concrete foreach key/value typing through nullable helper boundaries, typed property reads, and typed reindexing. |
| [classes_022_late_static_method_basic](../../../tests/php/classes/level_02/classes_022_late_static_method_basic.test-info.json) | Generate / compile / run | Basic late static method dispatch through an inherited static method. |
| [classes_023_late_static_misc_basic](../../../tests/php/classes/level_02/classes_023_late_static_misc_basic.test-info.json) | Generate / compile / run | Late static property, const, and new basic coverage. |
| [classes_024_late_static_unrelated_context](../../../tests/php/classes/level_02/classes_024_late_static_unrelated_context.test-info.json) | Generate / compile / run | Unrelated class families should not reuse an outer late-static context. |
| [classes_025_late_static_local_single_call](../../../tests/php/classes/level_02/classes_025_late_static_local_single_call.test-info.json) | Generate / compile / run | Exact regression for a class-local static::method() call; must not duplicate the call. |
| [classes_026_late_static_inherited_dual_dispatch](../../../tests/php/classes/level_02/classes_026_late_static_inherited_dual_dispatch.test-info.json) | Generate / compile / run | Exact regression for inherited static::method() dispatch across both base and child call sites. |
| [classes_027_override_dispatch_through_this](../../../tests/php/classes/level_02/classes_027_override_dispatch_through_this.test-info.json) | Generate / compile / run | Regression for instance method override dispatch through a base-class method calling another instance method on $this. |

## closures

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [closures_001_typed_callable_use_capture](../../../tests/php/closures/strict_level_01/closures_001_typed_callable_use_capture.test-info.json) | Generate / compile / run | Typed callable local with explicit closure return type and by-value use capture snapshots the original value. |
| [closures_002_typed_callable_supplies_return](../../../tests/php/closures/strict_level_01/closures_002_typed_callable_supplies_return.test-info.json) | Generate / compile / run | Typed callable local supplies the closure return type when the initializer omits it. |
| [closures_003_direct_signature_synthesis](../../../tests/php/closures/strict_level_01/closures_003_direct_signature_synthesis.test-info.json) | Generate / compile / run | Direct local closure assignment synthesizes std::function type from explicit closure signature. |
| [closures_004_use_reference_capture](../../../tests/php/closures/strict_level_02/closures_004_use_reference_capture.test-info.json) | Generate / compile / run | Closure use capture by reference observes later writes. |
| [closures_005_multiple_use_capture](../../../tests/php/closures/strict_level_02/closures_005_multiple_use_capture.test-info.json) | Generate / compile / run | Multiple use captures preserve predictable by-value closure behavior. |
| [closures_006_vector_return_typed_callable](../../../tests/php/closures/strict_level_02/closures_006_vector_return_typed_callable.test-info.json) | Generate / compile / run | Typed callable local supplies a vector<int> closure return type and returns a typed vector literal. |
| [closures_007_vector_return_direct_signature](../../../tests/php/closures/strict_level_02/closures_007_vector_return_direct_signature.test-info.json) | Generate / compile / run | Direct closure assignment synthesizes a callable with vector<int> return after closure use capture. |
| [closures_008_missing_return_type_negative](../../../tests/php/closures/strict_level_02/closures_008_missing_return_type_negative.test-info.json) | Reject generation | Untyped closure assignment with a value return requires either an explicit closure return type or an expected callable local signature. Expected diagnostic: Closure return types must be declared explicitly in std::function lowering |
| [closures_009_container_storage_negative](../../../tests/php/closures/strict_level_02/closures_009_container_storage_negative.test-info.json) | Reject generation | Closures remain concrete local callable values and cannot be appended into dynamic containers. Expected diagnostic: Closures cannot be stored in array or dynamic container slots |

## constants

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [constants_001_class_const_basic](../../../tests/php/constants/level_01/constants_001_class_const_basic.test-info.json) | Generate / compile / run | Basic class constant access. |
| [constants_002_const_read_basic](../../../tests/php/constants/level_01/constants_002_const_read_basic.test-info.json) | Generate / compile / run | Basic constant read. |
| [constants_003_const_in_expression](../../../tests/php/constants/level_02/constants_003_const_in_expression.test-info.json) | Generate / compile / run | Constant usage inside an expression. |
| [constants_004_const_cross_namespace](../../../tests/php/constants/level_02/constants_004_const_cross_namespace.test-info.json) | Generate / compile / run | Cross-namespace constant access. |
| [constants_005_top_level_array_const_regression](../../../tests/php/constants/level_02/constants_005_top_level_array_const_regression.test-info.json) | Generate / compile / run | Regression for issue #51: top-level const declarations, including array initializers, must not crash due to missing ConstantDecl line metadata. |

## control_flow

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [control_flow_001_if_basic](../../../tests/php/control_flow/level_01/control_flow_001_if_basic.test-info.json) | Generate / compile / run | Basic if statement. |
| [control_flow_002_if_else_basic](../../../tests/php/control_flow/level_01/control_flow_002_if_else_basic.test-info.json) | Generate / compile / run | Basic if/else statement. |
| [control_flow_003_while_basic](../../../tests/php/control_flow/level_01/control_flow_003_while_basic.test-info.json) | Generate / compile / run | Basic while loop. |
| [control_flow_004_do_while_basic](../../../tests/php/control_flow/level_01/control_flow_004_do_while_basic.test-info.json) | Generate / compile / run | Basic do/while loop. |
| [control_flow_005_for_basic](../../../tests/php/control_flow/level_01/control_flow_005_for_basic.test-info.json) | Generate / compile / run | Basic for loop. |
| [control_flow_006_foreach_value_basic](../../../tests/php/control_flow/level_01/control_flow_006_foreach_value_basic.test-info.json) | Generate / compile / run | Basic foreach value iteration on vector<int>. |
| [control_flow_007_return_basic](../../../tests/php/control_flow/level_01/control_flow_007_return_basic.test-info.json) | Generate / compile / run | Basic function return statement. |
| [control_flow_027_foreach_hash_key_value_basic](../../../tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.test-info.json) | Generate / compile / run | Basic foreach key/value iteration on hash<int>. |
| [control_flow_031_foreach_hash_value_basic](../../../tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.test-info.json) | Generate / compile / run | Typed hash value-only foreach should iterate values directly without degrading through keyed access paths. |
| [control_flow_008_if_elseif_else_basic](../../../tests/php/control_flow/level_02/control_flow_008_if_elseif_else_basic.test-info.json) | Generate / compile / run | if / elseif / else chain. |
| [control_flow_009_break_one_level](../../../tests/php/control_flow/level_02/control_flow_009_break_one_level.test-info.json) | Generate / compile / run | break for one level. |
| [control_flow_010_continue_one_level](../../../tests/php/control_flow/level_02/control_flow_010_continue_one_level.test-info.json) | Generate / compile / run | continue for one level. |
| [control_flow_011_switch_basic](../../../tests/php/control_flow/level_02/control_flow_011_switch_basic.test-info.json) | Generate / compile / run | Basic switch. |
| [control_flow_012_match_basic](../../../tests/php/control_flow/level_02/control_flow_012_match_basic.test-info.json) | Generate / compile / run | Basic match expression. |
| [control_flow_013_foreach_ref_value_basic](../../../tests/php/control_flow/level_02/control_flow_013_foreach_ref_value_basic.test-info.json) | Generate / compile / run | foreach by-reference value iteration. |
| [control_flow_014_foreach_outer_name_shadow_kept](../../../tests/php/control_flow/level_02/control_flow_014_foreach_outer_name_shadow_kept.test-info.json) | Generate / compile / run | Simple C++ policy: if the foreach value variable already exists before the loop, the loop reuses that outer binding and the final iterated value remains visible after the loop. |
| [control_flow_015_foreach_key_value_negative](../../../tests/php/control_flow/level_02/control_flow_015_foreach_key_value_negative.test-info.json) | Generate / compile / run | foreach key/value form currently lowers and runs in the active implementation. |
| [control_flow_016_foreach_key_ref_value_negative](../../../tests/php/control_flow/level_02/control_flow_016_foreach_key_ref_value_negative.test-info.json) | Generate / compile / run | foreach key/value-by-reference form currently lowers and runs in the active implementation. |
| [control_flow_017_false_null_explicit_split](../../../tests/php/control_flow/level_02/control_flow_017_false_null_explicit_split.test-info.json) | Generate / compile / run | Uses explicit strict checks to keep false, null, and present scalar states distinct. |
| [control_flow_018_foreach_result_wrapper_value_basic](../../../tests/php/control_flow/level_02/control_flow_018_foreach_result_wrapper_value_basic.test-info.json) | Generate / compile / run | Foreach can iterate a result_or_false<vector<string>> success payload through runtime iterable delegation. |
| [control_flow_018_isset_key_present_missing_null](../../../tests/php/control_flow/level_02/control_flow_018_isset_key_present_missing_null.test-info.json) | Generate / compile / run | Covers the current keyed isset() contract: present value true, present null false, missing false. |
| [control_flow_019_empty_scalar_subset_contract](../../../tests/php/control_flow/level_02/control_flow_019_empty_scalar_subset_contract.test-info.json) | Generate / compile / run | Locks the PHP-aligned Prism++ empty() scalar contract, which intentionally differs from PHP only for "0". |
| [control_flow_020_isset_vs_empty_key_intent](../../../tests/php/control_flow/level_02/control_flow_020_isset_vs_empty_key_intent.test-info.json) | Generate / compile / run | Shows why isset() and empty() are not interchangeable for keyed lookups. |
| [control_flow_021_string_condition_truthiness](../../../tests/php/control_flow/level_02/control_flow_021_string_condition_truthiness.test-info.json) | Generate / compile / run | Locks PHP-style string truthiness for direct conditions and elvis selection while keeping it distinct from strict string->bool normalization. |
| [control_flow_022_foreach_mixed_object_value_only](../../../tests/php/control_flow/level_02/control_flow_022_foreach_mixed_object_value_only.test-info.json) | Generate / compile / run | mixed_t object-like foreach should iterate entry values in insertion order. |
| [control_flow_023_foreach_mixed_object_key_value](../../../tests/php/control_flow/level_02/control_flow_023_foreach_mixed_object_key_value.test-info.json) | Generate / compile / run | mixed_t object-like foreach should expose both key and value using entry iteration. |
| [control_flow_024_foreach_mixed_object_ref_value](../../../tests/php/control_flow/level_02/control_flow_024_foreach_mixed_object_ref_value.test-info.json) | Generate / compile / run | foreach mixed object by-reference value form should mutate the underlying table entries. |
| [control_flow_025_foreach_mixed_object_key_ref_value](../../../tests/php/control_flow/level_02/control_flow_025_foreach_mixed_object_key_ref_value.test-info.json) | Generate / compile / run | foreach mixed object key/value-by-reference form should expose keys and mutate underlying entries. |
| [control_flow_026_foreach_result_wrapper_key_ref_value](../../../tests/php/control_flow/level_02/control_flow_026_foreach_result_wrapper_key_ref_value.test-info.json) | Generate / compile / run | foreach over result_or_false<vector<T>> should expose keys and support by-reference element updates. |
| [control_flow_028_hash_isset_empty_keyed](../../../tests/php/control_flow/level_02/control_flow_028_hash_isset_empty_keyed.test-info.json) | Generate / compile / run | Typed hash<mixed> should use the keyed isset()/empty() helper contract. |
| [control_flow_029_foreach_hash_ref_value](../../../tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.test-info.json) | Generate / compile / run | foreach by-reference value form should mutate typed hash<int> entries in place. |
| [control_flow_029_isset_typed_hash_property_missing](../../../tests/php/control_flow/level_02/control_flow_029_isset_typed_hash_property_missing.test-info.json) | Generate / compile / run | isset() over a typed hash stored on an object property must stay on the keyed helper path so missing keys return false instead of throwing. |
| [control_flow_030_foreach_hash_key_ref_value](../../../tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.test-info.json) | Generate / compile / run | foreach key plus by-reference value form should mutate typed hash<int> entries in place. |
| [strict_control_flow_001_php_helpers](../../../tests/php/control_flow/strict_level_01/strict_control_flow_001_php_helpers.test-info.json) | Generate / compile / run | Strict profile still needs the PHP-owned helper layer for count/isset/empty/coalesce/ternary/identity lowering. |
| [strict_control_flow_002_mixed_type_predicates](../../../tests/php/control_flow/strict_level_01/strict_control_flow_002_mixed_type_predicates.test-info.json) | Generate / compile / run | Strict profile should expose mixed type predicates through the PHP helper registry instead of leaking raw C++ concept names into generated output. |
| [strict_control_flow_003_if_bool_literal_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_003_if_bool_literal_return.test-info.json) | Compile only | Minimal strict authority for bool-literal if with early return and fallback return. |
| [strict_control_flow_004_if_local_bool_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_004_if_local_bool_return.test-info.json) | Compile only | Minimal strict authority for bool local condition with early return and fallback return. |
| [strict_control_flow_005_if_local_comparison_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_005_if_local_comparison_return.test-info.json) | Compile only | Minimal strict authority for int local comparison as a bool if condition with early return and fallback return. |
| [strict_control_flow_006_if_local_greater_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_006_if_local_greater_return.test-info.json) | Compile only | Representative strict authority proving a second int local comparison operator uses the same bool if condition capability. |
| [strict_control_flow_007_if_else_bool_literal_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_007_if_else_bool_literal_return.test-info.json) | Compile only | Minimal strict authority for bool-literal if/else with literal returns and no string output. |
| [strict_control_flow_008_if_else_local_bool_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_008_if_else_local_bool_return.test-info.json) | Compile only | Representative strict authority for local-bool if/else with literal returns and no string output. |
| [strict_control_flow_009_if_else_local_comparison_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_009_if_else_local_comparison_return.test-info.json) | Compile only | Representative strict authority for local-comparison if/else with literal returns and no string output. |
| [strict_control_flow_010_ternary_int_literal_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_010_ternary_int_literal_return.test-info.json) | Compile only | Minimal strict authority for bool-condition int literal ternary return without string output or runtime dependency. |
| [strict_control_flow_011_ternary_local_bool_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_011_ternary_local_bool_return.test-info.json) | Compile only | Representative strict authority for local bool condition feeding int literal ternary return without string output or runtime dependency. |
| [strict_control_flow_012_ternary_local_comparison_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_012_ternary_local_comparison_return.test-info.json) | Compile only | Representative strict authority for local int comparison feeding int literal ternary return without string output or runtime dependency. |
| [strict_control_flow_013_if_assigned_local_bool_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_013_if_assigned_local_bool_return.test-info.json) | Compile only | Strict authority for a bool local assigned before being consumed as an if condition with literal returns. |
| [strict_control_flow_014_if_assigned_local_eq_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_014_if_assigned_local_eq_return.test-info.json) | Compile only | Strict authority for an int equality result assigned to a bool local before if-condition consumption. |
| [strict_control_flow_015_if_assigned_local_lt_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_015_if_assigned_local_lt_return.test-info.json) | Compile only | Strict authority for an int less-than result assigned to a bool local before if-condition consumption. |
| [strict_control_flow_016_if_else_assigned_local_bool_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_016_if_else_assigned_local_bool_return.test-info.json) | Compile only | Strict authority for an assigned bool local consumed by an explicit if/else condition with literal returns. |
| [strict_control_flow_017_if_else_assigned_local_eq_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_017_if_else_assigned_local_eq_return.test-info.json) | Compile only | Strict authority for an assigned equality result consumed by an explicit if/else condition with literal returns. |
| [strict_control_flow_018_if_else_assigned_local_lt_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_018_if_else_assigned_local_lt_return.test-info.json) | Compile only | Strict authority for an assigned less-than result consumed by an explicit if/else condition with literal returns. |
| [strict_control_flow_019_ternary_assigned_local_bool_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_019_ternary_assigned_local_bool_return.test-info.json) | Compile only | Strict authority for an assigned bool local consumed by an int literal ternary return. |
| [strict_control_flow_020_ternary_assigned_local_eq_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_020_ternary_assigned_local_eq_return.test-info.json) | Compile only | Strict authority for an assigned equality result consumed by an int literal ternary return. |
| [strict_control_flow_021_ternary_assigned_local_lt_return](../../../tests/php/control_flow/strict_level_01/strict_control_flow_021_ternary_assigned_local_lt_return.test-info.json) | Compile only | Strict authority for an assigned less-than result consumed by an int literal ternary return. |

## curl

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [curl_001_legacy_file_surface](../../../tests/php/curl/level_01/curl_001_legacy_file_surface.test-info.json) | Generate / compile / run | Legacy curl surface should expose falseable init/exec plus string body retrieval for file:// with CURLOPT_RETURNTRANSFER. |
| [curl_002_legacy_http_get](../../../tests/php/curl/level_01/curl_002_legacy_http_get.test-info.json) | Generate / compile / run | Legacy curl surface should perform a local HTTP GET and return the body string when CURLOPT_RETURNTRANSFER is enabled. |
| [curl_003_legacy_post_input](../../../tests/php/curl/level_01/curl_003_legacy_post_input.test-info.json) | Generate / compile / run | Legacy curl surface should POST form data to the local PHP test server and receive the body string back unchanged. |
| [curl_004_legacy_json_roundtrip](../../../tests/php/curl/level_01/curl_004_legacy_json_roundtrip.test-info.json) | Generate / compile / run | Legacy curl surface should POST JSON to the local PHP test server and consume the JSON response through legacy JSON builtins. |
| [strict_curl_001_surface](../../../tests/php/curl/strict_level_01/strict_curl_001_surface.test-info.json) | Generate / compile / run | Strict curl surface should lower to the shared scpp::curl runtime module using a local file:// source. |
| [strict_curl_002_http_get](../../../tests/php/curl/strict_level_01/strict_curl_002_http_get.test-info.json) | Generate / compile / run | Strict curl surface should perform a real local HTTP GET through the shared scpp::curl runtime module. |
| [strict_curl_003_github_raw_optin](../../../tests/php/curl/strict_level_01/strict_curl_003_github_raw_optin.test-info.json) | Generate / compile / run | Opt-in external integration test: fetch a pinned raw GitHub URL from origin/main and verify the response body. |
| [strict_curl_004_post_input](../../../tests/php/curl/strict_level_01/strict_curl_004_post_input.test-info.json) | Generate / compile / run | Strict curl surface should POST request body content to the local PHP test server and receive it back unchanged. |
| [strict_curl_005_json_roundtrip](../../../tests/php/curl/strict_level_01/strict_curl_005_json_roundtrip.test-info.json) | Generate / compile / run | Strict curl surface should POST JSON to the local PHP test server and consume the JSON response through the strict JSON builtins. |

## datetime

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [datetime_001_legacy_date_strtotime](../../../tests/php/datetime/level_01/datetime_001_legacy_date_strtotime.test-info.json) | Generate / compile / run | Legacy PHP date()/strtotime() supports the common local Y-m-d H:i:s shape. |
| [strict_datetime_001_datetime_surface](../../../tests/php/datetime/strict_level_01/strict_datetime_001_datetime_surface.test-info.json) | Generate / compile / run | Strict datetime profile surface maps directly to scpp::dt::* runtime functions. |
| [strict_datetime_002_common_format](../../../tests/php/datetime/strict_level_01/strict_datetime_002_common_format.test-info.json) | Generate / compile / run | Strict datetime common-format helpers support the common Y-m-d H:i:s shape without PHP DateTime object semantics. |
| [strict_datetime_003_monotonic_precision](../../../tests/php/datetime/strict_level_01/strict_datetime_003_monotonic_precision.test-info.json) | Generate / compile / run | High-resolution monotonic timers should be positive and non-decreasing across a small sleep. |

## debug

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_debug_001_dbg_scalar](../../../tests/php/debug/strict_level_01/strict_debug_001_dbg_scalar.test-info.json) | Generate / compile / run | Strict dbg helper calls should compile for scalar arguments without requiring module discovery in the compiler. |

## filesystem

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_filesystem_001_fs_surface](../../../tests/php/filesystem/strict_level_01/strict_filesystem_001_fs_surface.test-info.json) | Generate / compile / run | Strict filesystem profile surface should map directly to scpp::fs::* runtime functions. |
| [strict_filesystem_002_fs_error_paths](../../../tests/php/filesystem/strict_level_01/strict_filesystem_002_fs_error_paths.test-info.json) | Generate / compile / run | Strict filesystem error paths should fail through result<T> extraction without mutating pre-seeded outputs. |

## functions

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [functions_001_typed_param_basic](../../../tests/php/functions/level_01/functions_001_typed_param_basic.test-info.json) | Generate / compile / run | Basic typed parameter. |
| [functions_002_typed_return_basic](../../../tests/php/functions/level_01/functions_002_typed_return_basic.test-info.json) | Generate / compile / run | Basic typed return. |
| [functions_003_default_param_basic](../../../tests/php/functions/level_01/functions_003_default_param_basic.test-info.json) | Generate / compile / run | Basic default parameter. |
| [functions_004_multi_param_basic](../../../tests/php/functions/level_02/functions_004_multi_param_basic.test-info.json) | Generate / compile / run | Function with multiple parameters. |
| [functions_005_reference_param_basic](../../../tests/php/functions/level_02/functions_005_reference_param_basic.test-info.json) | Generate / compile / run | Function pass-by-reference parameter. |
| [functions_006_reference_return_basic](../../../tests/php/functions/level_02/functions_006_reference_return_basic.test-info.json) | Generate / compile / run | Function return-by-reference. |
| [functions_007_void_return_basic](../../../tests/php/functions/level_02/functions_007_void_return_basic.test-info.json) | Generate / compile / run | Void return function. |
| [functions_008_variadic_negative](../../../tests/php/functions/level_02/functions_008_variadic_negative.test-info.json) | Generate / compile / run | Typed trailing variadics currently lower and run in the active implementation. |
| [strict_functions_001_direct_zero_arg_return](../../../tests/php/functions/strict_level_01/strict_functions_001_direct_zero_arg_return.test-info.json) | Compile only | Minimal strict authority for returning a zero-argument direct call without echo or string output. |
| [strict_functions_002_direct_one_int_arg_return](../../../tests/php/functions/strict_level_01/strict_functions_002_direct_one_int_arg_return.test-info.json) | Compile only | Representative strict authority for returning a one-int-argument direct call without echo or string output. |
| [strict_functions_003_direct_multi_int_arg_return](../../../tests/php/functions/strict_level_01/strict_functions_003_direct_multi_int_arg_return.test-info.json) | Compile only | Representative strict authority for returning a multi-int-argument direct call without echo or string output. |
| [strict_functions_004_direct_local_target_return](../../../tests/php/functions/strict_level_01/strict_functions_004_direct_local_target_return.test-info.json) | Compile only | Representative strict authority for returning a direct call whose target lowers local int storage and a local-local expression without echo output. |
| [strict_functions_005_default_int_arg_return](../../../tests/php/functions/strict_level_01/strict_functions_005_default_int_arg_return.test-info.json) | Compile only | Minimal strict authority for a direct call using a default int argument. |
| [strict_functions_006_default_int_arg_explicit_return](../../../tests/php/functions/strict_level_01/strict_functions_006_default_int_arg_explicit_return.test-info.json) | Compile only | Strict authority that an explicit int argument overrides an available default. |
| [strict_functions_007_default_trailing_int_arg_return](../../../tests/php/functions/strict_level_01/strict_functions_007_default_trailing_int_arg_return.test-info.json) | Compile only | Strict authority for omitting one trailing int default while passing the required first argument. |
| [strict_functions_008_default_two_int_args_return](../../../tests/php/functions/strict_level_01/strict_functions_008_default_two_int_args_return.test-info.json) | Compile only | Strict authority for a direct call where all int arguments are supplied by defaults. |
| [strict_functions_009_default_multi_int_args_return](../../../tests/php/functions/strict_level_01/strict_functions_009_default_multi_int_args_return.test-info.json) | Compile only | Strict authority for materializing multiple omitted trailing int defaults. |

## io

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_io_001_io_surface](../../../tests/php/io/strict_level_01/strict_io_001_io_surface.test-info.json) | Generate / compile / run | Strict IO profile surface should map directly to scpp::io::* runtime functions. |
| [strict_io_002_io_open_failure](../../../tests/php/io/strict_level_01/strict_io_002_io_open_failure.test-info.json) | Generate / compile / run | Strict IO open should stay falseable while successful handles remain usable through the flat visible API. |

## json

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_json_001_json_surface](../../../tests/php/json/strict_level_01/strict_json_001_json_surface.test-info.json) | Generate / compile / run | Strict JSON decoding returns a checked result: take captures malformed input without terminating the process, preserves the output on failure, and accepts repaired input, null, and false. Encoding also returns a checked result, preserves output on nested failure, and recovers in the same process. |

## known_gap

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [interpolation_001_basic_var_known_gap](../../../tests/php/known_gap/interpolation/level_01/interpolation_001_basic_var_known_gap.test-info.json) | Generate / compile / run | Basic variable interpolation is supported. |
| [interpolation_002_braced_var_known_gap](../../../tests/php/known_gap/interpolation/level_01/interpolation_002_braced_var_known_gap.test-info.json) | Generate / compile / run | Braced variable interpolation is supported. |
| [interpolation_003_property_known_gap](../../../tests/php/known_gap/interpolation/level_02/interpolation_003_property_known_gap.test-info.json) | Generate / compile / run | Property interpolation is supported for explicitly typed properties. |
| [interpolation_004_index_known_gap](../../../tests/php/known_gap/interpolation/level_02/interpolation_004_index_known_gap.test-info.json) | Generate / compile / run | Index interpolation is supported for typed vector access. |
| [interpolation_005_static_known_gap](../../../tests/php/known_gap/interpolation/level_02/interpolation_005_static_known_gap.test-info.json) | Disabled/todo | Static access interpolation is not part of the supported interpolation surface documented for Simple C++. |
| [interpolation_006_function_call_known_gap](../../../tests/php/known_gap/interpolation/level_02/interpolation_006_function_call_known_gap.test-info.json) | Disabled/todo | Free-function-call interpolation is not valid PHP interpolation syntax and remains disabled. |
| [interpolation_007_expression_known_gap](../../../tests/php/known_gap/interpolation/level_02/interpolation_007_expression_known_gap.test-info.json) | Disabled/todo | Expression interpolation like {$a + 1} is not valid PHP interpolation syntax and remains disabled. |

## namespaces

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [namespaces_001_basic_namespace_class](../../../tests/php/namespaces/level_01/namespaces_001_basic_namespace_class.test-info.json) | Generate / compile / run | Basic namespace class definition and use. |
| [namespaces_002_fully_qualified_class_access](../../../tests/php/namespaces/level_01/namespaces_002_fully_qualified_class_access.test-info.json) | Generate / compile / run | Fully qualified class access across namespaces. |
| [namespaces_003_cross_namespace_static_access](../../../tests/php/namespaces/level_02/namespaces_003_cross_namespace_static_access.test-info.json) | Generate / compile / run | Cross-namespace static access. |
| [namespaces_004_namespace_const_access](../../../tests/php/namespaces/level_02/namespaces_004_namespace_const_access.test-info.json) | Generate / compile / run | Cross-namespace constant access. |
| [strict_namespaces_001_qualified_function_return](../../../tests/php/namespaces/strict_level_01/strict_namespaces_001_qualified_function_return.test-info.json) | Compile only | Minimal strict authority for a fully qualified namespaced function call returning an int literal without use imports, classes, static lookup, echo, or string output. |
| [strict_namespaces_002_relative_function_return](../../../tests/php/namespaces/strict_level_01/strict_namespaces_002_relative_function_return.test-info.json) | Compile only | Minimal strict authority for namespace-relative unqualified function lookup with a duplicate helper name in another namespace, without use imports, classes, static lookup, echo, or string output. |
| [strict_namespaces_003_block_function_return](../../../tests/php/namespaces/strict_level_01/strict_namespaces_003_block_function_return.test-info.json) | Compile only | Minimal strict authority for namespace block syntax containing one function return, without use imports, classes, static lookup, constants, echo, or string output. |

## output

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [output_001_echo_string_literal](../../../tests/php/output/level_01/output_001_echo_string_literal.test-info.json) | Generate / compile / run | Basic echo of a string literal. |
| [output_002_echo_multiple_values](../../../tests/php/output/level_01/output_002_echo_multiple_values.test-info.json) | Generate / compile / run | Basic echo with multiple values. |
| [output_003_echo_concat_basic](../../../tests/php/output/level_02/output_003_echo_concat_basic.test-info.json) | Generate / compile / run | Echo concatenated values. |
| [output_004_echo_numeric_cast_path](../../../tests/php/output/level_02/output_004_echo_numeric_cast_path.test-info.json) | Generate / compile / run | Echo numeric cast path. |

## process

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [process_001_argc_argv_basic](../../../tests/php/process/level_01/process_001_argc_argv_basic.test-info.json) | Generate / compile / run | CLI globals should expose argc and argv entries from the host entrypoint. |
| [process_002_shell_exec_basic](../../../tests/php/process/level_01/process_002_shell_exec_basic.test-info.json) | Generate / compile / run | shell_exec should return command stdout as a string for a successful command. |
| [process_003_cli_helpers_in_function](../../../tests/php/process/level_01/process_003_cli_helpers_in_function.test-info.json) | Generate / compile / run | CLI helper functions should be usable inside ordinary functions. |
| [strict_process_001_shell_exec_contract](../../../tests/php/process/strict_level_01/strict_process_001_shell_exec_contract.test-info.json) | Generate / compile / run | Strict shell_exec should preserve the same visible string-or-false branch contract as legacy. |

## references

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [references_001_local_reference_basic](../../../tests/php/references/level_01/references_001_local_reference_basic.test-info.json) | Generate / compile / run | Basic local reference alias. |
| [references_002_reference_assignment_basic](../../../tests/php/references/level_01/references_002_reference_assignment_basic.test-info.json) | Generate / compile / run | Basic chained reference assignment. |
| [references_003_pass_by_reference_basic](../../../tests/php/references/level_02/references_003_pass_by_reference_basic.test-info.json) | Generate / compile / run | Pass-by-reference function call. |
| [references_004_return_by_reference_basic](../../../tests/php/references/level_02/references_004_return_by_reference_basic.test-info.json) | Generate / compile / run | Return-by-reference function call. |
| [references_005_reference_mutation_chain](../../../tests/php/references/level_02/references_005_reference_mutation_chain.test-info.json) | Generate / compile / run | Reference mutation through a chain. |

## source

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_source_001_buffer_span](../../../tests/php/source/strict_level_01/strict_source_001_buffer_span.test-info.json) | Generate / compile / run | Strict PHS source buffers should expose byte operations, spans, release, and hash parity with hash_string. |
| [strict_source_002_line_index](../../../tests/php/source/strict_level_01/strict_source_002_line_index.test-info.json) | Generate / compile / run | Source line indexes should map byte offsets to 1-based line/byte-column locations for empty, LF, CRLF, and UTF-8 byte-column inputs. |
| [strict_source_003_stable_hash](../../../tests/php/source/strict_level_01/strict_source_003_stable_hash.test-info.json) | Generate / compile / run | Stable hash helpers keep legacy hex output and add numeric uint64 helpers for compiler cache keys. |

## strings

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_strings_001_str_surface](../../../tests/php/strings/strict_level_01/strict_strings_001_str_surface.test-info.json) | Generate / compile / run | Strict shared helper names should preserve legacy-visible branch contracts while remaining compatible with strict runtime helpers. |
| [strict_strings_002_str_edges](../../../tests/php/strings/strict_level_01/strict_strings_002_str_edges.test-info.json) | Generate / compile / run | Strict string edge behavior should stay deterministic for offsets, masks, and explode limits. |
| [strict_strings_003_str_result_error](../../../tests/php/strings/strict_level_01/strict_strings_003_str_result_error.test-info.json) | Generate / compile / run | Strict shared hex helpers should preserve legacy-visible false-sentinel failure behavior. |
| [strict_strings_004_strpos_false_branch_regression](../../../tests/php/strings/strict_level_01/strict_strings_004_strpos_false_branch_regression.test-info.json) | Generate / compile / run | Regression for strict strpos false-branch handling when the result feeds a downstream substr offset. |
| [strict_strings_005_php_microtime_static_call](../../../tests/php/strings/strict_level_01/strict_strings_005_php_microtime_static_call.test-info.json) | Generate / compile / run | Regression for strict php::microtime(true) static-call lowering so PHP helper namespaces do not get wrapped as late-static class scopes. |
| [strict_strings_006_unqualified_helper_resolution_repro](../../../tests/php/strings/strict_level_01/strict_strings_006_unqualified_helper_resolution_repro.test-info.json) | Generate / compile / run | Regression repro for unqualified strict helper resolution across trim, strtolower, substr, and explode. |
| [strict_strings_007_explicit_string_units](../../../tests/php/strings/strict_level_01/strict_strings_007_explicit_string_units.test-info.json) | Generate / compile / run | Explicit string unit APIs should keep byte, UTF-8 codepoint, and grapheme coordinates separate. |
| [strict_strings_008_string_parts_builder](../../../tests/php/strings/strict_level_01/strict_strings_008_string_parts_builder.test-info.json) | Generate / compile / run | The string_parts_builder runtime helper should expose a typed, reusable string construction surface. |
| [strict_strings_009_text_builder](../../../tests/php/strings/strict_level_01/strict_strings_009_text_builder.test-info.json) | Generate / compile / run | The text_builder runtime helper should expose typed, reusable contiguous text construction with byte-span appends. |

## take

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [take_001_wrapper_extraction_basic](../../../tests/php/take/level_02/take_001_wrapper_extraction_basic.test-info.json) | Generate / compile / run | Covers nullable, result_or_false, and result_or_bool extraction plus unchanged outputs on inactive branches. |
| [take_002_nullable_wrong_arity_negative](../../../tests/php/take/level_02/take_002_nullable_wrong_arity_negative.test-info.json) | Reject generation | Nullable take() rejects a third output slot. Expected diagnostic: take(nullable<T>) requires exactly one output variable and one source expression |
| [take_003_result_or_bool_wrong_flag_type_negative](../../../tests/php/take/level_02/take_003_result_or_bool_wrong_flag_type_negative.test-info.json) | Reject generation | result_or_bool take() requires a bool output slot for the bool branch. Expected diagnostic: take(result_or_bool<T>) expects output type bool_t but got string_t |
| [take_004_non_local_output_negative](../../../tests/php/take/level_02/take_004_non_local_output_negative.test-info.json) | Reject generation | Locks the v1 rule that take() output slots must be simple local variables. Expected diagnostic: take() output arguments must be simple local variables |
| [take_005_non_wrapper_source_negative](../../../tests/php/take/level_02/take_005_non_wrapper_source_negative.test-info.json) | Reject generation | take() rejects non-wrapper source expressions when the source type is known. Expected diagnostic: take() requires nullable<T>, result<T>, result_or_false<T>, or result_or_bool<T> as the source expression |
| [take_006_result_wrapper_basic](../../../tests/php/take/level_02/take_006_result_wrapper_basic.test-info.json) | Generate / compile / run | Covers the PHP-facing take(result<T>) path, including unchanged value output on the error branch. |
| [take_007_builtin_wrapper_consumption_basic](../../../tests/php/take/level_02/take_007_builtin_wrapper_consumption_basic.test-info.json) | Generate / compile / run | Connects PHP-level take() usage to real filesystem wrapper-returning builtins: file_get_contents, realpath, fopen, and fgets. |

## tokenizer

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [strict_tokenizer_001_typed_buffer_accessors](../../../tests/php/tokenizer/strict_level_01/strict_tokenizer_001_typed_buffer_accessors.test-info.json) | Generate / compile / run | Typed tokenizer buffers should expose kind, byte offset, length, line, column, and flags without mixed conversion. |

## types

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [bool_001_init_basic](../../../tests/php/types/bool/level_01/bool_001_init_basic.test-info.json) | Generate / compile / run | Basic bool initialization. |
| [bool_002_assign_basic](../../../tests/php/types/bool/level_01/bool_002_assign_basic.test-info.json) | Generate / compile / run | Basic bool assignment and reassignment. |
| [bool_003_logic_basic](../../../tests/php/types/bool/level_02/bool_003_logic_basic.test-info.json) | Generate / compile / run | Basic boolean logic. |
| [bool_004_comparison_basic](../../../tests/php/types/bool/level_02/bool_004_comparison_basic.test-info.json) | Generate / compile / run | Basic bool comparisons. |
| [bool_005_concat_cast_path](../../../tests/php/types/bool/level_02/bool_005_concat_cast_path.test-info.json) | Generate / compile / run | Bool to string conversion through concat. |
| [strict_bool_006_bool_literal_return](../../../tests/php/types/bool/strict_level_01/strict_bool_006_bool_literal_return.test-info.json) | Compile only | Strict bool literal return should compile without requiring ternary or string output semantics. |
| [strict_bool_007_bool_local_return](../../../tests/php/types/bool/strict_level_01/strict_bool_007_bool_local_return.test-info.json) | Compile only | Strict bool local return should compile without requiring ternary or string output semantics. |
| [enum_001_fixed_width_backing](../../../tests/php/types/enum/level_01/enum_001_fixed_width_backing.test-info.json) | Generate / compile / run | Fixed-width integer-backed enums should lower to native enum class values and support same-enum equality. |
| [enum_004_conversion_helpers](../../../tests/php/types/enum/level_01/enum_004_conversion_helpers.test-info.json) | Generate / compile / run | Explicit enum_value and enum_from_value helpers should convert through the declared fixed-width backing type. |
| [enum_006_hash_key](../../../tests/php/types/enum/level_01/enum_006_hash_key.test-info.json) | Generate / compile / run | Typed hashes should accept enum key families once enum backing and hash key ops exist. |
| [enum_007_name_helper](../../../tests/php/types/enum/level_01/enum_007_name_helper.test-info.json) | Generate / compile / run | enum_name should expose the declared enum case name without changing enum value representation. |
| [enum_002_duplicate_value_negative](../../../tests/php/types/enum/level_02/enum_002_duplicate_value_negative.test-info.json) | Reject generation | Duplicate backed enum values should fail with a clear generator diagnostic. Expected diagnostic: Duplicate enum case value `1` in enum `token_kind` |
| [enum_003_raw_int_assignment_negative](../../../tests/php/types/enum/level_02/enum_003_raw_int_assignment_negative.test-info.json) | Reject generation | Enum-typed boundaries should not accept raw integers without an explicit conversion helper. Expected diagnostic: Implicit assignment from raw integer to enum `token_kind` is not supported |
| [enum_005_from_value_class_marker_negative](../../../tests/php/types/enum/level_02/enum_005_from_value_class_marker_negative.test-info.json) | Reject generation | enum_from_value should require an explicit Enum::class marker so the compiler knows the target enum. Expected diagnostic: enum_from_value() first argument must be an enum class marker |
| [fixed_array_001_basic](../../../tests/php/types/fixed_array/level_01/fixed_array_001_basic.test-info.json) | Generate / compile / run | Basic fixed_array local, return, prefix-generic parameter, count, indexed read, and indexed write. |
| [fixed_array_004_zero_size](../../../tests/php/types/fixed_array/level_01/fixed_array_004_zero_size.test-info.json) | Generate / compile / run | fixed_array<T, 0> is allowed and is empty/countable. |
| [fixed_array_002_size_mismatch_negative](../../../tests/php/types/fixed_array/level_02/fixed_array_002_size_mismatch_negative.test-info.json) | Reject generation | fixed_array literals must exactly match the declared compile-time size. Expected diagnostic: fixed_array literal size mismatch: declared 3 element(s), got 2. |
| [fixed_array_003_keyed_literal_negative](../../../tests/php/types/fixed_array/level_02/fixed_array_003_keyed_literal_negative.test-info.json) | Reject generation | fixed_array literals are positional only in the first slice. Expected diagnostic: fixed_array literals cannot contain explicit keys |
| [fixed_array_005_property_storage](../../../tests/php/types/fixed_array/level_02/fixed_array_005_property_storage.test-info.json) | Generate / compile / run | fixed_array property storage supports literal initialization, indexed writes, indexed reads, and count. |
| [fixed_array_006_nested_vector](../../../tests/php/types/fixed_array/level_02/fixed_array_006_nested_vector.test-info.json) | Generate / compile / run | fixed_array can store nested vector<T> elements initialized from nested literals. |
| [fixed_array_007_nonliteral_size_negative](../../../tests/php/types/fixed_array/level_02/fixed_array_007_nonliteral_size_negative.test-info.json) | Reject generation | fixed_array size parameter must be a non-negative integer literal. Expected diagnostic: fixed_array<T, N> requires a non-negative integer literal size. |
| [float_001_init_basic](../../../tests/php/types/float/level_01/float_001_init_basic.test-info.json) | Generate / compile / run | Basic float initialization. |
| [float_002_assign_basic](../../../tests/php/types/float/level_01/float_002_assign_basic.test-info.json) | Generate / compile / run | Basic float assignment and reassignment. |
| [float_003_arithmetic_basic](../../../tests/php/types/float/level_02/float_003_arithmetic_basic.test-info.json) | Generate / compile / run | Basic float arithmetic. |
| [float_004_comparison_basic](../../../tests/php/types/float/level_02/float_004_comparison_basic.test-info.json) | Generate / compile / run | Basic float comparisons. |
| [float_005_concat_cast_path](../../../tests/php/types/float/level_02/float_005_concat_cast_path.test-info.json) | Generate / compile / run | Float to string conversion through concat. |
| [hash_001_empty_hash_init](../../../tests/php/types/hash/level_01/hash_001_empty_hash_init.test-info.json) | Generate / compile / run | Basic empty hash initialization in explicit hash<int> context. |
| [hash_002_hash_literal_and_access](../../../tests/php/types/hash/level_01/hash_002_hash_literal_and_access.test-info.json) | Generate / compile / run | Typed hash literals should support default string-key writes, count, and keyed reads. |
| [hash_003_hash_literal_mixed_keyed_append](../../../tests/php/types/hash/level_01/hash_003_hash_literal_mixed_keyed_append.test-info.json) | Generate / compile / run | Typed hashes with explicit int keys should allow literal initialization plus append-style entries. |
| [hash_004_hash_unset_keyed](../../../tests/php/types/hash/level_01/hash_004_hash_unset_keyed.test-info.json) | Generate / compile / run | Typed hash<int> keyed unset() should remove present keys and stay a no-op on missing keys. |
| [hash_005_fixed_width_key_domains](../../../tests/php/types/hash/level_01/hash_005_fixed_width_key_domains.test-info.json) | Generate / compile / run | Typed hashes with fixed-width integer key families should support keyed writes, append, and assignment-expression value semantics. |
| [hash_006_default_and_int_key_compatibility](../../../tests/php/types/hash/level_01/hash_006_default_and_int_key_compatibility.test-info.json) | Generate / compile / run | Covers hash<int> default string-key compatibility and hash<int,int> explicit integer-key append behavior. |
| [hash_007_unsupported_key_negative](../../../tests/php/types/hash/level_02/hash_007_unsupported_key_negative.test-info.json) | Reject generation | Unsupported explicit hash key families should fail in the generator with a clear diagnostic. Expected diagnostic: Unsupported hash<T,T_KEY> key type `bool` |
| [int_000_top_level_return_zero](../../../tests/php/types/int/level_01/int_000_top_level_return_zero.test-info.json) | Generate / compile / run | Basic top-level integer return. |
| [int_001_init_basic](../../../tests/php/types/int/level_01/int_001_init_basic.test-info.json) | Generate / compile / run | Basic int initialization. |
| [int_002_assign_basic](../../../tests/php/types/int/level_01/int_002_assign_basic.test-info.json) | Generate / compile / run | Basic int assignment and reassignment. |
| [int_003_arithmetic_basic](../../../tests/php/types/int/level_02/int_003_arithmetic_basic.test-info.json) | Generate / compile / run | Basic int arithmetic inside the int type bucket. |
| [int_004_comparison_basic](../../../tests/php/types/int/level_02/int_004_comparison_basic.test-info.json) | Generate / compile / run | Basic int comparisons. |
| [int_005_concat_cast_path](../../../tests/php/types/int/level_02/int_005_concat_cast_path.test-info.json) | Generate / compile / run | Int to string conversion through concat. |
| [strict_int_006_fixed_width_local_returns](../../../tests/php/types/int/strict_level_01/strict_int_006_fixed_width_local_returns.test-info.json) | Generate / compile / run | Strict fixed-width signed integer aliases should compile as local declarations, returns, and a simple int64 subtraction. |
| [strict_int_007_fixed_width_unsigned_local_returns](../../../tests/php/types/int/strict_level_01/strict_int_007_fixed_width_unsigned_local_returns.test-info.json) | Generate / compile / run | Strict fixed-width signed and unsigned integer aliases should compile as local declarations and returns. |
| [strict_int_008_div_mod_basic](../../../tests/php/types/int/strict_level_01/strict_int_008_div_mod_basic.test-info.json) | Generate / compile / run | Strict int64 division and modulo should compile and run through the vendor language surface. |
| [nullable_001_nullable_int_init](../../../tests/php/types/nullable/level_01/nullable_001_nullable_int_init.test-info.json) | Generate / compile / run | Basic nullable int initialization to null. |
| [nullable_002_nullable_int_assign](../../../tests/php/types/nullable/level_01/nullable_002_nullable_int_assign.test-info.json) | Generate / compile / run | Basic nullable int assignment from null to value. |
| [nullable_003_null_coalesce_basic](../../../tests/php/types/nullable/level_02/nullable_003_null_coalesce_basic.test-info.json) | Generate / compile / run | Basic null-coalesce on nullable int. |
| [nullable_004_mixed_null_coalesce_basic](../../../tests/php/types/nullable/level_02/nullable_004_mixed_null_coalesce_basic.test-info.json) | Generate / compile / run | Null coalesce supports mixed_t left operands and preserves PHP null fallback behavior. |
| [nullable_004_nullable_return_basic](../../../tests/php/types/nullable/level_02/nullable_004_nullable_return_basic.test-info.json) | Generate / compile / run | Basic nullable return behavior. |
| [nullable_005_mixed_null_coalesce_chain](../../../tests/php/types/nullable/level_02/nullable_005_mixed_null_coalesce_chain.test-info.json) | Generate / compile / run | Chained null coalesce supports exact mixed_t ?? mixed_t resolution without template ambiguity. |
| [nullable_005_untyped_null_negative](../../../tests/php/types/nullable/level_02/nullable_005_untyped_null_negative.test-info.json) | Generate / compile / run | Untyped null currently lowers through the dynamic value_t path in the active implementation. |
| [nullable_006_ternary_nullable_branch](../../../tests/php/types/nullable/level_02/nullable_006_ternary_nullable_branch.test-info.json) | Generate / compile / run | Nullable ternary branch normalization through runtime helper. |
| [nullable_007_elvis_nullable_basic](../../../tests/php/types/nullable/level_02/nullable_007_elvis_nullable_basic.test-info.json) | Generate / compile / run | Nullable elvis through runtime ternary helper. |
| [nullable_007_mixed_ternary_elvis_basic](../../../tests/php/types/nullable/level_02/nullable_007_mixed_ternary_elvis_basic.test-info.json) | Generate / compile / run | Mixed shorthand ternary / elvis branch normalization for the allowed mixed condition kinds (bool/int/float). |
| [nullable_007_ternary_string_null_union_syntax](../../../tests/php/types/nullable/level_02/nullable_007_ternary_string_null_union_syntax.test-info.json) | Generate / compile / run | string&#124;null union syntax should normalize to nullable<string_t> and behave the same as ?string in ternary lowering. |
| [nullable_008_bool_identity_nullable_return](../../../tests/php/types/nullable/level_02/nullable_008_bool_identity_nullable_return.test-info.json) | Generate / compile / run | Strict identity on mixed_t bool payloads must match PHP and remain compatible with mixed_t -> nullable<T> typed returns. |
| [nullable_008_ternary_literal_string_null](../../../tests/php/types/nullable/level_02/nullable_008_ternary_literal_string_null.test-info.json) | Generate / compile / run | Literal string-versus-null ternary branches should normalize to nullable<string_t> through the runtime ternary helper. |
| [nullable_009_mixed_strict_scalar_identity](../../../tests/php/types/nullable/level_02/nullable_009_mixed_strict_scalar_identity.test-info.json) | Generate / compile / run | Strict identity must not reuse mixed_t loose numeric equality when comparing dynamic scalar payloads. |
| [nullable_010_string_null_ternary_regression_repro](../../../tests/php/types/nullable/level_02/nullable_010_string_null_ternary_regression_repro.test-info.json) | Generate / compile / run | Regression repro for string&#124;null ternary flow returning through ?string and consumed via ?? in strict code. |
| [string_001_init_basic](../../../tests/php/types/string/level_01/string_001_init_basic.test-info.json) | Generate / compile / run | Basic string initialization. |
| [string_002_assign_basic](../../../tests/php/types/string/level_01/string_002_assign_basic.test-info.json) | Generate / compile / run | Basic string assignment and reassignment. |
| [string_003_concat_basic](../../../tests/php/types/string/level_02/string_003_concat_basic.test-info.json) | Generate / compile / run | Basic string concatenation. |
| [string_004_comparison_basic](../../../tests/php/types/string/level_02/string_004_comparison_basic.test-info.json) | Generate / compile / run | Basic string ordering now compiles and runs on the active implementation. |
| [vector_001_empty_vector_init](../../../tests/php/types/vector/level_01/vector_001_empty_vector_init.test-info.json) | Generate / compile / run | Basic empty vector initialization in explicit vector<int> context. |
| [vector_002_vector_literal_init](../../../tests/php/types/vector/level_01/vector_002_vector_literal_init.test-info.json) | Generate / compile / run | Basic vector literal initialization in explicit vector<int> context. |
| [vector_008_capacity_helpers](../../../tests/php/types/vector/level_01/vector_008_capacity_helpers.test-info.json) | Generate / compile / run | Source helpers expose reserve, capacity, resize-with-fill, filled construction, clear-preserves-capacity, and compact with a target capacity for typed and fixed-width vectors. |
| [vector_003_vector_assign_basic](../../../tests/php/types/vector/level_02/vector_003_vector_assign_basic.test-info.json) | Generate / compile / run | Basic vector assignment. |
| [vector_004_vector_append_basic](../../../tests/php/types/vector/level_02/vector_004_vector_append_basic.test-info.json) | Generate / compile / run | Vector append generation lowers to push_back and should compile and run successfully. |
| [vector_005_vector_index_read](../../../tests/php/types/vector/level_02/vector_005_vector_index_read.test-info.json) | Generate / compile / run | Basic vector index read. |
| [vector_006_vector_index_write](../../../tests/php/types/vector/level_02/vector_006_vector_index_write.test-info.json) | Generate / compile / run | Basic vector index write. |
| [vector_007_untyped_empty_array_negative](../../../tests/php/types/vector/level_02/vector_007_untyped_empty_array_negative.test-info.json) | Generate / compile / run | Untyped empty arrays are supported on this path and lower to mixed_t-backed tables; count() must return 0. |
| [vector_007_vector_index_unset](../../../tests/php/types/vector/level_02/vector_007_vector_index_unset.test-info.json) | Generate / compile / run | Indexed unset() on vector<int> should remove the element, compact later indexes, and ignore invalid indexes. |
| [vector_008_untyped_array_literal_negative](../../../tests/php/types/vector/level_02/vector_008_untyped_array_literal_negative.test-info.json) | Generate / compile / run | Untyped positional array literals currently lower through the dynamic value_t table path and run successfully. |
| [vector_009_vector_unset_regression_repro](../../../tests/php/types/vector/level_02/vector_009_vector_unset_regression_repro.test-info.json) | Generate / compile / run | Regression repro for indexed unset() on vector<string> from downstream strict smoke coverage. |
| [vector_010_helper_reassign_and_const_foreach_regression](../../../tests/php/types/vector/level_02/vector_010_helper_reassign_and_const_foreach_regression.test-info.json) | Generate / compile / run | Regression for typed vector literal reassignment plus value-only foreach over a const vector parameter in a helper. |

## use

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [use_001_use_class_basic](../../../tests/php/use/level_01/use_001_use_class_basic.test-info.json) | Generate / compile / run | Basic class import with use. |
| [use_002_use_namespace_alias_basic](../../../tests/php/use/level_01/use_002_use_namespace_alias_basic.test-info.json) | Reject compilation | Namespace alias import remains a known gap. STAN treats unresolved receivers as advisory; native compilation must reject the namespace used as a type alias at source line 14. Compiler-specific wording is not required. Expected diagnostic: use_002_use_namespace_alias_basic.phs:14:; Math |
| [use_003_use_function_basic](../../../tests/php/use/level_02/use_003_use_function_basic.test-info.json) | Generate / compile / run | Function import with use. |
| [use_004_use_const_basic](../../../tests/php/use/level_02/use_004_use_const_basic.test-info.json) | Generate / compile / run | Constant import with use. |
| [use_005_use_multiple_basic](../../../tests/php/use/level_02/use_005_use_multiple_basic.test-info.json) | Generate / compile / run | Multiple use imports in one file. |
| [strict_use_001_namespace_function_return](../../../tests/php/use/strict_level_01/strict_use_001_namespace_function_return.test-info.json) | Compile only | Minimal strict authority for a namespaced function declaration returning an int literal without use imports, classes, qualified lookup, echo, or string output. |
| [strict_use_002_namespace_use_function_return](../../../tests/php/use/strict_level_01/strict_use_002_namespace_use_function_return.test-info.json) | Compile only | Minimal strict authority for namespace use-function import and imported function return, without class aliases, constants, namespace block mixed statements, echo, or string output. |

## variables

| Expectation file | Expected result | Scope / expected diagnostics |
| --- | --- | --- |
| [variables_001_definition_basic](../../../tests/php/variables/level_01/variables_001_definition_basic.test-info.json) | Generate / compile / run | Basic variable definition. |
| [variables_002_reassignment_basic](../../../tests/php/variables/level_01/variables_002_reassignment_basic.test-info.json) | Generate / compile / run | Basic variable reassignment. |
| [variables_003_inner_scope_shadow_basic](../../../tests/php/variables/level_02/variables_003_inner_scope_shadow_basic.test-info.json) | Generate / compile / run | Simple same-variable reassignment in nested block. |
| [variables_004_assign_from_variable](../../../tests/php/variables/level_02/variables_004_assign_from_variable.test-info.json) | Generate / compile / run | Assignment from a variable. |
| [variables_005_assign_from_expression](../../../tests/php/variables/level_02/variables_005_assign_from_expression.test-info.json) | Generate / compile / run | Assignment from an expression. |

