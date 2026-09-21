#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class LoweringPlan;
class PartitionReadinessArtifact;
struct ProjectSymbolIndexRow;
shared_p<PartitionReadinessArtifact> __latency_fn_lowering_plan_backend_lowering_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan);
}
