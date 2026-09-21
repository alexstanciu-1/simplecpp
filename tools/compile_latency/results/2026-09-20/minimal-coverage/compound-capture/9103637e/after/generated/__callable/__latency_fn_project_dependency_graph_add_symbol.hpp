#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraph;
class ProjectReferenceResolution;
struct ProjectSymbolIndexRow;
void __latency_fn_project_dependency_graph_add_symbol(ProjectDependencyGraph& graph, ProjectSymbolIndexRow symbol, shared_p<ProjectReferenceResolution> references);
}
