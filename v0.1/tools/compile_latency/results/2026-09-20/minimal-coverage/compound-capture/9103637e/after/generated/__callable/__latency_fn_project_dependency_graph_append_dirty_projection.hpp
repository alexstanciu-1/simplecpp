#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraph;
struct ProjectDirtyReuseProjectionRow;
int_t<std::uint32_t> __latency_fn_project_dependency_graph_append_dirty_projection(ProjectDependencyGraph& graph, ProjectDirtyReuseProjectionRow row);
}
