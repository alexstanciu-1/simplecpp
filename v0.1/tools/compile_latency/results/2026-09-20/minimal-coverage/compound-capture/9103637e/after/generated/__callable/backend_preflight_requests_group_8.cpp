#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowList.hpp"
#include "__types/BackendStepRouteDescriptorRow.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__types/CallableAbiReadinessArtifact.hpp"
#include "__types/CallableAbiReadinessRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_callable_abi.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_callable_abi.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_callable_abi_from_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_callable_abi_row_from_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_callable_abi_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_callable_abi_row_by_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_artifact_kind_backend_request_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_model_sink_neutral_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_reserve.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_string_literal_capacity.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_step_route_by_feature_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_from_operation.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_return_value_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_from_operation.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_callable_abi(CallableAbiReadinessArtifact& artifact, CallableAbiReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_callable_abi", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[123]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->abi_status_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_status_ready_id())))) {
		artifact->abi_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->abi_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_status_blocked_id())))) {
		artifact->emission_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->emission_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
CallableAbiReadinessArtifact __latency_fn_backend_preflight_requests_callable_abi_from_contract(ProjectCallableContractRow contract, StorageLifetimeRequestRow storage) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::callable_abi_from_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[124]);
	CallableAbiReadinessArtifact artifact = __latency_fn_backend_preflight_requests_new_callable_abi_artifact(static_cast<int_t<> >(1));
	CallableAbiReadinessRow row = __latency_fn_backend_preflight_requests_callable_abi_row_from_contract(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), contract, storage);
	__latency_fn_backend_preflight_requests_append_callable_abi(artifact, row);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
CallableAbiReadinessRow __latency_fn_backend_preflight_requests_callable_abi_row_by_id(CallableAbiReadinessArtifact artifact, int_t<std::uint32_t> abiRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::callable_abi_row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[125]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->abi_row_id), cast<int_t<>>(abiRowId)))) {
			return row;
		}
	}
	CallableAbiReadinessRow empty = CallableAbiReadinessRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(int_t<> rowCapacity, int_t<> binaryOperandCapacity, int_t<> localOperandCapacity, int_t<> callArgumentCapacity, int_t<> controlFlowOperandCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::new_backend_request_artifact_with_sidecar_capacity", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[126]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = create<BackendRequestAuthorizationArtifact>();
	artifact->artifact_kind_id = __latency_fn_backend_preflight_requests_artifact_kind_backend_request_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->request_model_id = __latency_fn_backend_preflight_requests_request_model_sink_neutral_id();
	__latency_fn_backend_request_row_lists_reserve(artifact->request_rows, rowCapacity);
	php::vector_reserve(artifact->binary_operands, binaryOperandCapacity);
	php::vector_reserve(artifact->local_operands, localOperandCapacity);
	php::vector_reserve(artifact->call_arguments, callArgumentCapacity);
	php::vector_reserve(artifact->control_flow_operands, controlFlowOperandCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_new_backend_request_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::new_backend_request_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[127]);
	return __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(rowCapacity, rowCapacity, rowCapacity, rowCapacity, rowCapacity);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_string_literal_capacity(int_t<> rowCapacity, int_t<> stringLiteralCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::new_backend_request_artifact_with_string_literal_capacity", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[128]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(rowCapacity, static_cast<int_t<> >(0), static_cast<int_t<> >(0), static_cast<int_t<> >(0), static_cast<int_t<> >(0));
	php::vector_reserve(artifact->string_literal_operands, stringLiteralCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_lowering_step_kind_from_operation(OperationReadiness operation) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::lowering_step_kind_from_operation", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[129]);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(operation->consumer_feature_id, operation->provider_type_ref_id);
	if (static_cast<bool>(((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::not_identical(cast<int_t<>>(operatorRow->lowering_step_kind_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))))) {
		return operatorRow->lowering_step_kind_id;
	}
	BackendStepRouteDescriptorRow route = __latency_fn_backend_preflight_requests_backend_step_route_by_feature_id(operation->consumer_feature_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(route->feature_id), cast<int_t<>>(operation->consumer_feature_id)))) {
		return route->lowering_step_kind_id;
	}
	return __latency_fn_backend_preflight_requests_lowering_step_kind_return_value_id();
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_operation(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_operation", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[130]);
	BackendRequestAuthorizationRow row = BackendRequestAuthorizationRow{};
	row->request_id = requestId;
	row->contract_id = operation->contract_id;
	row->project_callable_contract_id = contract->contract_id;
	row->source_row_id = operation->source_row_id;
	row->source_reference_id = contract->reference_id;
	row->target_symbol_id = contract->target_symbol_id;
	row->feature_id = operation->consumer_feature_id;
	row->lowering_adapter_id = operation->lowering_adapter_id;
	row->lowering_step_kind_id = __latency_fn_backend_preflight_requests_lowering_step_kind_from_operation(operation);
	row->provider_type_ref_id = operation->result_type_ref_id;
	row->cache_owner_symbol_id = contract->from_symbol_id;
	row->project_callable_blocked_reason_id = contract->blocked_reason_id;
	row->value = __latency_fn_structure_row_ids_int32_from_int(cast<int_t<>>(contract->actual_arg_count));
	if (static_cast<bool>((php::identical(cast<int_t<>>(operation->status_id), cast<int_t<>>(__latency_fn_operation_readiness_status_blocked_id())) || php::identical(cast<int_t<>>(storage->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_blocked_id()))))) {
		row->status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
		row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id();
		return row;
	}
	row->status_id = __latency_fn_backend_preflight_requests_status_ready_id();
	row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, int_t<std::uint32_t> sourceRowId, int_t<std::int32_t> value, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_operation_source_value", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[131]);
	ProjectCallableContractRow emptyContract = ProjectCallableContractRow{};
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation(cast<int_t<std::uint32_t>>(requestId), operation, storage, emptyContract);
	row->project_callable_contract_id = __latency_fn_structure_row_ids_none_id();
	row->source_row_id = sourceRowId;
	row->source_reference_id = __latency_fn_structure_row_ids_none_id();
	row->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	row->cache_owner_symbol_id = ownerSymbol->symbol_id;
	row->project_callable_blocked_reason_id = __latency_fn_structure_row_ids_none_kind_id();
	row->value = value;
	return row;
}

}
