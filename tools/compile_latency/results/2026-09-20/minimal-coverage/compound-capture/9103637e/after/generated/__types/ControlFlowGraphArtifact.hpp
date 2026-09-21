#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ControlFlowBlockRow.hpp"
#include "__types/ControlFlowEdgeRow.hpp"
namespace scpp {
class ControlFlowGraphArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint32_t> owner_source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> owner_symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> block_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> edge_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<ControlFlowBlockRow> blocks = vector_t<ControlFlowBlockRow>{};
	vector_t<ControlFlowEdgeRow> edges = vector_t<ControlFlowEdgeRow>{};
};
}
