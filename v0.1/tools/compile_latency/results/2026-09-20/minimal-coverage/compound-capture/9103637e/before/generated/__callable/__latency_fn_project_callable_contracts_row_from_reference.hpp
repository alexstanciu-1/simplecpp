#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectCallableContractArtifact;
struct ProjectCallableContractRow;
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
class ProjectSymbolIndex;
ProjectCallableContractRow __latency_fn_project_callable_contracts_row_from_reference(shared_p<ProjectCallableContractArtifact> artifact, ProjectReferenceResolutionRow reference, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols);
}
