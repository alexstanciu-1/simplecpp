#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectCallableContractArtifact;
struct ProjectDependencyGraph;
class ProjectReferenceResolution;
void __latency_fn_project_dependency_graph_add_reference_edges(ProjectDependencyGraph& graph, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts);
}
