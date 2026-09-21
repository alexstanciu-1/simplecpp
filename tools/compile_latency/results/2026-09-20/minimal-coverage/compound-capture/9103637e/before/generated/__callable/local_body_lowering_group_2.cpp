#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_for_local_immediate_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_ternary_local_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_ternary_select_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_expression_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_ternary_local_binary_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_ternary_select_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_for_local_immediate_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> forSourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> bodyLastSourceRowId, int_t<std::uint32_t> bodyTerminatorSourceRowId, int_t<std::uint16_t> bodyTerminatorKindId, int_t<std::uint32_t> updateSourceRowId, int_t<std::uint32_t> conditionLocalSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint16_t> conditionLocalOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_for_local_immediate_condition_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[20]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_for_bool_loop_id(), cast<int_t<std::uint32_t>>(forSourceRowId));
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_for_bool_loop_id(), forSourceRowId);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->provider_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, forSourceRowId, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), entrySymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	BackendControlFlowOperandRow controlFlowOperand = __latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row(request->request_id, conditionSourceRowId, bodyFirstSourceRowId, conditionResultTypeRefId, conditionLocalSourceRowId, conditionProviderTypeRefId, conditionLocalOperationId, conditionValue, __latency_fn_backend_preflight_requests_control_operation_for_bool_loop_id());
	controlFlowOperand->body_last_source_row_id = bodyLastSourceRowId;
	controlFlowOperand->body_terminator_source_row_id = bodyTerminatorSourceRowId;
	controlFlowOperand->body_terminator_kind_id = bodyTerminatorKindId;
	controlFlowOperand->else_body_first_source_row_id = updateSourceRowId;
	controlFlowOperand->else_body_last_source_row_id = updateSourceRowId;
	__latency_fn_backend_preflight_requests_append_control_flow_operand(backendRequests, controlFlowOperand);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_ternary_local_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, FrontendNodeRow ternaryNode, FrontendNodeRow conditionNode, FrontendNodeRow thenNode, FrontendLiteralPayloadRow thenLiteral, FrontendNodeRow elseNode, FrontendLiteralPayloadRow elseLiteral, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> conditionLocalSourceRowId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_ternary_local_condition_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[21]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_ternary_select_id(), ternaryNode->node_id);
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_ternary_select_id(), ternaryNode->node_id);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->result_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, ternaryNode->node_id, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), entrySymbol);
	request->source_reference_id = __latency_fn_structure_row_ids_none_id();
	request->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	__latency_fn_backend_preflight_requests_append_binary_operand(backendRequests, __latency_fn_backend_preflight_requests_binary_operand_row(request->request_id, thenNode, elseNode, thenLiteral, elseLiteral));
	__latency_fn_backend_preflight_requests_append_local_operand(backendRequests, __latency_fn_backend_preflight_requests_local_operand_row(request->request_id, conditionLocalSourceRowId, conditionNode->node_id, conditionTypeRefId, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), __latency_fn_backend_preflight_requests_local_operation_loaded_ternary_select_id()));
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_ternary_local_binary_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, FrontendNodeRow ternaryNode, FrontendNodeRow conditionBinaryNode, FrontendNodeRow thenNode, FrontendLiteralPayloadRow thenLiteral, FrontendNodeRow elseNode, FrontendLiteralPayloadRow elseLiteral, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightLocalSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::uint16_t> conditionLocalOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_ternary_local_binary_condition_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[22]);
	OperationReadiness operation = __latency_fn_local_body_lowering_operation_from_capability_source(capabilityCoverage, __latency_fn_type_capability_readiness_feature_ternary_select_id(), ternaryNode->node_id);
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, __latency_fn_type_capability_readiness_feature_ternary_select_id(), ternaryNode->node_id);
	CapabilityProviderRow provider = __latency_fn_type_capability_readiness_provider_by_type_ref_id(capabilityCoverage, operation->result_type_ref_id);
	StorageLifetimeRequestRow storage = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(backendRequests->request_count) + static_cast<int_t<> >(1))), operation, consumer, provider);
	int_t<std::uint32_t> requestId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_preflight_requests_next_request_id(backendRequests));
	BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(requestId, operation, storage, ternaryNode->node_id, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), entrySymbol);
	request->source_reference_id = __latency_fn_structure_row_ids_none_id();
	request->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	__latency_fn_backend_preflight_requests_append_backend_request(backendRequests, request);
	__latency_fn_backend_preflight_requests_append_binary_operand(backendRequests, __latency_fn_backend_preflight_requests_binary_operand_row(request->request_id, thenNode, elseNode, thenLiteral, elseLiteral));
	__latency_fn_backend_preflight_requests_append_control_flow_operand(backendRequests, __latency_fn_backend_preflight_requests_control_flow_local_binary_operand_row(request->request_id, conditionBinaryNode->node_id, __latency_fn_structure_row_ids_none_id(), conditionResultTypeRefId, leftLocalSourceRowId, rightLocalSourceRowId, conditionProviderTypeRefId, conditionLocalOperationId, __latency_fn_backend_preflight_requests_control_operation_expression_condition_id()));
}

}
