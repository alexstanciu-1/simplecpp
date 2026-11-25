#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class ObjectOutputWorkerResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> cache_object_id = static_cast<int_t<> >(0);
	int_t<> partition_execution_id = static_cast<int_t<> >(0);
	int_t<> owner_symbol_id = static_cast<int_t<> >(0);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<std::uint64_t> input_partition_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> cache_key_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> logical_object_key_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<> cache_status_id = static_cast<int_t<> >(0);
	int_t<> dirty_status_id = static_cast<int_t<> >(0);
	int_t<> object_action_id = static_cast<int_t<> >(0);
	int_t<> status_id = static_cast<int_t<> >(0);
	int_t<> owner_key_id = static_cast<int_t<> >(0);
	int_t<> source_unit_key_id = static_cast<int_t<> >(0);
	int_t<> partition_key_id = static_cast<int_t<> >(0);
	int_t<> backend_row_key_id = static_cast<int_t<> >(0);
	int_t<> output_object_key_id = static_cast<int_t<> >(0);
};
}
