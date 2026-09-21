#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
class ProjectCallableContractArtifact;
class ProjectReferenceResolution;
struct ProjectSymbolIndexRow;
shared_p<PartitionReadinessArtifact> __latency_fn_project_reference_resolution_reference_contract_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts);
}
