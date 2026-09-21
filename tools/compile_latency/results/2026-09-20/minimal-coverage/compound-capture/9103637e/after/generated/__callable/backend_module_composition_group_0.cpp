#include <scpp/lang/php.hpp>
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendLocalBindingSummaryRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/backend_module_composition.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_backend_module_composition_function_text_for_symbol.hpp"
#include "__callable/__latency_fn_backend_module_composition_stable_local_literal_initialization_for_argument.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_actual_args.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_backend_emission_for_entry.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_direct_call_reference_from_frontend.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_call_argument_list_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_direct_call_function_text_from_local_reference_argument.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_direct_call_function_text_from_target_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_function_text_from_symbol_rows.hpp"
#include "__callable/__latency_fn_project_callable_contracts_append_from_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_new_artifact.hpp"
#include "__callable/__latency_fn_project_callable_contracts_row_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_is_stable_local.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_rows.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_module_composition_stable_local_literal_initialization_for_argument.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_declaration.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_by_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_id_for_variable_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_literal_status_ready.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_type_refs_scalar_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_backend_module_composition_function_text_for_symbol.hpp"
#include "__callable/__latency_fn_backend_module_composition_module_text_for_entry.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_backend_emission_for_entry.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_direct_call_reference_from_frontend.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
namespace scpp { extern const int __latency_lines_backend_module_composition[]; }
namespace scpp {
bool_t backend_module_composition::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == backend_module_composition::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_backend_module_composition[]; }
namespace scpp {
string_t __latency_fn_backend_module_composition_function_text_for_symbol(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, int_t<> depth) {
	SCPP_CALL_DEPTH_GUARD("backend_module_composition::function_text_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_module_composition.phs", __latency_lines_backend_module_composition[0]);
	if (static_cast<bool>(((depth > static_cast<int_t<> >(4)) || php::identical(cast<int_t<>>(symbol->symbol_id), static_cast<int_t<> >(0))))) {
		return string_t("");
	}
	string_t functionName = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(symbols, symbol->symbol_id));
	string_t parameterText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_parameter_function_text_from_symbol_rows(functionName, symbols, symbol, model, sourceText));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(parameterText, string_t(""))))) {
		return parameterText;
	}
	shared_p<ProjectReferenceResolution> references = __latency_fn_project_reference_resolution_new_artifact(static_cast<int_t<> >(1));
	ProjectReferenceResolutionRow reference = __latency_fn_compiler_entry_backend_direct_call_reference_from_frontend(references, symbols, model, sourceText, symbol);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(reference->resolution_kind_id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id()))))) {
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id()))))) {
			return string_t("");
		}
		ProjectSymbolIndexRow targetSymbol = __latency_fn_project_symbol_index_row_by_id(symbols, reference->resolved_symbol_id);
		string_t targetText = required_cast<string_t>(__latency_fn_backend_module_composition_function_text_for_symbol(symbols, targetSymbol, model, sourceText, (depth + static_cast<int_t<> >(1))));
		if (static_cast<bool>(php::identical(targetText, string_t("")))) {
			return string_t("");
		}
		shared_p<ProjectCallableContractArtifact> contracts = __latency_fn_project_callable_contracts_new_artifact(static_cast<int_t<> >(1));
		__latency_fn_project_callable_contracts_append_from_reference(contracts, reference, references, symbols);
		if (static_cast<bool>(php::identical(cast<int_t<>>(contracts->contract_count), static_cast<int_t<> >(0)))) {
			return string_t("");
		}
		ProjectCallableContractRow contract = __latency_fn_project_callable_contracts_row_by_id(contracts, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		vector_t<ProjectReferenceActualArgumentRow> arguments = required_cast<vector_t<ProjectReferenceActualArgumentRow>>(__latency_fn_project_reference_resolution_actual_argument_rows(references, reference));
		shared_p<BackendRequestAuthorizationArtifact> requests = __latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_actual_args(contract, symbols, arguments);
		vector_t<BackendCallArgumentRow> backendArgs = required_cast<vector_t<BackendCallArgumentRow>>(__latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id(requests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1))));
		if (static_cast<bool>(((php::identical(php::count(arguments), static_cast<int_t<> >(1)) && php::identical(php::count(backendArgs), static_cast<int_t<> >(1))) && __latency_fn_project_reference_resolution_actual_argument_is_stable_local(arguments.at(static_cast<int_t<> >(0)))))) {
			int_t<std::uint32_t> localTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
			int_t<std::int32_t> localInitialValue = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
			if (static_cast<bool>(php::condition_truthy(__latency_fn_backend_module_composition_stable_local_literal_initialization_for_argument(symbol, model, sourceText, arguments.at(static_cast<int_t<> >(0)), localTypeRefId, localInitialValue)))) {
				return __latency_fn_llvm_text_from_plan_direct_call_function_text_from_local_reference_argument(functionName, __latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(contract->return_type_ref_id), targetText, __latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(symbols, targetSymbol->symbol_id), localTypeRefId, localInitialValue);
			}
		}
		string_t argumentListText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_call_argument_list_text(backendArgs));
		return __latency_fn_llvm_text_from_plan_direct_call_function_text_from_target_text(functionName, __latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(contract->return_type_ref_id), targetText, __latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(symbols, targetSymbol->symbol_id), argumentListText);
	}
	shared_p<BackendRequestAuthorizationArtifact> targetRequests = create<BackendRequestAuthorizationArtifact>();
	shared_p<LoweringPlan> targetPlan = create<LoweringPlan>();
	shared_p<CapabilityCoverageArtifact> targetCoverage = create<CapabilityCoverageArtifact>();
	BackendEmissionDecisionArtifact targetEmission = __latency_fn_compiler_entry_backend_backend_emission_for_entry(symbols, symbol, model, sourceText, targetRequests, targetPlan, targetCoverage);
	if (static_cast<bool>((cast<int_t<>>(targetRequests->local_operand_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_llvm_text_from_plan_local_function_text_from_emission_and_backend_requests(functionName, targetEmission, targetRequests);
	}
	return __latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests(functionName, targetEmission, targetRequests);
}

}

namespace scpp { extern const int __latency_lines_backend_module_composition[]; }
namespace scpp {
bool_t __latency_fn_backend_module_composition_stable_local_literal_initialization_for_argument(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, ProjectReferenceActualArgumentRow argument, int_t<std::uint32_t>& localTypeRefId, int_t<std::int32_t>& initialValue) {
	SCPP_CALL_DEPTH_GUARD("backend_module_composition::stable_local_literal_initialization_for_argument", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_module_composition.phs", __latency_lines_backend_module_composition[1]);
	localTypeRefId = __latency_fn_structure_row_ids_none_id();
	initialValue = __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0));
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNodeRow argumentNode = __latency_fn_frontend_model_tables_node_by_id(model, argument->argument_source_row_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(argumentNode->node_id), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendNodeRow declarationNode = __latency_fn_frontend_model_tables_node_by_id(model, symbol->source_row_id, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(declarationNode->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(declarationNode->payload_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_payload_kind_declaration_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationNode->payload_row_id, counters);
	shared_p<FrontendBodySummaryArtifact> summary = __latency_fn_frontend_body_summaries_from_declaration(model, sourceText, declaration);
	int_t<std::uint32_t> localId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_body_summaries_local_id_for_variable_node(summary, model, sourceText, argumentNode, counters));
	FrontendLocalBindingSummaryRow local = __latency_fn_frontend_body_summaries_local_by_id(summary, localId);
	if (static_cast<bool>((php::identical(cast<int_t<>>(local->local_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(local->initializer_node_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, local->initializer_node_id, counters);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendLiteralPayloadRow literal = __latency_fn_frontend_model_tables_literal_by_id(model, valueNode->payload_row_id, counters);
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(local->type_ref_id), cast<int_t<>>(argument->type_ref_id)) || (!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(local->type_ref_id, literal->type_ref_id))) || (!__latency_fn_project_symbol_index_literal_status_ready(literal->literal_status_id))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	localTypeRefId = local->type_ref_id;
	initialValue = literal->numeric_payload;
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_backend_module_composition[]; }
namespace scpp {
string_t __latency_fn_backend_module_composition_module_text_for_entry(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow entrySymbol, shared_p<FrontendModel> model, const string_t& sourceText, BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests) {
	SCPP_CALL_DEPTH_GUARD("backend_module_composition::module_text_for_entry", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_module_composition.phs", __latency_lines_backend_module_composition[2]);
	shared_p<ProjectReferenceResolution> references = __latency_fn_project_reference_resolution_new_artifact(static_cast<int_t<> >(1));
	ProjectReferenceResolutionRow reference = __latency_fn_compiler_entry_backend_direct_call_reference_from_frontend(references, symbols, model, sourceText, entrySymbol);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reference->resolution_kind_id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id())))) {
		return __latency_fn_llvm_text_from_plan_module_text_from_emission_and_backend_requests(entryEmission, entryRequests);
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id()))))) {
		return string_t("");
	}
	ProjectSymbolIndexRow targetSymbol = __latency_fn_project_symbol_index_row_by_id(symbols, reference->resolved_symbol_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(targetSymbol->symbol_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t targetFunctionText = required_cast<string_t>(__latency_fn_backend_module_composition_function_text_for_symbol(symbols, targetSymbol, model, sourceText, static_cast<int_t<> >(0)));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(targetFunctionText, string_t(""))))) {
		string_t targetName = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(symbols, targetSymbol->symbol_id));
		return __latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text(entryEmission, entryRequests, targetFunctionText, targetName);
	}
	shared_p<BackendRequestAuthorizationArtifact> targetRequests = create<BackendRequestAuthorizationArtifact>();
	shared_p<LoweringPlan> targetPlan = create<LoweringPlan>();
	shared_p<CapabilityCoverageArtifact> targetCoverage = create<CapabilityCoverageArtifact>();
	BackendEmissionDecisionArtifact targetEmission = __latency_fn_compiler_entry_backend_backend_emission_for_entry(symbols, targetSymbol, model, sourceText, targetRequests, targetPlan, targetCoverage);
	return __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions(entryEmission, entryRequests, targetEmission, targetRequests, symbols);
}

}
