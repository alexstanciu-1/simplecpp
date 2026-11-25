#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDirtyFanoutRow;
ProjectDirtyFanoutRow __latency_fn_project_dependency_graph_make_dirty_fanout(int_t<std::uint32_t> fanoutId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> dependentSymbolId, int_t<std::uint16_t> recomputeTargetId, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> graphEdgeKindId, int_t<std::uint16_t> reuseScopeId, int_t<std::uint16_t> statusId);
}
