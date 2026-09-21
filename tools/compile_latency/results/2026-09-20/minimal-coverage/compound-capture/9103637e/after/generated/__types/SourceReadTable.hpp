#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/SourceReadDescriptorRow.hpp"
#include "__types/SourceReadResultRow.hpp"
namespace scpp {
class SourceReadTable {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> descriptor_count = static_cast<int_t<> >(0);
	int_t<> result_count = static_cast<int_t<> >(0);
	int_t<std::uint32_t> source_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> failure_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<SourceReadDescriptorRow> descriptors = vector_t<SourceReadDescriptorRow>{};
	vector_t<SourceReadResultRow> results = vector_t<SourceReadResultRow>{};
	vector_t<string_t> source_unit_keys = vector_t<string_t>{};
	vector_t<string_t> relative_paths = vector_t<string_t>{};
	vector_t<string_t> paths = vector_t<string_t>{};
	vector_t<string_t> source_texts = vector_t<string_t>{};
};
}
