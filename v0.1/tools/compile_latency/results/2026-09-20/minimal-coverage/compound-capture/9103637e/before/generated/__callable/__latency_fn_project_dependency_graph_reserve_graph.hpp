#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraph;
void __latency_fn_project_dependency_graph_reserve_graph(ProjectDependencyGraph& graph, int_t<> nodeCapacity, int_t<> edgeCapacity, int_t<> projectionCapacity, int_t<> fanoutCapacity);
}
