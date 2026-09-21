#include <scpp/lang/php.hpp>
#include "__types/AnalysisContext.hpp"
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__types/compiler_entry_backend.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_accepted_literal_return_node_from_body_summary.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_accepted_literal_return_statement.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_append_entry.hpp"
#include "__callable/__latency_fn_analysis_context_new_context.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_accepted_literal_return_node_from_body_summary.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_literal_return_emission_from_frontend.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_literal_scalar_backend_request.hpp"
#include "__callable/__latency_fn_lowering_plan_from_entry_and_backend_requests.hpp"
#include "__callable/__latency_fn_lowering_plan_new_plan.hpp"
#include "__callable/__latency_fn_project_callable_contracts_new_artifact.hpp"
#include "__callable/__latency_fn_project_dependency_graph_from_symbols_references_and_contracts.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_literal.hpp"
#include "__callable/__latency_fn_type_refs_table_from_project_symbols.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_direct_call_reference_from_frontend.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_frontend.hpp"
#include "__callable/__latency_fn_analysis_context_append_entry.hpp"
#include "__callable/__latency_fn_analysis_context_new_context.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_actual_args.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_direct_call_emission_from_frontend.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_direct_call_reference_from_frontend.hpp"
#include "__callable/__latency_fn_lowering_plan_from_entry_and_backend_requests.hpp"
#include "__callable/__latency_fn_lowering_plan_new_plan.hpp"
#include "__callable/__latency_fn_project_callable_contracts_append_from_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_new_artifact.hpp"
#include "__callable/__latency_fn_project_callable_contracts_row_by_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_from_symbols_references_and_contracts.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_rows.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts.hpp"
#include "__callable/__latency_fn_type_refs_table_from_project_symbols.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_id.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range__exec.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request_with_value_text.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_assignment_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_id.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id__exec.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request_with_value_text.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_assignment_id.hpp"
namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
bool_t compiler_entry_backend::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == compiler_entry_backend::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
FrontendNodeRow __latency_fn_compiler_entry_backend_accepted_literal_return_node_from_body_summary(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, FrontendLiteralPayloadRow& literal) {
	SCPP_CALL_DEPTH_GUARD("compiler_entry_backend::accepted_literal_return_node_from_body_summary", "/tmp/scpp-edit-latency-20260919/app/compile/backend/compiler_entry_backend.phs", __latency_lines_compiler_entry_backend[0]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	shared_p<FrontendBodySummaryArtifact> summary = __latency_fn_frontend_body_summaries_from_symbol(model, sourceText, symbol, counters);
	return __latency_fn_frontend_body_summaries_accepted_literal_return_statement(summary, model, symbol, literal, counters);
}

}

namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_compiler_entry_backend_literal_return_emission_from_frontend(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<LoweringPlan>& plan, shared_p<CapabilityCoverageArtifact>& capabilityCoverage) {
	SCPP_CALL_DEPTH_GUARD("compiler_entry_backend::literal_return_emission_from_frontend", "/tmp/scpp-edit-latency-20260919/app/compile/backend/compiler_entry_backend.phs", __latency_lines_compiler_entry_backend[1]);
	FrontendLiteralPayloadRow literal = FrontendLiteralPayloadRow{};
	FrontendNodeRow literalNode = __latency_fn_compiler_entry_backend_accepted_literal_return_node_from_body_summary(symbol, model, sourceText, literal);
	if (static_cast<bool>((php::identical(cast<int_t<>>(literalNode->node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(literal->payload_id), static_cast<int_t<> >(0))))) {
		plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
		return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	}
	shared_p<ProjectReferenceResolution> references = __latency_fn_project_reference_resolution_new_artifact(static_cast<int_t<> >(0));
	shared_p<ProjectCallableContractArtifact> contracts = __latency_fn_project_callable_contracts_new_artifact(static_cast<int_t<> >(0));
	ProjectDependencyGraph graph = __latency_fn_project_dependency_graph_from_symbols_references_and_contracts(symbols, references, contracts);
	shared_p<AnalysisContext> analysis = __latency_fn_analysis_context_new_context(static_cast<int_t<> >(1));
	AnalysisEntryContextRow entry = __latency_fn_analysis_context_append_entry(analysis, symbol, references, contracts, graph);
	TypeRefTable typeRefs = __latency_fn_type_refs_table_from_project_symbols(symbols);
	capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_literal(typeRefs, literalNode->node_id, literal->type_ref_id);
	backendRequests = __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(static_cast<int_t<> >(1), static_cast<int_t<> >(0), static_cast<int_t<> >(0), static_cast<int_t<> >(0), static_cast<int_t<> >(0));
	__latency_fn_local_body_lowering_append_literal_scalar_backend_request(backendRequests, capabilityCoverage, literalNode, literal, symbol);
	plan = __latency_fn_lowering_plan_from_entry_and_backend_requests(entry, backendRequests);
	return __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(plan, backendRequests);
}

}

namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
ProjectReferenceResolutionRow __latency_fn_compiler_entry_backend_direct_call_reference_from_frontend(shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolIndexRow symbol) {
	SCPP_CALL_DEPTH_GUARD("compiler_entry_backend::direct_call_reference_from_frontend", "/tmp/scpp-edit-latency-20260919/app/compile/backend/compiler_entry_backend.phs", __latency_lines_compiler_entry_backend[2]);
	return __latency_fn_project_reference_resolution_append_direct_call_from_frontend(references, symbols, model, sourceText, symbol);
}

}

namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_compiler_entry_backend_direct_call_emission_from_frontend(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<LoweringPlan>& plan, shared_p<CapabilityCoverageArtifact>& capabilityCoverage) {
	SCPP_CALL_DEPTH_GUARD("compiler_entry_backend::direct_call_emission_from_frontend", "/tmp/scpp-edit-latency-20260919/app/compile/backend/compiler_entry_backend.phs", __latency_lines_compiler_entry_backend[3]);
	shared_p<ProjectReferenceResolution> references = __latency_fn_project_reference_resolution_new_artifact(static_cast<int_t<> >(1));
	shared_p<ProjectCallableContractArtifact> contracts = __latency_fn_project_callable_contracts_new_artifact(static_cast<int_t<> >(1));
	ProjectReferenceResolutionRow reference = __latency_fn_compiler_entry_backend_direct_call_reference_from_frontend(references, symbols, model, sourceText, symbol);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reference->resolution_kind_id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id())))) {
		backendRequests = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(0));
		plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
		return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id())))) {
		__latency_fn_project_callable_contracts_append_from_reference(contracts, reference, references, symbols);
	}
	ProjectDependencyGraph graph = __latency_fn_project_dependency_graph_from_symbols_references_and_contracts(symbols, references, contracts);
	shared_p<AnalysisContext> analysis = __latency_fn_analysis_context_new_context(static_cast<int_t<> >(1));
	AnalysisEntryContextRow entry = __latency_fn_analysis_context_append_entry(analysis, symbol, references, contracts, graph);
	TypeRefTable typeRefs = __latency_fn_type_refs_table_from_project_symbols(symbols);
	capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts(typeRefs, contracts);
	if (static_cast<bool>(php::identical(cast<int_t<>>(contracts->contract_count), static_cast<int_t<> >(0)))) {
		backendRequests = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(0));
		plan = __latency_fn_lowering_plan_from_entry_and_backend_requests(entry, backendRequests);
		return __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(plan, backendRequests);
	}
	ProjectCallableContractRow contract = __latency_fn_project_callable_contracts_row_by_id(contracts, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	vector_t<ProjectReferenceActualArgumentRow> actualArguments = required_cast<vector_t<ProjectReferenceActualArgumentRow>>(__latency_fn_project_reference_resolution_actual_argument_rows(references, reference));
	backendRequests = __latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_actual_args(contract, symbols, actualArguments);
	plan = __latency_fn_lowering_plan_from_entry_and_backend_requests(entry, backendRequests);
	return __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(plan, backendRequests);
}

}

namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
void __latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range__exec(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> firstSourceRowId, int_t<std::uint32_t> lastSourceRowId, int_t<>& binaryIndex) {
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((((((((assignmentIndex < php::count(assignmentSourceRowIds)) && (assignmentIndex < php::count(assignmentTargetLocalSourceRowIds))) && (assignmentIndex < php::count(assignmentTypeRefIds))) && (assignmentIndex < php::count(assignmentValueSourceRowIds))) && (assignmentIndex < php::count(assignmentValues))) && (assignmentIndex < php::count(assignmentValueTexts))) && (assignmentIndex < php::count(assignmentLocalOperationIds))))) {
		int_t<std::uint32_t> sourceRowId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(assignmentSourceRowIds.at(assignmentIndex)));
		bool_t inRange = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
		if (static_cast<bool>((php::identical(cast<int_t<>>(firstSourceRowId), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(lastSourceRowId), static_cast<int_t<> >(0))))) {
			inRange = bool_t(static_cast<bool_t>(true));
		}
		else {
			if (static_cast<bool>(((php::identical(cast<int_t<>>(firstSourceRowId), static_cast<int_t<> >(0)) && (cast<int_t<>>(lastSourceRowId) > static_cast<int_t<> >(0))) && (cast<int_t<>>(sourceRowId) <= cast<int_t<>>(lastSourceRowId))))) {
				inRange = bool_t(static_cast<bool_t>(true));
			}
			else {
				if (static_cast<bool>(((((cast<int_t<>>(firstSourceRowId) > static_cast<int_t<> >(0)) && (cast<int_t<>>(lastSourceRowId) > static_cast<int_t<> >(0))) && (cast<int_t<>>(sourceRowId) >= cast<int_t<>>(firstSourceRowId))) && (cast<int_t<>>(sourceRowId) <= cast<int_t<>>(lastSourceRowId))))) {
					inRange = bool_t(static_cast<bool_t>(true));
				}
			}
		}
		if (static_cast<bool>(php::condition_truthy(inRange))) {
			int_t<std::uint16_t> operationId = required_cast<int_t<std::uint16_t>>(cast<int_t<std::uint16_t>>(assignmentLocalOperationIds.at(assignmentIndex)));
			if (static_cast<bool>(php::identical(cast<int_t<>>(operationId), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_store_id())))) {
				__latency_fn_local_body_lowering_append_local_backend_request_with_value_text(backendRequests, capabilityCoverage, __latency_fn_type_capability_readiness_feature_local_assignment_id(), assignmentSourceRowIds.at(assignmentIndex), assignmentTargetLocalSourceRowIds.at(assignmentIndex), assignmentValueSourceRowIds.at(assignmentIndex), assignmentValues.at(assignmentIndex), assignmentValueTexts.at(assignmentIndex), operationId, symbol);
			}
			else {
				__latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests(backendRequests, capabilityCoverage, binaryFeatureIds.at(binaryIndex), binarySourceRowIds.at(binaryIndex), assignmentTargetLocalSourceRowIds.at(assignmentIndex), assignmentTypeRefIds.at(assignmentIndex), binaryLeftLocalSourceRowIds.at(binaryIndex), binaryRightSourceRowIds.at(binaryIndex), binaryRightValues.at(binaryIndex), operationId, symbol);
				binaryIndex = (binaryIndex + static_cast<int_t<> >(1));
			}
		}
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
void __latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id__exec(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> sourceRowId, int_t<>& binaryIndex) {
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> selectedBinaryIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((((((((assignmentIndex < php::count(assignmentSourceRowIds)) && (assignmentIndex < php::count(assignmentTargetLocalSourceRowIds))) && (assignmentIndex < php::count(assignmentTypeRefIds))) && (assignmentIndex < php::count(assignmentValueSourceRowIds))) && (assignmentIndex < php::count(assignmentValues))) && (assignmentIndex < php::count(assignmentValueTexts))) && (assignmentIndex < php::count(assignmentLocalOperationIds))))) {
		if (static_cast<bool>(php::identical(cast<int_t<>>(assignmentSourceRowIds.at(assignmentIndex)), cast<int_t<>>(sourceRowId)))) {
			int_t<std::uint16_t> operationId = required_cast<int_t<std::uint16_t>>(cast<int_t<std::uint16_t>>(assignmentLocalOperationIds.at(assignmentIndex)));
			if (static_cast<bool>(php::identical(cast<int_t<>>(operationId), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_store_id())))) {
				__latency_fn_local_body_lowering_append_local_backend_request_with_value_text(backendRequests, capabilityCoverage, __latency_fn_type_capability_readiness_feature_local_assignment_id(), assignmentSourceRowIds.at(assignmentIndex), assignmentTargetLocalSourceRowIds.at(assignmentIndex), assignmentValueSourceRowIds.at(assignmentIndex), assignmentValues.at(assignmentIndex), assignmentValueTexts.at(assignmentIndex), operationId, symbol);
			}
			else {
				if (static_cast<bool>((((((selectedBinaryIndex >= php::count(binarySourceRowIds)) || (selectedBinaryIndex >= php::count(binaryFeatureIds))) || (selectedBinaryIndex >= php::count(binaryLeftLocalSourceRowIds))) || (selectedBinaryIndex >= php::count(binaryRightSourceRowIds))) || (selectedBinaryIndex >= php::count(binaryRightValues))))) {
					return;
				}
				__latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests(backendRequests, capabilityCoverage, binaryFeatureIds.at(selectedBinaryIndex), binarySourceRowIds.at(selectedBinaryIndex), assignmentTargetLocalSourceRowIds.at(assignmentIndex), assignmentTypeRefIds.at(assignmentIndex), binaryLeftLocalSourceRowIds.at(selectedBinaryIndex), binaryRightSourceRowIds.at(selectedBinaryIndex), binaryRightValues.at(selectedBinaryIndex), operationId, symbol);
				binaryIndex = (selectedBinaryIndex + static_cast<int_t<> >(1));
			}
			return;
		}
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(assignmentLocalOperationIds.at(assignmentIndex)), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_store_id()))))) {
			selectedBinaryIndex = (selectedBinaryIndex + static_cast<int_t<> >(1));
		}
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
}

}
