#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
class FrontendModel;
class LoweringPlan;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
BackendEmissionDecisionArtifact __latency_fn_compiler_entry_backend_literal_return_emission_from_frontend(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<LoweringPlan>& plan, shared_p<CapabilityCoverageArtifact>& capabilityCoverage);
}
