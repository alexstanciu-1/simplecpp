#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectCallableContractArtifact;
struct ProjectDependencyGraph;
class ProjectReferenceResolution;
class ProjectSymbolIndex;
ProjectDependencyGraph __latency_fn_project_dependency_graph_from_symbols_references_and_contracts(shared_p<ProjectSymbolIndex> symbols, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts);
}
