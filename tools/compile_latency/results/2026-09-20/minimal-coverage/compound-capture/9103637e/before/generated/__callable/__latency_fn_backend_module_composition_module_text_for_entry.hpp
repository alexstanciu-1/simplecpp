#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
class FrontendModel;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
string_t __latency_fn_backend_module_composition_module_text_for_entry(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow entrySymbol, shared_p<FrontendModel> model, const string_t& sourceText, BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests);
}
