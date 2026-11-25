#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_backend_emission_for_entry.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_direct_call_emission_from_frontend.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_literal_return_emission_from_frontend.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_scalar_local_body_emission.hpp"
namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_compiler_entry_backend_backend_emission_for_entry(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<LoweringPlan>& plan, shared_p<CapabilityCoverageArtifact>& capabilityCoverage) {
	SCPP_CALL_DEPTH_GUARD("compiler_entry_backend::backend_emission_for_entry", "/tmp/scpp-edit-latency-20260919/app/compile/backend/compiler_entry_backend.phs", __latency_lines_compiler_entry_backend[5]);
	BackendEmissionDecisionArtifact directCallEmission = __latency_fn_compiler_entry_backend_direct_call_emission_from_frontend(symbols, symbol, model, sourceText, backendRequests, plan, capabilityCoverage);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(__latency_fn_backend_emission_decisions_status_from_plan(plan)), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_ready_id())) || (cast<int_t<>>(backendRequests->ready_count) > static_cast<int_t<> >(0))) || (cast<int_t<>>(backendRequests->blocked_count) > static_cast<int_t<> >(0))))) {
		return directCallEmission;
	}
	BackendEmissionDecisionArtifact localEmission = __latency_fn_compiler_entry_backend_scalar_local_body_emission(symbols, symbol, model, sourceText, backendRequests, plan, capabilityCoverage);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_backend_emission_decisions_status_from_plan(plan)), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_ready_id())))) {
		return localEmission;
	}
	return __latency_fn_compiler_entry_backend_literal_return_emission_from_frontend(symbols, symbol, model, sourceText, backendRequests, plan, capabilityCoverage);
}

}
