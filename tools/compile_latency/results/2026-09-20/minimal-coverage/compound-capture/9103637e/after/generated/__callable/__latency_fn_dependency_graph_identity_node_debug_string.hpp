#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraphNodeRow;
class ProjectSymbolIndex;
string_t __latency_fn_dependency_graph_identity_node_debug_string(ProjectDependencyGraphNodeRow row, shared_p<ProjectSymbolIndex> symbols);
}
