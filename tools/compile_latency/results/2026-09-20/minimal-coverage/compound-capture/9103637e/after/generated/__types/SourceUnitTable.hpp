#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/SourceUnitTableRow.hpp"
namespace scpp {
class SourceUnitTable {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> source_unit_count = static_cast<int_t<> >(0);
	string_t entry_source_unit_key = string_t("");
	int_t<std::uint16_t> scope_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	vector_t<SourceUnitTableRow> rows = vector_t<SourceUnitTableRow>{};
	vector_t<string_t> source_unit_keys = vector_t<string_t>{};
	vector_t<string_t> relative_paths = vector_t<string_t>{};
	vector_t<string_t> paths = vector_t<string_t>{};
	vector_t<string_t> source_texts = vector_t<string_t>{};
};
}
