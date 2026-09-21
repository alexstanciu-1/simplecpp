#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class ReferenceContractWorkerInput {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> language_id = static_cast<int_t<> >(0);
	int_t<> status_id = static_cast<int_t<> >(0);
	int_t<> dirty_signal_id = static_cast<int_t<> >(0);
	int_t<> reuse_signal_id = static_cast<int_t<> >(0);
	int_t<> partition_id = static_cast<int_t<> >(0);
	int_t<> source_unit_key_id = static_cast<int_t<> >(0);
	int_t<> relative_path_id = static_cast<int_t<> >(0);
	int_t<> path_id = static_cast<int_t<> >(0);
	int_t<> source_length = static_cast<int_t<> >(0);
	int_t<> line_count = static_cast<int_t<> >(0);
	int_t<> entry_symbol_id = static_cast<int_t<> >(0);
	string_t source_text = string_t("");
	vector_t<int_t<>> symbol_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_source_unit_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_source_row_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_kind_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_scope_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_return_type_ref_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_parameter_counts = vector_t<int_t<>>{};
	vector_t<int_t<>> symbol_first_parameter_type_ref_ids = vector_t<int_t<>>{};
	vector_t<string_t> symbol_names = vector_t<string_t>{};
	vector_t<string_t> symbol_qualified_names = vector_t<string_t>{};
	vector_t<string_t> symbol_signature_shapes = vector_t<string_t>{};
	vector_t<string_t> symbol_body_shapes = vector_t<string_t>{};
	vector_t<string_t> symbol_source_unit_keys = vector_t<string_t>{};
	vector_t<int_t<>> function_import_source_unit_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> function_import_source_row_ids = vector_t<int_t<>>{};
	vector_t<string_t> function_import_namespaces = vector_t<string_t>{};
	vector_t<string_t> function_import_alias_names = vector_t<string_t>{};
	vector_t<string_t> function_import_target_qualified_names = vector_t<string_t>{};
};
}
