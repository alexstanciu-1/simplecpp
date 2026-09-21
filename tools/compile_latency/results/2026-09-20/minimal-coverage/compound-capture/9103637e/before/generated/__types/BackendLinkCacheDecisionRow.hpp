#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendLinkCacheDecisionRow {
	BackendLinkCacheDecisionRow* operator->() { return this; }
	const BackendLinkCacheDecisionRow* operator->() const { return this; }
	int_t<std::uint32_t> cache_link_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> link_execution_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> program_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> object_set_shape_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_set_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_set_edge_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_partition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ready_partition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> object_set_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> command_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> link_cache_key_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> cache_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> relink_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> link_action_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> command_surface_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
