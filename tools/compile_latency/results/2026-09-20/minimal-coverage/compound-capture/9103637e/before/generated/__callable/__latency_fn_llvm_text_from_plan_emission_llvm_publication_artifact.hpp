#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct FunctionBodyTextEmissionPreflightArtifact;
class PartitionReadinessArtifact;
struct ProjectSymbolIndexRow;
shared_p<PartitionReadinessArtifact> __latency_fn_llvm_text_from_plan_emission_llvm_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact);
}
