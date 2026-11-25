#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendLocalImmediateTextOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_direct_call_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_return_lowering_blocked_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_direct_call_zero_arg_return_value_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_compatible_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_lowering_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_literal.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_binary_plus.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_binary_operator.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_binary_plus.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_sources_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_row_with_value_text.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_immediate_text_operand_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_row.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_direct_call_contract(int_t<std::uint32_t> requestId, ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_direct_call_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[132]);
	BackendRequestAuthorizationRow row = BackendRequestAuthorizationRow{};
	row->request_id = requestId;
	row->contract_id = __latency_fn_operation_readiness_contract_return_lowering_blocked_id();
	row->project_callable_contract_id = contract->contract_id;
	row->source_row_id = contract->contract_id;
	row->source_reference_id = contract->reference_id;
	row->target_symbol_id = contract->target_symbol_id;
	row->feature_id = __latency_fn_type_capability_readiness_feature_callable_return_lowering_id();
	row->lowering_adapter_id = __latency_fn_operation_readiness_lowering_adapter_direct_call_zero_arg_return_value_id();
	row->lowering_step_kind_id = __latency_fn_backend_preflight_requests_lowering_step_kind_direct_call_id();
	row->provider_type_ref_id = contract->return_type_ref_id;
	row->cache_owner_symbol_id = contract->from_symbol_id;
	row->project_callable_blocked_reason_id = contract->blocked_reason_id;
	row->value = __latency_fn_structure_row_ids_int32_from_int(cast<int_t<>>(contract->actual_arg_count));
	if (static_cast<bool>(php::identical(cast<int_t<>>(contract->status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_status_compatible_id())))) {
		row->status_id = __latency_fn_backend_preflight_requests_status_ready_id();
		row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_literal(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendLiteralPayloadRow literal, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_literal", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[133]);
	ProjectCallableContractRow emptyContract = ProjectCallableContractRow{};
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation(cast<int_t<std::uint32_t>>(requestId), operation, storage, emptyContract);
	row->project_callable_contract_id = __latency_fn_structure_row_ids_none_id();
	row->cache_owner_symbol_id = ownerSymbol->symbol_id;
	row->project_callable_blocked_reason_id = __latency_fn_structure_row_ids_none_kind_id();
	row->value = literal->numeric_payload;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_binary_plus(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow binaryNode, FrontendLiteralPayloadRow leftLiteral, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_binary_plus", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[134]);
	ProjectCallableContractRow emptyContract = ProjectCallableContractRow{};
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation(cast<int_t<std::uint32_t>>(requestId), operation, storage, emptyContract);
	row->project_callable_contract_id = __latency_fn_structure_row_ids_none_id();
	row->source_row_id = binaryNode->node_id;
	row->source_reference_id = __latency_fn_structure_row_ids_none_id();
	row->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	row->cache_owner_symbol_id = ownerSymbol->symbol_id;
	row->project_callable_blocked_reason_id = __latency_fn_structure_row_ids_none_kind_id();
	row->value = leftLiteral->numeric_payload;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_binary_operator(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow binaryNode, FrontendLiteralPayloadRow leftLiteral, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_binary_operator", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[135]);
	return __latency_fn_backend_preflight_requests_backend_request_from_binary_plus(cast<int_t<std::uint32_t>>(requestId), operation, storage, binaryNode, leftLiteral, ownerSymbol);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendBinaryOperandRow __latency_fn_backend_preflight_requests_binary_operand_row(int_t<std::uint32_t> ownerRowId, FrontendNodeRow leftNode, FrontendNodeRow rightNode, FrontendLiteralPayloadRow leftLiteral, FrontendLiteralPayloadRow rightLiteral) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::binary_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[136]);
	BackendBinaryOperandRow row = BackendBinaryOperandRow{};
	row->owner_row_id = ownerRowId;
	row->left_source_row_id = leftNode->node_id;
	row->right_source_row_id = rightNode->node_id;
	row->left_value = leftLiteral->numeric_payload;
	row->right_value = rightLiteral->numeric_payload;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendBinaryOperandRow __latency_fn_backend_preflight_requests_binary_operand_sources_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> leftSourceRowId, int_t<std::uint32_t> rightSourceRowId, int_t<std::int32_t> leftValue, int_t<std::int32_t> rightValue) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::binary_operand_sources_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[137]);
	BackendBinaryOperandRow row = BackendBinaryOperandRow{};
	row->owner_row_id = ownerRowId;
	row->left_source_row_id = leftSourceRowId;
	row->right_source_row_id = rightSourceRowId;
	row->left_value = leftValue;
	row->right_value = rightValue;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendLocalOperandRow __latency_fn_backend_preflight_requests_local_operand_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::int32_t> value, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[138]);
	BackendLocalOperandRow row = BackendLocalOperandRow{};
	row->owner_row_id = ownerRowId;
	row->local_source_row_id = localSourceRowId;
	row->value_source_row_id = valueSourceRowId;
	row->type_ref_id = typeRefId;
	row->value = value;
	row->local_operation_id = localOperationId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendLocalOperandRow __latency_fn_backend_preflight_requests_local_operand_row_with_value_text(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::int32_t> value, const string_t& valueText, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_operand_row_with_value_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[139]);
	BackendLocalOperandRow row = __latency_fn_backend_preflight_requests_local_operand_row(cast<int_t<std::uint32_t>>(ownerRowId), cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(valueSourceRowId), cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::int32_t>>(value), cast<int_t<std::uint16_t>>(localOperationId));
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendLocalImmediateTextOperandRow> __latency_fn_backend_preflight_requests_local_immediate_text_operand_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> valueSourceRowId, const string_t& valueText) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_immediate_text_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[140]);
	shared_p<BackendLocalImmediateTextOperandRow> row = create<BackendLocalImmediateTextOperandRow>();
	row->owner_row_id = ownerRowId;
	row->value_source_row_id = valueSourceRowId;
	row->byte_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(valueText));
	row->value_text = valueText;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendCallArgumentRow __latency_fn_backend_preflight_requests_call_argument_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> argumentSourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::int32_t> value, int_t<std::uint16_t> position) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::call_argument_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[141]);
	BackendCallArgumentRow row = BackendCallArgumentRow{};
	row->owner_row_id = ownerRowId;
	row->argument_source_row_id = argumentSourceRowId;
	row->type_ref_id = typeRefId;
	row->value = value;
	row->position = position;
	return row;
}

}
