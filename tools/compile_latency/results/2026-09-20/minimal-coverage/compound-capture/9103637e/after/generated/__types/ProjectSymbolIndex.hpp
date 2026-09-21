#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectSymbolFunctionImportRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
namespace scpp {
class ProjectSymbolIndex {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> index_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> symbol_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> declaration_rows_indexed = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> body_shape_elapsed_us = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> body_shape_source_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> body_shape_walk_rows = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> body_shape_cached_rows = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> function_import_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lookup_policy_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> resolution_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	vector_t<ProjectSymbolIndexRow> rows = vector_t<ProjectSymbolIndexRow>{};
	vector_t<string_t> names = vector_t<string_t>{};
	vector_t<string_t> qualified_names = vector_t<string_t>{};
	vector_t<string_t> signature_shapes = vector_t<string_t>{};
	vector_t<string_t> body_shapes = vector_t<string_t>{};
	vector_t<string_t> source_unit_keys = vector_t<string_t>{};
	vector_t<ProjectSymbolParameterRow> parameter_rows = vector_t<ProjectSymbolParameterRow>{};
	vector_t<ProjectSymbolFunctionImportRow> function_import_rows = vector_t<ProjectSymbolFunctionImportRow>{};
	hash_t<int_t<std::uint32_t>> name_ids_by_text = [&]() -> hash_t<int_t<std::uint32_t>> {
	hash_t<int_t<std::uint32_t>> __scpp_hash_value{};
	return __scpp_hash_value;
}();
	hash_t<int_t<std::uint32_t>> qualified_name_ids_by_text = [&]() -> hash_t<int_t<std::uint32_t>> {
	hash_t<int_t<std::uint32_t>> __scpp_hash_value{};
	return __scpp_hash_value;
}();
	hash_t<int_t<std::uint32_t>> signature_shape_ids_by_text = [&]() -> hash_t<int_t<std::uint32_t>> {
	hash_t<int_t<std::uint32_t>> __scpp_hash_value{};
	return __scpp_hash_value;
}();
	hash_t<int_t<std::uint32_t>> body_shape_ids_by_text = [&]() -> hash_t<int_t<std::uint32_t>> {
	hash_t<int_t<std::uint32_t>> __scpp_hash_value{};
	return __scpp_hash_value;
}();
	hash_t<int_t<std::uint32_t>> source_unit_key_ids_by_text = [&]() -> hash_t<int_t<std::uint32_t>> {
	hash_t<int_t<std::uint32_t>> __scpp_hash_value{};
	return __scpp_hash_value;
}();
	vector_t<int_t<std::uint32_t>> function_name_symbol_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> function_name_match_counts = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> function_qualified_name_symbol_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> function_qualified_name_match_counts = vector_t<int_t<std::uint32_t>>{};
};
}
