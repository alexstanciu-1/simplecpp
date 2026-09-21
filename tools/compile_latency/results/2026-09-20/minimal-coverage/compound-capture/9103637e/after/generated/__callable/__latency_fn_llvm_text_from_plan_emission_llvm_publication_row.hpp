#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct FunctionBodyTextEmissionPreflightArtifact;
struct PartitionReadinessRow;
struct ProjectSymbolIndexRow;
PartitionReadinessRow __latency_fn_llvm_text_from_plan_emission_llvm_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact);
}
