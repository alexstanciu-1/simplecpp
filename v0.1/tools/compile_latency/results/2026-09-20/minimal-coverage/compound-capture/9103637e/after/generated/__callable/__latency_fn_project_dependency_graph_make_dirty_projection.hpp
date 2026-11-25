#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDirtyReuseProjectionRow;
struct ProjectSymbolIndexRow;
ProjectDirtyReuseProjectionRow __latency_fn_project_dependency_graph_make_dirty_projection(int_t<std::uint32_t> projectionId, ProjectSymbolIndexRow symbol, int_t<std::uint16_t> recomputeTargetId, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyScopeId, int_t<std::uint16_t> reuseScopeId, int_t<std::uint16_t> statusId);
}
