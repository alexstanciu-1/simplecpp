# Runtime Config – Human Review Version
Non-authoritative mirror of `config.json`

## schema_version

### Description
Auto-generated section for `schema_version`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|

## artifact

### Description
Auto-generated section for `artifact`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|

## runtime

### Description
Auto-generated section for `runtime`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `namespace` | `scpp` | `// example` | `// generated` | Auto-mapped from config |
| `umbrella_header` | `scpp/runtime.hpp` | `// example` | `// generated` | Auto-mapped from config |
| `create_default_owner` | `shared` | `// example` | `// generated` | Auto-mapped from config |
| `default_cast_policy` | `forbidden` | `// example` | `// generated` | Auto-mapped from config |
| `default_overload_policy` | `forbidden` | `// example` | `// generated` | Auto-mapped from config |
| `emit_deleted_for_forbidden_operations` | `True` | `// example` | `// generated` | Auto-mapped from config |
| `comparison_result_type` | `bool_t` | `// example` | `// generated` | Auto-mapped from config |
| `condition_lowering` | `{'semantic_type': 'bool_t', 'cpp_bridge': 'native_value'}` | `// example` | `// generated` | Auto-mapped from config |
| `default_assignment_policy` | `forbidden` | `// example` | `// generated` | Auto-mapped from config |

## types

### Description
Auto-generated section for `types`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `null_t` | `{'kind': 'scalar_tag', 'header': 'scpp/null_t.hpp', 'template': False, 'cpp_underlying': None, 'family': 'sentinel', 'semantic_role': 'generic_null', 'stable_core_api': ['default_ctor']}` | `// example` | `// generated` | Auto-mapped from config |
| `nullopt_t` | `{'kind': 'scalar_tag', 'header': 'scpp/nullopt_t.hpp', 'template': False, 'cpp_underlying': None, 'family': 'sentinel', 'semantic_role': 'optional_empty', 'stable_core_api': ['default_ctor']}` | `// example` | `// generated` | Auto-mapped from config |
| `nullptr_t` | `{'kind': 'scalar_tag', 'header': 'scpp/nullptr_t.hpp', 'template': False, 'cpp_underlying': None, 'family': 'sentinel', 'semantic_role': 'pointer_empty', 'stable_core_api': ['default_ctor']}` | `// example` | `// generated` | Auto-mapped from config |
| `bool_t` | `{'kind': 'scalar_wrapper', 'header': 'scpp/bool_t.hpp', 'template': False, 'cpp_underlying': 'bool', 'stable_core_api': ['default_ctor', 'explicit_native_ctor', 'native_value'], 'entry_ctors': [{'from_cpp': 'bool', 'explicit': True}]}` | `// example` | `// generated` | Auto-mapped from config |
| `int_t` | `{'kind': 'scalar_wrapper', 'header': 'scpp/int_t.hpp', 'template': False, 'cpp_underlying': 'std::int64_t', 'stable_core_api': ['default_ctor', 'explicit_native_ctor', 'native_value'], 'entry_ctors': [{'from_cpp': 'std::int64_t', 'explicit': True}]}` | `// example` | `// generated` | Auto-mapped from config |
| `float_t` | `{'kind': 'scalar_wrapper', 'header': 'scpp/float_t.hpp', 'template': False, 'cpp_underlying': 'double', 'stable_core_api': ['default_ctor', 'explicit_native_ctor', 'native_value'], 'entry_ctors': [{'from_cpp': 'double', 'explicit': True}]}` | `// example` | `// generated` | Auto-mapped from config |
| `string_t` | `{'kind': 'scalar_wrapper', 'header': 'scpp/string_t.hpp', 'template': False, 'cpp_underlying': 'std::string', 'stable_core_api': ['default_ctor', 'explicit_native_ctor', 'explicit_string_view_ctor', 'explicit_cstr_ctor', 'native_value', 'size', 'empty', 'append'], 'entry_ctors': [{'from_cpp': 'std::string', 'explicit': True}, {'from_cpp': 'std::string_view', 'explicit': True}, {'from_cpp': 'const char*', 'explicit': True}]}` | `// example` | `// generated` | Auto-mapped from config |
| `vector_t` | `{'kind': 'template_wrapper', 'header': 'scpp/vector_t.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'std::vector<T>', 'stable_core_api': ['default_ctor', 'size', 'empty', 'clear', 'at', 'index', 'append']}` | `// example` | `// generated` | Auto-mapped from config |
| `value_p` | `{'kind': 'template_wrapper', 'header': 'scpp/value_p.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'T', 'family': 'inline_storage', 'allocates': False, 'copyable': True, 'movable': True, 'stable_core_api': ['default_ctor', 'copy_ctor', 'move_ctor', 'copy_assign', 'move_assign', 'explicit_value_ctor', 'in_place_ctor', 'has_value', 'get', 'deref', 'arrow']}` | `// example` | `// generated` | Auto-mapped from config |
| `ref_p` | `{'kind': 'template_wrapper', 'header': 'scpp/ref_p.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'T*', 'family': 'reference', 'allocates': False, 'copyable': True, 'movable': True, 'nullable': False, 'stable_core_api': ['value_ctor', 'copy_ctor', 'move_ctor', 'copy_assign', 'move_assign', 'get', 'deref', 'arrow']}` | `// example` | `// generated` | Auto-mapped from config |
| `shared_p` | `{'kind': 'template_wrapper', 'header': 'scpp/shared_p.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'std::shared_ptr<T>', 'stable_core_api': ['default_ctor', 'null_ctor', 'explicit_native_ctor', 'has_value', 'get', 'deref', 'arrow', 'native_value']}` | `// example` | `// generated` | Auto-mapped from config |
| `unique_p` | `{'kind': 'template_wrapper', 'header': 'scpp/unique_p.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'std::unique_ptr<T>', 'stable_core_api': ['default_ctor', 'null_ctor', 'explicit_native_ctor', 'move_ctor', 'move_assign', 'has_value', 'get', 'deref', 'arrow', 'native_value'], 'copyable': False, 'movable': True}` | `// example` | `// generated` | Auto-mapped from config |
| `weak_p` | `{'kind': 'template_wrapper', 'header': 'scpp/weak_p.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'std::weak_ptr<T>', 'stable_core_api': ['default_ctor', 'null_ctor', 'explicit_native_ctor', 'explicit_from_shared', 'expired', 'lock', 'native_value']}` | `// example` | `// generated` | Auto-mapped from config |
| `nullable` | `{'kind': 'template_wrapper', 'header': 'scpp/nullable.hpp', 'template': True, 'template_params': ['T'], 'cpp_underlying': 'std::optional<T>', 'stable_core_api': ['default_ctor', 'null_ctor', 'value_ctor', 'has_value', 'reset', 'value', 'value_or', 'native_value']}` | `// example` | `// generated` | Auto-mapped from config |

## memory_helpers

### Description
Auto-generated section for `memory_helpers`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `create` | `{'enabled': True, 'returns': 'shared_p<T>', 'kind': 'factory', 'policy_role': 'default_managed_creation'}` | `// example` | `// generated` | Auto-mapped from config |
| `shared` | `{'enabled': True, 'returns': 'shared_p<T>', 'kind': 'factory'}` | `// example` | `// generated` | Auto-mapped from config |
| `unique` | `{'enabled': True, 'returns': 'unique_p<T>', 'kind': 'factory'}` | `// example` | `// generated` | Auto-mapped from config |
| `weak` | `{'enabled': True, 'returns': 'weak_p<T>', 'kind': 'derived_reference', 'from': 'shared_p<T>', 'allocates': False}` | `// example` | `// generated` | Auto-mapped from config |
| `value` | `{'enabled': True, 'returns': 'value_p<T>', 'kind': 'factory', 'allocates': False, 'policy_role': 'explicit_inline_value_creation'}` | `// example` | `// generated` | Auto-mapped from config |
| `ref` | `{'enabled': True, 'kind': 'reference_adapter', 'allocates': False, 'overloads': [{'from': 'T&', 'returns': 'ref_p<T>', 'when': 'T is not handle_like and not ref_like'}, {'from': 'value_p<T>&', 'returns': 'ref_p<T>'}, {'from': 'ref_p<T>', 'returns': 'ref_p<T>', 'policy_role': 'identity'}, {'from': 'T&', 'returns': 'T&', 'when': 'T is handle_like', 'policy_role': 'handle_passthrough'}]}` | `// example` | `// generated` | Auto-mapped from config |

## casts

### Description
Auto-generated section for `casts`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `0` | `{'from': 'null_t', 'to': 'shared_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `1` | `{'from': 'null_t', 'to': 'unique_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `2` | `{'from': 'null_t', 'to': 'weak_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `3` | `{'from': 'null_t', 'to': 'nullable<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `4` | `{'from': 'T', 'to': 'nullable<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `5` | `{'from': 'shared_p<T>', 'to': 'weak_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `6` | `{'from': 'int_t', 'to': 'float_t', 'kind': 'implicit', 'form': 'constructor', 'template_rule': False}` | `// example` | `// generated` | Auto-mapped from config |
| `7` | `{'from': 'bool_t', 'to': 'int_t', 'kind': 'explicit', 'form': 'constructor', 'template_rule': False}` | `// example` | `// generated` | Auto-mapped from config |
| `8` | `{'from': 'bool_t', 'to': 'float_t', 'kind': 'explicit', 'form': 'constructor', 'template_rule': False}` | `// example` | `// generated` | Auto-mapped from config |
| `9` | `{'from': 'int_t', 'to': 'bool_t', 'kind': 'explicit', 'form': 'named_cast', 'cast_name': 'cast', 'template_rule': False}` | `// example` | `// generated` | Auto-mapped from config |
| `10` | `{'from': 'float_t', 'to': 'bool_t', 'kind': 'explicit', 'form': 'named_cast', 'cast_name': 'cast', 'template_rule': False}` | `// example` | `// generated` | Auto-mapped from config |
| `11` | `{'from': 'float_t', 'to': 'int_t', 'kind': 'explicit', 'form': 'named_cast', 'cast_name': 'cast', 'template_rule': False}` | `// example` | `// generated` | Auto-mapped from config |
| `12` | `{'from': 'string_t', 'to': 'string_t', 'kind': 'explicit', 'form': 'named_cast', 'cast_name': 'cast', 'template_rule': False, 'policy_role': 'identity'}` | `// example` | `// generated` | Auto-mapped from config |
| `13` | `{'from': 'nullable<T>', 'to': 'T', 'kind': 'explicit', 'form': 'named_cast', 'cast_name': 'cast', 'template_rule': True, 'policy_role': 'unwrap_present_value'}` | `// example` | `// generated` | Auto-mapped from config |
| `14` | `{'from': 'nullopt_t', 'to': 'nullable<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `15` | `{'from': 'nullptr_t', 'to': 'shared_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `16` | `{'from': 'nullptr_t', 'to': 'unique_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `17` | `{'from': 'nullptr_t', 'to': 'weak_p<T>', 'kind': 'implicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `18` | `{'from': 'T', 'to': 'value_p<T>', 'kind': 'explicit', 'form': 'constructor', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `19` | `{'from': 'T&', 'to': 'ref_p<T>', 'kind': 'explicit', 'form': 'helper', 'helper_name': 'ref', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `20` | `{'from': 'value_p<T>&', 'to': 'ref_p<T>', 'kind': 'explicit', 'form': 'helper', 'helper_name': 'ref', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |

## coercions

### Description
Auto-generated section for `coercions`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `condition` | `{'semantic_type': 'bool_t', 'bridge': 'native_value', 'allowed_inputs': ['bool_t']}` | `// example` | `// generated` | Auto-mapped from config |
| `text` | `{'result_type': 'string_t', 'dispatch_helper': 'to_string', 'null_rendering': {'null_t': '', 'nullopt_t': '', 'nullptr_t': ''}, 'rules': [{'from': 'string_t', 'kind': 'identity'}, {'from': 'bool_t', 'kind': 'helper', 'helper_name': 'to_string'}, {'from': 'int_t', 'kind': 'helper', 'helper_name': 'to_string'}, {'from': 'float_t', 'kind': 'helper', 'helper_name': 'to_string'}, {'from': 'null_t', 'kind': 'literal', 'value': ''}, {'from': 'nullopt_t', 'kind': 'literal', 'value': ''}, {'from': 'nullptr_t', 'kind': 'literal', 'value': ''}, {'from': 'nullable<T>', 'kind': 'helper', 'helper_name': 'to_string', 'template_rule': True}, {'from': 'value_p<T>', 'kind': 'helper', 'helper_name': 'to_string', 'template_rule': True}, {'from': 'ref_p<T>', 'kind': 'helper', 'helper_name': 'to_string', 'template_rule': True}, {'from': 'shared_p<T>', 'kind': 'helper', 'helper_name': 'to_string', 'template_rule': True}, {'from': 'unique_p<T>', 'kind': 'helper', 'helper_name': 'to_string', 'template_rule': True}, {'from': 'weak_p<T>', 'kind': 'helper', 'helper_name': 'to_string', 'template_rule': True}]}` | `// example` | `// generated` | Auto-mapped from config |

## subtyping

### Description
Auto-generated section for `subtyping`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `object_subtype_source` | `cpp_pointer_convertibility` | `// example` | `// generated` | Auto-mapped from config |
| `wrapper_rules` | `[{'wrapper': 'shared_p<T>', 'variance': 'covariant', 'from': 'shared_p<U>', 'to': 'shared_p<T>', 'when': 'U* is convertible to T*', 'kind': 'implicit'}, {'wrapper': 'weak_p<T>', 'variance': 'covariant', 'from': 'weak_p<U>', 'to': 'weak_p<T>', 'when': 'U* is convertible to T*', 'kind': 'implicit'}, {'wrapper': 'ref_p<T>', 'variance': 'covariant', 'from': 'ref_p<U>', 'to': 'ref_p<T>', 'when': 'U* is convertible to T*', 'kind': 'implicit'}]` | `// example` | `// generated` | Auto-mapped from config |
| `forbidden_wrapper_rules` | `[{'wrapper': 'unique_p<T>', 'from': 'unique_p<U>', 'to': 'unique_p<T>', 'reason': 'ownership_transfer_and_deleter_policy_not_standardized'}, {'wrapper': 'nullable<T>', 'from': 'nullable<U>', 'to': 'nullable<T>', 'reason': 'generic_inner_subtyping_not_enabled'}, {'wrapper': 'value_p<T>', 'from': 'value_p<U>', 'to': 'value_p<T>', 'reason': 'inline_value_wrapper_is_not_polymorphic'}]` | `// example` | `// generated` | Auto-mapped from config |

## overload_families

### Description
Auto-generated section for `overload_families`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `0` | `{'name': 'bool_logical', 'enabled': True, 'operators': [{'symbol': '!', 'arity': 1, 'operands': ['bool_t'], 'result': 'bool_t'}, {'symbol': '&&', 'arity': 2, 'operands': ['bool_t', 'bool_t'], 'result': 'bool_t'}, {'symbol': '\|\|', 'arity': 2, 'operands': ['bool_t', 'bool_t'], 'result': 'bool_t'}, {'symbol': '==', 'arity': 2, 'operands': ['bool_t', 'bool_t'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['bool_t', 'bool_t'], 'result': 'bool_t'}]}` | `// example` | `// generated` | Auto-mapped from config |
| `1` | `{'name': 'int_arithmetic', 'enabled': True, 'operators': [{'symbol': '+', 'arity': 1, 'operands': ['int_t'], 'result': 'int_t'}, {'symbol': '-', 'arity': 1, 'operands': ['int_t'], 'result': 'int_t'}, {'symbol': '+', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'int_t'}, {'symbol': '-', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'int_t'}, {'symbol': '*', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'int_t'}, {'symbol': '/', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'int_t'}, {'symbol': '==', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'bool_t'}, {'symbol': '<', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'bool_t'}, {'symbol': '<=', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'bool_t'}, {'symbol': '>', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'bool_t'}, {'symbol': '>=', 'arity': 2, 'operands': ['int_t', 'int_t'], 'result': 'bool_t'}]}` | `// example` | `// generated` | Auto-mapped from config |
| `2` | `{'name': 'float_arithmetic', 'enabled': True, 'operators': [{'symbol': '+', 'arity': 1, 'operands': ['float_t'], 'result': 'float_t'}, {'symbol': '-', 'arity': 1, 'operands': ['float_t'], 'result': 'float_t'}, {'symbol': '+', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'float_t'}, {'symbol': '-', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'float_t'}, {'symbol': '*', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'float_t'}, {'symbol': '/', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'float_t'}, {'symbol': '==', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '<', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '<=', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '>', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '>=', 'arity': 2, 'operands': ['float_t', 'float_t'], 'result': 'bool_t'}]}` | `// example` | `// generated` | Auto-mapped from config |
| `3` | `{'name': 'mixed_numeric', 'enabled': True, 'promotion': {'left': 'int_t', 'right': 'float_t', 'promote_to': 'float_t'}, 'symmetric': True, 'operators': [{'symbol': '+', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'float_t'}, {'symbol': '-', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'float_t'}, {'symbol': '*', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'float_t'}, {'symbol': '/', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'float_t'}, {'symbol': '==', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '<', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '<=', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '>', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'bool_t'}, {'symbol': '>=', 'arity': 2, 'operands': ['int_t', 'float_t'], 'result': 'bool_t'}]}` | `// example` | `// generated` | Auto-mapped from config |
| `4` | `{'name': 'string_ops', 'enabled': True, 'operators': [{'symbol': '==', 'arity': 2, 'operands': ['string_t', 'string_t'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['string_t', 'string_t'], 'result': 'bool_t'}]}` | `// example` | `// generated` | Auto-mapped from config |
| `5` | `{'name': 'pointer_null_comparisons', 'enabled': True, 'template_rule': True, 'operators': [{'symbol': '==', 'arity': 2, 'operands': ['shared_p<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '!=', 'arity': 2, 'operands': ['shared_p<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '==', 'arity': 2, 'operands': ['unique_p<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '!=', 'arity': 2, 'operands': ['unique_p<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '==', 'arity': 2, 'operands': ['weak_p<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '!=', 'arity': 2, 'operands': ['weak_p<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '==', 'arity': 2, 'operands': ['shared_p<T>', 'shared_p<T>'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['shared_p<T>', 'shared_p<T>'], 'result': 'bool_t'}], 'sentinel_resolution': 'equivalence_group', 'sentinel_equivalence_group': ['null_t', 'nullopt_t', 'nullptr_t']}` | `// example` | `// generated` | Auto-mapped from config |
| `6` | `{'name': 'nullable_ops', 'enabled': True, 'template_rule': True, 'operators': [{'symbol': '==', 'arity': 2, 'operands': ['nullable<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '!=', 'arity': 2, 'operands': ['nullable<T>', 'null_t'], 'result': 'bool_t', 'symmetric': True}, {'symbol': '==', 'arity': 2, 'operands': ['nullable<T>', 'nullable<T>'], 'result': 'bool_t'}, {'symbol': '!=', 'arity': 2, 'operands': ['nullable<T>', 'nullable<T>'], 'result': 'bool_t'}], 'sentinel_resolution': 'equivalence_group', 'sentinel_equivalence_group': ['null_t', 'nullopt_t', 'nullptr_t']}` | `// example` | `// generated` | Auto-mapped from config |

## forbidden_operation_groups

### Description
Auto-generated section for `forbidden_operation_groups`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `0` | `{'name': 'bool_arithmetic', 'patterns': [['bool_t', '+', 'bool_t'], ['bool_t', '-', 'bool_t'], ['bool_t', '*', 'bool_t'], ['bool_t', '/', 'bool_t']]}` | `// example` | `// generated` | Auto-mapped from config |
| `1` | `{'name': 'pointer_cross_family_comparisons', 'patterns': [['shared_p<T>', '==', 'unique_p<T>'], ['shared_p<T>', '!=', 'unique_p<T>'], ['shared_p<T>', '==', 'weak_p<T>'], ['shared_p<T>', '!=', 'weak_p<T>'], ['unique_p<T>', '==', 'weak_p<T>'], ['unique_p<T>', '!=', 'weak_p<T>']]}` | `// example` | `// generated` | Auto-mapped from config |
| `2` | `{'name': 'vector_arithmetic', 'patterns': [['vector_t<T>', '+', 'vector_t<T>'], ['vector_t<T>', '-', 'vector_t<T>'], ['vector_t<T>', '*', 'vector_t<T>'], ['vector_t<T>', '/', 'vector_t<T>']]}` | `// example` | `// generated` | Auto-mapped from config |
| `3` | `{'name': 'string_arithmetic', 'patterns': [['string_t', '+', 'string_t'], ['string_t', '-', 'string_t'], ['string_t', '*', 'string_t'], ['string_t', '/', 'string_t']]}` | `// example` | `// generated` | Auto-mapped from config |
| `4` | `{'name': 'nullable_arithmetic', 'patterns': [['nullable<T>', '+', 'nullable<T>'], ['nullable<T>', '-', 'nullable<T>'], ['nullable<T>', '*', 'nullable<T>'], ['nullable<T>', '/', 'nullable<T>']]}` | `// example` | `// generated` | Auto-mapped from config |
| `5` | `{'name': 'pointer_arithmetic', 'patterns': [['shared_p<T>', '+', 'shared_p<T>'], ['shared_p<T>', '-', 'shared_p<T>'], ['shared_p<T>', '*', 'shared_p<T>'], ['shared_p<T>', '/', 'shared_p<T>'], ['unique_p<T>', '+', 'unique_p<T>'], ['unique_p<T>', '-', 'unique_p<T>'], ['unique_p<T>', '*', 'unique_p<T>'], ['unique_p<T>', '/', 'unique_p<T>'], ['weak_p<T>', '+', 'weak_p<T>'], ['weak_p<T>', '-', 'weak_p<T>'], ['weak_p<T>', '*', 'weak_p<T>'], ['weak_p<T>', '/', 'weak_p<T>']]}` | `// example` | `// generated` | Auto-mapped from config |
| `6` | `{'name': 'sentinel_arithmetic', 'patterns': [['null_t', '+', 'null_t'], ['null_t', '-', 'null_t'], ['null_t', '*', 'null_t'], ['null_t', '/', 'null_t']]}` | `// example` | `// generated` | Auto-mapped from config |

## composition_constraints

### Description
Auto-generated section for `composition_constraints`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `family_tags` | `{'shared_p<T>': 'ownership', 'unique_p<T>': 'ownership', 'weak_p<T>': 'ownership', 'value_p<T>': 'inline_storage', 'ref_p<T>': 'reference', 'nullable<T>': 'optionality'}` | `// example` | `// generated` | Auto-mapped from config |
| `forbidden_nesting` | `[{'outer': 'value_p<T>', 'inner_family': 'ownership', 'reason': 'inline_value_wrapper_must_not_embed_handle_like_ownership_wrappers'}, {'outer': 'value_p<T>', 'inner': 'ref_p<U>', 'reason': 'inline_value_wrapper_must_not_embed_reference_wrappers'}, {'outer': 'ref_p<T>', 'inner_family': 'ownership', 'reason': 'reference_wrapper_must_not_target_handle_like_ownership_wrappers'}, {'outer': 'ref_p<T>', 'inner': 'ref_p<U>', 'reason': 'reference_wrapper_must_not_wrap_reference_wrapper'}, {'outer': 'nullable<T>', 'inner': 'unique_p<U>', 'reason': 'optional_unique_ownership_is_forbidden_due_to_redundant_and_confusing_null_layering'}]` | `// example` | `// generated` | Auto-mapped from config |
| `allowed_special_nesting` | `[{'outer': 'nullable<T>', 'inner': 'ref_p<U>', 'reason': 'optional_borrowed_reference_is_allowed_for_runtime_level_use_but_is_not_the_primary_php_object_lowering_model'}]` | `// example` | `// generated` | Auto-mapped from config |
| `helper_collapse_rules` | `[{'helper': 'ref', 'input': 'ref_p<T>', 'result': 'ref_p<T>', 'policy': 'identity'}, {'helper': 'ref', 'input': 'value_p<T>&', 'result': 'ref_p<T>', 'policy': 'unwrap_inner_value'}, {'helper': 'ref', 'input_family': 'ownership', 'result': 'same_type', 'policy': 'handle_passthrough'}]` | `// example` | `// generated` | Auto-mapped from config |
| `php_lowering_guidance` | `{'nullable_object_like': 'nullable<shared_p<T>>', 'disfavored_forms': ['nullable<ref_p<T>>', 'nullable<unique_p<T>>']}` | `// example` | `// generated` | Auto-mapped from config |

## assignments

### Description
Auto-generated section for `assignments`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `0` | `{'target': 'bool_t', 'source': 'bool_t', 'kind': 'copy'}` | `// example` | `// generated` | Auto-mapped from config |
| `1` | `{'target': 'int_t', 'source': 'int_t', 'kind': 'copy'}` | `// example` | `// generated` | Auto-mapped from config |
| `2` | `{'target': 'float_t', 'source': 'float_t', 'kind': 'copy'}` | `// example` | `// generated` | Auto-mapped from config |
| `3` | `{'target': 'string_t', 'source': 'string_t', 'kind': 'copy'}` | `// example` | `// generated` | Auto-mapped from config |
| `4` | `{'target': 'vector_t<T>', 'source': 'vector_t<T>', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `5` | `{'target': 'nullable<T>', 'source': 'T', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `6` | `{'target': 'nullable<T>', 'source': 'nullable<T>', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `7` | `{'target': 'nullable<T>', 'source': 'null_t', 'kind': 'reset_to_empty', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `8` | `{'target': 'shared_p<T>', 'source': 'shared_p<T>', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `9` | `{'target': 'shared_p<T>', 'source': 'null_t', 'kind': 'reset_to_null', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `10` | `{'target': 'unique_p<T>', 'source': 'unique_p<T>', 'kind': 'move', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `11` | `{'target': 'unique_p<T>', 'source': 'null_t', 'kind': 'reset_to_null', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `12` | `{'target': 'weak_p<T>', 'source': 'weak_p<T>', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `13` | `{'target': 'weak_p<T>', 'source': 'shared_p<T>', 'kind': 'copy_from_shared', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `14` | `{'target': 'weak_p<T>', 'source': 'null_t', 'kind': 'reset_to_empty', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `15` | `{'target': 'value_p<T>', 'source': 'value_p<T>', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `16` | `{'target': 'value_p<T>', 'source': 'T', 'kind': 'copy_into_inner', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `17` | `{'target': 'ref_p<T>', 'source': 'ref_p<T>', 'kind': 'copy', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `18` | `{'target': 'nullable<T>', 'source': 'nullopt_t', 'kind': 'reset_to_empty', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `19` | `{'target': 'nullable<T>', 'source': 'nullptr_t', 'kind': 'reset_to_empty', 'template_rule': True, 'policy_role': 'sentinel_equivalence'}` | `// example` | `// generated` | Auto-mapped from config |
| `20` | `{'target': 'shared_p<T>', 'source': 'nullptr_t', 'kind': 'reset_to_null', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `21` | `{'target': 'shared_p<T>', 'source': 'nullopt_t', 'kind': 'reset_to_null', 'template_rule': True, 'policy_role': 'sentinel_equivalence'}` | `// example` | `// generated` | Auto-mapped from config |
| `22` | `{'target': 'unique_p<T>', 'source': 'nullptr_t', 'kind': 'reset_to_null', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `23` | `{'target': 'unique_p<T>', 'source': 'nullopt_t', 'kind': 'reset_to_null', 'template_rule': True, 'policy_role': 'sentinel_equivalence'}` | `// example` | `// generated` | Auto-mapped from config |
| `24` | `{'target': 'weak_p<T>', 'source': 'nullptr_t', 'kind': 'reset_to_empty', 'template_rule': True}` | `// example` | `// generated` | Auto-mapped from config |
| `25` | `{'target': 'weak_p<T>', 'source': 'nullopt_t', 'kind': 'reset_to_empty', 'template_rule': True, 'policy_role': 'sentinel_equivalence'}` | `// example` | `// generated` | Auto-mapped from config |
| `26` | `{'target': 'shared_p<T>', 'source': 'shared_p<U>', 'kind': 'copy_upcast', 'template_rule': True, 'when': 'U* is convertible to T*'}` | `// example` | `// generated` | Auto-mapped from config |
| `27` | `{'target': 'weak_p<T>', 'source': 'weak_p<U>', 'kind': 'copy_upcast', 'template_rule': True, 'when': 'U* is convertible to T*'}` | `// example` | `// generated` | Auto-mapped from config |
| `28` | `{'target': 'ref_p<T>', 'source': 'ref_p<U>', 'kind': 'copy_upcast', 'template_rule': True, 'when': 'U* is convertible to T*'}` | `// example` | `// generated` | Auto-mapped from config |

## sentinel_semantics

### Description
Auto-generated section for `sentinel_semantics`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `equivalence_groups` | `[{'members': ['null_t', 'nullopt_t', 'nullptr_t'], 'policy': 'comparison_equivalent'}]` | `// example` | `// generated` | Auto-mapped from config |

## runtime_helpers_contract

### Description
Auto-generated section for `runtime_helpers_contract`.

| Key | Value | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `stable_helpers` | `['create', 'shared', 'unique', 'weak', 'value', 'ref', 'cast', 'to_string']` | `// example` | `// generated` | Auto-mapped from config |
| `namespaces` | `{'core': 'scpp', 'php': 'scpp::php'}` | `// example` | `// generated` | Auto-mapped from config |
| `generator_allowed_helpers` | `['create', 'shared', 'unique', 'weak', 'value', 'ref', 'cast', 'to_string']` | `// example` | `// generated` | Auto-mapped from config |
| `notes` | `{'purpose': 'declares stable runtime helper entry points that frontends/generators may target directly', 'separation_rule': 'helpers listed here are shared knowledge contracts, not generator implementation details'}` | `// example` | `// generated` | Auto-mapped from config |

