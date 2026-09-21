#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ProjectDependencyGraphEdgeRow {
	ProjectDependencyGraphEdgeRow* operator->() { return this; }
	const ProjectDependencyGraphEdgeRow* operator->() const { return this; }
	int_t<std::uint32_t> edge_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> from_source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> from_symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> to_source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> to_symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> reference_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> callable_contract_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> edge_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
