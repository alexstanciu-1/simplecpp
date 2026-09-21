#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PartitionReadinessRow;
class ProjectCallableContractArtifact;
class ProjectReferenceResolution;
struct ProjectSymbolIndexRow;
PartitionReadinessRow __latency_fn_project_reference_resolution_reference_contract_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts);
}
