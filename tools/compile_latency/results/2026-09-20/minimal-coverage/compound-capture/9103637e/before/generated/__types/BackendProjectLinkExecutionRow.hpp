#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendProjectLinkExecutionRow {
	BackendProjectLinkExecutionRow* operator->() { return this; }
	const BackendProjectLinkExecutionRow* operator->() const { return this; }
	int_t<std::uint16_t> link_execution_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> link_scope_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> program_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> object_set_shape_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_set_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_set_edge_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_partition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ready_partition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> link_action_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> execution_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> blocked_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> command_surface_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
