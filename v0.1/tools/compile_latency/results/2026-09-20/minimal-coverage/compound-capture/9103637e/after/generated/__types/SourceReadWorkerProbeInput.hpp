#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SourceReadWorkerProbeInput {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> descriptor_id = static_cast<int_t<> >(0);
	int_t<> source_id = static_cast<int_t<> >(0);
	int_t<> manifest_order_id = static_cast<int_t<> >(0);
	int_t<> language_id = static_cast<int_t<> >(0);
	int_t<> status_id = static_cast<int_t<> >(0);
	int_t<> source_unit_key_id = static_cast<int_t<> >(0);
	int_t<> relative_path_id = static_cast<int_t<> >(0);
	int_t<> path_id = static_cast<int_t<> >(0);
	int_t<> expected_source_unit_key_id = static_cast<int_t<> >(0);
	string_t path = string_t("");
};
}
