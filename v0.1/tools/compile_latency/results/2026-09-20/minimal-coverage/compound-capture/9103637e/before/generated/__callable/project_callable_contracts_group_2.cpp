#include <scpp/lang/php.hpp>
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__callable/__latency_fn_project_callable_contracts_has_by_reference_default.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_has_ready_default.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_is_by_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_return_type_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_ref_name.hpp"
#include "__callable/__latency_fn_project_callable_contracts_reference_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_row_by_id.hpp"
#include "__callable/__latency_fn_reference_identity_debug_string_from_artifact.hpp"
#include "__callable/__latency_fn_project_callable_contracts_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_project_callable_contracts_target_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_project_callable_contracts_target_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_status_matched_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_status_not_checked_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_type_status_matched_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_type_status_mismatched_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_type_status_not_checked_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_backend_lowering_status_blocked_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_argument_count_mismatch_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_argument_type_mismatch_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_by_reference_actual_not_stable_local_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_by_reference_default_not_ready_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_multi_arg_contract_not_ready_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_unresolved_reference_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_by_reference_arguments_ready.hpp"
#include "__callable/__latency_fn_project_callable_contracts_has_by_reference_default.hpp"
#include "__callable/__latency_fn_project_callable_contracts_return_status_matched_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_return_status_not_checked_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_row_from_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_blocked_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_compatible_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_rows.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
bool_t __latency_fn_project_callable_contracts_has_by_reference_default(const vector_t<ProjectSymbolParameterRow>& expectedParameters) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::has_by_reference_default", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[27]);
	auto& __latency_local_0 = expectedParameters;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto parameter = __latency_local_1.value_copy();
		if (static_cast<bool>((__latency_fn_project_symbol_index_parameter_is_by_reference(parameter) && __latency_fn_project_symbol_index_parameter_has_ready_default(parameter)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
string_t __latency_fn_project_callable_contracts_return_type_name(ProjectCallableContractRow row) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::return_type_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[28]);
	return __latency_fn_project_symbol_index_type_ref_name(row->return_type_ref_id);
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
string_t __latency_fn_project_callable_contracts_reference_key(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::reference_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[29]);
	ProjectReferenceResolutionRow reference = __latency_fn_project_reference_resolution_row_by_id(references, row->reference_id);
	return __latency_fn_reference_identity_debug_string_from_artifact(references, reference);
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
string_t __latency_fn_project_callable_contracts_from_symbol_key(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::from_symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[30]);
	ProjectReferenceResolutionRow reference = __latency_fn_project_reference_resolution_row_by_id(references, row->reference_id);
	string_t referenceSymbolKey = required_cast<string_t>(__latency_fn_project_reference_resolution_from_symbol_key(references, reference));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(referenceSymbolKey, string_t(""))))) {
		return referenceSymbolKey;
	}
	ProjectSymbolIndexRow symbol = __latency_fn_project_symbol_index_row_by_id(symbols, row->from_symbol_id);
	return __latency_fn_project_symbol_index_symbol_key(symbols, symbol);
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
string_t __latency_fn_project_callable_contracts_target_symbol_key(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::target_symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[31]);
	ProjectReferenceResolutionRow reference = __latency_fn_project_reference_resolution_row_by_id(references, row->reference_id);
	string_t referenceSymbolKey = required_cast<string_t>(__latency_fn_project_reference_resolution_resolved_symbol_key(references, reference));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(referenceSymbolKey, string_t(""))))) {
		return referenceSymbolKey;
	}
	ProjectSymbolIndexRow symbol = __latency_fn_project_symbol_index_row_by_id(symbols, row->target_symbol_id);
	return __latency_fn_project_symbol_index_symbol_key(symbols, symbol);
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
string_t __latency_fn_project_callable_contracts_target_source_unit_key(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::target_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[32]);
	ProjectReferenceResolutionRow reference = __latency_fn_project_reference_resolution_row_by_id(references, row->reference_id);
	string_t referenceSourceUnitKey = required_cast<string_t>(__latency_fn_project_reference_resolution_resolved_source_unit_key(references, reference));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(referenceSourceUnitKey, string_t(""))))) {
		return referenceSourceUnitKey;
	}
	ProjectSymbolIndexRow symbol = __latency_fn_project_symbol_index_row_by_id(symbols, row->target_symbol_id);
	return __latency_fn_project_symbol_index_source_unit_key(symbols, symbol);
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
ProjectCallableContractRow __latency_fn_project_callable_contracts_row_from_reference(shared_p<ProjectCallableContractArtifact> artifact, ProjectReferenceResolutionRow reference, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::row_from_reference", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[33]);
	ProjectCallableContractRow row = ProjectCallableContractRow{};
	row->contract_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows));
	row->reference_id = reference->reference_id;
	row->from_symbol_id = reference->from_symbol_id;
	row->target_symbol_id = reference->resolved_symbol_id;
	row->target_source_unit_id = reference->resolved_source_unit_id;
	row->actual_arg_count = reference->actual_arg_count;
	row->backend_adapter_id = __latency_fn_structure_row_ids_none_kind_id();
	row->backend_lowering_status_id = __latency_fn_project_callable_contracts_backend_lowering_status_blocked_id();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id()))))) {
		row->argument_count_status_id = __latency_fn_project_callable_contracts_argument_status_not_checked_id();
		row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_not_checked_id();
		row->return_type_status_id = __latency_fn_project_callable_contracts_return_status_not_checked_id();
		row->status_id = __latency_fn_project_callable_contracts_status_blocked_id();
		row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_unresolved_reference_id();
		return row;
	}
	ProjectSymbolIndexRow target = __latency_fn_project_symbol_index_row_by_id(symbols, reference->resolved_symbol_id);
	row->return_type_ref_id = target->return_type_ref_id;
	row->expected_arg_count = target->parameter_count;
	row->expected_first_parameter_type_ref_id = target->first_parameter_type_ref_id;
	row->actual_first_argument_type_ref_id = reference->actual_first_argument_type_ref_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->actual_arg_count), cast<int_t<>>(row->expected_arg_count)))) {
		row->argument_count_status_id = __latency_fn_project_callable_contracts_argument_status_matched_id();
	}
	else {
		row->argument_count_status_id = __latency_fn_project_callable_contracts_argument_status_not_checked_id();
	}
	row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_not_checked_id();
	bool_t multiArgContractReady = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->argument_count_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_status_matched_id())))) {
		vector_t<ProjectReferenceActualArgumentRow> actualArguments = required_cast<vector_t<ProjectReferenceActualArgumentRow>>(__latency_fn_project_reference_resolution_actual_argument_rows(references, reference));
		vector_t<ProjectSymbolParameterRow> expectedParameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, target));
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->actual_arg_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->expected_arg_count), static_cast<int_t<> >(0))))) {
			row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_matched_id();
			multiArgContractReady = bool_t(static_cast<bool_t>(true));
		}
		else {
			if (static_cast<bool>((php::identical(php::count(actualArguments), cast<int_t<>>(row->actual_arg_count)) && php::identical(php::count(expectedParameters), cast<int_t<>>(row->expected_arg_count))))) {
				bool_t allArgumentTypesMatched = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
				int_t<> argumentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
				while (static_cast<bool>((argumentIndex < php::count(actualArguments)))) {
					ProjectReferenceActualArgumentRow actual = actualArguments.at(argumentIndex);
					ProjectSymbolParameterRow expected = expectedParameters.at(argumentIndex);
					if (static_cast<bool>((((php::not_identical(cast<int_t<>>(actual->literal_status_id), cast<int_t<>>(__latency_fn_frontend_model_builder_literal_status_ready_id())) || php::identical(cast<int_t<>>(actual->type_ref_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(expected->type_ref_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(actual->type_ref_id), cast<int_t<>>(expected->type_ref_id))))) {
						allArgumentTypesMatched = bool_t(static_cast<bool_t>(false));
						break;
					}
					argumentIndex = (argumentIndex + static_cast<int_t<> >(1));
				}
				if (static_cast<bool>(php::condition_truthy(allArgumentTypesMatched))) {
					row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_matched_id();
				}
				else {
					row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_mismatched_id();
				}
				multiArgContractReady = bool_t(static_cast<bool_t>(true));
			}
			else {
				if (static_cast<bool>((((php::identical(cast<int_t<>>(row->actual_arg_count), static_cast<int_t<> >(1)) && php::identical(cast<int_t<>>(row->expected_arg_count), static_cast<int_t<> >(1))) && (cast<int_t<>>(row->actual_first_argument_type_ref_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(row->expected_first_parameter_type_ref_id) > static_cast<int_t<> >(0))))) {
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->actual_first_argument_type_ref_id), cast<int_t<>>(row->expected_first_parameter_type_ref_id)))) {
						row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_matched_id();
					}
					else {
						row->argument_type_status_id = __latency_fn_project_callable_contracts_argument_type_status_mismatched_id();
					}
					multiArgContractReady = bool_t(static_cast<bool_t>(true));
				}
				else {
					if (static_cast<bool>(((cast<int_t<>>(row->actual_arg_count) > static_cast<int_t<> >(1)) || (cast<int_t<>>(row->expected_arg_count) > static_cast<int_t<> >(1))))) {
						multiArgContractReady = bool_t(static_cast<bool_t>(false));
					}
					else {
						multiArgContractReady = bool_t(static_cast<bool_t>(true));
					}
				}
			}
		}
	}
	row->return_type_status_id = __latency_fn_project_callable_contracts_return_status_matched_id();
	bool_t byReferenceActualsReady = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t byReferenceDefaultBlocked = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->argument_count_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_status_matched_id())))) {
		vector_t<ProjectReferenceActualArgumentRow> actualArgumentsForReference = required_cast<vector_t<ProjectReferenceActualArgumentRow>>(__latency_fn_project_reference_resolution_actual_argument_rows(references, reference));
		vector_t<ProjectSymbolParameterRow> expectedParametersForReference = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, target));
		byReferenceActualsReady = __latency_fn_project_callable_contracts_by_reference_arguments_ready(expectedParametersForReference, actualArgumentsForReference, row->actual_arg_count);
		byReferenceDefaultBlocked = __latency_fn_project_callable_contracts_has_by_reference_default(expectedParametersForReference);
	}
	if (static_cast<bool>(((((php::identical(cast<int_t<>>(row->argument_count_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_status_matched_id())) && php::not_identical(cast<int_t<>>(row->argument_type_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_type_status_mismatched_id()))) && multiArgContractReady) && byReferenceActualsReady) && (!byReferenceDefaultBlocked)))) {
		row->status_id = __latency_fn_project_callable_contracts_status_compatible_id();
		row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_none_id();
	}
	else {
		row->status_id = __latency_fn_project_callable_contracts_status_blocked_id();
		if (static_cast<bool>(php::condition_truthy(byReferenceDefaultBlocked))) {
			row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_by_reference_default_not_ready_id();
		}
		else {
			if (static_cast<bool>((!byReferenceActualsReady))) {
				row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_by_reference_actual_not_stable_local_id();
			}
			else {
				if (static_cast<bool>((!multiArgContractReady))) {
					row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_multi_arg_contract_not_ready_id();
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->argument_type_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_type_status_mismatched_id())))) {
						row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_argument_type_mismatch_id();
					}
					else {
						row->blocked_reason_id = __latency_fn_project_callable_contracts_blocked_reason_argument_count_mismatch_id();
					}
				}
			}
		}
	}
	return row;
}

}
