#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraphEdgeRow;
class ProjectSymbolIndex;
string_t __latency_fn_dependency_graph_identity_edge_debug_string(ProjectDependencyGraphEdgeRow row, shared_p<ProjectSymbolIndex> symbols);
}
