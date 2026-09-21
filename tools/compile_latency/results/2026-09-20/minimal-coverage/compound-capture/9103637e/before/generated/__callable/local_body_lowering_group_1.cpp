#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalImmediateTextOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_immediate_text_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_immediate_text_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row_with_value_text.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request_with_value_text.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request_from_capability_rows.hpp"
#include "__callable/__latency_fn_operation_readiness_readiness_from_capability.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_sources_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_literal.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_literal_scalar_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_literal_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_return_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_load_return_backend_request.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_load_return_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_set_control_flow_else_body.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_if_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_set_control_flow_else_body.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_if_local_immediate_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_while_local_immediate_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_local_backend_request_with_value_text(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint16_t> featureId, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, const string_t& valueText, int_t<std::uint16_t> localOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_local_backend_request_with_value_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[12]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, cast<int_t<std::uint16_t>>(featureId), cast<int_t<std::uint32_t>>(sourceRowId));
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, featureId, sourceRowId);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->provider_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, sourceRowId, value, entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	__latency_fn_backend_preflight_requests_append_local_operand(backendRequests, __latency_fn_backend_preflight_requests_local_operand_row_with_value_text(request->request_id, localSourceRowId, valueSourceRowId, operation->provider_type_ref_id, value, valueText, localOperationId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(valueText, string_t(""))))) {
		__latency_fn_backend_preflight_requests_append_local_immediate_text_operand(backendRequests, __latency_fn_backend_preflight_requests_local_immediate_text_operand_row(request->request_id, valueSourceRowId, valueText));
	}
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_local_backend_request_from_capability_rows(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, CapabilityConsumerRow consumer, CapabilityReadinessRow readiness, CapabilityProviderRow provider, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, int_t<std::uint16_t> localOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_local_backend_request_from_capability_rows", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[13]);
	OperationReadiness operation = __latency_fn_operation_readiness_readiness_from_capability(consumer, readiness);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, sourceRowId, value, entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	__latency_fn_backend_preflight_requests_append_local_operand(backendRequests, __latency_fn_backend_preflight_requests_local_operand_row(request->request_id, localSourceRowId, valueSourceRowId, operation->provider_type_ref_id, value, localOperationId));
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint16_t> featureId, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> targetLocalSourceRowId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightValueSourceRowId, int_t<std::int32_t> rightValue, int_t<std::uint16_t> localOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_scalar_binary_result_store_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[14]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, cast<int_t<std::uint16_t>>(featureId), cast<int_t<std::uint32_t>>(binarySourceRowId));
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, featureId, binarySourceRowId);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->provider_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, binarySourceRowId, rightValue, entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	__latency_fn_backend_preflight_requests_append_local_operand(backendRequests, __latency_fn_backend_preflight_requests_local_operand_row(request->request_id, targetLocalSourceRowId, rightValueSourceRowId, targetTypeRefId, rightValue, localOperationId));
	__latency_fn_backend_preflight_requests_append_binary_operand(backendRequests, __latency_fn_backend_preflight_requests_binary_operand_sources_row(request->request_id, leftLocalSourceRowId, rightValueSourceRowId, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), rightValue));
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_literal_scalar_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, FrontendNodeRow literalNode, FrontendLiteralPayloadRow literal, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_literal_scalar_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[15]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_literal_scalar_id(), literalNode->node_id);
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_literal_scalar_id(), literalNode->node_id);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->provider_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_literal(__latency_fn_backend_preflight_requests_next_request_id(backendRequests), operation, storage, literal, entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_local_load_return_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::uint32_t> typeRefId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_local_load_return_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[16]);
	__latency_fn_local_body_lowering_append_local_backend_request(backendRequests, capabilityCoverage, __latency_fn_type_capability_readiness_feature_local_load_return_id(), cast<int_t<std::uint32_t>>(returnSourceRowId), cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(valueSourceRowId), __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), __latency_fn_backend_preflight_requests_local_operation_loaded_return_id(), entrySymbol);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_if_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> bodyLastSourceRowId, int_t<std::uint32_t> elseBodyFirstSourceRowId, int_t<std::uint32_t> elseBodyLastSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint32_t> conditionLocalSourceRowId, bool_t conditionIsLocal, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_if_condition_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[17]);
	OperationReadiness ifOperation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_if_bool_then_block_id(), cast<int_t<std::uint32_t>>(ifSourceRowId));
	CapabilityConsumerRow ifConsumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_if_bool_then_block_id(), ifSourceRowId);
	CapabilityProviderRow ifProvider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, ifOperation->provider_type_ref_id);
	StorageLifetimeRequestRow ifStorage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), ifOperation, ifConsumer, ifProvider);
	int_t<std::uint32_t> ifRequestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow ifRequest = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(ifRequestId, ifOperation, ifStorage, ifSourceRowId, conditionValue, entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, ifRequest);
	BackendControlFlowOperandRow controlFlowOperand = BackendControlFlowOperandRow{};
	if (static_cast<bool>(php::condition_truthy(conditionIsLocal))) {
		controlFlowOperand = __latency_fn_backend_preflight_requests_control_flow_local_operand_row(ifRequest->request_id, conditionSourceRowId, bodyFirstSourceRowId, conditionTypeRefId, conditionLocalSourceRowId, __latency_fn_backend_preflight_requests_control_operation_if_bool_then_id());
	}
	else {
		controlFlowOperand = __latency_fn_backend_preflight_requests_control_flow_operand_row(ifRequest->request_id, conditionSourceRowId, bodyFirstSourceRowId, conditionTypeRefId, conditionValue, __latency_fn_backend_preflight_requests_control_operation_if_bool_then_id());
	}
	controlFlowOperand->body_last_source_row_id = bodyLastSourceRowId;
	__latency_fn_backend_preflight_requests_set_control_flow_else_body(controlFlowOperand, elseBodyFirstSourceRowId, elseBodyLastSourceRowId);
	__latency_fn_backend_preflight_requests_append_control_flow_operand(backendRequests, controlFlowOperand);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_if_local_immediate_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> bodyLastSourceRowId, int_t<std::uint32_t> elseBodyFirstSourceRowId, int_t<std::uint32_t> elseBodyLastSourceRowId, int_t<std::uint32_t> conditionLocalSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint16_t> conditionLocalOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_if_local_immediate_condition_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[18]);
	OperationReadiness ifOperation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_if_bool_then_block_id(), cast<int_t<std::uint32_t>>(ifSourceRowId));
	CapabilityConsumerRow ifConsumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_if_bool_then_block_id(), ifSourceRowId);
	CapabilityProviderRow ifProvider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, ifOperation->provider_type_ref_id);
	StorageLifetimeRequestRow ifStorage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), ifOperation, ifConsumer, ifProvider);
	int_t<std::uint32_t> ifRequestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow ifRequest = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(ifRequestId, ifOperation, ifStorage, ifSourceRowId, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, ifRequest);
	BackendControlFlowOperandRow controlFlowOperand = __latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row(ifRequest->request_id, conditionSourceRowId, bodyFirstSourceRowId, conditionResultTypeRefId, conditionLocalSourceRowId, conditionProviderTypeRefId, conditionLocalOperationId, conditionValue, __latency_fn_backend_preflight_requests_control_operation_if_bool_then_id());
	controlFlowOperand->body_last_source_row_id = bodyLastSourceRowId;
	__latency_fn_backend_preflight_requests_set_control_flow_else_body(controlFlowOperand, elseBodyFirstSourceRowId, elseBodyLastSourceRowId);
	__latency_fn_backend_preflight_requests_append_control_flow_operand(backendRequests, controlFlowOperand);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_while_local_immediate_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> whileSourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> bodyLastSourceRowId, int_t<std::uint32_t> bodyTerminatorSourceRowId, int_t<std::uint16_t> bodyTerminatorKindId, int_t<std::uint32_t> conditionLocalSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint16_t> conditionLocalOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_while_local_immediate_condition_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[19]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_while_bool_loop_id(), cast<int_t<std::uint32_t>>(whileSourceRowId));
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_while_bool_loop_id(), whileSourceRowId);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->provider_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, whileSourceRowId, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	BackendControlFlowOperandRow controlFlowOperand = __latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row(request->request_id, conditionSourceRowId, bodyFirstSourceRowId, conditionResultTypeRefId, conditionLocalSourceRowId, conditionProviderTypeRefId, conditionLocalOperationId, conditionValue, __latency_fn_backend_preflight_requests_control_operation_while_bool_loop_id());
	controlFlowOperand->body_last_source_row_id = bodyLastSourceRowId;
	controlFlowOperand->body_terminator_source_row_id = bodyTerminatorSourceRowId;
	controlFlowOperand->body_terminator_kind_id = bodyTerminatorKindId;
	__latency_fn_backend_preflight_requests_append_control_flow_operand(backendRequests, controlFlowOperand);
}

}
