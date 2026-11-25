#include <scpp/lang/php.hpp>
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestListRef.hpp"
#include "__types/BackendRequestRowList.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_if_condition.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_call_argument.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_literal_arg.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_argument_storage_ready.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_is_by_reference.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_callable_contract_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_reference_stable_local.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_value.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_formal_parameter_by_reference_slot.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_formal_parameter_slot.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_call_argument.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_actual_args.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_call_argument_storage_not_ready_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_row_from_actual.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_argument_storage_ready.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_compatible_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_by_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_by_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_count.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_count.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_list_ref.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_if_condition(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow ifNode, FrontendLiteralPayloadRow conditionLiteral, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_if_condition", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[170]);
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(cast<int_t<std::uint32_t>>(requestId), operation, storage, ifNode->node_id, conditionLiteral->numeric_payload, ownerSymbol);
	row->source_reference_id = __latency_fn_structure_row_ids_none_id();
	row->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract(ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_direct_call_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[171]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(1));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), contract);
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_literal_arg(ProjectCallableContractRow contract, FrontendNodeRow argumentNode, FrontendLiteralPayloadRow argumentLiteral) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_direct_call_contract_with_literal_arg", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[172]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(1));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), contract);
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	__latency_fn_backend_preflight_requests_append_call_argument(artifact, __latency_fn_backend_preflight_requests_call_argument_row(row->request_id, argumentNode->node_id, argumentLiteral->type_ref_id, argumentLiteral->numeric_payload, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_direct_call_argument_storage_ready(ProjectCallableContractRow contract, shared_p<ProjectSymbolIndex> symbols, const vector_t<ProjectReferenceActualArgumentRow>& actualArguments) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::direct_call_argument_storage_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[173]);
	int_t<> argumentCount = required_cast<int_t<>>(php::count(actualArguments));
	if (static_cast<bool>((!__latency_fn_storage_lifetime_readiness_callable_contract_ready(contract)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	ProjectSymbolIndexRow target = __latency_fn_project_symbol_index_row_by_id(symbols, contract->target_symbol_id);
	vector_t<ProjectSymbolParameterRow> parameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, target));
	if (static_cast<bool>(((php::not_identical(argumentCount, php::count(parameters)) || php::not_identical(argumentCount, cast<int_t<>>(contract->actual_arg_count))) || php::not_identical(php::count(parameters), cast<int_t<>>(contract->expected_arg_count))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(parameters)))) {
		ProjectSymbolParameterRow parameter = parameters.at(index);
		StorageLifetimeRequestRow formalStorage = StorageLifetimeRequestRow{};
		if (static_cast<bool>(php::condition_truthy(__latency_fn_project_symbol_index_parameter_is_by_reference(parameter)))) {
			formalStorage = __latency_fn_storage_lifetime_readiness_request_from_formal_parameter_by_reference_slot(__latency_fn_structure_row_ids_uint32_from_int(((index * static_cast<int_t<> >(2)) + static_cast<int_t<> >(1))), contract, parameter);
		}
		else {
			formalStorage = __latency_fn_storage_lifetime_readiness_request_from_formal_parameter_slot(__latency_fn_structure_row_ids_uint32_from_int(((index * static_cast<int_t<> >(2)) + static_cast<int_t<> >(1))), contract, parameter);
		}
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(formalStorage->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		ProjectReferenceActualArgumentRow argument = actualArguments.at(index);
		StorageLifetimeRequestRow actualStorage = StorageLifetimeRequestRow{};
		if (static_cast<bool>(php::condition_truthy(__latency_fn_project_symbol_index_parameter_is_by_reference(parameter)))) {
			actualStorage = __latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_reference_stable_local(__latency_fn_structure_row_ids_uint32_from_int(((index * static_cast<int_t<> >(2)) + static_cast<int_t<> >(2))), contract, argument);
		}
		else {
			actualStorage = __latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_value(__latency_fn_structure_row_ids_uint32_from_int(((index * static_cast<int_t<> >(2)) + static_cast<int_t<> >(2))), contract, argument);
		}
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(actualStorage->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_direct_call_contract_with_actual_args(ProjectCallableContractRow contract, shared_p<ProjectSymbolIndex> symbols, const vector_t<ProjectReferenceActualArgumentRow>& actualArguments) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_direct_call_contract_with_actual_args", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[174]);
	int_t<> argumentCount = required_cast<int_t<>>(php::count(actualArguments));
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(static_cast<int_t<> >(1), static_cast<int_t<> >(0), static_cast<int_t<> >(0), argumentCount, static_cast<int_t<> >(0));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), contract);
	bool_t argumentStorageReady = required_cast<bool_t>(__latency_fn_backend_preflight_requests_direct_call_argument_storage_ready(contract, symbols, actualArguments));
	if (static_cast<bool>(((php::identical(cast<int_t<>>(contract->status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_status_compatible_id())) && php::identical(argumentCount, cast<int_t<>>(contract->actual_arg_count))) && argumentStorageReady))) {
		row->status_id = __latency_fn_backend_preflight_requests_status_ready_id();
		row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_none_id();
	}
	else {
		if (static_cast<bool>(((php::identical(cast<int_t<>>(contract->status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_status_compatible_id())) && php::identical(argumentCount, cast<int_t<>>(contract->actual_arg_count))) && (!argumentStorageReady)))) {
			row->status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
			row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_call_argument_storage_not_ready_id();
		}
	}
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	if (static_cast<bool>(php::condition_truthy(argumentStorageReady))) {
		auto& __latency_local_0 = actualArguments;
		for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
			auto argument = __latency_local_1.value_copy();
			__latency_fn_backend_preflight_requests_append_call_argument(artifact, __latency_fn_backend_preflight_requests_call_argument_row_from_actual(row->request_id, argument));
		}
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_by_id(shared_p<BackendRequestAuthorizationArtifact>& artifact, int_t<std::uint32_t> requestId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[175]);
	return __latency_fn_backend_request_row_lists_row_by_id(artifact->request_rows, requestId);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_request_count(shared_p<BackendRequestAuthorizationArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::request_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[176]);
	return __latency_fn_backend_request_row_lists_row_count(artifact->request_rows);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_next_request_id(shared_p<BackendRequestAuthorizationArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::next_request_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[177]);
	return __latency_fn_structure_row_ids_next_dense_id(cast<int_t<>>(__latency_fn_backend_preflight_requests_request_count(artifact)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestListRef __latency_fn_backend_preflight_requests_request_list_ref(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> ownerSymbolId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::request_list_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[178]);
	return __latency_fn_backend_request_row_lists_list_ref_for_generation(artifact->request_rows, ownerSourceUnitId, ownerSymbolId, __latency_fn_row_segment_policy_initial_list_id(), __latency_fn_structure_row_ids_none_id());
}

}
