#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/source_units.hpp"
namespace scpp {
class SourceUnitTable;
class SourceUnitFrontendWorkerPayloadInput {
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
	string_t source_text = string_t("");
	shared_p<SourceUnitTable> source_units;
	SourceUnitFrontendWorkerPayloadInput();
};
}
