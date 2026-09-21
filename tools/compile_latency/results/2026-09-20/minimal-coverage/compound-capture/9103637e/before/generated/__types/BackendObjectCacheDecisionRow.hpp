#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendObjectCacheDecisionRow {
	BackendObjectCacheDecisionRow* operator->() { return this; }
	const BackendObjectCacheDecisionRow* operator->() const { return this; }
	int_t<std::uint32_t> cache_object_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_execution_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> owner_symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> input_partition_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> cache_key_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> logical_object_key_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> cache_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> dirty_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> object_action_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> owner_key_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> source_unit_key_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> partition_key_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> backend_row_key_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> output_object_key_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
