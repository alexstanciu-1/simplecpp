#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class BackendRequestAuthorizationArtifact;
class LoweringPlan;
shared_p<LoweringPlan> __latency_fn_lowering_plan_from_entry_and_backend_requests(AnalysisEntryContextRow entry, shared_p<BackendRequestAuthorizationArtifact> requests);
}
