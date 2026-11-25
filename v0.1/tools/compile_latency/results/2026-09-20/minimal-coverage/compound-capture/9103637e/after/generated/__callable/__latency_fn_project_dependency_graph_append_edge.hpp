#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraph;
struct ProjectDependencyGraphEdgeRow;
int_t<std::uint32_t> __latency_fn_project_dependency_graph_append_edge(ProjectDependencyGraph& graph, ProjectDependencyGraphEdgeRow row);
}
